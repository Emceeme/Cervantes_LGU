<?php
session_start();
require_once '../config/security.php';
require_once '../config/db.php';
require_once '../config/department_settings.php';

// Set security headers
setSecurityHeaders();

// SECURITY GUARD: Restrict access to Treasury department only
$department = html_entity_decode($_SESSION['department'] ?? '', ENT_QUOTES);
if (!isset($_SESSION['role']) || $department !== 'Treasury') {
    logSecurityEvent('unauthorized_access', $_SESSION['id'] ?? null, ['endpoint' => 'treasury/settings', 'department' => $department]);
    http_response_code(403);
    die("Access Denied: You do not have permission to access Treasury settings.");
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $settings = [
        'department_name' => $_POST['department_name'],
        'contact_email' => $_POST['contact_email'],
        'contact_phone' => $_POST['contact_phone'],
        'office_hours' => $_POST['office_hours'],
        'min_balance_threshold' => floatval($_POST['min_balance_threshold']),
        'max_balance_threshold' => floatval($_POST['max_balance_threshold']),
        'auto_approve_transactions' => isset($_POST['auto_approve_transactions']),
        'transaction_timeout_minutes' => intval($_POST['transaction_timeout_minutes']),
        'require_receipt_upload' => isset($_POST['require_receipt_upload']),
        'notification_email_enabled' => isset($_POST['notification_email_enabled'])
    ];
    
    if (updateDepartmentSettings('Treasury', $settings)) {
        $success_message = "Settings updated successfully!";
    } else {
        $error_message = "Failed to update settings.";
    }
}

// Get current settings
$current_settings = getDepartmentSettings('Treasury');
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Treasury Settings</title>
<link rel="stylesheet" href="styles.css">
</head>
<body>

<div class="container">

    <!-- SIDEBAR -->
    <aside class="sidebar">
        <div class="logo">Treasury <span>Portal</span></div>
        <nav class="sidebar-nav">
            <a href="dashboard.php">Dashboard</a>
            <a href="manage_employees.php">Employees</a>
            <a href="messages.php">Messages</a>
            <a href="settings.php" class="active">Settings</a>
            <a href="../logout.php">Logout</a>
        </nav>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="main-content">

        <div class="top-bar">
            <h2>Treasury Settings</h2>
            <p>Configure Treasury department settings and transaction parameters</p>
        </div>

        <?php if (isset($success_message)): ?>
        <div style="background: #22c55e; color: white; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
            <?php echo $success_message; ?>
        </div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
        <div style="background: #ef4444; color: white; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
            <?php echo $error_message; ?>
        </div>
        <?php endif; ?>

        <section class="card">
            <form method="POST" action="">
                <h3 style="margin-bottom: 20px; color: #1e3a5f;">General Information</h3>
                
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 20px;">
                    <div>
                        <label style="display: block; margin-bottom: 5px; color: #1e3a5f; font-weight: 500;">Department Name *</label>
                        <input type="text" name="department_name" required value="<?php echo htmlspecialchars($current_settings['department_name'] ?? 'Municipal Treasury'); ?>" style="width: 100%; padding: 10px; border: 2px solid #e0f2fe; border-radius: 8px;">
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 5px; color: #1e3a5f; font-weight: 500;">Contact Email *</label>
                        <input type="email" name="contact_email" required value="<?php echo htmlspecialchars($current_settings['contact_email'] ?? ''); ?>" style="width: 100%; padding: 10px; border: 2px solid #e0f2fe; border-radius: 8px;">
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 5px; color: #1e3a5f; font-weight: 500;">Contact Phone *</label>
                        <input type="text" name="contact_phone" required value="<?php echo htmlspecialchars($current_settings['contact_phone'] ?? ''); ?>" style="width: 100%; padding: 10px; border: 2px solid #e0f2fe; border-radius: 8px;">
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 5px; color: #1e3a5f; font-weight: 500;">Office Hours *</label>
                        <input type="text" name="office_hours" required value="<?php echo htmlspecialchars($current_settings['office_hours'] ?? '8:00 AM - 5:00 PM'); ?>" style="width: 100%; padding: 10px; border: 2px solid #e0f2fe; border-radius: 8px;">
                    </div>
                </div>

                <h3 style="margin-bottom: 20px; color: #1e3a5f; margin-top: 30px;">Balance Thresholds</h3>
                
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 20px;">
                    <div>
                        <label style="display: block; margin-bottom: 5px; color: #1e3a5f; font-weight: 500;">Minimum Balance Threshold (₱) *</label>
                        <input type="number" name="min_balance_threshold" required min="0" step="100" value="<?php echo htmlspecialchars($current_settings['min_balance_threshold'] ?? 100); ?>" style="width: 100%; padding: 10px; border: 2px solid #e0f2fe; border-radius: 8px;">
                        <small style="color: #64748b;">Alert when balance falls below this amount</small>
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 5px; color: #1e3a5f; font-weight: 500;">Maximum Balance Threshold (₱) *</label>
                        <input type="number" name="max_balance_threshold" required min="0" step="1000" value="<?php echo htmlspecialchars($current_settings['max_balance_threshold'] ?? 1000000); ?>" style="width: 100%; padding: 10px; border: 2px solid #e0f2fe; border-radius: 8px;">
                        <small style="color: #64748b;">Alert when balance exceeds this amount</small>
                    </div>
                </div>

                <h3 style="margin-bottom: 20px; color: #1e3a5f; margin-top: 30px;">Transaction Settings</h3>
                
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 20px;">
                    <div>
                        <label style="display: block; margin-bottom: 5px; color: #1e3a5f; font-weight: 500;">Transaction Timeout (minutes) *</label>
                        <input type="number" name="transaction_timeout_minutes" required min="5" max="120" value="<?php echo htmlspecialchars($current_settings['transaction_timeout_minutes'] ?? 30); ?>" style="width: 100%; padding: 10px; border: 2px solid #e0f2fe; border-radius: 8px;">
                    </div>
                </div>

                <div style="margin-bottom: 20px;">
                    <label style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px; color: #1e3a5f; font-weight: 500;">
                        <input type="checkbox" name="auto_approve_transactions" <?php echo ($current_settings['auto_approve_transactions'] ?? false) ? 'checked' : ''; ?>>
                        Auto-approve transactions below threshold
                    </label>
                    <label style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px; color: #1e3a5f; font-weight: 500;">
                        <input type="checkbox" name="require_receipt_upload" <?php echo ($current_settings['require_receipt_upload'] ?? true) ? 'checked' : ''; ?>>
                        Require receipt upload for all transactions
                    </label>
                    <label style="display: flex; align-items: center; gap: 10px; color: #1e3a5f; font-weight: 500;">
                        <input type="checkbox" name="notification_email_enabled" <?php echo ($current_settings['notification_email_enabled'] ?? true) ? 'checked' : ''; ?>>
                        Enable email notifications for transactions
                    </label>
                </div>

                <button type="submit" class="view-btn" style="background: #1e3a5f; color: white; padding: 12px 30px; border: none; border-radius: 8px; cursor: pointer; font-size: 1rem;">Save Settings</button>
            </form>
        </section>

    </main>
</div>

</body>
</html>
