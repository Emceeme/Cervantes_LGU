<?php
// Super Admin Creation Script for Render
// Access this file with ?secret=YOUR_SECRET_KEY to create super admin

require_once __DIR__ . '/config/env.php';
require_once __DIR__ . '/config/db.php';

// Secret key for security (change this in production)
$SECRET_KEY = 'create_admin_secret_2024';

// Check for secret key
if (!isset($_GET['secret']) || $_GET['secret'] !== $SECRET_KEY) {
    http_response_code(403);
    die("Access denied. Invalid secret key.");
}

// Check if Super Admin already exists
if ($conn instanceof PDO) {
    $check = $conn->prepare("SELECT id FROM users WHERE role='SUPER_ADMIN'");
    $check->execute();
    $check_result = $check->fetch();
    
    if ($check_result) {
        die("Setup cancelled: A Super Admin account already exists.");
    }
} else {
    $check = $conn->prepare("SELECT id FROM users WHERE role='SUPER_ADMIN'");
    $check->execute();
    $check_result = $check->get_result();
    
    if ($check_result && $check_result->num_rows > 0) {
        die("Setup cancelled: A Super Admin account already exists.");
    }
    $check->close();
}

// User parameters from environment variables
$first_name = "System";
$last_name  = "Admin";
$username   = env('SUPER_ADMIN_USERNAME', 'superadmin');
$email      = env('SUPER_ADMIN_EMAIL', 'admin@lgu.local');
$raw_pass   = env('SUPER_ADMIN_PASSWORD', 'admin123');
$password   = password_hash($raw_pass, PASSWORD_DEFAULT);
$role       = "SUPER_ADMIN";
$department = "IT Department";

// Validate environment variables - use defaults if not set
if (empty($username) || empty($email) || empty($raw_pass)) {
    echo "Note: Super Admin credentials not set in environment. Using defaults.<br>";
    $username = 'superadmin';
    $email = 'admin@lgu.local';
    $raw_pass = 'admin123';
    $password = password_hash($raw_pass, PASSWORD_DEFAULT);
}

if ($conn instanceof PDO) {
    $stmt = $conn->prepare("
        INSERT INTO users (first_name, last_name, username, email, password, role, department)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    
    try {
        $stmt->execute([$first_name, $last_name, $username, $email, $password, $role, $department]);
        echo "<h2>Super Admin created successfully!</h2>";
        echo "<ul>";
        echo "<li><strong>Username:</strong> " . htmlspecialchars($username) . "</li>";
        echo "<li><strong>Password:</strong> " . htmlspecialchars($raw_pass) . "</li>";
        echo "<li><strong>Role:</strong> " . htmlspecialchars($role) . "</li>";
        echo "</ul>";
        echo "<p><em>Note: Please delete this setup script after verifying login.</em></p>";
    } catch (PDOException $e) {
        echo "Error inserting user: " . $e->getMessage();
    }
} else {
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
}
?>
