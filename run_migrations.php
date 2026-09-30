<?php
// Migration Runner for PostgreSQL
// Access this file with ?secret=YOUR_SECRET_KEY to run migrations

require_once __DIR__ . '/config/env.php';
require_once __DIR__ . '/config/db.php';

// Secret key for security (change this in production)
$SECRET_KEY = 'run_migrations_secret_2024';

// Check for secret key
if (!isset($_GET['secret']) || $_GET['secret'] !== $SECRET_KEY) {
    http_response_code(403);
    die("Access denied. Invalid secret key.");
}

// Migration files in order
$migrations = [
    'migrations/create_users_table.php',
    'migrations/create_jobs_table.php',
    'migrations/create_applicants_table.php',
    'migrations/create_news_posts_table.php',
    'migrations/create_scholarship_posts.php',
    'migrations/create_scholarship_applications.php',
    'migrations/create_procurement_posts_table.php',
    'migrations/create_department_settings.php',
    'migrations/create_migrations_table.php',
    'mswd/migrations/create_tables.php', // Run MSWD migrations before modifying its tables
    'migrations/add_applicant_account_link.php', // This modifies MSWD applications table
    'migrations/add_procurement_file_columns.php',
    'migrations/add_procurement_custom_date.php',
    'migrations/add_tracking_number_to_scholarship.php',
    'migrations/add_view_count_procurement.php',
    'migrations/make_procurement_description_nullable.php',
];

echo "<h1>Database Migration Runner</h1>";
echo "<p>Running migrations on PostgreSQL...</p>";
echo "<pre>";

$success_count = 0;
$error_count = 0;

foreach ($migrations as $migration) {
    $migration_path = __DIR__ . '/' . $migration;
    if (!file_exists($migration_path)) {
        echo "ERROR: Migration file not found: $migration\n";
        $error_count++;
        continue;
    }

    echo "Running: $migration... ";
    
    try {
        include $migration_path;
        echo "SUCCESS\n";
        $success_count++;
    } catch (Exception $e) {
        echo "FAILED: " . $e->getMessage() . "\n";
        $error_count++;
    }
}

echo "</pre>";
echo "<h2>Migration Summary</h2>";
echo "<p>Successful: $success_count</p>";
echo "<p>Failed: $error_count</p>";

if ($error_count === 0) {
    echo "<p style='color: green; font-weight: bold;'>All migrations completed successfully!</p>";
} else {
    echo "<p style='color: red; font-weight: bold;'>Some migrations failed. Please check the errors above.</p>";
}
?>
