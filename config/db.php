<?php
// Load environment variables
require_once __DIR__ . '/env.php';

// Check for cloud platform DATABASE_URL (Heroku, Render, etc.)
if (getenv('DATABASE_URL')) {
    $db_url = parse_url(getenv('DATABASE_URL'));
    $db_host = $db_url['host'];
    $db_user = $db_url['user'];
    $db_pass = $db_url['pass'];
    $db_name = ltrim($db_url['path'], '/');
    $db_port = $db_url['port'] ?? 3306;
    
    // Create MySQL connection for cloud platforms
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name, $db_port);
    
    if ($conn->connect_error) {
        if (env('APP_DEBUG', false)) {
            die("Database connection failed: " . $conn->connect_error);
        } else {
            die("Database connection failed. Please contact system administrator.");
        }
    }
    $conn->set_charset("utf8mb4");
} else {
    // Traditional MySQL connection for local deployment
    $db_host = env('DB_HOST', 'localhost');
    $db_user = env('DB_USER', 'root');
    $db_pass = env('DB_PASS', '');
    $db_name = env('DB_NAME', 'lgu_system');
    
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
    
    if ($conn->connect_error) {
        if (env('APP_DEBUG', false)) {
            die("Database connection failed: " . $conn->connect_error);
        } else {
            die("Database connection failed. Please contact system administrator.");
        }
    }
    
    $conn->set_charset("utf8mb4");
}
?>