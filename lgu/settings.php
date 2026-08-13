<?php
session_start();
require_once '../config/security.php';
require_once '../config/db.php';
require_once '../config/department_settings.php';

// Set security headers
setSecurityHeaders();

// SECURITY GUARD: Restrict access to Mayor's Office, LGU departments & Super Admins only
$department = html_entity_decode($_SESSION['department'] ?? '', ENT_QUOTES);
if (!isset($_SESSION['role']) || ($department !== "Mayor's Office" && $department !== 'Mayor Office' && $department !== 'LGU' && $_SESSION['role'] !== 'SUPER_ADMIN')) {
    logSecurityEvent('unauthorized_access', $_SESSION['id'] ?? null, ['endpoint' => 'lgu/settings', 'department' => $department]);
    http_response_code(403);
    die("Access Denied: You do not have permission to access department settings.");
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $settings = [
        'department_name' => $_POST['department_name'],
        'contact_email' => $_POST['contact_email'],
        'contact_phone' => $_POST['contact_phone'],
        'office_hours' => $_POST['office_hours'],
        'max_file_size_mb' => intval($_POST['max_file_size_mb']),
        'allowed_file_types' => explode(',', $_POST['allowed_file_types']),
        'auto_approve_news' => isset($_POST['auto_approve_news']),
        'require_admin_approval' => isset($_POST['require_admin_approval']),
        'scholarship_deadline_days' => intval($_POST['scholarship_deadline_days']),
        'procurement_notice_days' => intval($_POST['procurement_notice_days'])
    ];
    
    if (updateDepartmentSettings('LGU', $settings)) {
        $success_message = "Settings updated successfully!";
    } else {
        $error_message = "Failed to update settings.";
    }
}

// Get current settings
$current_settings = getDepartmentSettings('LGU');
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>LGU Settings</title>
<link rel="stylesheet" href="procurement.css">
</head>
<body>

<div class="container">

    <!-- SIDEBAR -->
    <aside class="sidebar">
        <div class="logo">LGU <span>Portal</span></div>
        <nav class="sidebar-nav">
            <a href="dashboard.php">Dashboard</a>
            <a href="applicants.php">Applicants</a>
            <a href="procurement.php">Procurement</a>
            <a href="scholarship_applications.php">Scholarship</a>
            <a href="newsfeed.php">News Feed</a>
            <a href="settings.php" class="active">Settings</a>
            <a href="../logout.php">Logout</a>
        </nav>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="main-content">

        <div class="top-bar">
            <h2>Department Settings</h2>
            <p>Configure LGU department settings and preferences</p>
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
                        <input type="text" name="department_name" required value="<?php echo htmlspecialchars($current_settings['department_name'] ?? "Mayor's Office"); ?>" style="width: 100%; padding: 10px; border: 2px solid #e0f2fe; border-radius: 8px;">
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

                <h3 style="margin-bottom: 20px; color: #1e3a5f; margin-top: 30px;">File Upload Settings</h3>
                
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 20px;">
                    <div>
                        <label style="display: block; margin-bottom: 5px; color: #1e3a5f; font-weight: 500;">Max File Size (MB) *</label>
                        <input type="number" name="max_file_size_mb" required min="1" max="50" value="<?php echo htmlspecialchars($current_settings['max_file_size_mb'] ?? 5); ?>" style="width: 100%; padding: 10px; border: 2px solid #e0f2fe; border-radius: 8px;">
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 5px; color: #1e3a5f; font-weight: 500;">Allowed File Types (comma-separated) *</label>
                        <input type="text" name="allowed_file_types" required value="<?php echo htmlspecialchars(implode(',', $current_settings['allowed_file_types'] ?? ['pdf', 'doc', 'docx', 'jpg', 'png'])); ?>" style="width: 100%; padding: 10px; border: 2px solid #e0f2fe; border-radius: 8px;">
                        <small style="color: #64748b;">Example: pdf,doc,docx,jpg,png</small>
                    </div>
                </div>

                <h3 style="margin-bottom: 20px; color: #1e3a5f; margin-top: 30px;">Approval Settings</h3>
                
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 20px;">
                    <div>
                        <label style="display: block; margin-bottom: 5px; color: #1e3a5f; font-weight: 500;">Scholarship Deadline (days) *</label>
                        <input type="number" name="scholarship_deadline_days" required min="1" max="365" value="<?php echo htmlspecialchars($current_settings['scholarship_deadline_days'] ?? 30); ?>" style="width: 100%; padding: 10px; border: 2px solid #e0f2fe; border-radius: 8px;">
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 5px; color: #1e3a5f; font-weight: 500;">Procurement Notice Period (days) *</label>
                        <input type="number" name="procurement_notice_days" required min="1" max="30" value="<?php echo htmlspecialchars($current_settings['procurement_notice_days'] ?? 7); ?>" style="width: 100%; padding: 10px; border: 2px solid #e0f2fe; border-radius: 8px;">
                    </div>
                </div>

                <div style="margin-bottom: 20px;">
                    <label style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px; color: #1e3a5f; font-weight: 500;">
                        <input type="checkbox" name="auto_approve_news" <?php echo ($current_settings['auto_approve_news'] ?? false) ? 'checked' : ''; ?>>
                        Auto-approve news posts
                    </label>
                    <label style="display: flex; align-items: center; gap: 10px; color: #1e3a5f; font-weight: 500;">
                        <input type="checkbox" name="require_admin_approval" <?php echo ($current_settings['require_admin_approval'] ?? true) ? 'checked' : ''; ?>>
                        Require admin approval for all posts
                    </label>
                </div>

                <button type="submit" class="view-btn" style="background: #1e3a5f; color: white; padding: 12px 30px; border: none; border-radius: 8px; cursor: pointer; font-size: 1rem;">Save Settings</button>
            </form>
        </section>

    </main>
</div>

</body>
</html>
