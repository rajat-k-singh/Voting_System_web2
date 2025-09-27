<?php
require_once 'includes/auth.php';
$auth = new Auth();

if ($auth->isLoggedIn()) {
    header('Location: pages/dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voting System - Login</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: Arial, sans-serif; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .container { 
            background: white; 
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            width: 90%;
            max-width: 400px;
        }
        h1 { 
            text-align: center; 
            color: #333;
            margin-bottom: 30px;
        }
        .form-group { margin-bottom: 20px; }
        label { 
            display: block; 
            margin-bottom: 5px; 
            color: #555;
            font-weight: bold;
        }
        input {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }
        input:focus {
            border-color: #667eea;
            outline: none;
        }
        button {
            width: 100%;
            padding: 12px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            transition: background 0.3s;
        }
        button:hover {
            background: #764ba2;
        }
        .tabs {
            display: flex;
            margin-bottom: 20px;
            border-bottom: 2px solid #eee;
        }
        .tab {
            flex: 1;
            text-align: center;
            padding: 10px;
            cursor: pointer;
            border-bottom: 3px solid transparent;
        }
        .tab.active {
            border-bottom-color: #667eea;
            color: #667eea;
            font-weight: bold;
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
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
    <div class="container">
        <h1>🗳️ Voting System</h1>
        
        <div class="tabs">
            <div class="tab active" onclick="showTab('login')">Login</div>
            <div class="tab" onclick="showTab('register')">Register</div>
        </div>

        <!-- Login Form -->
        <div id="login-tab" class="tab-content active">
            <form id="loginForm">
                <div class="form-group">
                    <label>Username or Email:</label>
                    <input type="text" name="username" placeholder="Enter username or email" required>
                </div>
                <div class="form-group">
                    <label>Password:</label>
                    <input type="password" name="password" placeholder="Enter password" required>
                </div>
                <button type="submit">Login</button>
            </form>
            <div id="loginMessage"></div>
        </div>

        <!-- Register Form -->
        <div id="register-tab" class="tab-content">
            <form id="registerForm">
                <div class="form-group">
                    <label>Username:</label>
                    <input type="text" name="username" placeholder="Choose a username" required>
                </div>
                <div class="form-group">
                    <label>Email:</label>
                    <input type="email" name="email" placeholder="Enter your email" required>
                </div>
                <div class="form-group">
                    <label>Password:</label>
                    <input type="password" name="password" placeholder="Choose a password" required>
                </div>
                <button type="submit">Register</button>
            </form>
            <div id="registerMessage"></div>
        </div>
    </div>

    <script>
        function showTab(tabName) {
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            document.querySelectorAll('.tab').forEach(tab => {
                tab.classList.remove('active');
            });

            // Show selected tab
            document.getElementById(tabName + '-tab').classList.add('active');
            event.target.classList.add('active');
        }

        // Login functionality
        document.getElementById('loginForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            const button = e.target.querySelector('button');
            const originalText = button.textContent;
            
            button.textContent = 'Logging in...';
            button.disabled = true;

            try {
                const response = await fetch('api/auth/login.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        username: formData.get('username'),
                        password: formData.get('password')
                    })
                });
                
                const result = await response.json();
                const messageDiv = document.getElementById('loginMessage');
                
                if (result.success) {
                    messageDiv.innerHTML = '<div class="message success">Login successful! Redirecting...</div>';
                    setTimeout(() => {
                        window.location.href = 'pages/dashboard.php';
                    }, 1000);
                } else {
                    messageDiv.innerHTML = '<div class="message error">' + result.message + '</div>';
                    button.textContent = originalText;
                    button.disabled = false;
                }
            } catch (error) {
                document.getElementById('loginMessage').innerHTML = '<div class="message error">Network error. Please try again.</div>';
                button.textContent = originalText;
                button.disabled = false;
            }
        });

        // Register functionality
        document.getElementById('registerForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            const button = e.target.querySelector('button');
            const originalText = button.textContent;
            
            button.textContent = 'Registering...';
            button.disabled = true;

            try {
                const response = await fetch('api/auth/register.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        username: formData.get('username'),
                        email: formData.get('email'),
                        password: formData.get('password')
                    })
                });
                
                const result = await response.json();
                const messageDiv = document.getElementById('registerMessage');
                
                if (result.success) {
                    messageDiv.innerHTML = '<div class="message success">' + result.message + ' You can now login.</div>';
                    e.target.reset();
                    showTab('login');
                } else {
                    messageDiv.innerHTML = '<div class="message error">' + result.message + '</div>';
                }
                
                button.textContent = originalText;
                button.disabled = false;
            } catch (error) {
                document.getElementById('registerMessage').innerHTML = '<div class="message error">Network error. Please try again.</div>';
                button.textContent = originalText;
                button.disabled = false;
            }
        });
    </script>
</body>
</html>