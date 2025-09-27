<?php
require_once '../includes/auth.php';
require_once '../config/database.php';

$auth = new Auth();
$auth->redirectIfNotLoggedIn();

$database = new Database();
$db = $database->getConnection();
$userId = $auth->getCurrentUserId();

// Get user's polls
$polls = [];
try {
    $query = "SELECT p.*, 
                     (SELECT COUNT(*) FROM votes WHERE poll_id = p.id) as total_votes,
                     (SELECT COUNT(*) FROM options WHERE poll_id = p.id) as option_count
              FROM polls p 
              WHERE p.created_by = ? 
              ORDER BY p.created_at DESC";
    $stmt = $db->prepare($query);
    $stmt->execute([$userId]);
    $polls = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $error = "Error loading polls: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Polls - Voting System</title>
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
            transition: background 0.3s;
        }
        .nav-links a:hover {
            background: rgba(255,255,255,0.2);
        }
        .container {
            max-width: 1000px;
            margin: 2rem auto;
            padding: 0 2rem;
        }
        .poll-item {
            background: white;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .poll-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
        }
        .poll-question {
            font-size: 1.2rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
            color: #333;
            line-height: 1.4;
        }
        .poll-meta {
            color: #666;
            margin-bottom: 1rem;
            font-size: 0.9rem;
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
        }
        .poll-meta span {
            background: #f8f9fa;
            padding: 0.3rem 0.6rem;
            border-radius: 4px;
            font-size: 0.8rem;
        }
        .poll-actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
            margin-bottom: 1rem;
        }
        .btn {
            padding: 8px 16px;
            text-decoration: none;
            border-radius: 5px;
            font-size: 0.9rem;
            border: none;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        .btn-primary { 
            background: #007bff; 
            color: white; 
        }
        .btn-primary:hover { 
            background: #0056b3; 
            transform: translateY(-1px); 
        }
        .btn-success { 
            background: #28a745; 
            color: white; 
        }
        .btn-success:hover { 
            background: #1e7e34; 
            transform: translateY(-1px); 
        }
        .btn-info { 
            background: #17a2b8; 
            color: white; 
        }
        .btn-info:hover { 
            background: #138496; 
            transform: translateY(-1px); 
        }
        .btn-warning { 
            background: #ffc107; 
            color: #212529; 
        }
        .btn-warning:hover { 
            background: #e0a800; 
            transform: translateY(-1px); 
        }
        .btn-danger { 
            background: #dc3545; 
            color: white; 
        }
        .btn-danger:hover { 
            background: #c82333; 
            transform: translateY(-1px); 
        }
        .poll-share {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #eee;
        }
        .share-buttons {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
            margin-top: 0.5rem;
        }
        .share-btn {
            padding: 5px 10px;
            font-size: 0.8rem;
            background: #6c757d;
            color: white;
            text-decoration: none;
            border-radius: 3px;
            transition: background 0.3s;
        }
        .share-btn:hover {
            background: #545b62;
        }
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #666;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .create-poll-btn {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 8px;
            margin: 1rem 0;
            font-weight: bold;
            transition: transform 0.2s;
        }
        .create-poll-btn:hover {
            transform: translateY(-2px);
        }
        .stats-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            flex-wrap: wrap;
            gap: 1rem;
        }
        .stats-card {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
            flex: 1;
            min-width: 150px;
        }
        .stats-number {
            font-size: 2rem;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 0.5rem;
        }
        .stats-label {
            color: #666;
            font-size: 0.9rem;
        }
        .message {
            padding: 1rem;
            margin: 1rem 0;
            border-radius: 5px;
            text-align: center;
        }
        .success { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
    <nav class="navbar">
        <h1>📊 My Polls</h1>
        <div class="nav-links">
            <a href="dashboard.php">← Dashboard</a>
            <a href="create_poll.php">➕ Create Poll</a>
            <a href="../api/auth/logout.php">🚪 Logout</a>
        </div>
    </nav>
    
    <div class="container">
        <?php if (isset($error)): ?>
            <div class="message error"><?php echo $error; ?></div>
        <?php endif; ?>

        <!-- Statistics Header -->
        <div class="stats-header">
            <div class="stats-card">
                <div class="stats-number"><?php echo count($polls); ?></div>
                <div class="stats-label">Total Polls</div>
            </div>
            <div class="stats-card">
                <div class="stats-number">
                    <?php 
                    $totalVotes = 0;
                    foreach ($polls as $poll) {
                        $totalVotes += $poll['total_votes'];
                    }
                    echo $totalVotes;
                    ?>
                </div>
                <div class="stats-label">Total Votes</div>
            </div>
            <div class="stats-card">
                <div class="stats-number">
                    <?php 
                    $avgVotes = count($polls) > 0 ? round($totalVotes / count($polls), 1) : 0;
                    echo $avgVotes;
                    ?>
                </div>
                <div class="stats-label">Avg Votes/Poll</div>
            </div>
        </div>

        <?php if (empty($polls)): ?>
            <div class="empty-state">
                <h2>📝 No polls yet</h2>
                <p>You haven't created any polls. Create your first poll to get started!</p>
                <a href="create_poll.php" class="create-poll-btn">Create Your First Poll</a>
            </div>
        <?php else: ?>
            <h2 style="margin-bottom: 1.5rem; color: #333;">Your Polls (<?php echo count($polls); ?>)</h2>
            
            <?php foreach ($polls as $poll): ?>
                <div class="poll-item">
                    <div class="poll-question"><?php echo htmlspecialchars($poll['question']); ?></div>
                    
                    <div class="poll-meta">
                        <span>📅 <?php echo date('M j, Y g:i A', strtotime($poll['created_at'])); ?></span>
                        <span>⚙️ <?php echo $poll['option_count']; ?> options</span>
                        <span>🗳️ <?php echo $poll['total_votes']; ?> votes</span>
                    </div>

                    <div class="poll-actions">
                        <a href="vote.php?poll_id=<?php echo $poll['id']; ?>" class="btn btn-primary">
                            🗳️ Vote
                        </a>
                        <a href="results.php?poll_id=<?php echo $poll['id']; ?>" class="btn btn-success">
                            📊 Results
                        </a>
                        <a href="chart_results.php?poll_id=<?php echo $poll['id']; ?>" class="btn btn-info">
                            📈 Charts
                        </a>
                        <a href="edit_poll.php?poll_id=<?php echo $poll['id']; ?>" class="btn btn-warning">
                            ✏️ Edit
                        </a>
                        <button onclick="confirmDelete(<?php echo $poll['id']; ?>)" class="btn btn-danger">
                            🗑️ Delete
                        </button>
                    </div>

                    <div class="poll-share">
                        <strong>🔗 Share this poll:</strong>
                        <div class="share-buttons">
                            <a href="public_vote.php?poll_id=<?php echo $poll['id']; ?>" class="share-btn">
                                Public Vote Link
                            </a>
                            <a href="public_results.php?poll_id=<?php echo $poll['id']; ?>" class="share-btn">
                                Public Results
                            </a>
                            <button onclick="copyPollLink(<?php echo $poll['id']; ?>)" class="share-btn" style="border: none; cursor: pointer;">
                                Copy Link
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <script>
        async function confirmDelete(pollId) {
            if (confirm('❓ Are you sure you want to delete this poll?\n\nThis will permanently delete:\n• The poll question\n• All voting options\n• All votes received\n• Any shared links\n\nThis action cannot be undone!')) {
                try {
                    const response = await fetch('../api/polls/delete.php', {
                        method: 'POST',
                        headers: { 
                            'Content-Type': 'application/json' 
                        },
                        body: JSON.stringify({ 
                            pollId: pollId 
                        })
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        // Show success message
                        showMessage('✅ ' + result.message, 'success');
                        
                        // Remove the poll item from the page after a delay
                        setTimeout(() => {
                            // Refresh the page to show updated list
                            location.reload();
                        }, 1500);
                    } else {
                        showMessage('❌ ' + result.message, 'error');
                    }
                } catch (error) {
                    showMessage('❌ Network error: ' + error.message, 'error');
                }
            }
        }

        function copyPollLink(pollId) {
            const voteLink = window.location.origin + '/voting-system/pages/public_vote.php?poll_id=' + pollId;
            
            navigator.clipboard.writeText(voteLink).then(() => {
                // Show temporary notification
                const notification = document.createElement('div');
                notification.style.cssText = `
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    background: #28a745;
                    color: white;
                    padding: 1rem;
                    border-radius: 5px;
                    box-shadow: 0 2px 10px rgba(0,0,0,0.2);
                    z-index: 1000;
                `;
                notification.textContent = '✅ Poll link copied to clipboard!';
                document.body.appendChild(notification);
                
                setTimeout(() => {
                    document.body.removeChild(notification);
                }, 3000);
            }).catch(err => {
                showMessage('❌ Failed to copy link: ' + err, 'error');
            });
        }

        function showMessage(text, type) {
            // Remove existing messages
            const existingMessages = document.querySelectorAll('.temp-message');
            existingMessages.forEach(msg => msg.remove());
            
            const messageDiv = document.createElement('div');
            messageDiv.className = `message ${type} temp-message`;
            messageDiv.textContent = text;
            messageDiv.style.marginTop = '1rem';
            
            document.querySelector('.container').insertBefore(messageDiv, document.querySelector('.container').firstChild);
            
            // Auto-remove success messages after 5 seconds
            if (type === 'success') {
                setTimeout(() => {
                    if (messageDiv.parentNode) {
                        messageDiv.parentNode.removeChild(messageDiv);
                    }
                }, 5000);
            }
        }

        // Add confirmation for navigation away from page if there are unsaved changes
        window.addEventListener('beforeunload', function (e) {
            // You can add logic here to check for unsaved changes
            // For now, we'll just leave it as a placeholder
        });

        // Add keyboard shortcut support
        document.addEventListener('keydown', function(e) {
            // Ctrl+D to focus on delete buttons (for accessibility)
            if (e.ctrlKey && e.key === 'd') {
                e.preventDefault();
                const firstDeleteBtn = document.querySelector('.btn-danger');
                if (firstDeleteBtn) {
                    firstDeleteBtn.focus();
                }
            }
        });

        // Add loading state for buttons
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('btn-danger')) {
                const button = e.target;
                const originalText = button.innerHTML;
                button.innerHTML = '⏳ Deleting...';
                button.disabled = true;
                
                // Revert after 5 seconds if still processing
                setTimeout(() => {
                    if (button.disabled) {
                        button.innerHTML = originalText;
                        button.disabled = false;
                    }
                }, 5000);
            }
        });
    </script>
</body>
</html>