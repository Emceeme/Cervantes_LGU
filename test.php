<?php
// Simple test script to verify PHP execution
echo "PHP is working!\n";
echo "PHP Version: " . phpversion() . "\n";
echo "Current time: " . date('Y-m-d H:i:s') . "\n";

try {
    require_once 'config/db.php';
    echo "Database config loaded successfully\n";
    
    if ($conn instanceof PDO) {
        echo "Using PDO connection\n";
    } else {
        echo "Using MySQLi connection\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "Test complete\n";
?>
