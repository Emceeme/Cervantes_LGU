<?php
// Migration: Add file_path and original_file_name columns to procurement_posts table
// PostgreSQL-compatible version

require_once __DIR__ . '/../config/db.php';

// Detect database type
$is_postgres = (strpos(getenv('DATABASE_URL') ?? '', 'postgres') !== false) || 
               ($conn instanceof PDO);

// Add file_path column
if ($is_postgres) {
    // PostgreSQL syntax
    $sql1 = "ALTER TABLE procurement_posts ADD COLUMN IF NOT EXISTS file_path VARCHAR(255)";
    $sql2 = "ALTER TABLE procurement_posts ADD COLUMN IF NOT EXISTS original_file_name VARCHAR(255)";
    $sql3 = "ALTER TABLE procurement_posts ADD COLUMN IF NOT EXISTS award_winner VARCHAR(255)";
} else {
    // MySQL syntax
    $sql1 = "ALTER TABLE procurement_posts ADD COLUMN file_path VARCHAR(255)";
    $sql2 = "ALTER TABLE procurement_posts ADD COLUMN original_file_name VARCHAR(255)";
    $sql3 = "ALTER TABLE procurement_posts ADD COLUMN award_winner VARCHAR(255)";
}

// Execute migrations
foreach ([$sql1, $sql2, $sql3] as $sql) {
    $result = $conn->query($sql);
    if ($result) {
        echo "✓ Column added successfully.<br>";
    } else {
        if ($conn instanceof PDO) {
            $error = $conn->errorInfo();
            // Check if column already exists (PostgreSQL error message)
            if (strpos($error[2] ?? '', 'column') !== false && strpos($error[2] ?? '', 'already exists') !== false) {
                echo "✓ Column already exists.<br>";
            } else {
                echo "❌ Error: " . ($error[2] ?? 'Unknown error') . "<br>";
            }
        } else {
            // Check if column already exists (MySQL error message)
            if (strpos($conn->error, 'Duplicate column name') !== false) {
                echo "✓ Column already exists.<br>";
            } else {
                echo "❌ Error: " . $conn->error . "<br>";
            }
        }
    }
}

// Only close connection for MySQLi (PDO doesn't have close())
if (!($conn instanceof PDO)) {
    $conn->close();
}
echo "<br>Migration completed successfully!";
?>
