<?php
include 'config/db.php';

echo "=== Procurement Posts Table Structure ===\n\n";
$result = $conn->query("DESCRIBE procurement_posts");
while($row = $result->fetch_assoc()) {
    echo $row['Field'] . ' - ' . $row['Type'] . ' - ' . $row['Null'] . PHP_EOL;
}
?>
