<?php
session_start();
require_once '../../config/security.php';
require_once '../../config/upload_helper.php';
include '../../config/db.php';

// Set security headers
setSecurityHeaders();

// SECURITY GUARD: Restrict access to Mayor's Office, LGU departments & Super Admins only
$department = html_entity_decode($_SESSION['department'] ?? '', ENT_QUOTES);
if (!isset($_SESSION['role']) || ($department !== "Mayor's Office" && $department !== 'Mayor Office' && $department !== 'LGU' && $_SESSION['role'] !== 'SUPER_ADMIN')) {
    logSecurityEvent('unauthorized_access', $_SESSION['id'] ?? null, ['endpoint' => 'delete_applicant', 'department' => $department]);
    header('Location: /login.php?unauthorized=1');
    exit();
}

$id = intval($_GET['id'] ?? 0);

if (!$id) {
    header("Location: ../applicants.php?error=Invalid+ID");
    exit();
}

// Get the resume path before deleting
$stmt = $conn->prepare("SELECT resume FROM applicants WHERE id = ?");

if ($conn instanceof PDO) {
    // PostgreSQL/PDO
    $stmt->execute([$id]);
    $result = $stmt->fetchAll();
    
    if (count($result) > 0) {
        $resume = $result[0]['resume'];
    }
} else {
    // MySQLi
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $resume = $row['resume'];
    }
    $stmt->close();
}

// Delete from database
$stmt = $conn->prepare("DELETE FROM applicants WHERE id = ?");

if ($conn instanceof PDO) {
    // PostgreSQL/PDO
    $stmt->execute([$id]);
} else {
    // MySQLi
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
}

// Delete the resume file using UploadHelper
if (!empty($resume)) {
    UploadHelper::deleteFile($resume, 'resumes');
}

header("Location: ../applicants.php?success=deleted");
exit();
?>
