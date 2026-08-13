<?php
/**
 * MSWD Database Migration Script
 * Creates tables for the MSWD module
 * 
 * Usage: php mswd/migrations/create_tables.php
 */

require_once __DIR__ . '/../../config/db.php';

echo "Starting MSWD database migration...\n\n";

try {
    // Create assistance_types table
    $sql_assistance_types = "
        CREATE TABLE IF NOT EXISTS assistance_types (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            description TEXT,
            eligibility_requirements TEXT,
            process_steps TEXT,
            required_documents JSON,
            is_active TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_active (is_active)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";
    
    if ($conn->query($sql_assistance_types)) {
        echo "✓ Created assistance_types table\n";
    } else {
        throw new Exception("Failed to create assistance_types: " . $conn->error);
    }
    
    // Create applications table
    $sql_applications = "
        CREATE TABLE IF NOT EXISTS applications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            tracking_number VARCHAR(50) NOT NULL UNIQUE,
            assistance_type_id INT NOT NULL,
            
            -- Applicant Information
            first_name VARCHAR(100) NOT NULL,
            middle_name VARCHAR(100),
            last_name VARCHAR(100) NOT NULL,
            birthdate DATE NOT NULL,
            gender ENUM('Male', 'Female', 'Other') NOT NULL,
            civil_status ENUM('Single', 'Married', 'Widowed', 'Separated', 'Divorced') NOT NULL,
            contact_number VARCHAR(20) NOT NULL,
            email VARCHAR(255),
            barangay VARCHAR(100) NOT NULL,
            street_address TEXT NOT NULL,
            
            -- Application Status
            status ENUM('pending', 'under_review', 'approved', 'rejected') DEFAULT 'pending',
            remarks TEXT,
            
            -- Assigned Worker
            assigned_worker_id INT NULL,
            
            -- Timestamps
            submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            reviewed_at TIMESTAMP NULL,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            -- Foreign Keys
            FOREIGN KEY (assistance_type_id) REFERENCES assistance_types(id) ON DELETE RESTRICT,
            FOREIGN KEY (assigned_worker_id) REFERENCES users(id) ON DELETE SET NULL,
            
            -- Indexes
            INDEX idx_tracking_number (tracking_number),
            INDEX idx_status (status),
            INDEX idx_barangay (barangay),
            INDEX idx_assistance_type (assistance_type_id),
            INDEX idx_submitted_at (submitted_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";
    
    if ($conn->query($sql_applications)) {
        echo "✓ Created applications table\n";
    } else {
        throw new Exception("Failed to create applications: " . $conn->error);
    }
    
    // Create application_documents table
    $sql_documents = "
        CREATE TABLE IF NOT EXISTS application_documents (
            id INT AUTO_INCREMENT PRIMARY KEY,
            application_id INT NOT NULL,
            document_type VARCHAR(100) NOT NULL,
            file_name VARCHAR(255) NOT NULL,
            file_path VARCHAR(500) NOT NULL,
            file_size INT NOT NULL,
            mime_type VARCHAR(100) NOT NULL,
            uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            
            -- Foreign Key
            FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE,
            
            -- Index
            INDEX idx_application (application_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";
    
    if ($conn->query($sql_documents)) {
        echo "✓ Created application_documents table\n";
    } else {
        throw new Exception("Failed to create application_documents: " . $conn->error);
    }
    
    // Create application_status_history table
    $sql_history = "
        CREATE TABLE IF NOT EXISTS application_status_history (
            id INT AUTO_INCREMENT PRIMARY KEY,
            application_id INT NOT NULL,
            old_status VARCHAR(50),
            new_status VARCHAR(50) NOT NULL,
            changed_by INT NOT NULL,
            remarks TEXT,
            changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            
            -- Foreign Keys
            FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE,
            FOREIGN KEY (changed_by) REFERENCES users(id) ON DELETE RESTRICT,
            
            -- Index
            INDEX idx_application (application_id),
            INDEX idx_changed_at (changed_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";
    
    if ($conn->query($sql_history)) {
        echo "✓ Created application_status_history table\n";
    } else {
        throw new Exception("Failed to create application_status_history: " . $conn->error);
    }
    
    // Create storage directory
    $storage_dir = __DIR__ . '/../../storage/mswd_documents';
    if (!file_exists($storage_dir)) {
        mkdir($storage_dir, 0755, true);
        echo "✓ Created storage directory\n";
    }
    
    echo "\n✓ MSWD database migration completed successfully!\n";
    
} catch (Exception $e) {
    echo "\n✗ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
?>
