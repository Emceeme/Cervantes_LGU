<?php
require_once 'config/db.php';

echo "=== Checking procurement_posts records ===\n\n";
$result = $conn->query("SELECT * FROM procurement_posts");
if ($result) {
    $count = $result->num_rows;
    echo "Total records: " . $count . "\n\n";
    
    if ($count > 0) {
        while($row = $result->fetch_assoc()) {
            echo "ID: " . $row['id'] . "\n";
            echo "Title: " . $row['title'] . "\n";
            echo "Category: " . ($row['category'] ?? 'NULL') . "\n";
            echo "File: " . $row['file_path'] . "\n";
            echo "Status: " . $row['status'] . "\n";
            echo "Custom Date: " . ($row['custom_date'] ?? 'NULL') . "\n";
            echo "Created: " . $row['created_at'] . "\n";
            echo "---\n";
        }
    }
} else {
    echo "Query failed: " . $conn->error . "\n";
}
?>
