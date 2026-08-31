<?php
// Check PHP extensions and environment configuration
header('Content-Type: text/plain');

echo "=== PHP Configuration Check ===\n\n";

echo "PHP Version: " . phpversion() . "\n";
echo "PHP SAPI: " . php_sapi_name() . "\n\n";

echo "=== Loaded Extensions ===\n";
$required_extensions = ['pdo', 'pdo_mysql', 'pdo_pgsql', 'mysqli', 'pgsql'];
foreach ($required_extensions as $ext) {
    $loaded = extension_loaded($ext);
    echo sprintf("%-15s: %s\n", $ext, $loaded ? '✓ LOADED' : '✗ NOT LOADED');
}

echo "\n=== PDO Drivers ===\n";
if (extension_loaded('pdo')) {
    $drivers = PDO::getAvailableDrivers();
    echo "Available PDO drivers: " . implode(', ', $drivers) . "\n";
    
    if (in_array('pgsql', $drivers)) {
        echo "✓ PostgreSQL PDO driver available\n";
    } else {
        echo "✗ PostgreSQL PDO driver NOT available\n";
    }
    
    if (in_array('mysql', $drivers)) {
        echo "✓ MySQL PDO driver available\n";
    } else {
        echo "✗ MySQL PDO driver NOT available\n";
    }
} else {
    echo "✗ PDO extension not loaded\n";
}

echo "\n=== Environment Variables ===\n";
$env_vars = ['DATABASE_URL', 'APP_ENV', 'APP_DEBUG', 'DB_HOST', 'DB_USER', 'DB_NAME'];
foreach ($env_vars as $var) {
    $value = getenv($var);
    if ($value === false) {
        echo sprintf("%-15s: NOT SET\n", $var);
    } else {
        // Mask sensitive values
        if (in_array($var, ['DATABASE_URL', 'DB_PASS'])) {
            $masked = substr($value, 0, 10) . '...' . substr($value, -5);
            echo sprintf("%-15s: %s\n", $var, $masked);
        } else {
            echo sprintf("%-15s: %s\n", $var, $value);
        }
    }
}

echo "\n=== Database Connection Test ===\n";
try {
    require_once 'config/db.php';
    echo "✓ Database connection successful\n";
    
    if ($conn instanceof PDO) {
        echo "Connection type: PDO\n";
        echo "PDO Driver: " . $conn->getAttribute(PDO::ATTR_DRIVER_NAME) . "\n";
    } else {
        echo "Connection type: MySQLi\n";
    }
} catch (Exception $e) {
    echo "✗ Database connection failed: " . $e->getMessage() . "\n";
}

echo "\n=== Check Complete ===\n";
?>
