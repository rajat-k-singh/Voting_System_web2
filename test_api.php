<?php
// Test if API is accessible
echo "<h1>API Connection Test</h1>";

// Test database connection
require_once 'config/database.php';
try {
    $database = new Database();
    $conn = $database->getConnection();
    echo "<p style='color: green'>✅ Database connection successful</p>";
} catch (Exception $e) {
    echo "<p style='color: red'>❌ Database connection failed: " . $e->getMessage() . "</p>";
}

// Test if user is logged in
session_start();
if (isset($_SESSION['user_id'])) {
    echo "<p style='color: green'>✅ User is logged in (ID: " . $_SESSION['user_id'] . ")</p>";
} else {
    echo "<p style='color: red'>❌ User is not logged in</p>";
}

// Test API endpoint directly
echo "<h2>Testing Poll Create API:</h2>";
$url = 'http://' . $_SERVER['HTTP_HOST'] . '/voting-system/api/polls/create.php';
echo "<p>API URL: $url</p>";

// Test if file exists
if (file_exists('api/polls/create.php')) {
    echo "<p style='color: green'>✅ API file exists</p>";
} else {
    echo "<p style='color: red'>❌ API file not found at: api/polls/create.php</p>";
}

echo "<h2>Session Data:</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";
?>