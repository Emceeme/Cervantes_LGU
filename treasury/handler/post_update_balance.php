<?php
session_start();
require_once '../../config/security.php';
include '../../config/db.php';

// Set security headers
setSecurityHeaders();

// 🔒 Authorization Check
if (!isset($_SESSION['name']) || ($_SESSION['department'] !== 'Treasury' && $_SESSION['role'] !== 'SUPER_ADMIN')) {
    logSecurityEvent('unauthorized_access', $_SESSION['id'] ?? null, ['endpoint' => 'post_update_balance']);
    header('Location: /login.php?unauthorized=1');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        logSecurityEvent('csrf_validation_failed', $_SESSION['id'] ?? null, ['endpoint' => 'post_update_balance']);
        $_SESSION['error'] = "Security validation failed. Please try again.";
        header("Location: ../update_balance.php");
        exit();
    }

    $citizen_id       = intval($_POST['citizen_id']);
    $officer_id       = $_SESSION['id']; // ID of logged-in Treasury officer
    $transaction_type = $_POST['transaction_type'];
    $fee_category     = trim($_POST['fee_category']);
    $base_amount      = floatval($_POST['base_amount']);
    $penalty          = floatval($_POST['penalty'] ?? 0);
    $remarks          = trim($_POST['remarks']);

    $total_amount = $base_amount + $penalty;

    if ($total_amount <= 0) {
        $_SESSION['error'] = "Total amount must be greater than zero.";
        header("Location: ../update_balance.php?search_id=" . $citizen_id);
        exit();
    }

    // 1. Get current citizen balance AND verify role
    $user_stmt = $conn->prepare("SELECT balance, role FROM users WHERE id = ?");
    $user_stmt->bind_param("i", $citizen_id);
    $user_stmt->execute();
    $user = $user_stmt->get_result()->fetch_assoc();
    $user_stmt->close();

    if (!$user || $user['role'] !== 'CITIZEN') {
        $_SESSION['error'] = "Invalid account selected. Assessments can only be issued to citizens.";
        header("Location: ../update_balance.php");
        exit();
    }

    $previous_balance = floatval($user['balance'] ?? 0);

    // 2. Calculate new balance based on transaction type
    if ($transaction_type === 'ASSESSMENT') {
        $new_balance = $previous_balance + $total_amount;
    } elseif ($transaction_type === 'PAYMENT') {
        $new_balance = $previous_balance - $total_amount;
    } else { // ADJUSTMENT (Direct overwrite or custom)
        $new_balance = $previous_balance + $total_amount;
    }

    // Start DB Transaction for atomicity
    $conn->begin_transaction();

    try {
        // Update user balance
        $update_stmt = $conn->prepare("UPDATE users SET balance = ? WHERE id = ?");
        $update_stmt->bind_param("di", $new_balance, $citizen_id);
        $update_stmt->execute();
        $update_stmt->close();

        // Insert Audit Log
        $log_stmt = $conn->prepare("
            INSERT INTO treasury_logs (citizen_id, officer_id, transaction_type, fee_category, amount, previous_balance, new_balance, remarks) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $log_stmt->bind_param("iissddds", $citizen_id, $officer_id, $transaction_type, $fee_category, $total_amount, $previous_balance, $new_balance, $remarks);
        $log_stmt->execute();
        $log_stmt->close();

        $conn->commit();
        $_SESSION['success'] = "Transaction processed successfully! New Balance: ₱" . number_format($new_balance, 2);

    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error'] = "Transaction failed: " . $e->getMessage();
    }

    header("Location: ../update_balance.php?search_id=" . $citizen_id);
    exit();
}
?>