<?php
require_once '../config/security.php';
include '../config/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set security headers
setSecurityHeaders();

// 🔒 SECURITY GUARD: Treasury Staff/Sub-Admin or Super Admin only
if (!isset($_SESSION['name']) || ($_SESSION['department'] !== 'Treasury' && $_SESSION['role'] !== 'SUPER_ADMIN')) {
    logSecurityEvent('unauthorized_access', $_SESSION['id'] ?? null, ['endpoint' => 'create_public_user']);
    header("Location: ../login.php");
    exit();
}

// Generate CSRF token
$csrf_token = generateCsrfToken();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Treasury - Register Public User</title>

<!-- Font Awesome 6 CDN -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<!-- Google Fonts -->
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<!-- Separate Treasury CSS -->
<link rel="stylesheet" href="styles.css">

<style>
.form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}

@media (max-width: 768px) {
    .form-grid {
        grid-template-columns: 1fr;
    }
}

.form-group {
    margin-bottom: 16px;
}

.form-group label {
    display: block;
    font-size: 0.85rem;
    color: #cbd5e1;
    margin-bottom: 6px;
    font-weight: 500;
}

.form-control {
    width: 100%;
    padding: 12px 14px;
    background: rgba(15, 23, 42, 0.6);
    border: 1px solid rgba(255, 255, 255, 0.12);
    border-radius: 10px;
    color: #f8fafc;
    font-size: 0.95rem;
    outline: none;
    transition: border-color 0.2s ease;
}

.form-control:focus {
    border-color: #38bdf8;
}

.btn-submit {
    width: 100%;
    padding: 14px;
    background: #2563eb;
    color: #fff;
    border: none;
    border-radius: 10px;
    font-weight: 600;
    cursor: pointer;
    font-size: 0.95rem;
    margin-top: 10px;
    transition: background 0.2s ease;
}

.btn-submit:hover {
    background: #1d4ed8;
}
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

        <a href="dashboard.php">
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
        <a href="create_public_user.php" class="active">
            <i class="fas fa-users"></i> Create Public User
        </a>
        <a href="../logout.php" style="margin-top: auto; color: #f87171;">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="main-content">

        <div class="top-bar">
            <h2>Taxpayer & Public User Registration</h2>
            <p class="muted">Register new walk-in citizens or business owners into the Treasury database.</p>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div style="background: rgba(34, 197, 94, 0.2); border: 1px solid #22c55e; color: #4ade80; padding: 12px 16px; border-radius: 10px; margin-bottom: 20px;">
                <i class="fas fa-check-circle"></i> <?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div style="background: rgba(239, 68, 68, 0.2); border: 1px solid #ef4444; color: #f87171; padding: 12px 16px; border-radius: 10px; margin-bottom: 20px;">
                <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <section class="card" style="max-width: 700px;">
            <h3><i class="fas fa-user-plus" style="color: #38bdf8;"></i> Citizen Profile Registration</h3>
            <p class="muted" style="margin-bottom: 25px;">Once registered, this user can be selected in the <strong>Update Balance</strong> page for fee assessments.</p>

            <form action="handler/post_create_public_user.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                
                <div class="form-grid">
                    <div class="form-group">
                        <label>First Name:</label>
                        <input type="text" name="first_name" class="form-control" placeholder="e.g. Juan" required>
                    </div>

                    <div class="form-group">
                        <label>Last Name:</label>
                        <input type="text" name="last_name" class="form-control" placeholder="e.g. Dela Cruz" required>
                    </div>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label>Username / Tax Identification Account:</label>
                        <input type="text" name="username" class="form-control" placeholder="e.g. jdelacruz" required>
                    </div>

                    <div class="form-group">
                        <label>Email Address:</label>
                        <input type="email" name="email" class="form-control" placeholder="e.g. juan@example.com" required>
                    </div>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label>Password:</label>
                        <input type="password" name="password" class="form-control" placeholder="Temporary password" required>
                    </div>

                    <div class="form-group">
                        <label>Initial Opening Balance (₱):</label>
                        <input type="number" step="0.01" name="initial_balance" class="form-control" placeholder="0.00" value="0.00">
                    </div>
                </div>

                <button type="submit" class="btn-submit">
                    <i class="fas fa-user-check"></i> Register Citizen
                </button>
            </form>
        </section>

    </main>

</div>

</body>
</html>