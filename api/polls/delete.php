<?php
// Turn off HTML error display
ini_set('display_errors', 0);
error_reporting(E_ALL);

session_start();
header('Content-Type: application/json');

// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

try {
    // Include required files
    require_once __DIR__ . '/../../config/database.php';
    require_once __DIR__ . '/../../includes/auth.php';

    // Check if user is logged in
    $auth = new Auth();
    if (!$auth->isLoggedIn()) {
        echo json_encode(['success' => false, 'message' => 'Please login to delete polls']);
        exit;
    }

    // Only allow POST requests
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'Only POST method allowed']);
        exit;
    }

    // Get JSON input
    $input = file_get_contents('php://input');
    if (empty($input)) {
        echo json_encode(['success' => false, 'message' => 'No data received']);
        exit;
    }

    $data = json_decode($input, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo json_encode(['success' => false, 'message' => 'Invalid JSON data']);
        exit;
    }

    // Validate input
    $pollId = intval($data['pollId'] ?? 0);

    if ($pollId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid poll ID']);
        exit;
    }

    // Verify user owns this poll
    $database = new Database();
    $db = $database->getConnection();
    $userId = $auth->getCurrentUserId();

    $ownershipQuery = "SELECT id FROM polls WHERE id = ? AND created_by = ?";
    $stmt = $db->prepare($ownershipQuery);
    $stmt->execute([$pollId, $userId]);
    
    if ($stmt->rowCount() === 0) {
        echo json_encode(['success' => false, 'message' => 'Poll not found or access denied']);
        exit;
    }

    // Delete poll (cascade delete will handle options and votes)
    $deleteQuery = "DELETE FROM polls WHERE id = ?";
    $stmt = $db->prepare($deleteQuery);
    
    if ($stmt->execute([$pollId])) {
        echo json_encode(['success' => true, 'message' => 'Poll deleted successfully!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete poll']);
    }

} catch (Exception $e) {
    error_log('Poll delete error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to delete poll: ' . $e->getMessage()]);
}
?>