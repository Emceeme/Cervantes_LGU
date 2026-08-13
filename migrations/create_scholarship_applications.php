<?php
require_once './config/db.php';

// Create scholarship_applications table
$sql = "CREATE TABLE IF NOT EXISTS scholarship_applications (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    scholarship_id INT(11),
    full_name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(50) NOT NULL,
    address TEXT NOT NULL,
    birth_date DATE NOT NULL,
    gender VARCHAR(20) NOT NULL,
    civil_status VARCHAR(20) NOT NULL,
    school_name VARCHAR(255) NOT NULL,
    course VARCHAR(255) NOT NULL,
    year_level VARCHAR(20) NOT NULL,
    gpa DECIMAL(3,2) NOT NULL,
    family_income DECIMAL(15,2) NOT NULL,
    family_members INT(11) NOT NULL,
    parent_name VARCHAR(255) NOT NULL,
    parent_contact VARCHAR(50) NOT NULL,
    parent_occupation VARCHAR(255) NOT NULL,
    essay TEXT,
    file_path VARCHAR(500),
    original_file_name VARCHAR(255),
    status ENUM('PENDING', 'UNDER_REVIEW', 'APPROVED', 'REJECTED') DEFAULT 'PENDING',
    admin_notes TEXT,
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    reviewed_at TIMESTAMP NULL,
    reviewed_by INT(11) NULL,
    INDEX idx_scholarship_id (scholarship_id),
    INDEX idx_status (status),
    INDEX idx_submitted_at (submitted_at)
)";

if ($conn->query($sql)) {
    echo "✅ scholarship_applications table created successfully.<br>";
} else {
    echo "❌ Error creating scholarship_applications table: " . $conn->error . "<br>";
}

$conn->close();
?>
