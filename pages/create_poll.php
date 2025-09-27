<?php
require_once '../includes/auth.php';
$auth = new Auth();
$auth->redirectIfNotLoggedIn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Poll - Voting System</title>
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
        .option-input {
            margin-bottom: 0.5rem;
        }
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            transition: background 0.3s;
        }
        .btn-primary {
            background: #667eea;
            color: white;
        }
        .btn-secondary {
            background: #6c757d;
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
    </style>
</head>
<body>
    <nav class="navbar">
        <h1>🗳️ Create New Poll</h1>
        <div class="nav-links">
            <a href="dashboard.php">← Dashboard</a>
            <a href="../api/auth/logout.php">Logout</a>
        </div>
    </nav>
    
    <div class="container">
        <div class="form-container">
            <h2>Create Your Poll</h2>
            
            <form id="createPollForm">
                <div class="form-group">
                    <label for="question">Poll Question:</label>
                    <textarea 
                        id="question" 
                        placeholder="What would you like to ask?" 
                        rows="3"
                        required
                    >What is your favorite programming language?</textarea>
                </div>
                
                <div class="form-group">
                    <label>Poll Options:</label>
                    <div id="optionsContainer">
                        <input type="text" class="option-input" placeholder="Option 1" value="JavaScript" required>
                        <input type="text" class="option-input" placeholder="Option 2" value="Python" required>
                        <input type="text" class="option-input" placeholder="Option 3" value="PHP" required>
                    </div>
                    <button type="button" class="btn btn-secondary" onclick="addOption()" style="margin-top: 10px;">
                        + Add Another Option
                    </button>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-primary" style="width: 100%;">
                        Create Poll
                    </button>
                </div>
            </form>
            
            <div id="message"></div>
        </div>
    </div>

    <script>
        let optionCount = 3;
        
        function addOption() {
            optionCount++;
            const newInput = document.createElement('input');
            newInput.type = 'text';
            newInput.className = 'option-input';
            newInput.placeholder = `Option ${optionCount}`;
            newInput.required = true;
            
            document.getElementById('optionsContainer').appendChild(newInput);
        }
        
        document.getElementById('createPollForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const question = document.getElementById('question').value;
            const optionInputs = document.querySelectorAll('.option-input');
            const options = Array.from(optionInputs).map(input => input.value).filter(val => val.trim() !== '');
            
            console.log('Submitting poll:', { question, options });
            
            if (options.length < 2) {
                showMessage('Please add at least 2 options', 'error');
                return;
            }
            
            const submitBtn = e.target.querySelector('button[type="submit"]');
            const originalText = submitBtn.textContent;
            submitBtn.textContent = 'Creating...';
            submitBtn.disabled = true;
            
            try {
                const response = await fetch('../api/polls/create.php', {
                    method: 'POST',
                    headers: { 
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ question, options })
                });
                
                console.log('Response status:', response.status);
                
                // FIRST, see what the response actually contains
                const responseText = await response.text();
                console.log('Raw response:', responseText);
                
                // Check if it starts with HTML (error)
                if (responseText.trim().startsWith('<') || responseText.includes('<b>') || responseText.includes('Warning') || responseText.includes('Error')) {
                    console.error('PHP Error detected in response');
                    showMessage('Server error detected. Check browser console for details.', 'error');
                    submitBtn.textContent = originalText;
                    submitBtn.disabled = false;
                    return;
                }
                
                // Try to parse as JSON
                let result;
                try {
                    result = JSON.parse(responseText);
                } catch (parseError) {
                    console.error('JSON parse error:', parseError);
                    console.error('Response that failed to parse:', responseText);
                    showMessage('Server returned invalid response. Check console.', 'error');
                    submitBtn.textContent = originalText;
                    submitBtn.disabled = false;
                    return;
                }
                
                console.log('API response:', result);
                
                if (result.success) {
                    showMessage('Poll created successfully! Redirecting...', 'success');
                    setTimeout(() => {
                        window.location.href = 'my_polls.php';
                    }, 1500);
                } else {
                    showMessage('Error: ' + (result.message || 'Unknown error'), 'error');
                    submitBtn.textContent = originalText;
                    submitBtn.disabled = false;
                }
            } catch (error) {
                console.error('Fetch error:', error);
                showMessage('Network error: ' + error.message, 'error');
                submitBtn.textContent = originalText;
                submitBtn.disabled = false;
            }
        });
        
        function showMessage(text, type) {
            const messageDiv = document.getElementById('message');
            messageDiv.innerHTML = `<div class="message ${type}">${text}</div>`;
            
            // Auto-hide success messages
            if (type === 'success') {
                setTimeout(() => {
                    messageDiv.innerHTML = '';
                }, 5000);
            }
        }
    </script>
</body>
</html>