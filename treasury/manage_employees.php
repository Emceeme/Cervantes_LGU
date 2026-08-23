<?php
require_once '../config/security.php';
include '../config/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set security headers
setSecurityHeaders();

// 🔒 STRICT SECURITY GUARD: Sub-Admin or Super Admin ONLY (Standard EMPLOYEES are blocked)
if (
    !isset($_SESSION['name']) || 
    $_SESSION['department'] !== 'Treasury' || 
    ($_SESSION['role'] !== 'SUB_ADMIN' && $_SESSION['role'] !== 'SUPER_ADMIN')
) {
    logSecurityEvent('unauthorized_access', $_SESSION['id'] ?? null, ['endpoint' => 'manage_employees']);
    header("Location: /login.php?unauthorized=1");
    exit();
}

$user_role = $_SESSION['role'];

// Generate CSRF token
$csrf_token = generateCsrfToken();

// Search functionality
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$search_term = "%$search%";

// Fetch Treasury Employees safely
$employees = false;
$emp_query = "
    SELECT id, first_name, last_name, email, role, status, created_at 
    FROM users 
    WHERE department = 'Treasury' AND role IN ('EMPLOYEE', 'SUB_ADMIN')
    AND (first_name LIKE ? OR last_name LIKE ? OR email LIKE ?)
    ORDER BY id DESC
";

$stmt = $conn->prepare($emp_query);
if ($stmt) {
    $stmt->bind_param("sss", $search_term, $search_term, $search_term);
    $stmt->execute();
    $employees = $stmt->get_result();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Treasury - Manage Staff</title>

<!-- Font Awesome 6 CDN -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<!-- Google Fonts -->
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<!-- Treasury Stylesheet -->
<link rel="stylesheet" href="styles.css">

<style>
.grid-container {
    display: grid;
    grid-template-columns: 360px 1fr;
    gap: 25px;
}

@media (max-width: 992px) {
    .grid-container {
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
    padding: 10px 14px;
    background: rgba(15, 23, 42, 0.8);
    border: 1px solid rgba(255, 255, 255, 0.12);
    border-radius: 8px;
    color: #f8fafc;
    font-size: 0.9rem;
    outline: none;
    box-sizing: border-box;
}

.form-control:focus {
    border-color: #38bdf8;
}

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

.status-active {
    color: #4ade80;
    background: rgba(34, 197, 94, 0.15);
    padding: 3px 8px;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 600;
}

.status-inactive {
    color: #f87171;
    background: rgba(239, 68, 68, 0.15);
    padding: 3px 8px;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 600;
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
        <a href="manage_employees.php" class="active">
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

        <div class="top-bar">
            <h2>Manage Treasury Personnel</h2>
            <p class="muted">Register new Treasury staff accounts and view current active staff members.</p>
        </div>

        <!-- NOTIFICATION ALERTS -->
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

        <div class="grid-container">

            <!-- CREATE STAFF FORM -->
            <div class="card">
                <h3><i class="fas fa-user-plus" style="color: #38bdf8;"></i> Register Treasury Staff</h3>
                <p class="muted" style="margin-bottom: 20px;">Creates a staff/clerk account with access to process payments and handle tax inquiries.</p>

                <form action="handler/post_create_employee.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                    <div class="form-group">
                        <label>First Name</label>
                        <input type="text" name="first_name" class="form-control" placeholder="e.g. Juan" required>
                    </div>

                    <div class="form-group">
                        <label>Last Name</label>
                        <input type="text" name="last_name" class="form-control" placeholder="e.g. Dela Cruz" required>
                    </div>

                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" name="email" class="form-control" placeholder="staff@treasury.gov" required>
                    </div>

                    <div class="form-group">
                        <label>Assigned Role</label>
                        <input type="text" class="form-control" value="Treasury Staff / Clerk (EMPLOYEE)" disabled style="opacity: 0.7; cursor: not-allowed;">
                    </div>

                    <div class="form-group">
                        <label>Default Password</label>
                        <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                    </div>

                    <button type="submit" class="btn-primary" style="width: 100%; margin-top: 10px;">
                        <i class="fas fa-plus-circle"></i> Create Employee Account
                    </button>
                </form>
            </div>

            <!-- STAFF LIST TABLE -->
            <div class="card">
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; margin-bottom: 15px;">
                    <div>
                        <h3><i class="fas fa-users-cog" style="color: #a855f7;"></i> Treasury Roster</h3>
                        <p class="muted">Personnel assigned to the Treasury Department.</p>
                    </div>

                    <!-- SEARCH FORM -->
                    <form action="manage_employees.php" method="GET" style="display: flex; gap: 8px;">
                        <input type="text" name="search" class="form-control" placeholder="Search staff..." value="<?= htmlspecialchars($search) ?>" style="padding: 6px 12px; width: 180px;">
                        <button type="submit" class="btn-primary" style="padding: 6px 12px;">
                            <i class="fas fa-search"></i>
                        </button>
                    </form>
                </div>

                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Created</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($employees && $employees->num_rows > 0): ?>
                                <?php while ($emp = $employees->fetch_assoc()): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']) ?></strong></td>
                                        <td style="color: #cbd5e1;"><?= htmlspecialchars($emp['email']) ?></td>
                                        <td>
                                            <span style="font-size: 0.75rem; font-weight: 600; color: <?= $emp['role'] === 'SUB_ADMIN' ? '#a855f7' : '#38bdf8' ?>;">
                                                <?= htmlspecialchars($emp['role']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="status-<?= strtolower($emp['status'] ?? 'ACTIVE') ?>">
                                                <?= htmlspecialchars($emp['status'] ?? 'ACTIVE') ?>
                                            </span>
                                        </td>
                                        <td style="color: #94a3b8; font-size: 0.8rem;"><?= date('M d, Y', strtotime($emp['created_at'])) ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" style="text-align: center; color: #64748b; padding: 25px;">
                                        No Treasury personnel found.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

    </main>

</div>

</body>
</html>