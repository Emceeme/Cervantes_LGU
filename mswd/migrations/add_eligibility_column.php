<?php
/**
 * Migration: Add eligibility_requirements column to assistance_types table
 * Run this if the column is missing
 */

require_once __DIR__ . '/../../config/db.php';

echo "Adding eligibility_requirements column to assistance_types table...\n";

// Check if column exists
$check_sql = "SHOW COLUMNS FROM assistance_types LIKE 'eligibility_requirements'";
$result = $conn->query($check_sql);

if ($result->num_rows > 0) {
    echo "Column 'eligibility_requirements' already exists.\n";
} else {
    // Add the column
    $sql = "ALTER TABLE assistance_types ADD COLUMN eligibility_requirements TEXT AFTER description";
    
    if ($conn->query($sql)) {
        echo "✓ Added eligibility_requirements column\n";
    } else {
        die("✗ Failed to add column: " . $conn->error);
    }
}

// Also check for process_steps column
$check_process = "SHOW COLUMNS FROM assistance_types LIKE 'process_steps'";
$result_process = $conn->query($check_process);

if ($result_process->num_rows === 0) {
    $sql_process = "ALTER TABLE assistance_types ADD COLUMN process_steps TEXT AFTER eligibility_requirements";
    
    if ($conn->query($sql_process)) {
        echo "✓ Added process_steps column\n";
    } else {
        echo "Warning: Failed to add process_steps column: " . $conn->error . "\n";
    }
}

echo "\nMigration completed successfully!\n";
?>
