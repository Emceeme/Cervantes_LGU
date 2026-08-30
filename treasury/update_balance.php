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
    logSecurityEvent('unauthorized_access', $_SESSION['id'] ?? null, ['endpoint' => 'update_balance']);
    header("Location: /login.php?unauthorized=1");
    exit();
}

// Generate CSRF token
$csrf_token = generateCsrfToken();

$selected_user = null;

// Handle searching for a specific citizen (Removed strict role restriction to prevent lookup failures)
if (isset($_GET['search_id']) && !empty($_GET['search_id'])) {
    $search_id = intval($_GET['search_id']);
    $user_stmt = $conn->prepare("SELECT id, first_name, last_name, email, username, balance, role FROM users WHERE id = ?");
    $user_stmt->bind_param("i", $search_id);
    $user_stmt->execute();
    $selected_user = $user_stmt->get_result()->fetch_assoc();
    $user_stmt->close();
}

// Fetch list of citizens/public users for live search dropdown
$citizens_stmt = $conn->prepare("
    SELECT id, first_name, last_name, username, role 
    FROM users 
    WHERE role = 'CITIZEN' 
      AND department = 'Public' 
    ORDER BY last_name ASC
");
$citizens_stmt->execute();
$citizens_result = $citizens_stmt->get_result();
if ($citizens_result) {
    while ($row = $citizens_result->fetch_assoc()) {
        $citizens_list[] = $row;
    }
}
$citizens_stmt->close();

// Fetch recent 10 assessment logs
$log_query = "SELECT l.*, 
                     c.first_name AS citizen_fn, c.last_name AS citizen_ln, 
                     o.first_name AS officer_fn, o.last_name AS officer_ln 
              FROM treasury_logs l 
              LEFT JOIN users c ON l.citizen_id = c.id 
              LEFT JOIN users o ON l.officer_id = o.id 
              ORDER BY l.id DESC LIMIT 10";
$recent_logs = $conn->query($log_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Treasury - Manual Assessment & Balance Update</title>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="styles.css">

<style>
.grid-container {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 25px;
}

@media (max-width: 992px) {
    .grid-container { grid-template-columns: 1fr; }
}

.form-group {
    margin-bottom: 16px;
    position: relative;
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
}

.form-control:focus { border-color: #38bdf8; }

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
}

.btn-submit:hover { background: #1d4ed8; }

.log-item {
    background: rgba(15, 23, 42, 0.4);
    border: 1px solid rgba(255, 255, 255, 0.05);
    border-radius: 10px;
    padding: 12px 15px;
    margin-bottom: 10px;
}

.type-badge {
    font-size: 0.75rem;
    padding: 3px 8px;
    border-radius: 4px;
    font-weight: 600;
    text-transform: uppercase;
}

.badge-ASSESSMENT { background: rgba(239, 68, 68, 0.2); color: #f87171; }
.badge-PAYMENT { background: rgba(34, 197, 94, 0.2); color: #4ade80; }
.badge-ADJUSTMENT { background: rgba(234, 179, 8, 0.2); color: #facc15; }

.search-wrapper { position: relative; }

.suggestions-box {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: #0f172a;
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 0 0 10px 10px;
    max-height: 220px;
    overflow-y: auto;
    z-index: 1000;
    display: none;
}

.suggestion-item {
    padding: 10px 14px;
    cursor: pointer;
    color: #cbd5e1;
    font-size: 0.9rem;
    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
    display: flex;
    justify-content: space-between;
}

.suggestion-item:hover { background: rgba(56, 189, 248, 0.15); color: #38bdf8; }
</style>
</head>

<body>

<div class="bg-blur blur1"></div>
<div class="bg-blur blur2"></div>

<div class="container">

    <!-- SIDEBAR -->
    <aside class="sidebar">
        <div class="logo"><i class="fas fa-landmark"></i> Treasury</div>
        <a href="dashboard.php"><i class="fas fa-chart-line"></i> Dashboard</a>
        <a href="messages.php"><i class="fas fa-envelope"></i> Messages</a>
        <a href="update_balance.php" class="active"><i class="fas fa-wallet"></i> Update Balance</a>
        <a href="manage_employees.php"><i class="fas fa-user-plus"></i> Create Employee</a>
        <a href="create_public_user.php"><i class="fas fa-users"></i> Create Public User</a>
        <a href="../logout.php" style="margin-top: auto; color: #f87171;"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </aside>

    <main class="main-content">

        <div class="top-bar">
            <h2>Manual Assessment & Balance Management</h2>
            <p class="muted">Assess fees, record payments, and manage taxpayer accounts.</p>
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

        <div class="grid-container">

            <!-- LEFT PANEL: FORM -->
            <section class="card">
                <h3><i class="fas fa-calculator" style="color: #38bdf8;"></i> Issue Assessment / Payment</h3>
                <p class="muted" style="margin-bottom: 20px;">Search and select a citizen to modify their balance.</p>

                <form id="searchForm" method="GET" action="update_balance.php" style="margin-bottom: 20px;">
                    <input type="hidden" name="search_id" id="search_id_input" value="<?= $selected_user['id'] ?? '' ?>">
                    
                    <div class="form-group search-wrapper">
                        <label><i class="fas fa-search"></i> Select Taxpayer / Citizen:</label>
                        <input type="text" 
                               id="citizen_search_input" 
                               class="form-control" 
                               placeholder="Type name or @username..." 
                               value="<?= $selected_user ? htmlspecialchars($selected_user['first_name'] . ' ' . $selected_user['last_name'] . ' (@' . $selected_user['username'] . ')') : '' ?>" 
                               autocomplete="off">
                        <div id="suggestions_box" class="suggestions-box"></div>
                    </div>
                </form>

                <?php if ($selected_user): ?>
                    <!-- CONFIRMATION BADGE OF TARGET USER -->
                    <div style="background: rgba(56, 189, 248, 0.1); padding: 15px; border-radius: 10px; margin-bottom: 20px; border: 1px solid rgba(56, 189, 248, 0.3);">
                        <p style="font-size: 0.85rem; color: #94a3b8;">Target Taxpayer (User ID #<?= $selected_user['id'] ?>):</p>
                        <p style="font-size: 1.1rem; font-weight: 600; color: #f8fafc; margin-top: 2px;">
                            <?= htmlspecialchars($selected_user['first_name'] . ' ' . $selected_user['last_name']) ?>
                        </p>
                        <p style="font-size: 0.95rem; margin-top: 5px;">Current Balance: <strong style="color: #38bdf8;">₱ <?= number_format($selected_user['balance'] ?? 0, 2) ?></strong></p>
                    </div>

                    <form action="handler/post_update_balance.php" method="POST">
                        <!-- CSRF Protection -->
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                        <!-- CRITICAL: Passes Selected Citizen ID, NOT Officer ID -->
                        <input type="hidden" name="citizen_id" value="<?= $selected_user['id'] ?>">

                        <div class="form-group">
                            <label>Transaction Type:</label>
                            <select name="transaction_type" class="form-control" required>
                                <option value="ASSESSMENT">Assessment (Add Charge to Balance)</option>
                                <option value="PAYMENT">Payment Received (Deduct from Balance)</option>
                                <option value="ADJUSTMENT">Manual Correction / Adjustment</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Fee Category:</label>
                            <select name="fee_category" class="form-control" required>
                                <option value="Real Property Tax">Real Property Tax</option>
                                <option value="Business Permit Fee">Business Permit Fee</option>
                                <option value="Market Stall Fee">Market Stall Fee</option>
                                <option value="Regulatory Clearance">Regulatory Clearance</option>
                                <option value="Miscellaneous Charge">Miscellaneous Charge</option>
                            </select>
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                            <div class="form-group">
                                <label>Base Amount (₱):</label>
                                <input type="number" step="0.01" name="base_amount" class="form-control" placeholder="0.00" required>
                            </div>
                            <div class="form-group">
                                <label>Penalty / Surcharge (₱):</label>
                                <input type="number" step="0.01" name="penalty" class="form-control" placeholder="0.00" value="0.00">
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Remarks / Note:</label>
                            <textarea name="remarks" class="form-control" rows="3" placeholder="State reason or official receipt #..." required></textarea>
                        </div>

                        <button type="submit" class="btn-submit">
                            <i class="fas fa-save"></i> Process Transaction
                        </button>
                    </form>
                <?php else: ?>
                    <p class="muted" style="text-align: center; padding: 30px 0;">
                        <i class="fas fa-arrow-up"></i> Search and click a citizen above to load their form.
                    </p>
                <?php endif; ?>

            </section>

            <!-- RIGHT PANEL: AUDIT LOG -->
            <section class="card">
                <h3><i class="fas fa-history" style="color: #a855f7;"></i> Recent Audit Logs</h3>
                <p class="muted" style="margin-bottom: 20px;">Log of balance updates processed by treasury staff.</p>

                <?php if ($recent_logs && $recent_logs->num_rows > 0): ?>
                    <?php while ($log = $recent_logs->fetch_assoc()): ?>
                        <div class="log-item">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                                <span class="type-badge badge-<?= $log['transaction_type'] ?>">
                                    <?= $log['transaction_type'] ?>
                                </span>
                                <small class="muted"><?= date('M d, Y h:i A', strtotime($log['created_at'])) ?></small>
                            </div>
                            
                            <p style="font-weight: 600; font-size: 0.95rem; color: #f8fafc;">
                                ₱ <?= number_format($log['amount'], 2) ?> — <?= htmlspecialchars($log['fee_category']) ?>
                            </p>

                            <!-- Clearly distinguishes Citizen from Officer -->
                            <p style="font-size: 0.85rem; color: #cbd5e1; margin-top: 4px;">
                                <strong>Taxpayer:</strong> <?= htmlspecialchars(($log['citizen_fn'] ?? 'N/A') . ' ' . ($log['citizen_ln'] ?? '')) ?>
                            </p>

                            <p style="font-size: 0.8rem; color: #94a3b8; margin-top: 2px;">
                                <em>"<?= htmlspecialchars($log['remarks']) ?>"</em>
                            </p>

                            <div style="display: flex; justify-content: space-between; font-size: 0.75rem; color: #64748b; margin-top: 8px; border-top: 1px solid rgba(255,255,255,0.05); padding-top: 6px;">
                                <span>Processed By Officer: <?= htmlspecialchars(($log['officer_fn'] ?? 'N/A') . ' ' . ($log['officer_ln'] ?? '')) ?></span>
                                <span>Balance: ₱<?= number_format($log['previous_balance'], 2) ?> ➔ ₱<?= number_format($log['new_balance'], 2) ?></span>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p class="muted" style="text-align: center; padding: 30px 0;">No assessment logs found.</p>
                <?php endif; ?>

            </section>

        </div>

    </main>

</div>

<script>
const citizensData = <?= json_encode($citizens_list); ?>;
const searchInput = document.getElementById('citizen_search_input');
const hiddenIdInput = document.getElementById('search_id_input');
const suggestionsBox = document.getElementById('suggestions_box');
const searchForm = document.getElementById('searchForm');

function renderSuggestions(filterText = '') {
    const query = filterText.toLowerCase().trim();
    suggestionsBox.innerHTML = '';

    if (!query) {
        suggestionsBox.style.display = 'none';
        return;
    }

    const matches = citizensData.filter(c => {
        const fullName = `${c.first_name} ${c.last_name}`.toLowerCase();
        const username = c.username.toLowerCase();
        return fullName.includes(query) || username.includes(query);
    });

    if (matches.length === 0) {
        const noResult = document.createElement('div');
        noResult.className = 'suggestion-item';
        noResult.style.color = '#64748b';
        noResult.innerHTML = '<span>No matching user found</span>';
        suggestionsBox.appendChild(noResult);
        suggestionsBox.style.display = 'block';
        return;
    }

    matches.forEach(citizen => {
        const item = document.createElement('div');
        item.className = 'suggestion-item';
        item.innerHTML = `
            <span><strong>${citizen.first_name} ${citizen.last_name}</strong></span>
            <span style="color: #64748b; font-size: 0.8rem;">@${citizen.username}</span>
        `;
        item.addEventListener('click', () => {
            selectCitizen(citizen);
        });
        suggestionsBox.appendChild(item);
    });

    suggestionsBox.style.display = 'block';
}

function selectCitizen(citizen) {
    searchInput.value = `${citizen.first_name} ${citizen.last_name} (@${citizen.username})`;
    hiddenIdInput.value = citizen.id;
    suggestionsBox.style.display = 'none';
    searchForm.submit();
}

searchInput.addEventListener('input', (e) => {
    renderSuggestions(e.target.value);
});

document.addEventListener('click', (e) => {
    if (!e.target.closest('.search-wrapper')) {
        suggestionsBox.style.display = 'none';
    }
});
</script>

</body>
</html>