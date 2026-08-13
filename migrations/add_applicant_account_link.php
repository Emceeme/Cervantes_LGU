<?php
// Migration: Add applicant_id column to applications table
// This allows linking applications to user accounts for history tracking

include '../config/db.php';

// Add applicant_id column
$sql = "ALTER TABLE applications 
        ADD COLUMN applicant_id INT NULL AFTER tracking_number,
        ADD INDEX idx_applicant_id (applicant_id)";

if ($conn->query($sql) === TRUE) {
    echo "Column 'applicant_id' added to applications table successfully.<br>";
} else {
    // Check if column already exists
    if (strpos($conn->error, "Duplicate column name") !== false) {
        echo "Column 'applicant_id' already exists in applications table.<br>";
    } else {
        die("Error adding column: " . $conn->error);
    }
}

// Add foreign key constraint
$sql_fk = "ALTER TABLE applications 
           ADD CONSTRAINT fk_applicant_id 
           FOREIGN KEY (applicant_id) REFERENCES users(id) 
           ON DELETE SET NULL";

if ($conn->query($sql_fk) === TRUE) {
    echo "Foreign key constraint added successfully.<br>";
} else {
    // Check if constraint already exists
    if (strpos($conn->error, "Duplicate foreign key constraint") !== false) {
        echo "Foreign key constraint already exists.<br>";
    } else {
        echo "Warning: Could not add foreign key constraint: " . $conn->error . "<br>";
    }
}

$conn->close();
echo "<br>Migration completed successfully!";
?>
