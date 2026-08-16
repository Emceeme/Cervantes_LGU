<?php
include __DIR__ . '/../config/db.php';

// Check if table exists
if ($conn instanceof PDO) {
    // PostgreSQL
    $check = $conn->query("SELECT EXISTS (SELECT FROM information_schema.tables WHERE table_name = 'news_posts')");
    $exists = $check->fetchColumn();
} else {
    // MySQLi
    $check = $conn->query("SHOW TABLES LIKE 'news_posts'");
    $exists = $check->num_rows > 0;
}

if ($exists) {
    echo "Table 'news_posts' already exists. Skipping creation.\n";
    exit;
}

// Create table
if ($conn instanceof PDO) {
    // PostgreSQL
    $sql = "
    CREATE TABLE news_posts (
        id SERIAL PRIMARY KEY,
        user_id INTEGER NOT NULL,
        title VARCHAR(255) NOT NULL,
        content TEXT NOT NULL,
        image VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );
    
    CREATE INDEX idx_news_posts_user_id ON news_posts(user_id);
    CREATE INDEX idx_news_posts_created_at ON news_posts(created_at);
    ";
    
    try {
        $conn->exec($sql);
        echo "Table 'news_posts' created successfully for PostgreSQL.\n";
    } catch (PDOException $e) {
        echo "Error creating table: " . implode(", ", $e->errorInfo()) . "\n";
    }
} else {
    // MySQLi
    $sql = "
    CREATE TABLE news_posts (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        user_id INT(11) NOT NULL,
        title VARCHAR(255) NOT NULL,
        content TEXT NOT NULL,
        image VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_user_id (user_id),
        INDEX idx_created_at (created_at)
    )
    ";
    
    if ($conn->query($sql)) {
        echo "Table 'news_posts' created successfully for MySQL.\n";
    } else {
        echo "Error creating table: " . $conn->error . "\n";
    }
}

if (!($conn instanceof PDO)) {
    $conn->close();
}
