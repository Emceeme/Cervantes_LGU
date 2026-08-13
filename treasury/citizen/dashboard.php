<?php
require_once '../../config/security.php';
include '../../config/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set security headers
setSecurityHeaders();

// 🔒 SECURITY GUARD: Only CITIZEN role allowed
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'CITIZEN') {
    logSecurityEvent('unauthorized_access', $_SESSION['id'] ?? null, ['endpoint' => 'citizen_dashboard']);
    header("Location: ../../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// 1. Fetch Citizen Account Info & Current Balance
$user_query = $conn->prepare("SELECT first_name, last_name, balance FROM users WHERE id = ?");
$user_query->bind_param("i", $user_id);
$user_query->execute();
$user_data = $user_query->get_result()->fetch_assoc();

$first_name = $user_data['first_name'] ?? 'Citizen';
$last_name = $user_data['last_name'] ?? '';
$balance = $user_data['balance'] ?? 0.00;

// 2. Unread Messages Counter
$unread_query = $conn->prepare("SELECT COUNT(*) AS unread_count FROM messages WHERE receiver_id = ? AND status = 'UNREAD'");
$unread_query->bind_param("i", $user_id);
$unread_query->execute();
$unread_result = $unread_query->get_result()->fetch_assoc();
$unread_count = $unread_result['unread_count'] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Citizen Dashboard</title>

<!-- Font Awesome 6 CDN -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<!-- Google Fonts -->
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: 'Poppins', sans-serif; background: #0f172a; color: #f8fafc; padding: 24px; }

.header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 28px; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 16px; }
.header h2 { font-size: 1.4rem; color: #f8fafc; display: flex; align-items: center; gap: 10px; }
.nav-links { display: flex; gap: 16px; align-items: center; }
.nav-link { color: #94a3b8; text-decoration: none; font-size: 0.9rem; font-weight: 500; transition: color 0.2s; position: relative; }
.nav-link:hover, .nav-link.active { color: #38bdf8; }

.badge-count { background: #ef4444; color: #fff; font-size: 0.65rem; border-radius: 10px; padding: 2px 6px; position: absolute; top: -6px; right: -10px; font-weight: 600; }

.dashboard-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin-bottom: 30px; }
.card { background: rgba(30, 41, 59, 0.7); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 14px; padding: 24px; display: flex; flex-direction: column; justify-content: space-between; position: relative; overflow: hidden; }

.card-title { font-size: 0.85rem; color: #94a3b8; text-transform: uppercase; font-weight: 600; letter-spacing: 0.5px; }
.card-value { font-size: 2.2rem; font-weight: 700; color: #f8fafc; margin: 12px 0 6px; }
.card-subtext { font-size: 0.8rem; color: #64748b; }

.balance-card { background: linear-gradient(135deg, rgba(14, 165, 233, 0.2), rgba(15, 23, 42, 0.8)); border: 1px solid rgba(56, 189, 248, 0.3); }
.balance-card .card-value { color: #38bdf8; }

.btn-action { display: inline-flex; align-items: center; gap: 8px; background: #38bdf8; color: #0f172a; border: none; padding: 10px 18px; border-radius: 8px; font-weight: 600; text-decoration: none; font-size: 0.875rem; margin-top: 12px; width: fit-content; transition: background 0.2s; }
.btn-action:hover { background: #0284c7; color: #fff; }
</style>
</head>
<body>

<div class="header">
    <h2><i class="fas fa-landmark" style="color: #38bdf8;"></i> Welcome, <?= htmlspecialchars($first_name . ' ' . $last_name) ?></h2>
    <div class="nav-links">
        <a href="dashboard.php" class="nav-link active"><i class="fas fa-chart-line"></i> Dashboard</a>
        <a href="messages.php" class="nav-link">
            <i class="fas fa-comments"></i> Messages
            <?php if ($unread_count > 0): ?>
                <span class="badge-count"><?= $unread_count ?></span>
            <?php endif; ?>
        </a>
        <a href="../../logout.php" class="nav-link" style="color: #f87171;"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
</div>

<div class="dashboard-cards">

    <!-- BALANCE DISPLAY CARD -->
    <div class="card balance-card">
        <div>
            <div class="card-title">Current Account Balance</div>
            <div class="card-value">₱<?= number_format($balance, 2) ?></div>
            <div class="card-subtext">Updated in real-time by Treasury Staff</div>
        </div>
        <a href="messages.php" class="btn-action" style="margin-top: 20px;">
            <i class="fas fa-circle-question"></i> Dispute or Inquire
        </a>
    </div>

    <!-- SUPPORT QUICK LINK CARD -->
    <div class="card">
        <div>
            <div class="card-title">Inquiry & Dispute Inbox</div>
            <div class="card-value" style="font-size: 1.5rem; margin-top: 16px;">
                <?= $unread_count ?> Unread Messages
            </div>
            <div class="card-subtext">Contact Treasury for balance adjustments or clarifications.</div>
        </div>
        <a href="messages.php" class="btn-action">
            <i class="fas fa-envelope"></i> Open Messages
        </a>
    </div>

</div>

</body>
</html>