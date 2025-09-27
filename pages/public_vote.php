<?php
require_once '../config/database.php';

if (!isset($_GET['poll_id'])) {
    die('Poll ID is required');
}

$pollId = intval($_GET['poll_id']);
$database = new Database();
$db = $database->getConnection();

// Get poll details
try {
    $pollQuery = "SELECT p.*, u.username as created_by 
                 FROM polls p 
                 JOIN users u ON p.created_by = u.id 
                 WHERE p.id = ?";
    $stmt = $db->prepare($pollQuery);
    $stmt->execute([$pollId]);
    $poll = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$poll) {
        die('Poll not found');
    }

    // Get options
    $optionsQuery = "SELECT * FROM options WHERE poll_id = ?";
    $stmt = $db->prepare($optionsQuery);
    $stmt->execute([$pollId]);
    $options = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    die('Error loading poll: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vote on: <?php echo htmlspecialchars($poll['question']); ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: Arial, sans-serif; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .vote-container {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 500px;
        }
        .poll-question {
            font-size: 1.4rem;
            font-weight: bold;
            text-align: center;
            margin-bottom: 1rem;
            color: #333;
            line-height: 1.4;
        }
        .poll-meta {
            text-align: center;
            color: #666;
            margin-bottom: 2rem;
            font-size: 0.9rem;
        }
        .option {
            padding: 1rem;
            margin: 0.8rem 0;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            background: #fafafa;
        }
        .option:hover {
            border-color: #667eea;
            background: #f0f4ff;
            transform: translateY(-2px);
        }
        .option.selected {
            border-color: #667eea;
            background: #e7f3ff;
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.2);
        }
        .option input {
            margin-right: 10px;
        }
        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
            margin-top: 1rem;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .btn-primary:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }
        .btn-primary:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
        }
        .login-prompt {
            text-align: center;
            margin-top: 2rem;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 8px;
        }
        .message {
            padding: 1rem;
            margin: 1rem 0;
            border-radius: 8px;
            text-align: center;
        }
        .success { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }
        .share-section {
            margin-top: 2rem;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 8px;
            text-align: center;
        }
        .share-link {
            background: white;
            padding: 0.5rem;
            border-radius: 5px;
            border: 1px solid #ddd;
            font-family: monospace;
            font-size: 0.9rem;
            word-break: break-all;
        }
    </style>
</head>
<body>
    <div class="vote-container">
        <div class="poll-question"><?php echo htmlspecialchars($poll['question']); ?></div>
        <div class="poll-meta">
            Created by: <?php echo htmlspecialchars($poll['created_by']); ?>
        </div>

        <form id="voteForm">
            <input type="hidden" id="pollId" value="<?php echo $pollId; ?>">
            
            <?php foreach ($options as $option): ?>
                <div class="option" onclick="selectOption(<?php echo $option['id']; ?>)">
                    <input type="radio" name="voteOption" value="<?php echo $option['id']; ?>" id="option_<?php echo $option['id']; ?>">
                    <label for="option_<?php echo $option['id']; ?>" style="cursor: pointer; font-weight: bold;">
                        <?php echo htmlspecialchars($option['option_text']); ?>
                    </label>
                </div>
            <?php endforeach; ?>
            
            <button type="submit" class="btn btn-primary" id="submitBtn" disabled>Submit Vote</button>
        </form>
        
        <div id="message"></div>
        
        <div class="login-prompt" id="loginPrompt">
            <p>💡 <strong>Want to create your own polls?</strong></p>
            <a href="../index.php" style="color: #667eea; text-decoration: none; font-weight: bold;">
                Login or Register to create polls
            </a>
        </div>
        
        <div class="share-section">
            <p>🔗 <strong>Share this poll:</strong></p>
            <div class="share-link" id="shareLink">
                <?php echo "http://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; ?>
            </div>
            <button onclick="copyShareLink()" style="margin-top: 0.5rem; padding: 0.5rem 1rem; background: #667eea; color: white; border: none; border-radius: 5px; cursor: pointer;">
                Copy Link
            </button>
        </div>
    </div>

    <script>
        let selectedOptionId = null;
        
        function selectOption(optionId) {
            selectedOptionId = optionId;
            
            // Update UI
            document.querySelectorAll('.option').forEach(opt => {
                opt.classList.remove('selected');
            });
            document.querySelector(`input[value="${optionId}"]`).parentElement.classList.add('selected');
            document.querySelector(`input[value="${optionId}"]`).checked = true;
            
            // Enable submit button
            document.getElementById('submitBtn').disabled = false;
        }
        
        document.getElementById('voteForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            if (!selectedOptionId) {
                showMessage('Please select an option', 'error');
                return;
            }
            
            const submitBtn = document.getElementById('submitBtn');
            const originalText = submitBtn.textContent;
            submitBtn.textContent = 'Submitting Vote...';
            submitBtn.disabled = true;
            
            try {
                const response = await fetch('../api/polls/vote.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        pollId: <?php echo $pollId; ?>,
                        optionId: selectedOptionId
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showMessage('✅ Vote recorded successfully! Thank you for voting.', 'success');
                    submitBtn.textContent = 'Vote Submitted!';
                    submitBtn.disabled = true;
                    
                    // Show results link after 2 seconds
                    setTimeout(() => {
                        const resultsLink = document.createElement('a');
                        resultsLink.href = `public_results.php?poll_id=<?php echo $pollId; ?>`;
                        resultsLink.textContent = 'View Live Results';
                        resultsLink.style.display = 'block';
                        resultsLink.style.marginTop = '1rem';
                        resultsLink.style.padding = '1rem';
                        resultsLink.style.background = '#28a745';
                        resultsLink.style.color = 'white';
                        resultsLink.style.textDecoration = 'none';
                        resultsLink.style.borderRadius = '8px';
                        resultsLink.style.textAlign = 'center';
                        resultsLink.style.fontWeight = 'bold';
                        
                        document.getElementById('message').appendChild(resultsLink);
                    }, 2000);
                    
                } else {
                    showMessage('❌ ' + result.message, 'error');
                    submitBtn.textContent = originalText;
                    submitBtn.disabled = false;
                }
            } catch (error) {
                showMessage('❌ Network error: ' + error.message, 'error');
                submitBtn.textContent = originalText;
                submitBtn.disabled = false;
            }
        });
        
        function showMessage(text, type) {
            const messageDiv = document.getElementById('message');
            messageDiv.innerHTML = `<div class="message ${type}">${text}</div>`;
        }
        
        function copyShareLink() {
            const shareLink = document.getElementById('shareLink').textContent;
            navigator.clipboard.writeText(shareLink).then(() => {
                alert('Link copied to clipboard!');
            });
        }
        
        // Add click handlers to options
        document.querySelectorAll('.option').forEach(option => {
            option.addEventListener('click', function() {
                const radio = this.querySelector('input[type="radio"]');
                selectOption(radio.value);
            });
        });
    </script>
</body>
</html>