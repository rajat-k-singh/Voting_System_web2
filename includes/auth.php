<?php
session_start();

// Include database configuration
require_once __DIR__ . '/../config/database.php';

class Auth {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
    
    public function redirectIfNotLoggedIn($redirectUrl = '../index.php') {
        if (!$this->isLoggedIn()) {
            header('Location: ' . $redirectUrl);
            exit;
        }
    }
    
    public function redirectIfLoggedIn($redirectUrl = 'pages/dashboard.php') {
        if ($this->isLoggedIn()) {
            header('Location: ' . $redirectUrl);
            exit;
        }
    }
    
    public function getCurrentUserId() {
        return $_SESSION['user_id'] ?? null;
    }
    
    public function getCurrentUsername() {
        return $_SESSION['username'] ?? null;
    }
}

// Create global auth instance
$auth = new Auth();
?>