<?php
session_start();
// Simulate being logged in
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'testuser';

require_once 'config/database.php';
require_once 'includes/auth.php';

$auth = new Auth();

if ($auth->isLoggedIn()) {
    echo "<p>User is logged in as: " . $auth->getCurrentUsername() . "</p>";
    
    // Test database connection
    $database = new Database();
    $db = $database->getConnection();
    
    // Test creating a poll directly
    try {
        $question = "Test Poll - Is this working?";
        $options = ["Yes", "No", "Maybe"];
        
        $db->beginTransaction();
        
        // Create poll
        $pollQuery = "INSERT INTO polls (question, created_by) VALUES (?, ?)";
        $stmt = $db->prepare($pollQuery);
        $stmt->execute([$question, $auth->getCurrentUserId()]);
        $pollId = $db->lastInsertId();
        
        echo "<p>Poll created with ID: $pollId</p>";
        
        // Create options
        $optionQuery = "INSERT INTO options (poll_id, option_text) VALUES (?, ?)";
        $stmt = $db->prepare($optionQuery);
        
        foreach ($options as $optionText) {
            $stmt->execute([$pollId, $optionText]);
            echo "<p>Option added: $optionText</p>";
        }
        
        $db->commit();
        echo "<p style='color: green'>✅ Direct database test successful!</p>";
        
    } catch (Exception $e) {
        $db->rollBack();
        echo "<p style='color: red'>❌ Database error: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p>Please login first</p>";
}
?>