<?php
require_once '../includes/auth.php';
require_once '../config/database.php';

$auth = new Auth();
$auth->redirectIfNotLoggedIn();

if (!isset($_GET['poll_id'])) {
    header('Location: my_polls.php');
    exit;
}

$pollId = intval($_GET['poll_id']);
$userId = $auth->getCurrentUserId();

// Verify ownership
$database = new Database();
$db = $database->getConnection();

$ownershipQuery = "SELECT id FROM polls WHERE id = ? AND created_by = ?";
$stmt = $db->prepare($ownershipQuery);
$stmt->execute([$pollId, $userId]);

if ($stmt->rowCount() === 0) {
    header('Location: my_polls.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Poll - Voting System</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: Arial, sans-serif; 
            background: #f5f5f5;
            color: #333;
        }
        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .nav-links a {
            color: white;
            text-decoration: none;
            margin-left: 1rem;
            padding: 0.5rem 1rem;
            border-radius: 5px;
        }
        .nav-links a:hover {
            background: rgba(255,255,255,0.2);
        }
        .container {
            max-width: 600px;
            margin: 2rem auto;
            padding: 0 2rem;
        }
        .form-container {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h2 {
            color: #667eea;
            margin-bottom: 1.5rem;
            text-align: center;
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: bold;
            color: #555;
        }
        input, textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }
        input:focus, textarea:focus {
            border-color: #667eea;
            outline: none;
        }
        .option-item {
            display: flex;
            gap: 10px;
            margin-bottom: 10px;
            align-items: center;
        }
        .option-input {
            flex: 1;
        }
        .remove-option {
            background: #dc3545;
            color: white;
            border: none;
            border-radius: 5px;
            padding: 10px 15px;
            cursor: pointer;
            font-size: 18px;
        }
        .remove-option:hover {
            background: #c82333;
        }
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            transition: background 0.3s;
            margin-right: 10px;
        }
        .btn-primary {
            background: #667eea;
            color: white;
        }
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        .btn:hover {
            opacity: 0.9;
        }
        .message {
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
            text-align: center;
        }
        .success { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }
        .warning { background: #fff3cd; color: #856404; }
        .actions {
            display: flex;
            justify-content: space-between;
            margin-top: 2rem;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <h1>✏️ Edit Poll</h1>
        <div class="nav-links">
            <a href="my_polls.php">← Back to My Polls</a>
            <a href="../api/auth/logout.php">Logout</a>
        </div>
    </nav>
    
    <div class="container">
        <div class="form-container">
            <h2>Edit Your Poll</h2>
            
            <form id="editPollForm">
                <input type="hidden" id="pollId" value="<?php echo $pollId; ?>">
                
                <div class="form-group">
                    <label for="question">Poll Question:</label>
                    <textarea 
                        id="question" 
                        placeholder="What would you like to ask?" 
                        rows="3"
                        required
                    ></textarea>
                </div>
                
                <div class="form-group">
                    <label>Poll Options:</label>
                    <div id="optionsContainer">
                        <!-- Options will be loaded here -->
                    </div>
                    <button type="button" class="btn btn-secondary" onclick="addOption()" style="margin-top: 10px;">
                        + Add Another Option
                    </button>
                </div>
                
                <div class="actions">
                    <div>
                        <button type="submit" class="btn btn-primary">
                            Update Poll
                        </button>
                        <a href="my_polls.php" class="btn btn-secondary">Cancel</a>
                    </div>
                    <button type="button" class="btn btn-danger" onclick="confirmDelete()">
                        Delete Poll
                    </button>
                </div>
            </form>
            
            <div id="message"></div>
        </div>
    </div>

    <script>
        let optionCount = 0;
        
        // Load poll data when page loads
        async function loadPollData() {
            const pollId = document.getElementById('pollId').value;
            
            try {
                const response = await fetch(`../api/polls/get.php?pollId=${pollId}`);
                const result = await response.json();
                
                if (result.success) {
                    // Set question
                    document.getElementById('question').value = result.poll.question;
                    
                    // Load options
                    const optionsContainer = document.getElementById('optionsContainer');
                    optionsContainer.innerHTML = '';
                    
                    result.options.forEach((option, index) => {
                        addOption(option.id, option.option_text);
                    });
                    
                    // Add empty option if no options exist
                    if (result.options.length === 0) {
                        addOption();
                        addOption();
                    }
                } else {
                    showMessage('Error loading poll: ' + result.message, 'error');
                }
            } catch (error) {
                showMessage('Network error: ' + error.message, 'error');
            }
        }
        
        function addOption(optionId = '', optionText = '') {
            optionCount++;
            const optionHtml = `
                <div class="option-item">
                    <input 
                        type="text" 
                        class="option-input" 
                        placeholder="Option ${optionCount}" 
                        value="${optionText}"
                        data-option-id="${optionId}"
                        required>
                    <button type="button" class="remove-option" onclick="removeOption(this)">×</button>
                </div>
            `;
            
            document.getElementById('optionsContainer').insertAdjacentHTML('beforeend', optionHtml);
        }
        
        function removeOption(button) {
            const optionItem = button.parentElement;
            if (document.querySelectorAll('.option-item').length > 2) {
                optionItem.remove();
            } else {
                showMessage('Poll must have at least 2 options', 'warning');
            }
        }
        
        document.getElementById('editPollForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const pollId = document.getElementById('pollId').value;
            const question = document.getElementById('question').value;
            const optionInputs = document.querySelectorAll('.option-input');
            
            const options = Array.from(optionInputs).map(input => ({
                id: input.dataset.optionId || '',
                text: input.value.trim()
            })).filter(opt => opt.text !== '');
            
            if (options.length < 2) {
                showMessage('Please add at least 2 options', 'error');
                return;
            }
            
            const submitBtn = e.target.querySelector('button[type="submit"]');
            const originalText = submitBtn.textContent;
            submitBtn.textContent = 'Updating...';
            submitBtn.disabled = true;
            
            try {
                const response = await fetch('../api/polls/update.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ pollId, question, options })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showMessage('Poll updated successfully! Redirecting...', 'success');
                    setTimeout(() => {
                        window.location.href = 'my_polls.php';
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
        
        async function confirmDelete() {
            if (confirm('Are you sure you want to delete this poll? This action cannot be undone.')) {
                const pollId = document.getElementById('pollId').value;
                
                try {
                    const response = await fetch('../api/polls/delete.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ pollId })
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        showMessage('Poll deleted successfully! Redirecting...', 'success');
                        setTimeout(() => {
                            window.location.href = 'my_polls.php';
                        }, 1500);
                    } else {
                        showMessage('Error: ' + result.message, 'error');
                    }
                } catch (error) {
                    showMessage('Network error: ' + error.message, 'error');
                }
            }
        }
        
        function showMessage(text, type) {
            const messageDiv = document.getElementById('message');
            messageDiv.innerHTML = `<div class="message ${type}">${text}</div>`;
        }
        
        // Load poll data when page loads
        loadPollData();
    </script>
</body>
</html>