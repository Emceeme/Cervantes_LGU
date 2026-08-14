<?php
/**
 * Migration: Add eligibility_requirements column to assistance_types table
 * PostgreSQL-compatible version
 * Run this if the column is missing
 */

require_once __DIR__ . '/../../config/db.php';

echo "Adding eligibility_requirements column to assistance_types table...\n";

// Detect database type
$is_postgres = (strpos(getenv('DATABASE_URL') ?? '', 'postgres') !== false) || 
               ($conn instanceof PDO);

// Check if column exists
if ($is_postgres) {
    // PostgreSQL: Check using information_schema
    $check_sql = "SELECT column_name FROM information_schema.columns 
                  WHERE table_name = 'assistance_types' AND column_name = 'eligibility_requirements'";
    $result = $conn->query($check_sql);
    $column_exists = ($result && $result->rowCount() > 0);
} else {
    // MySQL: Check using SHOW COLUMNS
    $check_sql = "SHOW COLUMNS FROM assistance_types LIKE 'eligibility_requirements'";
    $result = $conn->query($check_sql);
    $column_exists = ($result && $result->num_rows > 0);
}

if ($column_exists) {
    echo "Column 'eligibility_requirements' already exists.\n";
} else {
    // Add the column
    if ($is_postgres) {
        $sql = "ALTER TABLE assistance_types ADD COLUMN IF NOT EXISTS eligibility_requirements TEXT";
    } else {
        $sql = "ALTER TABLE assistance_types ADD COLUMN eligibility_requirements TEXT AFTER description";
    }
    
    if ($conn->query($sql)) {
        echo "✓ Added eligibility_requirements column\n";
    } else {
        die("✗ Failed to add column: " . $conn->error);
    }
}

// Also check for process_steps column
if ($is_postgres) {
    $check_process = "SELECT column_name FROM information_schema.columns 
                      WHERE table_name = 'assistance_types' AND column_name = 'process_steps'";
    $result_process = $conn->query($check_process);
    $process_exists = ($result_process && $result_process->rowCount() > 0);
} else {
    $check_process = "SHOW COLUMNS FROM assistance_types LIKE 'process_steps'";
    $result_process = $conn->query($check_process);
    $process_exists = ($result_process && $result_process->num_rows > 0);
}

if (!$process_exists) {
    if ($is_postgres) {
        $sql_process = "ALTER TABLE assistance_types ADD COLUMN IF NOT EXISTS process_steps TEXT";
    } else {
        $sql_process = "ALTER TABLE assistance_types ADD COLUMN process_steps TEXT AFTER eligibility_requirements";
    }
    
    if ($conn->query($sql_process)) {
        echo "✓ Added process_steps column\n";
    } else {
        echo "Warning: Failed to add process_steps column: " . $conn->error . "\n";
    }
}

echo "\nMigration completed successfully!\n";
?>
