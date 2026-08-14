<?php
// Migration: Create procurement_posts table
// PostgreSQL-compatible version

require_once __DIR__ . '/../config/db.php';

// Detect database type
$is_postgres = (strpos(getenv('DATABASE_URL') ?? '', 'postgres') !== false) || 
               ($conn instanceof PDO);

// Create procurement_posts table
if ($is_postgres) {
    // PostgreSQL syntax
    $sql = "CREATE TABLE IF NOT EXISTS procurement_posts (
        id SERIAL PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        description TEXT NOT NULL,
        category VARCHAR(50) DEFAULT 'philgeps',
        status VARCHAR(50) DEFAULT 'OPEN',
        custom_date DATE NULL,
        view_count INTEGER DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    // Create indexes separately for PostgreSQL
    $indexes = [
        "CREATE INDEX IF NOT EXISTS idx_procurement_status ON procurement_posts(status)",
        "CREATE INDEX IF NOT EXISTS idx_procurement_category ON procurement_posts(category)",
        "CREATE INDEX IF NOT EXISTS idx_procurement_custom_date ON procurement_posts(custom_date)",
        "CREATE INDEX IF NOT EXISTS idx_procurement_created_at ON procurement_posts(created_at)"
    ];
} else {
    // MySQL syntax
    $sql = "CREATE TABLE IF NOT EXISTS procurement_posts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        description TEXT NOT NULL,
        category VARCHAR(50) DEFAULT 'philgeps',
        status VARCHAR(50) DEFAULT 'OPEN',
        custom_date DATE NULL,
        view_count INT(11) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_status (status),
        INDEX idx_category (category),
        INDEX idx_custom_date (custom_date),
        INDEX idx_created_at (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $indexes = [];
}

if ($conn->query($sql)) {
    echo "✓ procurement_posts table created successfully.<br>";
} else {
    if ($conn instanceof PDO) {
        $error = $conn->errorInfo();
        die("Error creating procurement_posts table: " . ($error[2] ?? 'Unknown error'));
    } else {
        die("Error creating procurement_posts table: " . $conn->error);
    }
}

// Create indexes for PostgreSQL
foreach ($indexes as $index_sql) {
    $conn->query($index_sql);
}

// Only close connection for MySQLi (PDO doesn't have close())
if (!($conn instanceof PDO)) {
    $conn->close();
}
echo "<br>Migration completed successfully!";
?>
