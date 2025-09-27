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
        echo json_encode(['success' => false, 'message' => 'Please login to vote']);
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
    $optionId = intval($data['optionId'] ?? 0);
    $userId = $auth->getCurrentUserId();

    if ($pollId <= 0 || $optionId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid poll or option selection']);
        exit;
    }

    // Database operations
    $database = new Database();
    $db = $database->getConnection();

    // Start transaction
    $db->beginTransaction();

    try {
        // Check if user already voted in this poll
        $checkQuery = "SELECT id FROM votes WHERE poll_id = ? AND user_id = ?";
        $stmt = $db->prepare($checkQuery);
        $stmt->execute([$pollId, $userId]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => false, 'message' => 'You have already voted in this poll']);
            exit;
        }

        // Verify the option belongs to the poll
        $verifyQuery = "SELECT id FROM options WHERE id = ? AND poll_id = ?";
        $stmt = $db->prepare($verifyQuery);
        $stmt->execute([$optionId, $pollId]);
        
        if ($stmt->rowCount() === 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid option for this poll']);
            exit;
        }

        // Record vote
        $voteQuery = "INSERT INTO votes (poll_id, user_id, option_id) VALUES (?, ?, ?)";
        $stmt = $db->prepare($voteQuery);
        $stmt->execute([$pollId, $userId, $optionId]);

        // Update option count
        $updateQuery = "UPDATE options SET vote_count = vote_count + 1 WHERE id = ?";
        $stmt = $db->prepare($updateQuery);
        $stmt->execute([$optionId]);

        // Commit transaction
        $db->commit();

        echo json_encode(['success' => true, 'message' => 'Vote recorded successfully!']);

    } catch (Exception $e) {
        $db->rollBack();
        throw new Exception('Database error: ' . $e->getMessage());
    }

} catch (Exception $e) {
    error_log('Vote error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Vote failed: ' . $e->getMessage()]);
}
?>