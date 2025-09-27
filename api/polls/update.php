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
        echo json_encode(['success' => false, 'message' => 'Please login to edit polls']);
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
    $question = trim($data['question'] ?? '');
    $options = $data['options'] ?? []; // Array of objects: {id, text}

    if ($pollId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid poll ID']);
        exit;
    }

    if (empty($question)) {
        echo json_encode(['success' => false, 'message' => 'Poll question is required']);
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

    // Start transaction
    $db->beginTransaction();

    try {
        // Update poll question
        $pollQuery = "UPDATE polls SET question = ? WHERE id = ?";
        $stmt = $db->prepare($pollQuery);
        $stmt->execute([$question, $pollId]);

        // Update existing options and add new ones
        foreach ($options as $option) {
            if (isset($option['id']) && $option['id'] > 0) {
                // Update existing option
                $optionQuery = "UPDATE options SET option_text = ? WHERE id = ? AND poll_id = ?";
                $stmt = $db->prepare($optionQuery);
                $stmt->execute([trim($option['text']), $option['id'], $pollId]);
            } else {
                // Add new option
                $optionQuery = "INSERT INTO options (poll_id, option_text) VALUES (?, ?)";
                $stmt = $db->prepare($optionQuery);
                $stmt->execute([$pollId, trim($option['text'])]);
            }
        }

        $db->commit();
        echo json_encode(['success' => true, 'message' => 'Poll updated successfully!']);

    } catch (Exception $e) {
        $db->rollBack();
        throw new Exception('Database error: ' . $e->getMessage());
    }

} catch (Exception $e) {
    error_log('Poll update error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to update poll: ' . $e->getMessage()]);
}
?>