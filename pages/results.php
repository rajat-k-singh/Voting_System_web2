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
$username = $auth->getCurrentUsername();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Results - Voting System</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f5f5f5; }
        .navbar { background: #333; color: white; padding: 1rem 2rem; display: flex; justify-content: space-between; }
        .nav-links a { color: white; text-decoration: none; margin-left: 1rem; padding: 0.5rem 1rem; }
        .container { max-width: 800px; margin: 2rem auto; padding: 0 2rem; }
        .results-card { background: white; padding: 2rem; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .poll-question { font-size: 1.5rem; font-weight: bold; margin-bottom: 1rem; color: #333; text-align: center; }
        .poll-meta { text-align: center; color: #666; margin-bottom: 2rem; }
        .result-item { margin: 1.5rem 0; }
        .option-text { font-weight: bold; margin-bottom: 0.5rem; }
        .vote-bar-container { background: #f0f0f0; height: 30px; border-radius: 15px; overflow: hidden; margin: 0.5rem 0; }
        .vote-bar { height: 100%; background: linear-gradient(90deg, #007bff, #0056b3); transition: width 0.5s ease; }
        .vote-info { display: flex; justify-content: space-between; font-size: 0.9rem; color: #666; }
        .total-votes { text-align: center; font-size: 1.2rem; margin: 2rem 0; padding: 1rem; background: #f8f9fa; border-radius: 5px; }
        .loading { text-align: center; padding: 3rem; color: #666; }
        .btn { padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 0.5rem; display: inline-block; }
        .btn-primary { background: #007bff; color: white; }
        .btn-success { background: #28a745; color: white; }
    </style>
</head>
<body>
    <nav class="navbar">
        <h1>📊 Poll Results</h1>
        <div class="nav-links">
            <a href="my_polls.php">← My Polls</a>
            <a href="vote.php?poll_id=<?php echo $pollId; ?>">Vote</a>
            <a href="../api/auth/logout.php">Logout</a>
        </div>
    </nav>
    
    <div class="container">
        <div class="results-card">
            <div id="loading" class="loading">
                <h3>Loading results...</h3>
                <p>Please wait while we fetch the latest results.</p>
            </div>
            
            <div id="resultsContent" style="display: none;">
                <!-- Results will be loaded here by JavaScript -->
            </div>
            
            <div id="errorMessage" style="display: none;" class="loading">
                <h3 style="color: red;">Error loading results</h3>
                <p id="errorText"></p>
                <a href="my_polls.php" class="btn btn-primary">Back to My Polls</a>
            </div>
        </div>
    </div>

    <script>
        const pollId = <?php echo $pollId; ?>;
        
        async function loadResults() {
            try {
                const response = await fetch(`../api/polls/results.php?pollId=${pollId}`);
                const result = await response.json();
                
                if (result.success) {
                    displayResults(result);
                } else {
                    showError(result.message);
                }
            } catch (error) {
                showError('Network error: ' + error.message);
            }
        }
        
        function displayResults(data) {
            document.getElementById('loading').style.display = 'none';
            document.getElementById('errorMessage').style.display = 'none';
            
            const resultsContent = document.getElementById('resultsContent');
            
            let html = `
                <div class="poll-question">${data.poll.question}</div>
                <div class="poll-meta">
                    Created by: ${data.poll.created_by_name} | 
                    Total Votes: <strong>${data.totalVotes}</strong>
                </div>
            `;
            
            if (data.totalVotes === 0) {
                html += `<div class="total-votes">No votes yet. Be the first to vote!</div>`;
            } else {
                data.options.forEach(option => {
                    const percentage = data.totalVotes > 0 
                        ? ((option.vote_count / data.totalVotes) * 100).toFixed(1) 
                        : 0;
                    
                    html += `
                        <div class="result-item">
                            <div class="option-text">${option.option_text}</div>
                            <div class="vote-bar-container">
                                <div class="vote-bar" style="width: ${percentage}%"></div>
                            </div>
                            <div class="vote-info">
                                <span>${option.vote_count} votes</span>
                                <span>${percentage}%</span>
                            </div>
                        </div>
                    `;
                });
                
                html += `<div class="total-votes">Total Votes: <strong>${data.totalVotes}</strong></div>`;
            }
            
            html += `
                <div style="text-align: center; margin-top: 2rem;">
                    <a href="vote.php?poll_id=${pollId}" class="btn btn-primary">Vote on this Poll</a>
                    <a href="my_polls.php" class="btn btn-success">Back to My Polls</a>
                </div>
            `;
            
            resultsContent.innerHTML = html;
            resultsContent.style.display = 'block';
        }
        
        function showError(message) {
            document.getElementById('loading').style.display = 'none';
            document.getElementById('errorText').textContent = message;
            document.getElementById('errorMessage').style.display = 'block';
        }
        
        // Load results when page loads
        loadResults();
        
        // Auto-refresh every 10 seconds
        setInterval(loadResults, 10000);
    </script>
</body>
</html>