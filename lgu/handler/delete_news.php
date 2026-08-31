<?php
session_start();
require_once '../../config/security.php';
require_once '../../config/upload_helper.php';
include '../../config/db.php';

// Set security headers
setSecurityHeaders();

$id = intval($_GET['id'] ?? 0);

if (!$id) {
    header("Location: ../newsfeed.php?error=Invalid+ID");
    exit();
}

// Get the image path before deleting
$stmt = $conn->prepare("SELECT image FROM news_posts WHERE id = ?");

if ($conn instanceof PDO) {
    // PostgreSQL/PDO
    $stmt->execute([$id]);
    $result = $stmt->fetchAll();
    
    if (count($result) > 0) {
        $image = $result[0]['image'];
    }
} else {
    // MySQLi
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $image = $row['image'];
    }
    $stmt->close();
}

// Delete from database
$stmt = $conn->prepare("DELETE FROM news_posts WHERE id = ?");

if ($conn instanceof PDO) {
    // PostgreSQL/PDO
    $stmt->execute([$id]);
} else {
    // MySQLi
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
}

// Delete the image file using UploadHelper
if (!empty($image)) {
    UploadHelper::deleteFile($image, 'news');
}

header("Location: ../newsfeed.php");
exit();
?>