<?php
session_start();
include '../config/db.php';

if (!isset($_SESSION['id'])) {
    header("Location: ../login.php");
    exit();
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: ../lgu/newsfeed.php?error=invalid");
    exit();
}

$id = (int) $_GET['id'];

try {
    $stmt = $conn->prepare("
    DELETE FROM news_posts
    WHERE id=?
    ");

    $stmt->bind_param("i", $id);
    $stmt->execute();
} catch (mysqli_sql_exception $e) {
    error_log("Failed to delete news post $id: " . $e->getMessage());
    header("Location: ../lgu/newsfeed.php?error=delete");
    exit();
}

header("Location: ../lgu/newsfeed.php?deleted=1");
exit();
?>
