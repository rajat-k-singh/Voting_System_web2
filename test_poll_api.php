<?php
// Test the poll API directly
session_start();
// Simulate login
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'testuser';

echo "<h1>Testing Poll API Directly</h1>";

// Test data
$testData = [
    'question' => 'Test API Poll - Is this working?',
    'options' => ['Yes', 'No', 'Maybe']
];

echo "<h2>Test Data:</h2>";
echo "<pre>" . print_r($testData, true) . "</pre>";

// Test the API endpoint directly
$url = 'http://localhost/voting-system/api/polls/create.php';

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($testData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$headers = substr($response, 0, $headerSize);
$body = substr($response, $headerSize);

curl_close($ch);

echo "<h2>Response Headers:</h2>";
echo "<pre>" . htmlspecialchars($headers) . "</pre>";

echo "<h2>Response Body:</h2>";
echo "<pre>" . htmlspecialchars($body) . "</pre>";

echo "<h2>JSON Decoded:</h2>";
$decoded = json_decode($body, true);
if (json_last_error() === JSON_ERROR_NONE) {
    echo "<pre>" . print_r($decoded, true) . "</pre>";
} else {
    echo "JSON decode error: " . json_last_error_msg();
    echo "<p>Raw body starts with: " . substr($body, 0, 100) . "...</p>";
}
?>