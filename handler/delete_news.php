<?php
require '../config/auth.php';
require_login('../login.php');
verify_csrf();
include '../config/db.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: ../lgu/newsfeed.php?error=invalid");
    exit();
}

$id = (int) $_GET['id'];

$stmt = $conn->prepare("
DELETE FROM news_posts
WHERE id=?
");

$stmt->bind_param("i",$id);
$stmt->execute();

header("Location: ../lgu/newsfeed.php");
exit();
?>