<?php
$db_host = getenv('DB_HOST') ?: 'localhost';
$db_user = getenv('DB_USER') ?: 'root';
$db_pass = getenv('DB_PASS') ?: '';
$db_name = getenv('DB_NAME') ?: 'db_test';

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    // Log the detailed error server-side; do not leak it to the client.
    error_log("Database connection failed: " . $conn->connect_error);
    http_response_code(500);
    die("A database error occurred. Please try again later.");
}
?>