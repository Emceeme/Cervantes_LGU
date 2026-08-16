<?php
// Migration: Add applicant_id column to applications table
// PostgreSQL-compatible version
// This allows linking applications to user accounts for history tracking

require_once __DIR__ . '/../config/db.php';

// Detect database type
$is_postgres = (strpos(getenv('DATABASE_URL') ?? '', 'postgres') !== false) || 
               ($conn instanceof PDO);

// Add applicant_id column
if ($is_postgres) {
    // PostgreSQL syntax
    $sql = "ALTER TABLE applications 
            ADD COLUMN IF NOT EXISTS applicant_id INTEGER NULL";
} else {
    // MySQL syntax
    $sql = "ALTER TABLE applications 
            ADD COLUMN applicant_id INT NULL AFTER tracking_number,
            ADD INDEX idx_applicant_id (applicant_id)";
}

if ($conn->query($sql) !== FALSE) {
    echo "Column 'applicant_id' added to applications table successfully.<br>";
} else {
    // Check if column already exists
    if ($conn instanceof PDO) {
        $error = $conn->errorInfo();
        $error_msg = $error[2] ?? 'Unknown error';
        if (strpos($error_msg, "column") !== false || strpos($error_msg, "already exists") !== false) {
            echo "Column 'applicant_id' already exists in applications table.<br>";
        } else {
            die("Error adding column: " . $error_msg);
        }
    } else {
        if (strpos($conn->error, "Duplicate column name") !== false || 
            strpos($conn->error, "column") !== false) {
            echo "Column 'applicant_id' already exists in applications table.<br>";
        } else {
            die("Error adding column: " . $conn->error);
        }
    }
}

// Create index for PostgreSQL separately
if ($is_postgres) {
    $sql_index = "CREATE INDEX IF NOT EXISTS idx_applicant_id ON applications(applicant_id)";
    if ($conn->query($sql_index) === TRUE) {
        echo "Index on applicant_id created successfully.<br>";
    }
}

// Add foreign key constraint
if ($is_postgres) {
    // PostgreSQL syntax - check if constraint exists first
    $check_constraint = $conn->query("SELECT EXISTS (
        SELECT 1 FROM information_schema.table_constraints 
        WHERE constraint_name = 'fk_applicant_id' 
        AND table_name = 'applications'
    )");
    $constraint_exists = $check_constraint->fetchColumn();
    
    if (!$constraint_exists) {
        $sql_fk = "ALTER TABLE applications 
                   ADD CONSTRAINT fk_applicant_id 
                   FOREIGN KEY (applicant_id) REFERENCES users(id) 
                   ON DELETE SET NULL";
        
        if ($conn->query($sql_fk) !== FALSE) {
            echo "Foreign key constraint added successfully.<br>";
        } else {
            $error = $conn->errorInfo();
            $error_msg = $error[2] ?? 'Unknown error';
            echo "Warning: Could not add foreign key constraint: " . $error_msg . "<br>";
        }
    } else {
        echo "Foreign key constraint already exists.<br>";
    }
} else {
    // MySQL syntax
    $sql_fk = "ALTER TABLE applications 
               ADD CONSTRAINT fk_applicant_id 
               FOREIGN KEY (applicant_id) REFERENCES users(id) 
               ON DELETE SET NULL";
    
    if ($conn->query($sql_fk) !== FALSE) {
        echo "Foreign key constraint added successfully.<br>";
    } else {
        // Check if constraint already exists
        if (strpos($conn->error, "Duplicate foreign key constraint") !== false ||
            strpos($conn->error, "constraint") !== false) {
            echo "Foreign key constraint already exists.<br>";
        } else {
            echo "Warning: Could not add foreign key constraint: " . $conn->error . "<br>";
        }
    }
}

if (!($conn instanceof PDO)) {
    $conn->close();
}
echo "<br>Migration completed successfully!";
?>
