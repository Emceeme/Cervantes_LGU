<?php
session_start();
require_once '../../config/security.php';
require_once '../../config/db.php';

setSecurityHeaders();

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo 'Method Not Allowed';
    exit();
}

// Validate CSRF token
if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
    http_response_code(403);
    echo 'Security validation failed';
    exit();
}

// 🔒 SECURITY GUARD: Super Admin privileges required
if (!isset($_SESSION['name']) || $_SESSION['role'] !== 'SUPER_ADMIN') {
    logSecurityEvent('unauthorized_access', $_SESSION['id'] ?? null, ['endpoint' => 'delete_user', 'reason' => 'not_super_admin']);
    header("Location: ../../login.php");
    exit();
}

// Check if an ID was provided via POST request
if (isset($_POST['id'])) {
    $user_id = intval($_POST['id']);

    // Prevent Super Admin from deleting their active session account
    if (isset($_SESSION['id']) && $user_id === intval($_SESSION['id'])) {
        $_SESSION['msg'] = "You cannot delete your own Super Admin account!";
        $_SESSION['msg_type'] = "error";
        header("Location: ../lgu_list.php");
        exit();
    }

    // 1. Double check target is not another Super Admin
    $check_stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
    
    if ($conn instanceof PDO) {
        // PostgreSQL/PDO
        $check_stmt->execute([$user_id]);
        $target_user = $check_stmt->fetch();
    } else {
        // MySQLi
        $check_stmt->bind_param("i", $user_id);
        $check_stmt->execute();
        $target_user = $check_stmt->get_result()->fetch_assoc();
        $check_stmt->close();
    }

    if ($target_user && $target_user['role'] !== 'SUPER_ADMIN') {
        // 2. Safely delete the account using prepared statements
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        
        if ($conn instanceof PDO) {
            // PostgreSQL/PDO
            $stmt->execute([$user_id]);
            $success = true;
        } else {
            // MySQLi
            $stmt->bind_param("i", $user_id);
            $success = $stmt->execute();
            $stmt->close();
        }

        if ($success) {
            logSecurityEvent('user_deleted', $_SESSION['id'], ['deleted_user_id' => $user_id]);
            $_SESSION['msg'] = "User account successfully deleted.";
            $_SESSION['msg_type'] = "success";
        } else {
            logError('Failed to delete user: ' . ($conn instanceof PDO ? $conn->errorInfo()[2] : $conn->error));
            $_SESSION['msg'] = "Failed to delete user account.";
            $_SESSION['msg_type'] = "error";
        }
    } else {
        $_SESSION['msg'] = "User not found or cannot be deleted.";
        $_SESSION['msg_type'] = "error";
    }
}

// Redirect back to the LGU accounts list in the admin directory
header("Location: ../lgu_list.php");
exit();
?>