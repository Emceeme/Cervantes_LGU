<?php
// Migration: Make description column nullable in procurement_posts table
// PostgreSQL-compatible version

require_once __DIR__ . '/../config/db.php';

// Detect database type
$is_postgres = (strpos(getenv('DATABASE_URL') ?? '', 'postgres') !== false) || 
               ($conn instanceof PDO);

// Make description column nullable
if ($is_postgres) {
    // PostgreSQL syntax
    $sql = "ALTER TABLE procurement_posts ALTER COLUMN description DROP NOT NULL";
} else {
    // MySQL syntax
    $sql = "ALTER TABLE procurement_posts MODIFY description TEXT NULL";
}

$result = $conn->query($sql);
if ($result) {
    echo "✓ description column made nullable successfully.<br>";
} else {
    if ($conn instanceof PDO) {
        $error = $conn->errorInfo();
        echo "❌ Error: " . ($error[2] ?? 'Unknown error') . "<br>";
    } else {
        echo "❌ Error: " . $conn->error . "<br>";
    }
}

// Only close connection for MySQLi (PDO doesn't have close())
if (!($conn instanceof PDO)) {
    $conn->close();
}
echo "<br>Migration completed successfully!";
?>
