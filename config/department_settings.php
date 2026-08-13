<?php
// Department Settings Helper Functions
// Provides functions to manage department-specific settings

include 'db.php';

/**
 * Get settings for a specific department
 * @param string $department The department name
 * @return array The department settings as an associative array
 */
function getDepartmentSettings($department) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT settings_json FROM department_settings WHERE department = ?");
    $stmt->bind_param("s", $department);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $stmt->close();
        return [];
    }
    
    $row = $result->fetch_assoc();
    $settings = json_decode($row['settings_json'], true);
    $stmt->close();
    
    return $settings ?: [];
}

/**
 * Get a specific setting value for a department
 * @param string $department The department name
 * @param string $key The setting key
 * @param mixed $default Default value if setting doesn't exist
 * @return mixed The setting value or default
 */
function getDepartmentSetting($department, $key, $default = null) {
    $settings = getDepartmentSettings($department);
    return $settings[$key] ?? $default;
}

/**
 * Update settings for a specific department
 * @param string $department The department name
 * @param array $settings The settings array to update
 * @return bool Success status
 */
function updateDepartmentSettings($department, $settings) {
    global $conn;
    
    $settings_json = json_encode($settings);
    
    $stmt = $conn->prepare("INSERT INTO department_settings (department, settings_json) VALUES (?, ?) ON DUPLICATE KEY UPDATE settings_json = VALUES(settings_json)");
    $stmt->bind_param("ss", $department, $settings_json);
    
    $success = $stmt->execute();
    $stmt->close();
    
    return $success;
}

/**
 * Update a single setting for a department
 * @param string $department The department name
 * @param string $key The setting key
 * @param mixed $value The setting value
 * @return bool Success status
 */
function updateDepartmentSetting($department, $key, $value) {
    $settings = getDepartmentSettings($department);
    $settings[$key] = $value;
    return updateDepartmentSettings($department, $settings);
}

/**
 * Get all departments with their settings
 * @return array Array of departments with their settings
 */
function getAllDepartmentSettings() {
    global $conn;
    
    $result = $conn->query("SELECT department, settings_json FROM department_settings");
    $departments = [];
    
    while ($row = $result->fetch_assoc()) {
        $departments[$row['department']] = json_decode($row['settings_json'], true);
    }
    
    return $departments;
}

/**
 * Reset department settings to defaults
 * @param string $department The department name
 * @return bool Success status
 */
function resetDepartmentSettings($department) {
    $default_settings = [
        'LGU' => [
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
        ],
        'MSWD' => [
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
        ],
        'Treasury' => [
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
        ],
        'ADMIN' => [
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
        ]
    ];
    
    if (!isset($default_settings[$department])) {
        return false;
    }
    
    return updateDepartmentSettings($department, $default_settings[$department]);
}
?>
