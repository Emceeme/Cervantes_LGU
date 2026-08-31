<?php
/**
 * PostgreSQL Database Backup Script
 * 
 * This script creates a backup of the PostgreSQL database.
 * Run this script manually or set up a cron job for automated backups.
 * 
 * Usage: php backup_database.php
 * 
 * Security: This script should be protected and not accessible from the web.
 * Move it outside the web root or restrict access via .htaccess.
 */

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/security.php';

// Set security headers
setSecurityHeaders();

// Only allow CLI execution
if (php_sapi_name() !== 'cli') {
    die("This script can only be run from the command line.");
}

echo "=== PostgreSQL Database Backup ===\n\n";

// Parse DATABASE_URL if available
$db_host = 'localhost';
$db_port = '5432';
$db_name = 'cervantes_lgu';
$db_user = 'postgres';
$db_pass = '';

if (isset($_ENV['DATABASE_URL'])) {
    $db_url = $_ENV['DATABASE_URL'];
    
    // Parse PostgreSQL connection URL: postgresql://user:pass@host:port/dbname
    if (preg_match('/postgresql:\/\/([^:]+):([^@]+)@([^:]+):(\d+)\/(.+)/', $db_url, $matches)) {
        $db_user = $matches[1];
        $db_pass = $matches[2];
        $db_host = $matches[3];
        $db_port = $matches[4];
        $db_name = $matches[5];
    }
} elseif (isset($conn) && $conn instanceof PDO) {
    // Try to get connection details from PDO
    try {
        $db_host = $conn->getAttribute(PDO::ATTR_SERVER_INFO) ?: $db_host;
        $db_name = $conn->query('SELECT current_database()')->fetchColumn() ?: $db_name;
    } catch (Exception $e) {
        echo "Warning: Could not auto-detect database details. Using defaults.\n";
    }
}

// Create backup directory
$backup_dir = __DIR__ . '/backups';
if (!is_dir($backup_dir)) {
    if (!mkdir($backup_dir, 0755, true)) {
        die("Error: Failed to create backup directory: $backup_dir\n");
    }
    echo "Created backup directory: $backup_dir\n";
}

// Generate backup filename with timestamp
$timestamp = date('Y-m-d_H-i-s');
$backup_file = $backup_dir . '/backup_' . $timestamp . '.sql';
$compressed_file = $backup_file . '.gz';

echo "Database: $db_name\n";
echo "Host: $db_host:$db_port\n";
echo "Backup file: $backup_file\n\n";

// Build pg_dump command
$pg_dump_cmd = "pg_dump";
$pg_dump_cmd .= " --host=" . escapeshellarg($db_host);
$pg_dump_cmd .= " --port=" . escapeshellarg($db_port);
$pg_dump_cmd .= " --username=" . escapeshellarg($db_user);
$pg_dump_cmd .= " --dbname=" . escapeshellarg($db_name);
$pg_dump_cmd .= " --no-password";
$pg_dump_cmd .= " --format=plain";
$pg_dump_cmd .= " --no-owner";
$pg_dump_cmd .= " --no-acl";
$pg_dump_cmd .= " --verbose";

// Set PGPASSWORD environment variable for authentication
putenv("PGPASSWORD=" . escapeshellarg($db_pass));

echo "Running pg_dump...\n";

// Execute pg_dump and save to file
$exit_code = 0;
$output = [];
$return_var = 0;

$command = "$pg_dump_cmd > " . escapeshellarg($backup_file) . " 2>&1";
exec($command, $output, $return_var);

if ($return_var !== 0) {
    echo "Error: pg_dump failed with exit code $return_var\n";
    echo "Output: " . implode("\n", $output) . "\n";
    exit(1);
}

echo "Backup created successfully: $backup_file\n";
echo "File size: " . filesize($backup_file) . " bytes\n";

// Compress the backup
echo "\nCompressing backup...\n";
$gz_command = "gzip " . escapeshellarg($backup_file);
exec($gz_command . " 2>&1", $gz_output, $gz_return);

if ($gz_return === 0 && file_exists($compressed_file)) {
    echo "Backup compressed: $compressed_file\n";
    echo "Compressed size: " . filesize($compressed_file) . " bytes\n";
    $final_backup = $compressed_file;
} else {
    echo "Warning: Compression failed, keeping uncompressed backup\n";
    $final_backup = $backup_file;
}

// Clean up old backups (keep last 7 days)
echo "\nCleaning up old backups (keeping last 7 days)...\n";
$cutoff_time = time() - (7 * 24 * 60 * 60); // 7 days ago
$files = glob($backup_dir . '/backup_*.sql*');
$deleted_count = 0;

foreach ($files as $file) {
    if (filemtime($file) < $cutoff_time) {
        if (unlink($file)) {
            echo "Deleted old backup: " . basename($file) . "\n";
            $deleted_count++;
        }
    }
}

if ($deleted_count === 0) {
    echo "No old backups to delete.\n";
} else {
    echo "Deleted $deleted_count old backup(s).\n";
}

echo "\n=== Backup Complete ===\n";
echo "Final backup: $final_backup\n";

// Log backup event
logSecurityEvent('database_backup_completed', null, [
    'backup_file' => basename($final_backup),
    'file_size' => filesize($final_backup),
    'database' => $db_name
]);

exit(0);
?>
