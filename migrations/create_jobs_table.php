<?php
// Migration: Create jobs table
// PostgreSQL-compatible version

require_once __DIR__ . '/../config/db.php';

// Detect database type
$is_postgres = (strpos(getenv('DATABASE_URL') ?? '', 'postgres') !== false) || 
               ($conn instanceof PDO);

// Create jobs table
if ($is_postgres) {
    // PostgreSQL syntax
    $sql = "CREATE TABLE IF NOT EXISTS jobs (
        id SERIAL PRIMARY KEY,
        job_title VARCHAR(255) NOT NULL,
        department VARCHAR(100) NOT NULL,
        description TEXT NOT NULL,
        employment_type VARCHAR(50) NOT NULL DEFAULT 'Full-time',
        location VARCHAR(255) NOT NULL,
        salary VARCHAR(100) NOT NULL,
        status VARCHAR(20) NOT NULL DEFAULT 'OPEN',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    // Create indexes separately for PostgreSQL
    $indexes = [
        "CREATE INDEX IF NOT EXISTS idx_jobs_status ON jobs(status)",
        "CREATE INDEX IF NOT EXISTS idx_jobs_department ON jobs(department)",
        "CREATE INDEX IF NOT EXISTS idx_jobs_employment_type ON jobs(employment_type)"
    ];
} else {
    // MySQL syntax
    $sql = "CREATE TABLE IF NOT EXISTS jobs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        job_title VARCHAR(255) NOT NULL,
        department VARCHAR(100) NOT NULL,
        description TEXT NOT NULL,
        employment_type VARCHAR(50) NOT NULL DEFAULT 'Full-time',
        location VARCHAR(255) NOT NULL,
        salary VARCHAR(100) NOT NULL,
        status VARCHAR(20) NOT NULL DEFAULT 'OPEN',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_status (status),
        INDEX idx_department (department),
        INDEX idx_employment_type (employment_type)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $indexes = [];
}

if ($conn->query($sql)) {
    echo "✓ jobs table created successfully.<br>";
} else {
    if ($conn instanceof PDO) {
        $error = $conn->errorInfo();
        die("Error creating jobs table: " . ($error[2] ?? 'Unknown error'));
    } else {
        die("Error creating jobs table: " . $conn->error);
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
