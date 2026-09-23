<?php
// app/config/config.php

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'unidia_db');

// Base URL - Change this to match your actual path
define('BASE_URL', 'http://localhost/unidia/public');

// Application Paths
define('BASE_PATH', dirname(__DIR__)); // This points to /unidia/app
define('APP_PATH', BASE_PATH);
define('PUBLIC_PATH', dirname(BASE_PATH) . '/public'); // This points to /unidia/public
define('UPLOAD_PATH', PUBLIC_PATH . '/uploads');

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection function
function getDBConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    $conn->set_charset("utf8mb4");
    return $conn;
}

// Global database connection
$db = getDBConnection();
?>