<?php
// Turn off HTML error display
ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

try {
    // Include required files
    require_once __DIR__ . '/../../config/database.php';

    // Only allow GET requests
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        echo json_encode(['success' => false, 'message' => 'Only GET method allowed']);
        exit;
    }

    // Get poll ID from query parameter
    $pollId = intval($_GET['pollId'] ?? 0);
    
    if ($pollId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid poll ID']);
        exit;
    }

    // Database operations
    $database = new Database();
    $db = $database->getConnection();

    // Get poll question and details (publicly accessible)
    $pollQuery = "SELECT p.*, u.username as created_by_name 
                 FROM polls p 
                 JOIN users u ON p.created_by = u.id 
                 WHERE p.id = ?";
    $stmt = $db->prepare($pollQuery);
    $stmt->execute([$pollId]);
    $poll = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$poll) {
        echo json_encode(['success' => false, 'message' => 'Poll not found']);
        exit;
    }

    // Get options with vote counts
    $optionsQuery = "SELECT id, option_text, vote_count FROM options WHERE poll_id = ? ORDER BY vote_count DESC";
    $stmt = $db->prepare($optionsQuery);
    $stmt->execute([$pollId]);
    $options = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get total votes
    $totalQuery = "SELECT COUNT(*) as total FROM votes WHERE poll_id = ?";
    $stmt = $db->prepare($totalQuery);
    $stmt->execute([$pollId]);
    $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Check if current user has voted (only if logged in)
    session_start();
    $userVoted = false;
    $userVoteOption = null;
    
    if (isset($_SESSION['user_id'])) {
        $voteQuery = "SELECT option_id FROM votes WHERE poll_id = ? AND user_id = ?";
        $stmt = $db->prepare($voteQuery);
        $stmt->execute([$pollId, $_SESSION['user_id']]);
        $userVote = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($userVote) {
            $userVoted = true;
            $userVoteOption = $userVote['option_id'];
        }
    }

    echo json_encode([
        'success' => true,
        'poll' => $poll,
        'options' => $options,
        'totalVotes' => $total,
        'userVoted' => $userVoted,
        'userVoteOption' => $userVoteOption,
        'public' => true
    ]);

} catch (Exception $e) {
    error_log('Results error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error fetching results: ' . $e->getMessage()]);
}
?>