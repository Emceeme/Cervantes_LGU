<?php
include 'config/db.php';

echo "=== Procurement Posts Table Structure ===\n\n";
$result = $conn->query("DESCRIBE procurement_posts");
while($row = $result->fetch_assoc()) {
    echo $row['Field'] . ' - ' . $row['Type'] . ' - ' . $row['Null'] . PHP_EOL;
}

echo "\n=== Testing INSERT statement ===\n\n";
$stmt = $conn->prepare("INSERT INTO procurement_posts (title, category, file_path, original_file_name, status, custom_date, user_id) VALUES (?,?,?,?,?,?,?)");
if (!$stmt) {
    echo "Prepare failed: " . $conn->error . "\n";
} else {
    echo "Prepare succeeded\n";
}
?>
