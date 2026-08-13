<?php
session_start();
require_once '../../config/security.php';
include '../../config/db.php';

// Set security headers
setSecurityHeaders();

// 🔒 Authorization Guard
if (!isset($_SESSION['name']) || ($_SESSION['department'] !== 'Treasury' && $_SESSION['role'] !== 'SUPER_ADMIN')) {
    logSecurityEvent('unauthorized_access', $_SESSION['id'] ?? null, ['endpoint' => 'post_create_public_user']);
    die("Access denied.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        logSecurityEvent('csrf_validation_failed', $_SESSION['id'] ?? null, ['endpoint' => 'post_create_public_user']);
        $_SESSION['error'] = "Security validation failed. Please try again.";
        header("Location: ../create_public_user.php");
        exit();
    }
    $first_name      = trim($_POST['first_name']);
    $last_name       = trim($_POST['last_name']);
    $username        = trim($_POST['username']);
    $email           = trim($_POST['email']);
    $password        = $_POST['password'];
    $initial_balance = floatval($_POST['initial_balance'] ?? 0.00);

    // 1. Check for duplicate username or email
    $check_stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $check_stmt->bind_param("ss", $username, $email);
    $check_stmt->execute();
    if ($check_stmt->get_result()->num_rows > 0) {
        $_SESSION['error'] = "Username or Email address is already registered.";
        $check_stmt->close();
        header("Location: ../create_public_user.php");
        exit();
    }
    $check_stmt->close();

    // 2. Hash password & Insert into users table as CITIZEN
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $role = 'CITIZEN';

    $insert_stmt = $conn->prepare("
        INSERT INTO users (first_name, last_name, username, email, password, role, balance) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $insert_stmt->bind_param("ssssssd", $first_name, $last_name, $username, $email, $hashed_password, $role, $initial_balance);

    if ($insert_stmt->execute()) {
        $_SESSION['success'] = "Citizen profile for {$first_name} {$last_name} created successfully!";
    } else {
        $_SESSION['error'] = "Error creating user: " . $conn->error;
    }

    $insert_stmt->close();
    header("Location: ../create_public_user.php");
    exit();
}
?>