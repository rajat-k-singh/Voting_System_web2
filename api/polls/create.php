<?php
// Turn off HTML error display, use JSON only
ini_set('display_errors', 0);
error_reporting(E_ALL);

session_start();
header('Content-Type: application/json');

// Handle preflight request for CORS
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

try {
    // Include required files with error handling
    $configFile = __DIR__ . '/../../config/database.php';
    $authFile = __DIR__ . '/../../includes/auth.php';
    
    if (!file_exists($configFile)) {
        throw new Exception('Database configuration file not found');
    }
    if (!file_exists($authFile)) {
        throw new Exception('Auth file not found');
    }
    
    require_once $configFile;
    require_once $authFile;

    // Check if user is logged in
    $auth = new Auth();
    if (!$auth->isLoggedIn()) {
        echo json_encode(['success' => false, 'message' => 'Please login to create polls']);
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
        echo json_encode(['success' => false, 'message' => 'Invalid JSON data: ' . json_last_error_msg()]);
        exit;
    }

    // Validate required fields
    $question = trim($data['question'] ?? '');
    $options = $data['options'] ?? [];

    if (empty($question)) {
        echo json_encode(['success' => false, 'message' => 'Poll question is required']);
        exit;
    }

    if (!is_array($options) || count($options) < 2) {
        echo json_encode(['success' => false, 'message' => 'At least 2 options are required']);
        exit;
    }

    // Filter out empty options
    $validOptions = [];
    foreach ($options as $option) {
        $trimmedOption = trim($option);
        if (!empty($trimmedOption)) {
            $validOptions[] = $trimmedOption;
        }
    }

    if (count($validOptions) < 2) {
        echo json_encode(['success' => false, 'message' => 'At least 2 non-empty options are required']);
        exit;
    }

    // Database operations
    $database = new Database();
    $db = $database->getConnection();
    $userId = $auth->getCurrentUserId();

    // Start transaction
    $db->beginTransaction();

    try {
        // Insert poll
        $pollQuery = "INSERT INTO polls (question, created_by) VALUES (?, ?)";
        $stmt = $db->prepare($pollQuery);
        $stmt->execute([$question, $userId]);
        $pollId = $db->lastInsertId();

        // Insert options
        $optionQuery = "INSERT INTO options (poll_id, option_text) VALUES (?, ?)";
        $stmt = $db->prepare($optionQuery);
        
        foreach ($validOptions as $optionText) {
            $stmt->execute([$pollId, $optionText]);
        }

        // Commit transaction
        $db->commit();

        // Success response
        echo json_encode([
            'success' => true, 
            'message' => 'Poll created successfully!',
            'pollId' => (int)$pollId
        ]);

    } catch (Exception $e) {
        // Rollback on error
        $db->rollBack();
        throw new Exception('Database error: ' . $e->getMessage());
    }

} catch (Exception $e) {
    // Generic error handler
    error_log('Poll creation error: ' . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Failed to create poll: ' . $e->getMessage()
    ]);
}
?>