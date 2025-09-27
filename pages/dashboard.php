<?php
require_once '../includes/auth.php';
$auth = new Auth();
$auth->redirectIfNotLoggedIn();

// Get user info
$username = $auth->getCurrentUsername();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Voting System</title>
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
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .navbar h1 { font-size: 1.5rem; }
        .nav-links a {
            color: white;
            text-decoration: none;
            margin-left: 1rem;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            transition: background 0.3s;
        }
        .nav-links a:hover {
            background: rgba(255,255,255,0.2);
        }
        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 2rem;
        }
        .welcome-section {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
            margin-bottom: 2rem;
        }
        .welcome-section h2 {
            color: #667eea;
            margin-bottom: 1rem;
        }
        .action-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
            margin: 2rem 0;
        }
        .btn {
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            transition: transform 0.2s;
        }
        .btn:hover {
            transform: translateY(-2px);
        }
        .btn-primary {
            background: #667eea;
            color: white;
        }
        .btn-success {
            background: #28a745;
            color: white;
        }
        .btn-info {
            background: #17a2b8;
            color: white;
        }
        .features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }
        .feature-card {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        .feature-card h3 {
            color: #667eea;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <h1>🗳️ Voting System Dashboard</h1>
        <div class="nav-links">
            <span>Welcome, <strong><?php echo $username; ?></strong></span>
            <a href="create_poll.php">Create Poll</a>
            <a href="../api/auth/logout.php">Logout</a>
        </div>
    </nav>

    <div class="container">
        <div class="welcome-section">
            <h2>Welcome to Your Voting System Dashboard!</h2>
            <p>Create polls, collect votes, and view results in real-time.</p>
            
            <div class="action-buttons">
    <a href="create_poll.php" class="btn btn-primary">➕ Create New Poll</a>
    <a href="my_polls.php" class="btn btn-success">📊 View My Polls</a>
</div>
        </div>

        <div class="features">
            <div class="feature-card">
                <h3>📝 Create Polls</h3>
                <p>Easily create custom polls with multiple options for your audience to vote on.</p>
            </div>
            <div class="feature-card">
                <h3>🔒 Secure Voting</h3>
                <p>One vote per user ensures fair and accurate results for your polls.</p>
            </div>
            <div class="feature-card">
                <h3>📊 Real-time Results</h3>
                <p>Watch results update in real-time with beautiful charts and graphs.</p>
            </div>
        </div>

        <div style="text-align: center; margin-top: 3rem; color: #666;">
            <p>🚀 More features coming soon: Poll analytics, Export results, and more!</p>
        </div>
    </div>
</body>
</html>