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

    // Validate required fields
    $required_fields = ['first_name', 'last_name', 'username', 'email', 'password'];
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            header("Location: ../manage_employees.php?error=Missing+required+field:+" . urlencode($field));
            exit();
        }
    }

    $first_name = sanitizeInput(trim($_POST['first_name']));
    $last_name = sanitizeInput(trim($_POST['last_name']));
    $username = sanitizeInput(trim($_POST['username']));
    $email = sanitizeInput(trim($_POST['email']));
    $password = $_POST['password'];
    
    // Validate name format (letters, spaces, hyphens, apostrophes only)
    if (!preg_match('/^[a-zA-Z\s\-\'\.]+$/', $first_name)) {
        header("Location: ../manage_employees.php?error=Invalid+first+name+format");
        exit();
    }

    if (!preg_match('/^[a-zA-Z\s\-\'\.]+$/', $last_name)) {
        header("Location: ../manage_employees.php?error=Invalid+last+name+format");
        exit();
    }

    // Validate username (alphanumeric, underscores, hyphens only, 3-30 chars)
    if (!preg_match('/^[a-zA-Z0-9_\-]{3,30}$/', $username)) {
        header("Location: ../manage_employees.php?error=Username+must+be+3-30+characters+and+contain+only+letters,+numbers,+underscores,+and+hyphens");
        exit();
    }

    // Validate email format
    if (!validateEmail($email)) {
        header("Location: ../manage_employees.php?error=Invalid+email+address");
        exit();
    }

    // Validate password strength (minimum 8 characters)
    if (strlen($password) < 8) {
        header("Location: ../manage_employees.php?error=Password+must+be+at+least+8+characters");
        exit();
    }

    $password_hashed = password_hash($password, PASSWORD_DEFAULT);
    
    // Sub Admins can only assign users to their own department
    $department = ($_SESSION['role'] === 'ADMIN') ? $_SESSION['department'] : trim($_POST['department']);
    $role       = 'EMPLOYEE'; // Force role to EMPLOYEE

    // Check for duplicate username or email
    $check = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    
    if ($conn instanceof PDO) {
        // PostgreSQL/PDO
        $check->execute([$username, $email]);
        $result = $check->fetchAll();
        
        if (count($result) > 0) {
            header("Location: ../manage_employees.php?error=Username+or+Email+already+exists");
            exit();
        }
    } else {
        // MySQLi
        $check->bind_param("ss", $username, $email);
        $check->execute();
        
        if ($check->get_result()->num_rows > 0) {
            $check->close();
            header("Location: ../manage_employees.php?error=Username+or+Email+already+exists");
            exit();
        }
        $check->close();
    }

    // Insert new employee
    $stmt = $conn->prepare("
        INSERT INTO users (first_name, last_name, username, email, password, role, department)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");

    if ($conn instanceof PDO) {
        // PostgreSQL/PDO
        if ($stmt->execute([$first_name, $last_name, $username, $email, $password, $role, $department])) {
            header("Location: ../manage_employees.php?success=1");
        } else {
            header("Location: ../manage_employees.php?error=Database+Error");
        }
    } else {
        // MySQLi
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
    }
    exit();
}
?>