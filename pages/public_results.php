<?php
require_once '../config/database.php';

if (!isset($_GET['poll_id'])) {
    die('Poll ID is required');
}

$pollId = intval($_GET['poll_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Poll Results</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: Arial, sans-serif; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .results-container {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            max-width: 600px;
            margin: 0 auto;
        }
        .poll-question {
            font-size: 1.5rem;
            font-weight: bold;
            text-align: center;
            margin-bottom: 1rem;
            color: #333;
        }
        .poll-meta {
            text-align: center;
            color: #666;
            margin-bottom: 2rem;
        }
        .result-item {
            margin: 1.5rem 0;
        }
        .option-text {
            font-weight: bold;
            margin-bottom: 0.5rem;
            display: flex;
            justify-content: space-between;
        }
        .vote-bar-container {
            background: #f0f0f0;
            height: 30px;
            border-radius: 15px;
            overflow: hidden;
            margin: 0.5rem 0;
        }
        .vote-bar {
            height: 100%;
            background: linear-gradient(90deg, #667eea, #764ba2);
            transition: width 0.5s ease;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            padding-right: 10px;
            color: white;
            font-weight: bold;
            font-size: 0.8rem;
        }
        .total-votes {
            text-align: center;
            font-size: 1.2rem;
            margin: 2rem 0;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 8px;
            font-weight: bold;
        }
        .loading {
            text-align: center;
            padding: 3rem;
            color: #666;
        }
        .error {
            text-align: center;
            padding: 2rem;
            color: #dc3545;
            background: #f8d7da;
            border-radius: 8px;
        }
        .action-buttons {
            text-align: center;
            margin-top: 2rem;
        }
        .btn {
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 8px;
            margin: 0 10px;
            display: inline-block;
            font-weight: bold;
            transition: transform 0.2s;
        }
        .btn-primary {
            background: #667eea;
            color: white;
        }
        .btn-success {
            background: #28a745;
            color: white;
        }
        .btn:hover {
            transform: translateY(-2px);
        }
        .real-time-badge {
            background: #28a745;
            color: white;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            margin-left: 10px;
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.7; }
            100% { opacity: 1; }
        }
    </style>
</head>
<body>
    <div class="results-container">
        <div id="loading" class="loading">
            <h3>📊 Loading Results...</h3>
            <p>Fetching the latest voting data</p>
        </div>
        
        <div id="resultsContent" style="display: none;">
            <!-- Results will be loaded here -->
        </div>
        
        <div id="errorMessage" style="display: none;" class="error">
            <h3>❌ Error Loading Results</h3>
            <p id="errorText"></p>
        </div>
        
        <div class="action-buttons">
            <a href="public_vote.php?poll_id=<?php echo $pollId; ?>" class="btn btn-primary">← Back to Vote</a>
            <a href="../index.php" class="btn btn-success">Create Your Own Poll</a>
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
                    Created by: ${data.poll.created_by_name} 
                    <span class="real-time-badge">Live Results</span>
                </div>
            `;
            
            if (data.totalVotes === 0) {
                html += `<div class="total-votes">📝 No votes yet. Be the first to vote!</div>`;
            } else {
                data.options.forEach(option => {
                    const percentage = data.totalVotes > 0 
                        ? ((option.vote_count / data.totalVotes) * 100).toFixed(1) 
                        : 0;
                    
                    html += `
                        <div class="result-item">
                            <div class="option-text">
                                <span>${option.option_text}</span>
                                <span>${percentage}%</span>
                            </div>
                            <div class="vote-bar-container">
                                <div class="vote-bar" style="width: ${percentage}%">
                                    ${option.vote_count} votes
                                </div>
                            </div>
                        </div>
                    `;
                });
                
                html += `<div class="total-votes">✅ Total Votes: ${data.totalVotes}</div>`;
            }
            
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
        
        // Auto-refresh every 5 seconds for real-time updates
        setInterval(loadResults, 5000);
    </script>
</body>
</html>