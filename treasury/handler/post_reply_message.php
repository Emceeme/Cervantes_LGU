<?php
session_start();
require_once '../../config/security.php';

// Stepping 2 directories up to reach /config/db.php
// (treasury/handler/ -> treasury/ -> root -> config/db.php)
include '../../config/db.php';

// Set security headers
setSecurityHeaders();

// Ensure user is authenticated
if (!isset($_SESSION['user_id'])) {
    logSecurityEvent('unauthorized_access', $_SESSION['id'] ?? null, ['endpoint' => 'post_reply_message']);
    die("Unauthorized access.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        logSecurityEvent('csrf_validation_failed', $_SESSION['id'] ?? null, ['endpoint' => 'post_reply_message']);
        $_SESSION['error'] = "Security validation failed. Please try again.";
        header("Location: ../messages.php");
        exit();
    }
    $sender_id = $_SESSION['user_id'];
    
    // Receiver ID (Can be NULL if sending generally to a department)
    $receiver_id = (!empty($_POST['receiver_id']) && intval($_POST['receiver_id']) > 0) 
        ? intval($_POST['receiver_id']) 
        : NULL;

    // Parent ID for threaded replies
    $parent_id = (!empty($_POST['parent_id']) && intval($_POST['parent_id']) > 0) 
        ? intval($_POST['parent_id']) 
        : NULL;

    $subject    = isset($_POST['subject']) ? trim($_POST['subject']) : 'Treasury Inquiry';
    $message    = isset($_POST['message']) ? trim($_POST['message']) : '';
    $department = isset($_POST['department']) ? trim($_POST['department']) : 'Treasury';

    if (!empty($message)) {
        $stmt = $conn->prepare("
            INSERT INTO messages (sender_id, receiver_id, parent_id, department, subject, message, status) 
            VALUES (?, ?, ?, ?, ?, ?, 'UNREAD')
        ");
        
        $stmt->bind_param("iiisss", $sender_id, $receiver_id, $parent_id, $department, $subject, $message);

        if ($stmt->execute()) {
            $_SESSION['success'] = "Message sent successfully!";
            
            // If starting a new thread, redirect to the newly created thread ID
            if ($parent_id === NULL) {
                $parent_id = $stmt->insert_id;
            }
        } else {
            $_SESSION['error'] = "Failed to send message: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $_SESSION['error'] = "Message body cannot be empty.";
    }

    // 🔀 Smart Redirection out of the /handler/ folder
    if (isset($_SESSION['role']) && ($_SESSION['role'] === 'CITIZEN' || $_SESSION['role'] === 'PUBLIC')) {
        // Step out of /handler/ into /treasury/citizen/
        $redirect = "../citizen/messages.php" . ($parent_id ? "?thread_id=" . $parent_id : "");
    } else {
        // Step out of /handler/ into /treasury/messages.php
        $redirect = "../messages.php" . ($parent_id ? "?thread_id=" . $parent_id : "");
    }

    header("Location: " . $redirect);
    exit();
}
?>