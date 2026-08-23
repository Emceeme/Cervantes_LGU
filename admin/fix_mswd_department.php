<?php
/**
 * Quick script to update a user's department to MSWD
 * Run this to fix the department for a specific user
 */

session_start();
require_once '../config/security.php';
include '../config/db.php';

// Set security headers
setSecurityHeaders();

// SECURITY GUARD: Super Admin only
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'SUPER_ADMIN') {
    header('Location: /login.php?unauthorized=1');
    exit();
}

$username_to_update = $_GET['username'] ?? '';

if (empty($username_to_update)) {
    die("Usage: fix_mswd_department.php?username=USERNAME");
}

// Update the user's department to MSWD
$stmt = $conn->prepare("UPDATE users SET department = 'MSWD' WHERE username = ?");
$stmt->bind_param("s", $username_to_update);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        echo "✓ Successfully updated department to MSWD for user: " . htmlspecialchars($username_to_update) . "\n";
        echo "Please login again to see the changes.\n";
    } else {
        echo "✗ User not found: " . htmlspecialchars($username_to_update) . "\n";
    }
} else {
    echo "✗ Error: " . $stmt->error . "\n";
}

$stmt->close();
$conn->close();
?>
