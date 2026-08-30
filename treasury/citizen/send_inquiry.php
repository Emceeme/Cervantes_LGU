<?php
session_start();
require_once '../../config/security.php';
include '../../config/db.php';

// Set security headers
setSecurityHeaders();

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    logSecurityEvent('unauthorized_access', $_SESSION['id'] ?? null, ['endpoint' => 'send_inquiry']);
    header("Location: /login.php?unauthorized=1");
    exit();
}

$user_id = $_SESSION['user_id'];

// Generate CSRF token
$csrf_token = generateCsrfToken();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Submit Treasury Inquiry</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
    body {
        font-family: 'Poppins', sans-serif;
        background-color: #0f172a;
        color: #f8fafc;
        margin: 0;
        padding: 40px 20px;
        display: flex;
        justify-content: center;
    }
    .form-card {
        background: rgba(30, 41, 59, 0.7);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 16px;
        padding: 30px;
        width: 100%;
        max-width: 600px;
        box-shadow: 0 20px 40px rgba(0,0,0,0.4);
    }
    .form-group {
        margin-bottom: 20px;
    }
    label {
        display: block;
        margin-bottom: 8px;
        font-size: 0.9rem;
        color: #cbd5e1;
    }
    input, textarea, select {
        width: 100%;
        background: rgba(15, 23, 42, 0.8);
        border: 1px solid rgba(255, 255, 255, 0.15);
        border-radius: 10px;
        padding: 12px;
        color: #fff;
        font-family: inherit;
        box-sizing: border-box;
    }
    input:focus, textarea:focus {
        outline: none;
        border-color: #38bdf8;
    }
    .btn-submit {
        background: #38bdf8;
        color: #0f172a;
        font-weight: 600;
        border: none;
        padding: 12px 24px;
        border-radius: 10px;
        cursor: pointer;
        width: 100%;
        font-size: 1rem;
        transition: background 0.2s;
    }
    .btn-submit:hover {
        background: #0284c7;
        color: #fff;
    }
    .back-link {
        display: inline-block;
        margin-bottom: 20px;
        color: #38bdf8;
        text-decoration: none;
        font-size: 0.9rem;
    }
</style>
</head>
<body>

<div class="form-card">
    <a href="dashboard.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
    <h2><i class="fas fa-paper-plane" style="color: #38bdf8;"></i> Submit Inquiry to Treasury</h2>
    <p style="color: #94a3b8; font-size: 0.85rem; margin-bottom: 24px;">
        Have a question about your tax assessment, payments, or clearance? Send a message directly to the Treasury office.
    </p>

    <!-- Pointing to our shared handler inside treasury/handler/ -->
    <form action="../handler/post_reply_message.php" method="POST">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
        <!-- Default receiver_id set to 1 (or Treasury Admin ID) -->
        <input type="hidden" name="receiver_id" value="1">
        
        <div class="form-group">
            <label>Subject / Topic</label>
            <input type="text" name="subject" placeholder="e.g., Real Property Tax Assessment Clarification" required>
        </div>

        <div class="form-group">
            <label>Message Details</label>
            <textarea name="message" rows="5" placeholder="Provide details about your inquiry or dispute..." required></textarea>
        </div>

        <button type="submit" class="btn-submit">Submit Inquiry</button>
    </form>
</div>

</body>
</html>