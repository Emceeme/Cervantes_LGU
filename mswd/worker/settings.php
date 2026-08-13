<?php
session_start();
require_once '../../config/security.php';
require_once '../../config/db.php';
require_once '../../config/department_settings.php';

// Set security headers
setSecurityHeaders();

// SECURITY GUARD: Restrict access to MSWD department only
$department = html_entity_decode($_SESSION['department'] ?? '', ENT_QUOTES);
if (!isset($_SESSION['role']) || $department !== 'MSWD') {
    logSecurityEvent('unauthorized_access', $_SESSION['id'] ?? null, ['endpoint' => 'mswd/settings', 'department' => $department]);
    http_response_code(403);
    die("Access Denied: You do not have permission to access MSWD settings.");
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $settings = [
        'department_name' => $_POST['department_name'],
        'contact_email' => $_POST['contact_email'],
        'contact_phone' => $_POST['contact_phone'],
        'office_hours' => $_POST['office_hours'],
        'max_assistance_amount' => floatval($_POST['max_assistance_amount']),
        'min_assistance_amount' => floatval($_POST['min_assistance_amount']),
        'require_document_verification' => isset($_POST['require_document_verification']),
        'auto_approve_below_amount' => floatval($_POST['auto_approve_below_amount']),
        'review_deadline_days' => intval($_POST['review_deadline_days']),
        'max_monthly_applications' => intval($_POST['max_monthly_applications'])
    ];
    
    if (updateDepartmentSettings('MSWD', $settings)) {
        $success_message = "Settings updated successfully!";
    } else {
        $error_message = "Failed to update settings.";
    }
}

// Get current settings
$current_settings = getDepartmentSettings('MSWD');
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>MSWD Settings</title>
<link rel="stylesheet" href="../styles.css">
</head>
<body>

<div class="container">

    <!-- SIDEBAR -->
    <aside class="sidebar">
        <div class="logo">MSWD <span>Portal</span></div>
        <nav class="sidebar-nav">
            <a href="dashboard.php">Dashboard</a>
            <a href="review.php">Review Applications</a>
            <a href="settings.php" class="active">Settings</a>
            <a href="../../logout.php">Logout</a>
        </nav>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="main-content">

        <div class="top-bar">
            <h2>MSWD Settings</h2>
            <p>Configure MSWD department settings and assistance parameters</p>
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
                        <input type="text" name="department_name" required value="<?php echo htmlspecialchars($current_settings['department_name'] ?? 'Municipal Social Welfare and Development'); ?>" style="width: 100%; padding: 10px; border: 2px solid #e0f2fe; border-radius: 8px;">
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

                <h3 style="margin-bottom: 20px; color: #1e3a5f; margin-top: 30px;">Assistance Parameters</h3>
                
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 20px;">
                    <div>
                        <label style="display: block; margin-bottom: 5px; color: #1e3a5f; font-weight: 500;">Maximum Assistance Amount (₱) *</label>
                        <input type="number" name="max_assistance_amount" required min="100" step="100" value="<?php echo htmlspecialchars($current_settings['max_assistance_amount'] ?? 10000); ?>" style="width: 100%; padding: 10px; border: 2px solid #e0f2fe; border-radius: 8px;">
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 5px; color: #1e3a5f; font-weight: 500;">Minimum Assistance Amount (₱) *</label>
                        <input type="number" name="min_assistance_amount" required min="100" step="100" value="<?php echo htmlspecialchars($current_settings['min_assistance_amount'] ?? 500); ?>" style="width: 100%; padding: 10px; border: 2px solid #e0f2fe; border-radius: 8px;">
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 5px; color: #1e3a5f; font-weight: 500;">Auto-approve Below Amount (₱) *</label>
                        <input type="number" name="auto_approve_below_amount" required min="0" step="100" value="<?php echo htmlspecialchars($current_settings['auto_approve_below_amount'] ?? 1000); ?>" style="width: 100%; padding: 10px; border: 2px solid #e0f2fe; border-radius: 8px;">
                    </div>
                </div>

                <h3 style="margin-bottom: 20px; color: #1e3a5f; margin-top: 30px;">Application Settings</h3>
                
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 20px;">
                    <div>
                        <label style="display: block; margin-bottom: 5px; color: #1e3a5f; font-weight: 500;">Review Deadline (days) *</label>
                        <input type="number" name="review_deadline_days" required min="1" max="30" value="<?php echo htmlspecialchars($current_settings['review_deadline_days'] ?? 7); ?>" style="width: 100%; padding: 10px; border: 2px solid #e0f2fe; border-radius: 8px;">
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 5px; color: #1e3a5f; font-weight: 500;">Max Monthly Applications per Person *</label>
                        <input type="number" name="max_monthly_applications" required min="1" max="10" value="<?php echo htmlspecialchars($current_settings['max_monthly_applications'] ?? 3); ?>" style="width: 100%; padding: 10px; border: 2px solid #e0f2fe; border-radius: 8px;">
                    </div>
                </div>

                <div style="margin-bottom: 20px;">
                    <label style="display: flex; align-items: center; gap: 10px; color: #1e3a5f; font-weight: 500;">
                        <input type="checkbox" name="require_document_verification" <?php echo ($current_settings['require_document_verification'] ?? true) ? 'checked' : ''; ?>>
                        Require document verification for all applications
                    </label>
                </div>

                <button type="submit" class="view-btn" style="background: #1e3a5f; color: white; padding: 12px 30px; border: none; border-radius: 8px; cursor: pointer; font-size: 1rem;">Save Settings</button>
            </form>
        </section>

    </main>
</div>

</body>
</html>
