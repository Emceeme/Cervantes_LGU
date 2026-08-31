<?php
session_start();
require_once '../config/security.php';
require_once '../config/db.php';
require_once '../config/department_settings.php';

// Set security headers
setSecurityHeaders();

// SECURITY GUARD: Restrict access to Super Admin only
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'SUPER_ADMIN') {
    logSecurityEvent('unauthorized_access', $_SESSION['id'] ?? null, ['endpoint' => 'admin/settings', 'role' => $_SESSION['role'] ?? 'none']);
    header('Location: /login.php?unauthorized=1');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $settings = [
        'department_name' => $_POST['department_name'],
        'contact_email' => $_POST['contact_email'],
        'contact_phone' => $_POST['contact_phone'],
        'maintenance_mode' => isset($_POST['maintenance_mode']),
        'backup_retention_days' => intval($_POST['backup_retention_days']),
        'log_retention_days' => intval($_POST['log_retention_days']),
        'max_login_attempts' => intval($_POST['max_login_attempts']),
        'session_timeout_minutes' => intval($_POST['session_timeout_minutes']),
        'password_min_length' => intval($_POST['password_min_length']),
        'require_2fa' => isset($_POST['require_2fa']),
        'system_announcement' => $_POST['system_announcement']
    ];
    
    if (updateDepartmentSettings('ADMIN', $settings)) {
        $success_message = "Settings updated successfully!";
    } else {
        $error_message = "Failed to update settings.";
    }
}

// Get current settings
$current_settings = getDepartmentSettings('ADMIN');
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>System Settings</title>
<link rel="stylesheet" href="../static_page/styles.css">
</head>
<body>

<div class="container">

    <!-- SIDEBAR -->
    <aside class="sidebar">
        <div class="logo">Admin <span>Portal</span></div>
        <nav class="sidebar-nav">
            <a href="dashboard.php">Dashboard</a>
            <a href="lgu_list.php">LGU List</a>
            <a href="settings.php" class="active">Settings</a>
            <a href="../logout.php">Logout</a>
        </nav>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="main-content">

        <div class="top-bar">
            <h2>System Settings</h2>
            <p>Configure system-wide settings and security parameters</p>
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
                        <label style="display: block; margin-bottom: 5px; color: #1e3a5f; font-weight: 500;">System Name *</label>
                        <input type="text" name="department_name" required value="<?php echo htmlspecialchars($current_settings['department_name'] ?? 'System Administration'); ?>" style="width: 100%; padding: 10px; border: 2px solid #e0f2fe; border-radius: 8px;">
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 5px; color: #1e3a5f; font-weight: 500;">Contact Email *</label>
                        <input type="email" name="contact_email" required value="<?php echo htmlspecialchars($current_settings['contact_email'] ?? ''); ?>" style="width: 100%; padding: 10px; border: 2px solid #e0f2fe; border-radius: 8px;">
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 5px; color: #1e3a5f; font-weight: 500;">Contact Phone *</label>
                        <input type="text" name="contact_phone" required value="<?php echo htmlspecialchars($current_settings['contact_phone'] ?? ''); ?>" style="width: 100%; padding: 10px; border: 2px solid #e0f2fe; border-radius: 8px;">
                    </div>
                </div>

                <h3 style="margin-bottom: 20px; color: #1e3a5f; margin-top: 30px;">System Status</h3>
                
                <div style="margin-bottom: 20px;">
                    <label style="display: flex; align-items: center; gap: 10px; color: #1e3a5f; font-weight: 500;">
                        <input type="checkbox" name="maintenance_mode" <?php echo ($current_settings['maintenance_mode'] ?? false) ? 'checked' : ''; ?>>
                        Enable Maintenance Mode
                    </label>
                    <small style="color: #64748b;">When enabled, only administrators can access the system</small>
                </div>

                <div style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 5px; color: #1e3a5f; font-weight: 500;">System Announcement</label>
                    <textarea name="system_announcement" rows="3" style="width: 100%; padding: 10px; border: 2px solid #e0f2fe; border-radius: 8px;"><?php echo htmlspecialchars($current_settings['system_announcement'] ?? ''); ?></textarea>
                    <small style="color: #64748b;">This message will be displayed to all users on login</small>
                </div>

                <h3 style="margin-bottom: 20px; color: #1e3a5f; margin-top: 30px;">Data Retention</h3>
                
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 20px;">
                    <div>
                        <label style="display: block; margin-bottom: 5px; color: #1e3a5f; font-weight: 500;">Backup Retention (days) *</label>
                        <input type="number" name="backup_retention_days" required min="7" max="365" value="<?php echo htmlspecialchars($current_settings['backup_retention_days'] ?? 30); ?>" style="width: 100%; padding: 10px; border: 2px solid #e0f2fe; border-radius: 8px;">
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 5px; color: #1e3a5f; font-weight: 500;">Log Retention (days) *</label>
                        <input type="number" name="log_retention_days" required min="7" max="365" value="<?php echo htmlspecialchars($current_settings['log_retention_days'] ?? 90); ?>" style="width: 100%; padding: 10px; border: 2px solid #e0f2fe; border-radius: 8px;">
                    </div>
                </div>

                <h3 style="margin-bottom: 20px; color: #1e3a5f; margin-top: 30px;">Security Settings</h3>
                
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 20px;">
                    <div>
                        <label style="display: block; margin-bottom: 5px; color: #1e3a5f; font-weight: 500;">Max Login Attempts *</label>
                        <input type="number" name="max_login_attempts" required min="3" max="10" value="<?php echo htmlspecialchars($current_settings['max_login_attempts'] ?? 5); ?>" style="width: 100%; padding: 10px; border: 2px solid #e0f2fe; border-radius: 8px;">
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 5px; color: #1e3a5f; font-weight: 500;">Session Timeout (minutes) *</label>
                        <input type="number" name="session_timeout_minutes" required min="15" max="120" value="<?php echo htmlspecialchars($current_settings['session_timeout_minutes'] ?? 30); ?>" style="width: 100%; padding: 10px; border: 2px solid #e0f2fe; border-radius: 8px;">
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 5px; color: #1e3a5f; font-weight: 500;">Password Min Length *</label>
                        <input type="number" name="password_min_length" required min="6" max="20" value="<?php echo htmlspecialchars($current_settings['password_min_length'] ?? 8); ?>" style="width: 100%; padding: 10px; border: 2px solid #e0f2fe; border-radius: 8px;">
                    </div>
                </div>

                <div style="margin-bottom: 20px;">
                    <label style="display: flex; align-items: center; gap: 10px; color: #1e3a5f; font-weight: 500;">
                        <input type="checkbox" name="require_2fa" <?php echo ($current_settings['require_2fa'] ?? false) ? 'checked' : ''; ?>>
                        Require Two-Factor Authentication (2FA)
                    </label>
                    <small style="color: #64748b;">When enabled, all users must set up 2FA</small>
                </div>

                <button type="submit" class="view-btn" style="background: #1e3a5f; color: white; padding: 12px 30px; border: none; border-radius: 8px; cursor: pointer; font-size: 1rem;">Save Settings</button>
            </form>
        </section>

    </main>
</div>

</body>
</html>
