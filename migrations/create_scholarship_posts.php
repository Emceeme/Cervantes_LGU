<?php
require_once './config/db.php';

// Detect database type
$is_postgres = (strpos(getenv('DATABASE_URL') ?? '', 'postgres') !== false) || 
               ($conn instanceof PDO);

// Create scholarship_posts table
if ($is_postgres) {
    // PostgreSQL syntax
    $sql = "CREATE TABLE IF NOT EXISTS scholarship_posts (
        id SERIAL PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        description TEXT NOT NULL,
        image VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    // Create index separately for PostgreSQL
    $index_sql = "CREATE INDEX IF NOT EXISTS idx_scholarship_posts_created_at ON scholarship_posts(created_at)";
} else {
    // MySQL syntax
    $sql = "CREATE TABLE IF NOT EXISTS scholarship_posts (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        description TEXT NOT NULL,
        image VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_created_at (created_at)
    )";
    $index_sql = null;
}

if ($conn->query($sql)) {
    echo "✅ scholarship_posts table created successfully.<br>";
} else {
    echo "❌ Error creating scholarship_posts table: " . $conn->error . "<br>";
}

// Create index for PostgreSQL
if ($index_sql) {
    $conn->query($index_sql);
}

$conn->close();
?>
