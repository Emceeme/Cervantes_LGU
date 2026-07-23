<?php
require '../config/auth.php';
require_login('../login.php');
verify_csrf();
include '../config/db.php';

$user_id = $_SESSION['id'];

$title = $_POST['title'];
$content = $_POST['content'];

$image = '';

if (!empty($_FILES['image']['name'])) {

    $safeName = validate_upload($_FILES['image'], ['png', 'jpg', 'jpeg', 'gif', 'webp']);
    if ($safeName === false) {
        http_response_code(400);
        die("Invalid or disallowed image type.");
    }

    $image = time() . '_' . $safeName;

    $uploadDir = "../uploads/news/";

    // Create the folder if it doesn't exist
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // Upload the image
    if (!move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $image)) {
        http_response_code(500);
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