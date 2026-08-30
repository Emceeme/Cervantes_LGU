<?php
require_once __DIR__ . '/../config/db.php';

// Detect database type
$is_postgres = (strpos(getenv('DATABASE_URL') ?? '', 'postgres') !== false) || 
               ($conn instanceof PDO);

// Add tracking_number column to scholarship_applications table
if ($is_postgres) {
    // PostgreSQL syntax
    $sql = "ALTER TABLE scholarship_applications ADD COLUMN IF NOT EXISTS tracking_number VARCHAR(20) UNIQUE";
} else {
    // MySQL syntax
    $sql = "ALTER TABLE scholarship_applications ADD COLUMN tracking_number VARCHAR(20) UNIQUE";
}

if ($conn->query($sql)) {
    echo "✅ tracking_number column added to scholarship_applications table successfully.<br>";
} else {
    if ($conn instanceof PDO) {
        $error = $conn->errorInfo();
        echo "❌ Error adding tracking_number column: " . ($error[2] ?? 'Unknown error') . "<br>";
    } else {
        echo "❌ Error adding tracking_number column: " . $conn->error . "<br>";
    }
}

// Only close connection for MySQLi (PDO doesn't have close())
if (!($conn instanceof PDO)) {
    $conn->close();
}
?>
