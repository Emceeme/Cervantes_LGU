<?php
require_once './config/db.php';

// Add view_count column to procurement_posts table
$sql = "ALTER TABLE procurement_posts ADD COLUMN view_count INT(11) DEFAULT 0";

if ($conn->query($sql)) {
    echo "✅ view_count column added to procurement_posts table.<br>";
} else {
    echo "❌ Error adding view_count column: " . $conn->error . "<br>";
}

$conn->close();
?>
