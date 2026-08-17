<?php
session_start();
require_once '../../config/security.php';
include '../../config/db.php';

// Set security headers
setSecurityHeaders();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        logSecurityEvent('csrf_validation_failed', $_SESSION['id'] ?? null, ['endpoint' => 'post_news']);
        header("Location: ../newsfeed.php?error=Security+validation+failed");
        exit();
    }

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

if ($conn instanceof PDO) {
    // PostgreSQL/PDO
    $stmt->execute([$user_id, $title, $content, $image]);
} else {
    // MySQLi
    $stmt->bind_param(
        "isss",
        $user_id,
        $title,
        $content,
        $image
    );
    $stmt->execute();
    $stmt->close();
}

    header("Location: ../newsfeed.php");
exit();
}
?>