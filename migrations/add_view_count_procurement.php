<?php
require_once __DIR__ . '/../config/db.php';

// Detect database type
$is_postgres = (strpos(getenv('DATABASE_URL') ?? '', 'postgres') !== false) || 
               ($conn instanceof PDO);

// Add view_count column to procurement_posts table
if ($is_postgres) {
    $sql = "ALTER TABLE procurement_posts ADD COLUMN IF NOT EXISTS view_count INTEGER DEFAULT 0";
} else {
    $sql = "ALTER TABLE procurement_posts ADD COLUMN view_count INT(11) DEFAULT 0";
}

if ($conn->query($sql)) {
    echo "✅ view_count column added to procurement_posts table.<br>";
} else {
    // Check if column already exists
    if ($is_postgres) {
        $check = $conn->query("SELECT column_name FROM information_schema.columns WHERE table_name = 'procurement_posts' AND column_name = 'view_count'");
        if ($check && $check->rowCount() > 0) {
            echo "✅ view_count column already exists.<br>";
        } else {
            echo "❌ Error adding view_count column: " . $conn->error . "<br>";
        }
    } else {
        $check = $conn->query("SHOW COLUMNS FROM procurement_posts LIKE 'view_count'");
        if ($check->num_rows > 0) {
            echo "✅ view_count column already exists.<br>";
        } else {
            echo "❌ Error adding view_count column: " . $conn->error . "<br>";
        }
    }
}

$conn->close();
?>
