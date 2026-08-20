<?php
// MSWD-specific migration runner
// Access this file via: https://your-app.onrender.com/run_mswd_migrations.php

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('output_buffering', 0);
@ini_set('implicit_flush', 1);
@ob_implicit_flush(1);

require_once 'config/db.php';

header('Content-Type: text/plain; charset=utf-8');
header('X-Accel-Buffering: no'); // Disable nginx buffering

echo "=== MSWD Migration Runner ===\n\n";
flush();

// Test database connection
echo "Testing database connection...\n";
flush();

try {
    if ($conn instanceof PDO) {
        echo "✓ Using PDO connection\n";
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } else {
        echo "✓ Using MySQLi connection\n";
    }
    echo "✓ Database connection successful\n\n";
} catch (Exception $e) {
    echo "✗ Database connection failed: " . $e->getMessage() . "\n";
    exit(1);
}
flush();

// Function to run a migration file
function run_migration($file_path, $conn) {
    echo "Running: $file_path\n";
    flush();
    
    if (!file_exists($file_path)) {
        echo "  ✗ File not found\n";
        flush();
        return false;
    }
    
    global $conn;
    
    $original_dir = getcwd();
    $migration_dir = dirname($file_path);
    chdir($migration_dir);
    
    try {
        include basename($file_path);
        echo "  ✓ Completed\n";
        flush();
        chdir($original_dir);
        return true;
    } catch (Exception $e) {
        echo "  ✗ Error: " . $e->getMessage() . "\n";
        flush();
        chdir($original_dir);
        return false;
    }
}

// MSWD-specific migrations only
$migrations = [
    'mswd/migrations/create_tables.php',
    'mswd/migrations/seed_assistance_types.php',
    'mswd/migrations/add_eligibility_column.php'
];

echo "Starting MSWD migrations...\n\n";
flush();

$success_count = 0;
$failed_count = 0;

foreach ($migrations as $migration) {
    if (run_migration($migration, $conn)) {
        $success_count++;
    } else {
        $failed_count++;
    }
    echo "\n";
    flush();
}

echo "=== Migration Summary ===\n";
echo "Successful: $success_count\n";
echo "Failed: $failed_count\n";
flush();

// Verify tables were created
echo "\n=== Verifying Tables ===\n";
flush();

$tables_to_check = ['assistance_types', 'applications', 'application_documents', 'application_status_history'];

foreach ($tables_to_check as $table) {
    try {
        if ($conn instanceof PDO) {
            $stmt = $conn->query("SELECT EXISTS (SELECT FROM information_schema.tables WHERE table_name = '$table')");
            $exists = $stmt->fetchColumn();
        } else {
            $result = $conn->query("SHOW TABLES LIKE '$table'");
            $exists = $result && $result->num_rows > 0;
        }
        
        if ($exists) {
            echo "✓ Table '$table' exists\n";
        } else {
            echo "✗ Table '$table' does NOT exist\n";
        }
    } catch (Exception $e) {
        echo "✗ Error checking table '$table': " . $e->getMessage() . "\n";
    }
    flush();
}

echo "\n=== MSWD Migration Complete ===\n";
flush();
?>
