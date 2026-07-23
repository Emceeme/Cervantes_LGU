<?php
include '../config/db.php';

start_session();

$user_id = $_SESSION['id'];

$title = $_POST['title'];
$content = $_POST['content'];

$image = save_uploaded_file('image', "../uploads/news/");

if ($image === false) {
    die("Image upload failed.");
}

if ($image === null) {
    $image = '';
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

redirect('../lgu/newsfeed.php');
?>
