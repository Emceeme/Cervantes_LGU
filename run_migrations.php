<?php
// HTTP Migration Runner for PostgreSQL
// Access this file via: https://your-app.onrender.com/run_migrations.php
// SECURITY: Delete this file after running migrations in production

require_once 'config/db.php';

header('Content-Type: text/plain');

echo "=== LGU System Migration Runner ===\n\n";

// Check if this is a POST request (for security)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo "ERROR: This endpoint requires POST request for security.\n";
    echo "Use: curl -X POST https://your-app.onrender.com/run_migrations.php\n";
    exit(1);
}

// Simple security check (you can add a password check here)
$auth_key = $_POST['auth_key'] ?? '';
if ($auth_key !== 'run_migrations_secure_key') {
    echo "ERROR: Invalid authentication key.\n";
    exit(1);
}

echo "Starting migrations...\n\n";

// Function to run a migration file
function run_migration($file_path, $conn) {
    echo "Running: $file_path\n";
    
    if (!file_exists($file_path)) {
        echo "  ✗ File not found\n";
        return false;
    }
    
    // Include the migration file
    // The migration file should use the $conn connection
    include $file_path;
    
    echo "  ✓ Completed\n";
    return true;
}

// List of migrations to run (in order)
$migrations = [
    'migrations/create_department_settings.php',
    'mswd/migrations/create_tables.php',
    'mswd/migrations/seed_assistance_types.php',
    'migrations/add_applicant_account_link.php',
    'migrations/add_procurement_custom_date.php',
    'migrations/add_view_count_procurement.php',
    'migrations/create_scholarship_applications.php',
    'migrations/create_scholarship_posts.php',
    'mswd/migrations/add_eligibility_column.php'
];

$success_count = 0;
$failed_count = 0;

foreach ($migrations as $migration) {
    if (run_migration($migration, $conn)) {
        $success_count++;
    } else {
        $failed_count++;
    }
    echo "\n";
}

echo "=== Migration Summary ===\n";
echo "Successful: $success_count\n";
echo "Failed: $failed_count\n";
echo "\nNext step: Run create_super_admin.php\n";
echo "Use: curl -X POST -d 'auth_key=run_migrations_secure_key' https://your-app.onrender.com/run_super_admin.php\n";
?>
