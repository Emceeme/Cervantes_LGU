<?php
// Restrict execution to local environments to avoid unauthorized remote triggering
if (php_sapi_name() !== 'cli' && ($_SERVER['REMOTE_ADDR'] ?? '') !== '127.0.0.1' && ($_SERVER['REMOTE_ADDR'] ?? '') !== '::1') {
    http_response_code(403);
    die("Access denied: Setup scripts can only be run locally or via CLI.");
}

require_once 'config/env.php';
require_once 'config/db.php';

// Check if Super Admin already exists
$check = $conn->prepare("SELECT id FROM users WHERE role='SUPER_ADMIN'");
$check->execute();
$check_result = $check->get_result();

if ($check_result && $check_result->num_rows > 0) {
    die("Setup cancelled: A Super Admin account already exists.");
}
$check->close();

// User parameters from environment variables
$first_name = "System";
$last_name  = "Admin";
$username   = env('SUPER_ADMIN_USERNAME');
$email      = env('SUPER_ADMIN_EMAIL');
$raw_pass   = env('SUPER_ADMIN_PASSWORD');
$password   = password_hash($raw_pass, PASSWORD_DEFAULT);
$role       = "SUPER_ADMIN";
$department = "IT Department";

// Validate environment variables
if (empty($username) || empty($email) || empty($raw_pass)) {
    die("Error: Super Admin credentials not set in .env file. Please set SUPER_ADMIN_USERNAME, SUPER_ADMIN_EMAIL, and SUPER_ADMIN_PASSWORD.");
}

$stmt = $conn->prepare("
    INSERT INTO users (first_name, last_name, username, email, password, role, department)
    VALUES (?, ?, ?, ?, ?, ?, ?)
");

if (!$stmt) {
    die("Statement preparation failed: " . $conn->error);
}

$stmt->bind_param("sssssss",
    $first_name,
    $last_name,
    $username,
    $email,
    $password,
    $role,
    $department
);

if ($stmt->execute()) {
    echo "<h2>Super Admin created successfully!</h2>";
    echo "<ul>";
    echo "<li><strong>Username:</strong> " . htmlspecialchars($username) . "</li>";
    echo "<li><strong>Password:</strong> " . htmlspecialchars($raw_pass) . "</li>";
    echo "<li><strong>Role:</strong> " . htmlspecialchars($role) . "</li>";
    echo "</ul>";
    echo "<p><em>Note: Please delete this setup script after verifying login.</em></p>";
} else {
    echo "Error inserting user: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>