<?php
require_once '../includes/auth.php';
require_once '../config/database.php';

$auth = new Auth();
$auth->redirectIfNotLoggedIn();

// Check if poll_id is provided
if (!isset($_GET['poll_id'])) {
    header('Location: my_polls.php');
    exit;
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
        header('Location: my_polls.php');
        exit;
    }

    // Get options
    $optionsQuery = "SELECT * FROM options WHERE poll_id = ?";
    $stmt = $db->prepare($optionsQuery);
    $stmt->execute([$pollId]);
    $options = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Check if user already voted
    $userId = $auth->getCurrentUserId();
    $voteQuery = "SELECT id, option_id FROM votes WHERE poll_id = ? AND user_id = ?";
    $stmt = $db->prepare($voteQuery);
    $stmt->execute([$pollId, $userId]);
    $userVote = $stmt->fetch(PDO::FETCH_ASSOC);
    $hasVoted = $userVote !== false;

} catch (Exception $e) {
    $error = "Error loading poll: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vote - Voting System</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f5f5f5; }
        .navbar { background: #333; color: white; padding: 1rem 2rem; display: flex; justify-content: space-between; }
        .nav-links a { color: white; text-decoration: none; margin-left: 1rem; padding: 0.5rem 1rem; }
        .container { max-width: 600px; margin: 2rem auto; padding: 0 2rem; }
        .poll-card { background: white; padding: 2rem; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .poll-question { font-size: 1.3rem; font-weight: bold; margin-bottom: 1rem; color: #333; }
        .poll-meta { color: #666; margin-bottom: 1.5rem; }
        .option { padding: 1rem; margin: 0.5rem 0; border: 2px solid #ddd; border-radius: 5px; cursor: pointer; transition: all 0.3s; }
        .option:hover { border-color: #007bff; background: #f8f9fa; }
        .option.selected { border-color: #007bff; background: #e7f3ff; }
        .option input { margin-right: 10px; }
        .btn { padding: 12px 24px; border: none; border-radius: 5px; font-size: 16px; cursor: pointer; margin: 0.5rem; }
        .btn-primary { background: #007bff; color: white; }
        .btn-success { background: #28a745; color: white; }
        .message { padding: 1rem; margin: 1rem 0; border-radius: 5px; }
        .success { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }
        .already-voted { text-align: center; padding: 2rem; }
    </style>
</head>
<body>
    <nav class="navbar">
        <h1>🗳️ Cast Your Vote</h1>
        <div class="nav-links">
            <a href="my_polls.php">← Back to My Polls</a>
            <a href="../api/auth/logout.php">Logout</a>
        </div>
    </nav>
    
    <div class="container">
        <?php if (isset($error)): ?>
            <div class="message error"><?php echo $error; ?></div>
            <a href="my_polls.php" class="btn btn-primary">Back to My Polls</a>
        <?php elseif (!$poll): ?>
            <div class="message error">Poll not found</div>
            <a href="my_polls.php" class="btn btn-primary">Back to My Polls</a>
        <?php else: ?>
            <div class="poll-card">
                <div class="poll-question"><?php echo htmlspecialchars($poll['question']); ?></div>
                <div class="poll-meta">
                    Created by: <?php echo htmlspecialchars($poll['created_by']); ?> | 
                    Created: <?php echo date('M j, Y', strtotime($poll['created_at'])); ?>
                </div>

                <?php if ($hasVoted): ?>
                    <div class="already-voted">
                        <h3>✅ You have already voted in this poll!</h3>
                        <p>Thank you for participating. You cannot vote again in the same poll.</p>
                        <a href="results.php?poll_id=<?php echo $pollId; ?>" class="btn btn-success">View Results</a>
                        <a href="my_polls.php" class="btn btn-primary">Back to My Polls</a>
                    </div>
                <?php else: ?>
                    <form id="voteForm">
                        <input type="hidden" id="pollId" value="<?php echo $pollId; ?>">
                        
                        <h3>Select your choice:</h3>
                        
                        <?php foreach ($options as $option): ?>
                            <div class="option" onclick="selectOption(<?php echo $option['id']; ?>)">
                                <input type="radio" name="voteOption" value="<?php echo $option['id']; ?>" id="option_<?php echo $option['id']; ?>">
                                <label for="option_<?php echo $option['id']; ?>" style="cursor: pointer;">
                                    <?php echo htmlspecialchars($option['option_text']); ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                        
                        <div style="text-align: center; margin-top: 2rem;">
                            <button type="submit" class="btn btn-primary" id="submitBtn">Submit Vote</button>
                            <a href="my_polls.php" class="btn btn-success">Cancel</a>
                        </div>
                    </form>
                    
                    <div id="message"></div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
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
        }
        
        document.getElementById('voteForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            if (!selectedOptionId) {
                showMessage('Please select an option', 'error');
                return;
            }
            
            const submitBtn = document.getElementById('submitBtn');
            const originalText = submitBtn.textContent;
            submitBtn.textContent = 'Submitting...';
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
                    showMessage('Vote recorded successfully! Redirecting...', 'success');
                    setTimeout(() => {
                        window.location.href = 'results.php?poll_id=<?php echo $pollId; ?>';
                    }, 1500);
                } else {
                    showMessage('Error: ' + result.message, 'error');
                    submitBtn.textContent = originalText;
                    submitBtn.disabled = false;
                }
            } catch (error) {
                showMessage('Network error: ' + error.message, 'error');
                submitBtn.textContent = originalText;
                submitBtn.disabled = false;
            }
        });
        
        function showMessage(text, type) {
            const messageDiv = document.getElementById('message');
            messageDiv.innerHTML = `<div class="message ${type}">${text}</div>`;
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