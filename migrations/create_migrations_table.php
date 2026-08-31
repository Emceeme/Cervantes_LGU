<?php
require_once __DIR__ . '/../config/db.php';

// Detect database type
$is_postgres = (strpos(getenv('DATABASE_URL') ?? '', 'postgres') !== false) || 
               ($conn instanceof PDO);

// Create migrations tracking table
if ($is_postgres) {
    // PostgreSQL syntax
    $sql = "CREATE TABLE IF NOT EXISTS schema_migrations (
        id SERIAL PRIMARY KEY,
        migration_name VARCHAR(255) NOT NULL UNIQUE,
        executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    // Create index separately for PostgreSQL
    $indexes = [
        "CREATE INDEX IF NOT EXISTS idx_schema_migrations_migration_name ON schema_migrations(migration_name)",
        "CREATE INDEX IF NOT EXISTS idx_schema_migrations_executed_at ON schema_migrations(executed_at)"
    ];
} else {
    // MySQL syntax
    $sql = "CREATE TABLE IF NOT EXISTS schema_migrations (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        migration_name VARCHAR(255) NOT NULL UNIQUE,
        executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_migration_name (migration_name),
        INDEX idx_executed_at (executed_at)
    )";
    $indexes = [];
}

if ($conn->query($sql)) {
    echo "✅ schema_migrations table created successfully.<br>";
} else {
    if ($conn instanceof PDO) {
        $error = $conn->errorInfo();
        echo "❌ Error creating schema_migrations table: " . ($error[2] ?? 'Unknown error') . "<br>";
    } else {
        echo "❌ Error creating schema_migrations table: " . $conn->error . "<br>";
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
