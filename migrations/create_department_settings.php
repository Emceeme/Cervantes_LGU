<?php
// Migration: Create department_settings table
// This migration creates a table to store department-specific settings

include '../config/db.php';

// Create table
$sql = "CREATE TABLE IF NOT EXISTS department_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    department VARCHAR(100) NOT NULL UNIQUE,
    settings_json JSON NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === TRUE) {
    echo "Table 'department_settings' created successfully.<br>";
} else {
    die("Error creating table: " . $conn->error);
}

// Insert default settings for each department
$default_settings = [
    'LGU' => json_encode([
        'department_name' => "Mayor's Office",
        'contact_email' => 'mayor@cervantes.gov.ph',
        'contact_phone' => '(077) 123-4567',
        'office_hours' => '8:00 AM - 5:00 PM',
        'max_file_size_mb' => 5,
        'allowed_file_types' => ['pdf', 'doc', 'docx', 'jpg', 'png'],
        'auto_approve_news' => false,
        'require_admin_approval' => true,
        'scholarship_deadline_days' => 30,
        'procurement_notice_days' => 7
    ]),
    'MSWD' => json_encode([
        'department_name' => 'Municipal Social Welfare and Development',
        'contact_email' => 'mswd@cervantes.gov.ph',
        'contact_phone' => '(077) 123-4568',
        'office_hours' => '8:00 AM - 5:00 PM',
        'max_assistance_amount' => 10000,
        'min_assistance_amount' => 500,
        'require_document_verification' => true,
        'auto_approve_below_amount' => 1000,
        'review_deadline_days' => 7,
        'max_monthly_applications' => 3
    ]),
    'Treasury' => json_encode([
        'department_name' => 'Municipal Treasury',
        'contact_email' => 'treasury@cervantes.gov.ph',
        'contact_phone' => '(077) 123-4569',
        'office_hours' => '8:00 AM - 5:00 PM',
        'min_balance_threshold' => 100,
        'max_balance_threshold' => 1000000,
        'auto_approve_transactions' => false,
        'transaction_timeout_minutes' => 30,
        'require_receipt_upload' => true,
        'notification_email_enabled' => true
    ]),
    'ADMIN' => json_encode([
        'department_name' => 'System Administration',
        'contact_email' => 'admin@cervantes.gov.ph',
        'contact_phone' => '(077) 123-4570',
        'maintenance_mode' => false,
        'backup_retention_days' => 30,
        'log_retention_days' => 90,
        'max_login_attempts' => 5,
        'session_timeout_minutes' => 30,
        'password_min_length' => 8,
        'require_2fa' => false,
        'system_announcement' => ''
    ])
];

foreach ($default_settings as $department => $settings) {
    $stmt = $conn->prepare("INSERT INTO department_settings (department, settings_json) VALUES (?, ?) ON DUPLICATE KEY UPDATE settings_json = VALUES(settings_json)");
    $stmt->bind_param("ss", $department, $settings);
    
    if ($stmt->execute()) {
        echo "Default settings for '$department' inserted successfully.<br>";
    } else {
        echo "Error inserting settings for '$department': " . $stmt->error . "<br>";
    }
    $stmt->close();
}

$conn->close();
echo "<br>Migration completed successfully!";
?>
