<?php
session_start();
require_once '../../config/security.php';
include '../../config/db.php';

// Set security headers
setSecurityHeaders();

// 🔒 SECURITY GUARD: Sub-Admin or Super Admin ONLY
if (
    !isset($_SESSION['name']) || 
    $_SESSION['department'] !== 'Treasury' || 
    ($_SESSION['role'] !== 'SUB_ADMIN' && $_SESSION['role'] !== 'SUPER_ADMIN')
) {
    logSecurityEvent('unauthorized_access', $_SESSION['id'] ?? null, ['endpoint' => 'post_create_employee']);
    die("Unauthorized access.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        logSecurityEvent('csrf_validation_failed', $_SESSION['id'] ?? null, ['endpoint' => 'post_create_employee']);
        $_SESSION['error'] = "Security validation failed. Please try again.";
        header("Location: ../manage_employees.php");
        exit();
    }
    $first_name = trim($_POST['first_name']);
    $last_name  = trim($_POST['last_name']);
    $email      = trim($_POST['email']);
    $raw_pass   = trim($_POST['password']);
    
    // Sub-Admins only create standard Treasury EMPLOYEES
    $role       = 'EMPLOYEE';
    $department = 'Treasury';
    $status     = 'ACTIVE';

    // Check if email already exists in database
    $check_stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $check_stmt->bind_param("s", $email);
    $check_stmt->execute();
    $check_stmt->store_result();

    if ($check_stmt->num_rows > 0) {
        $_SESSION['error'] = "An account with the email '$email' already exists!";
        $check_stmt->close();
        header("Location: ../manage_employees.php");
        exit();
    }
    $check_stmt->close();

    // Securely hash password
    $hashed_password = password_hash($raw_pass, PASSWORD_DEFAULT);

    // Insert new staff into users table
    $insert_stmt = $conn->prepare("
        INSERT INTO users (first_name, last_name, email, password, department, role, status) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    
    if ($insert_stmt) {
        $insert_stmt->bind_param("sssssss", $first_name, $last_name, $email, $hashed_password, $department, $role, $status);
        if ($insert_stmt->execute()) {
            $_SESSION['success'] = "Treasury staff account ($first_name $last_name) created successfully!";
        } else {
            $_SESSION['error'] = "Database error: " . $conn->error;
        }
        $insert_stmt->close();
    } else {
        $_SESSION['error'] = "Failed to prepare database query: " . $conn->error;
    }

    header("Location: ../manage_employees.php");
    exit();
}
?>