<?php
session_start();
require_once '../../config/security.php';
require_once '../../config/db.php';

// 🔒 SECURITY GUARD: Super Admin privileges required
if (!isset($_SESSION['name']) || $_SESSION['role'] !== 'SUPER_ADMIN') {
    header("Location: ../../login.php");
    exit();
}

// Check if an ID was provided via GET request
if (isset($_GET['id'])) {
    $user_id = intval($_GET['id']);

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
            $_SESSION['msg'] = "User account successfully deleted.";
            $_SESSION['msg_type'] = "success";
        } else {
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