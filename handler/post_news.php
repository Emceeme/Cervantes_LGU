<?php
session_start();
include '../config/db.php';

if (!isset($_SESSION['id'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['id'];

$title = trim($_POST['title'] ?? '');
$content = trim($_POST['content'] ?? '');

if ($title === '' || $content === '') {
    header("Location: ../lgu/newsfeed.php?error=missing");
    exit();
}

$image = '';

if (!empty($_FILES['image']['name'])) {

    $image = time() . '_' . basename($_FILES['image']['name']);

    $uploadDir = "../uploads/news/";

    // Create the folder if it doesn't exist
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0777, true) && !is_dir($uploadDir)) {
        error_log("Failed to create upload directory: $uploadDir");
        header("Location: ../lgu/newsfeed.php?error=upload");
        exit();
    }

    // Upload the image
    if (!move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $image)) {
        error_log("News image upload failed: " . $_FILES['image']['name']);
        header("Location: ../lgu/newsfeed.php?error=upload");
        exit();
    }
}

try {
    $stmt = $conn->prepare("
    INSERT INTO news_posts
    (user_id,title,content,image)
    VALUES (?,?,?,?)
    ");

    $stmt->bind_param("isss", $user_id, $title, $content, $image);
    $stmt->execute();
} catch (mysqli_sql_exception $e) {
    error_log("Failed to insert news post: " . $e->getMessage());
    header("Location: ../lgu/newsfeed.php?error=save");
    exit();
}

header("Location: ../lgu/newsfeed.php?success=1");
exit();
?>
