<?php
session_start();
include '../config/db.php';

$user_id = $_SESSION['id'];

$title = $_POST['title'];
$content = $_POST['content'];

$image = '';

if (!empty($_FILES['image']['name'])) {

    $image = time() . '_' . basename($_FILES['image']['name']);

    $uploadDir = "../uploads/news/";

    // Create the folder if it doesn't exist
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    // Upload the image
    if (!move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $image)) {
        die("Image upload failed.");
    }
}

$stmt = $conn->prepare("
INSERT INTO news_posts
(user_id,title,content,image)
VALUES (?,?,?,?)
");

$stmt->bind_param(
"isss",
$user_id,
$title,
$content,
$image
);

$stmt->execute();

header("Location: ../lgu/newsfeed.php");
exit();
?>