<?php
/**
 * Pre-Deployment Validation Script
 * Run this script before deploying to ensure the server environment is ready
 */

echo "<h1>Pre-Deployment Validation</h1>";
echo "<style>body{font-family:Arial,sans-serif;max-width:800px;margin:50px auto;padding:20px;}.pass{color:green}.fail{color:red}.warn{color:orange}</style>";

$errors = [];
$warnings = [];

// Check PHP version
$phpVersion = PHP_VERSION;
echo "<h3>PHP Version</h3>";
echo "Current: $phpVersion<br>";
if (version_compare($phpVersion, '7.4.0', '>=')) {
    echo "<span class='pass'>✓ PHP version is compatible (>= 7.4)</span><br>";
} else {
    echo "<span class='fail'>✗ PHP version is too old (requires >= 7.4)</span><br>";
    $errors[] = "PHP version too old";
}

// Check required extensions
echo "<h3>PHP Extensions</h3>";
$requiredExtensions = ['mysqli', 'mbstring', 'json', 'fileinfo', 'session'];
foreach ($requiredExtensions as $ext) {
    if (extension_loaded($ext)) {
        echo "<span class='pass'>✓ $ext</span><br>";
    } else {
        echo "<span class='fail'>✗ $ext (missing)</span><br>";
        $errors[] = "Missing PHP extension: $ext";
    }
}

// Check .env file
echo "<h3>Configuration</h3>";
if (file_exists(__DIR__ . '/.env')) {
    echo "<span class='pass'>✓ .env file exists</span><br>";
} else {
    echo "<span class='warn'>⚠ .env file not found (using defaults)</span><br>";
    $warnings[] = ".env file not found";
}

// Check database connection
echo "<h3>Database Connection</h3>";
try {
    require_once __DIR__ . '/config/db.php';
    echo "<span class='pass'>✓ Database connection successful</span><br>";
} catch (Exception $e) {
    echo "<span class='fail'>✗ Database connection failed: " . htmlspecialchars($e->getMessage()) . "</span><br>";
    $errors[] = "Database connection failed";
}

// Check directories
echo "<h3>Directory Permissions</h3>";
require_once __DIR__ . '/config/directories.php';
$status = DirectoryManager::getDirectoryStatus();
foreach ($status as $dir => $result) {
    if ($result['valid']) {
        echo "<span class='pass'>✓ $dir</span><br>";
    } else {
        echo "<span class='fail'>✗ $dir - " . htmlspecialchars($result['error']) . "</span><br>";
        $errors[] = "Directory issue: $dir";
    }
}

// Check write permissions
echo "<h3>Write Permissions</h3>";
$writeCheckDirs = ['lgu/uploads', 'storage'];
foreach ($writeCheckDirs as $dir) {
    $fullPath = __DIR__ . '/' . $dir;
    if (is_writable($fullPath)) {
        echo "<span class='pass'>✓ $dir is writable</span><br>";
    } else {
        echo "<span class='fail'>✗ $dir is not writable</span><br>";
        $errors[] = "$dir is not writable";
    }
}

// Check security settings
echo "<h3>Security Settings</h3>";
if (ini_get('display_errors')) {
    echo "<span class='warn'>⚠ display_errors is ON (should be OFF in production)</span><br>";
    $warnings[] = "display_errors is ON";
} else {
    echo "<span class='pass'>✓ display_errors is OFF</span><br>";
}

if (session_status() === PHP_SESSION_NONE) {
    echo "<span class='pass'>✓ Session not started (will start on demand)</span><br>";
} else {
    echo "<span class='warn'>⚠ Session already started</span><br>";
}

// Summary
echo "<h2>Validation Summary</h2>";
if (empty($errors) && empty($warnings)) {
    echo "<span class='pass' style='font-size:18px'>✓ All checks passed! Ready for deployment.</span><br>";
} else {
    if (!empty($errors)) {
        echo "<span class='fail' style='font-size:18px'>✗ Found " . count($errors) . " error(s) that must be fixed before deployment:</span><br>";
        echo "<ul>";
        foreach ($errors as $error) {
            echo "<li class='fail'>$error</li>";
        }
        echo "</ul>";
    }
    
    if (!empty($warnings)) {
        echo "<span class='warn' style='font-size:18px'>⚠ Found " . count($warnings) . " warning(s) (recommended but not required):</span><br>";
        echo "<ul>";
        foreach ($warnings as $warning) {
            echo "<li class='warn'>$warning</li>";
        }
        echo "</ul>";
    }
}

echo "<hr>";
echo "<h3>Deployment Checklist</h3>";
echo "<ol>";
echo "<li>Set BASE_URL in .env file for your production domain</li>";
echo "<li>Configure database credentials in .env file</li>";
echo "<li>Set APP_DEBUG=false in .env file for production</li>";
echo "<li>Run all migration files to create database tables</li>";
echo "<li>Create super admin account if not exists</li>";
echo "<li>Test login functionality</li>";
echo "<li>Test file upload functionality</li>";
echo "<li>Verify all public pages are accessible</li>";
echo "<li>Set up HTTPS/SSL certificate</li>";
echo "<li>Configure backup schedule</li>";
echo "</ol>";
?>
