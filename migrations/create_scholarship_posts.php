<?php
require_once './config/db.php';

// Create scholarship_posts table
$sql = "CREATE TABLE IF NOT EXISTS scholarship_posts (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    image VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_created_at (created_at)
)";

if ($conn->query($sql)) {
    echo "✅ scholarship_posts table created successfully.<br>";
} else {
    echo "❌ Error creating scholarship_posts table: " . $conn->error . "<br>";
}

$conn->close();
?>
