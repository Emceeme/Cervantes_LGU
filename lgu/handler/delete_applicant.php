<?php
session_start();
require_once '../../config/security.php';
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

// Delete applicant
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

header("Location: ../applicants.php?success=deleted");
exit();
?>
