<?php
/**
 * Cleanup Script
 * 
 * WARNING: This script will:
 * 1. Delete all records from database tables
 * 2. Delete all uploaded files
 * 
 * Use this to reset the application for testing or production deployment.
 * 
 * Usage: php cleanup.php
 */

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/upload_helper.php';

echo "=== LGU Portal Cleanup Script ===\n\n";

// Confirm before proceeding
echo "⚠️  WARNING: This will delete ALL data and uploaded files!\n";
echo "Type 'DELETE' to confirm: ";
$handle = fopen("php://stdin", "r");
$line = trim(fgets($handle));
fclose($handle);

if ($line !== 'DELETE') {
    echo "\n❌ Cleanup cancelled.\n";
    exit(0);
}

echo "\n🗑️  Starting cleanup...\n\n";

// Detect database type
$is_postgres = (strpos(getenv('DATABASE_URL') ?? '', 'postgres') !== false) || 
               ($conn instanceof PDO);

// Tables to clear
$tables = [
    'applicants',
    'jobs',
    'news_posts',
    'scholarship_posts',
    'scholarship_applications',
    'procurement_posts'
];

// Clear database tables
foreach ($tables as $table) {
    echo "Clearing table: $table ... ";
    
    if ($is_postgres) {
        // PostgreSQL - use TRUNCATE with CASCADE to handle foreign keys
        $sql = "TRUNCATE TABLE $table RESTART IDENTITY CASCADE";
    } else {
        // MySQL - use TRUNCATE
        $sql = "TRUNCATE TABLE $table";
    }
    
    if ($conn->query($sql)) {
        echo "✅\n";
    } else {
        if ($conn instanceof PDO) {
            $error = $conn->errorInfo();
            echo "❌ Error: " . ($error[2] ?? 'Unknown error') . "\n";
        } else {
            echo "❌ Error: " . $conn->error . "\n";
        }
    }
}

// Delete uploaded files
$upload_types = ['news', 'scholarship', 'resumes', 'procurement'];

echo "\nDeleting uploaded files...\n";

foreach ($upload_types as $type) {
    $upload_dir = UploadHelper::getUploadDir($type);
    echo "Cleaning: $upload_dir ... ";
    
    if (is_dir($upload_dir)) {
        $files = glob($upload_dir . '/*');
        $deleted_count = 0;
        
        foreach ($files as $file) {
            if (is_file($file)) {
                if (unlink($file)) {
                    $deleted_count++;
                }
            }
        }
        
        echo "✅ (Deleted $deleted_count files)\n";
    } else {
        echo "⚠️  Directory does not exist\n";
    }
}

// Only close connection for MySQLi (PDO doesn't have close())
if (!($conn instanceof PDO)) {
    $conn->close();
}

echo "\n✅ Cleanup complete!\n";
echo "Database tables have been cleared and uploaded files deleted.\n";
?>
