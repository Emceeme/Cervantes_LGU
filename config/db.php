<?php
// Load environment variables
require_once __DIR__ . '/env.php';

// Check for cloud platform DATABASE_URL (Heroku, Render, etc.)
$database_url = getenv('DATABASE_URL');

if ($database_url) {
    // Parse DATABASE_URL
    $db_url = parse_url($database_url);
    
    if ($db_url === false) {
        die("Invalid DATABASE_URL format");
    }
    
    $db_host = $db_url['host'] ?? '';
    $db_user = $db_url['user'] ?? '';
    $db_pass = $db_url['pass'] ?? '';
    $db_name = ltrim($db_url['path'] ?? '', '/');
    $db_port = $db_url['port'] ?? 5432;
    
    // Debug: Show parsed values (remove in production)
    if (env('APP_DEBUG', false)) {
        error_log("DATABASE_URL parsed: host=$db_host, user=$db_user, name=$db_name, port=$db_port");
    }
    
    // Check if PostgreSQL or MySQL
    if (strpos($database_url, 'postgres') !== false) {
        // PostgreSQL connection using PDO
        try {
            $dsn = "pgsql:host={$db_host};port={$db_port};dbname={$db_name}";
            $conn = new PDO($dsn, $db_user, $db_pass);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            if (env('APP_DEBUG', false)) {
                die("PostgreSQL connection failed: " . $e->getMessage() . " | DSN: $dsn");
            } else {
                die("Database connection failed. Please contact system administrator.");
            }
        }
    } else {
        // MySQL connection for cloud platforms
        $conn = new mysqli($db_host, $db_user, $db_pass, $db_name, $db_port);
        
        if ($conn->connect_error) {
            if (env('APP_DEBUG', false)) {
                die("MySQL connection failed: " . $conn->connect_error);
            } else {
                die("Database connection failed. Please contact system administrator.");
            }
        }
        $conn->set_charset("utf8mb4");
    }
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