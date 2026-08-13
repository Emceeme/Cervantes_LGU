<?php
session_start();
require_once '../../config/security.php';
include '../../config/db.php';

// Set security headers
setSecurityHeaders();

// Access Check
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['ADMIN', 'SUPER_ADMIN'], true)) {
    logSecurityEvent('unauthorized_access', $_SESSION['id'] ?? null, ['endpoint' => 'post_employee']);
    die("Access denied.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        logSecurityEvent('csrf_validation_failed', $_SESSION['id'] ?? null, ['endpoint' => 'post_employee']);
        header("Location: ../manage_employees.php?error=Security+validation+failed");
        exit();
    }

    $first_name = trim($_POST['first_name']);
    $last_name  = trim($_POST['last_name']);
    $username   = trim($_POST['username']);
    $email      = trim($_POST['email']);
    $password   = password_hash($_POST['password'], PASSWORD_DEFAULT);
    
    // Sub Admins can only assign users to their own department
    $department = ($_SESSION['role'] === 'ADMIN') ? $_SESSION['department'] : trim($_POST['department']);
    $role       = 'EMPLOYEE'; // Force role to EMPLOYEE

    // Check for duplicate username or email
    $check = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $check->bind_param("ss", $username, $email);
    $check->execute();
    
    if ($check->get_result()->num_rows > 0) {
        $check->close();
        header("Location: ../manage_employees.php?error=Username+or+Email+already+exists");
        exit();
    }
    $check->close();

    // Insert new employee
    $stmt = $conn->prepare("
        INSERT INTO users (first_name, last_name, username, email, password, role, department)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");

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
        header("Location: ../manage_employees.php?success=1");
    } else {
        header("Location: ../manage_employees.php?error=Database+Error");
    }

    $stmt->close();
    $conn->close();
    exit();
}
?>