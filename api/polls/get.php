<?php
// Turn off HTML error display
ini_set('display_errors', 0);
error_reporting(E_ALL);

session_start();
header('Content-Type: application/json');

try {
    // Include required files
    require_once __DIR__ . '/../../config/database.php';
    require_once __DIR__ . '/../../includes/auth.php';

    // Check if user is logged in
    $auth = new Auth();
    if (!$auth->isLoggedIn()) {
        echo json_encode(['success' => false, 'message' => 'Please login to edit polls']);
        exit;
    }

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

    // Verify user owns this poll
    $database = new Database();
    $db = $database->getConnection();
    $userId = $auth->getCurrentUserId();

    // Get poll details
    $pollQuery = "SELECT * FROM polls WHERE id = ? AND created_by = ?";
    $stmt = $db->prepare($pollQuery);
    $stmt->execute([$pollId, $userId]);
    $poll = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$poll) {
        echo json_encode(['success' => false, 'message' => 'Poll not found or access denied']);
        exit;
    }

    // Get options
    $optionsQuery = "SELECT id, option_text, vote_count FROM options WHERE poll_id = ? ORDER BY id ASC";
    $stmt = $db->prepare($optionsQuery);
    $stmt->execute([$pollId]);
    $options = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'poll' => $poll,
        'options' => $options
    ]);

} catch (Exception $e) {
    error_log('Get poll error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error fetching poll: ' . $e->getMessage()]);
}
?>