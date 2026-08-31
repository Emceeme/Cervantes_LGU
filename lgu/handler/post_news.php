<?php
session_start();
require_once '../../config/security.php';
require_once '../../config/upload_helper.php';
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

    // Validate required fields
    $required_fields = ['title', 'content'];
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            header("Location: ../newsfeed.php?error=Missing+required+field:+" . urlencode($field));
            exit();
        }
    }

    $user_id = $_SESSION['id'];

    $title = sanitizeInput($_POST['title']);
    $content = sanitizeInput($_POST['content']);

    // Validate title length
    if (strlen($title) < 5 || strlen($title) > 200) {
        header("Location: ../newsfeed.php?error=Title+must+be+between+5+and+200+characters");
        exit();
    }

    // Validate content length
    if (strlen($content) < 10 || strlen($content) > 10000) {
        header("Location: ../newsfeed.php?error=Content+must+be+between+10+and+10000+characters");
        exit();
    }

$image = '';

if (!empty($_FILES['image']['name'])) {
    // Validate file size (5MB max)
    if ($_FILES['image']['size'] > 5 * 1024 * 1024) {
        header("Location: ../newsfeed.php?error=Image+size+exceeds+5MB+limit");
        exit();
    }

    // Validate file type using MIME type
    $allowed_types = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif', 'image/webp'];
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime_type = $finfo->file($_FILES['image']['tmp_name']);
    
    if (!in_array($mime_type, $allowed_types)) {
        header("Location: ../newsfeed.php?error=Invalid+image+type.+Only+JPG,+PNG,+GIF,+and+WebP+allowed");
        exit();
    }

    // Validate file extension
    $file_extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    if (!in_array($file_extension, $allowed_extensions)) {
        header("Location: ../newsfeed.php?error=Invalid+image+extension");
        exit();
    }

    // Upload using UploadHelper
    $image = UploadHelper::uploadFile($_FILES['image'], 'news');
    
    if (!$image) {
        header("Location: ../newsfeed.php?error=Image+upload+failed");
        exit();
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