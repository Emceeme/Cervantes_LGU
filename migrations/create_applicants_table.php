<?php
require_once __DIR__ . '/../config/db.php';

// Detect database type
$is_postgres = (strpos(getenv('DATABASE_URL') ?? '', 'postgres') !== false) || 
               ($conn instanceof PDO);

// Create applicants table
if ($is_postgres) {
    // PostgreSQL syntax
    $sql = "CREATE TABLE IF NOT EXISTS applicants (
        id SERIAL PRIMARY KEY,
        job_id INTEGER,
        full_name VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL,
        phone VARCHAR(50) NOT NULL,
        message TEXT,
        resume VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    // Create indexes separately for PostgreSQL
    $indexes = [
        "CREATE INDEX IF NOT EXISTS idx_applicants_job_id ON applicants(job_id)",
        "CREATE INDEX IF NOT EXISTS idx_applicants_created_at ON applicants(created_at)"
    ];
} else {
    // MySQL syntax
    $sql = "CREATE TABLE IF NOT EXISTS applicants (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        job_id INT(11),
        full_name VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL,
        phone VARCHAR(50) NOT NULL,
        message TEXT,
        resume VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_job_id (job_id),
        INDEX idx_created_at (created_at)
    )";
    $indexes = [];
}

if ($conn->query($sql)) {
    echo "✅ applicants table created successfully.<br>";
} else {
    if ($conn instanceof PDO) {
        $error = $conn->errorInfo();
        echo "❌ Error creating applicants table: " . ($error[2] ?? 'Unknown error') . "<br>";
    } else {
        echo "❌ Error creating applicants table: " . $conn->error . "<br>";
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
?>
