<?php
require_once 'config/db.php';

echo "=== Checking news_posts table ===\n\n";

$stmt = $conn->query("SELECT id, title, image FROM news_posts LIMIT 5");

if ($conn instanceof PDO) {
    $rows = $stmt->fetchAll();
    foreach ($rows as $r) {
        echo "ID: " . $r['id'] . "\n";
        echo "Title: " . $r['title'] . "\n";
        echo "Image: " . $r['image'] . "\n";
        echo "File exists: " . (file_exists('lgu/uploads/news/' . $r['image']) ? 'YES' : 'NO') . "\n";
        echo "---\n";
    }
} else {
    $result = $stmt->get_result();
    while ($r = $result->fetch_assoc()) {
        echo "ID: " . $r['id'] . "\n";
        echo "Title: " . $r['title'] . "\n";
        echo "Image: " . $r['image'] . "\n";
        echo "File exists: " . (file_exists('lgu/uploads/news/' . $r['image']) ? 'YES' : 'NO') . "\n";
        echo "---\n";
    }
}

if (!($conn instanceof PDO)) {
    $conn->close();
}
?>
