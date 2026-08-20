<?php
// HTTP Migration Runner for PostgreSQL
// Access this file via: https://your-app.onrender.com/run_migrations.php
// SECURITY: Delete this file after running migrations in production

require_once 'config/db.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

header('Content-Type: text/plain; charset=utf-8');

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
        echo "  ✗ File not\n";
        return false;
    }
    
    // Get the directory of the migration file
    $migration_dir = dirname($file_path);
    
    // Make the $conn connection available to the migration file
    // by including it in the global scope
    global $conn;
    
    // Change to the migration directory so relative includes work
    $original_dir = getcwd();
    chdir($migration_dir);
    
    // Include the migration file
    include basename($file_path);
    
    // Restore original directory
    chdir($original_dir);
    
    echo "  ✓ Completed\n";
    return true;
}

// List of migrations to run (in order)
$migrations = [
    'migrations/create_users_table.php',
    'migrations/create_department_settings.php',
    'migrations/create_jobs_table.php',
    'migrations/create_applicants_table.php',
    'migrations/create_news_posts_table.php',
    'migrations/create_procurement_posts_table.php',
    'migrations/add_procurement_file_columns.php',
    'migrations/make_procurement_description_nullable.php',
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
