<?php
session_start();
require_once '../../config/security.php';
require_once '../../config/db.php';

// Set security headers
setSecurityHeaders();

// Check if user is logged in
if (!isset($_SESSION['id'])) {
    header("Location: ../login.php");
    exit();
}

// SECURITY GUARD: Restrict delete access to Mayor's Office, LGU departments & Super Admins only
$department = html_entity_decode($_SESSION['department'] ?? '', ENT_QUOTES);
if (!isset($_SESSION['role']) || ($department !== "Mayor's Office" && $department !== 'Mayor Office' && $department !== 'LGU' && $_SESSION['role'] !== 'SUPER_ADMIN')) {
    logSecurityEvent('unauthorized_delete_attempt', $_SESSION['id'] ?? null, ['endpoint' => 'delete_procurement', 'department' => $department]);
    header('Location: /login.php?unauthorized=1');
    exit();
}

// Check if ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: ../procurement.php?error=Invalid+ID");
    exit();
}

$id = $_GET['id'];

// Get the file path before deleting
$stmt = $conn->prepare("SELECT file_path FROM procurement_posts WHERE id = ?");

if ($conn instanceof PDO) {
    // PostgreSQL/PDO
    $stmt->execute([$id]);
    $result = $stmt->fetchAll();
    
    if (count($result) === 0) {
        header("Location: ../procurement.php?error=Post+not+found");
        exit();
    }
    
    $file_path = $result[0]['file_path'];
} else {
    // MySQLi
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        header("Location: ../procurement.php?error=Post+not+found");
        exit();
    }
    
    $row = $result->fetch_assoc();
    $file_path = $row['file_path'];
    $stmt->close();
}

// Delete from database
$stmt = $conn->prepare("DELETE FROM procurement_posts WHERE id = ?");

if ($conn instanceof PDO) {
    // PostgreSQL/PDO
    if ($stmt->execute([$id])) {
        // Delete the file from uploads folder
        $upload_dir = __DIR__ . '/../uploads/procurement/';
        if (file_exists($upload_dir . $file_path)) {
            unlink($upload_dir . $file_path);
        }
        
        header("Location: ../procurement.php?status=deleted");
    } else {
        header("Location: ../procurement.php?error=Failed+to+delete");
    }
} else {
    // MySQLi
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        // Delete the file from uploads folder
        $upload_dir = __DIR__ . '/../uploads/procurement/';
        if (file_exists($upload_dir . $file_path)) {
            unlink($upload_dir . $file_path);
        }
        
        header("Location: ../procurement.php?status=deleted");
    } else {
        header("Location: ../procurement.php?error=Failed+to+delete");
    }
    $stmt->close();
}
?>
