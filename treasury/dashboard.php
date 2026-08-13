<?php
include '../config/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 🔒 SECURITY GUARD: Treasury Staff/Sub-Admin or Super Admin only
if (!isset($_SESSION['name']) || ($_SESSION['department'] !== 'Treasury' && $_SESSION['role'] !== 'SUPER_ADMIN')) {
    header("Location: ../login.php");
    exit();
}

// Safe Query Helpers to prevent Fatal Errors on MySQL queries
$total_staff = 0;
$res_staff = $conn->prepare("SELECT COUNT(*) as count FROM users WHERE department = 'Treasury' AND role = 'EMPLOYEE'");
if ($res_staff) { 
    $res_staff->execute();
    $total_staff = $res_staff->get_result()->fetch_assoc()['count'] ?? 0; 
    $res_staff->close();
}

$total_public_users = 0;
$res_users = $conn->prepare("SELECT COUNT(*) as count FROM users WHERE role = 'CITIZEN'");
if ($res_users) { 
    $res_users->execute();
    $total_public_users = $res_users->get_result()->fetch_assoc()['count'] ?? 0; 
    $res_users->close();
}

// 1. Total Revenue Collected (Actual Payments Received)
$total_collected = 0;
$res_collected = $conn->prepare("SELECT SUM(amount) as total FROM treasury_logs WHERE transaction_type = 'PAYMENT'");
if ($res_collected) { 
    $res_collected->execute();
    $total_collected = $res_collected->get_result()->fetch_assoc()['total'] ?? 0; 
    $res_collected->close();
}

// 2. Total Collectibles (Outstanding Citizen Receivables)
$total_receivables = 0;
$res_receivables = $conn->prepare("SELECT SUM(balance) as total FROM users WHERE role = 'CITIZEN'");
if ($res_receivables) { 
    $res_receivables->execute();
    $total_receivables = $res_receivables->get_result()->fetch_assoc()['total'] ?? 0; 
    $res_receivables->close();
}

// 3. Fetch Recent 5 Transactions from treasury_logs
$recent_transactions = false;
$recent_tx_query = "
    SELECT l.*, 
           c.first_name AS citizen_fn, c.last_name AS citizen_ln, 
           o.first_name AS officer_fn, o.last_name AS officer_ln 
    FROM treasury_logs l 
    LEFT JOIN users c ON l.citizen_id = c.id 
    LEFT JOIN users o ON l.officer_id = o.id 
    ORDER BY l.id DESC LIMIT 5
";
$recent_transactions = $conn->query($recent_tx_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Treasury - Dashboard</title>

<!-- Font Awesome 6 CDN -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<!-- Google Fonts -->
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<!-- Separate Treasury CSS -->
<link rel="stylesheet" href="styles.css">

<style>
.table-responsive {
    width: 100%;
    overflow-x: auto;
    margin-top: 15px;
}

.data-table {
    width: 100%;
    border-collapse: collapse;
    text-align: left;
    font-size: 0.9rem;
}

.data-table th {
    background: rgba(15, 23, 42, 0.6);
    color: #94a3b8;
    padding: 12px 15px;
    font-weight: 600;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.data-table td {
    padding: 12px 15px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
    color: #f8fafc;
}

.data-table tr:hover {
    background: rgba(255, 255, 255, 0.02);
}

.badge {
    font-size: 0.75rem;
    padding: 4px 8px;
    border-radius: 6px;
    font-weight: 600;
    text-transform: uppercase;
}

.badge-ASSESSMENT { background: rgba(239, 68, 68, 0.2); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.3); }
.badge-PAYMENT { background: rgba(34, 197, 94, 0.2); color: #4ade80; border: 1px solid rgba(34, 197, 94, 0.3); }
.badge-ADJUSTMENT { background: rgba(234, 179, 8, 0.2); color: #facc15; border: 1px solid rgba(234, 179, 8, 0.3); }
</style>
</head>

<body>

<div class="bg-blur blur1"></div>
<div class="bg-blur blur2"></div>

<div class="container">

    <!-- SIDEBAR -->
    <aside class="sidebar">
        <div class="logo">
            <i class="fas fa-landmark"></i> Treasury
        </div>

        <a href="dashboard.php" class="active">
            <i class="fas fa-chart-line"></i> Dashboard
        </a>
        <a href="messages.php">
            <i class="fas fa-envelope"></i> Messages
        </a>
        <a href="update_balance.php">
            <i class="fas fa-wallet"></i> Update Balance
        </a>
        <a href="manage_employees.php">
            <i class="fas fa-user-plus"></i> Create Employee
        </a>
        <a href="create_public_user.php">
            <i class="fas fa-users"></i> Create Public User
        </a>
        <a href="../logout.php" style="margin-top: auto; color: #f87171;">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="main-content">

        <!-- TOP BAR -->
        <div class="top-bar">
            <h2>Treasury Overview</h2>
            <p class="muted">Welcome back, <?= htmlspecialchars($_SESSION['name'] ?? 'Treasury Officer'); ?> 👋</p>
        </div>

        <!-- QUICK SUMMARY CARDS -->
        <div class="stats-grid">
            
            <div class="card">
                <h3><i class="fas fa-user-tie" style="color: #38bdf8;"></i> Treasury Staff</h3>
                <div class="stat-number"><?= $total_staff ?></div>
                <p class="muted">Active personnel in Treasury</p>
            </div>

            <div class="card">
                <h3><i class="fas fa-users" style="color: #a855f7;"></i> Public Users</h3>
                <div class="stat-number"><?= $total_public_users ?></div>
                <p class="muted">Registered taxpayers & citizens</p>
            </div>

            <div class="card">
                <h3><i class="fas fa-coins" style="color: #34d399;"></i> Total Revenue Collected</h3>
                <div class="stat-number" style="color: #34d399;">₱ <?= number_format($total_collected, 2) ?></div>
                <p class="muted">Payments processed to date</p>
            </div>

            <div class="card">
                <h3><i class="fas fa-file-invoice-dollar" style="color: #f87171;"></i> Total Collectibles</h3>
                <div class="stat-number" style="color: #f87171;">₱ <?= number_format($total_receivables, 2) ?></div>
                <p class="muted">Outstanding citizen balances</p>
            </div>

        </div>

        <!-- RECENT TRANSACTIONS AUDIT TABLE -->
        <section class="card" style="margin-bottom: 25px;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h3><i class="fas fa-history" style="color: #38bdf8;"></i> Recent Assessment & Payment Activity</h3>
                    <p class="muted">Real-time log of the 5 most recent treasury updates.</p>
                </div>
                <a href="update_balance.php" style="color: #38bdf8; text-decoration: none; font-size: 0.85rem; font-weight: 500;">
                    View All <i class="fas fa-arrow-right"></i>
                </a>
            </div>

            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>Taxpayer</th>
                            <th>Fee Category</th>
                            <th>Amount</th>
                            <th>Officer</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($recent_transactions && $recent_transactions->num_rows > 0): ?>
                            <?php while ($tx = $recent_transactions->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <span class="badge badge-<?= htmlspecialchars($tx['transaction_type']) ?>">
                                            <?= htmlspecialchars($tx['transaction_type']) ?>
                                        </span>
                                    </td>
                                    <td><strong><?= htmlspecialchars($tx['citizen_fn'] . ' ' . $tx['citizen_ln']) ?></strong></td>
                                    <td><?= htmlspecialchars($tx['fee_category']) ?></td>
                                    <td style="font-weight: 600; color: <?= $tx['transaction_type'] === 'PAYMENT' ? '#4ade80' : '#f87171' ?>;">
                                        ₱ <?= number_format($tx['amount'], 2) ?>
                                    </td>
                                    <td style="color: #cbd5e1;"><?= htmlspecialchars($tx['officer_fn'] . ' ' . $tx['officer_ln']) ?></td>
                                    <td style="color: #94a3b8; font-size: 0.8rem;"><?= date('M d, Y h:i A', strtotime($tx['created_at'])) ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" style="text-align: center; color: #64748b; padding: 25px;">
                                    No transactions recorded yet.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- QUICK OPERATIONS -->
        <section class="card">
            <h3>Quick Operations</h3>
            <p class="muted">Choose an action below or navigate using the left sidebar menu:</p>

            <div class="actions-grid">
                <a href="messages.php" class="btn-primary">
                    <i class="fas fa-envelope"></i> Open Messages
                </a>
                <a href="update_balance.php" class="btn-primary">
                    <i class="fas fa-wallet"></i> Update Balance
                </a>
                <a href="manage_employees.php" class="btn-primary">
                    <i class="fas fa-user-plus"></i> Create Employee
                </a>
                <a href="create_public_user.php" class="btn-primary">
                    <i class="fas fa-users"></i> Create Public User
                </a>
            </div>
        </section>

    </main>

</div>

</body>
</html>