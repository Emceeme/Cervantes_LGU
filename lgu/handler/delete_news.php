<?php
session_start();
require_once '../../config/security.php';
include '../../config/db.php';

$id = $_GET['id'];

$stmt = $conn->prepare("
DELETE FROM news_posts
WHERE id=?
");

if ($conn instanceof PDO) {
    // PostgreSQL/PDO
    $stmt->execute([$id]);
} else {
    // MySQLi
    $stmt->bind_param("i",$id);
    $stmt->execute();
    $stmt->close();
}

header("Location: ../newsfeed.php");
exit();
?>