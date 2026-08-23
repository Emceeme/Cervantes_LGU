<?php
session_start();
require_once '../config/security.php';
include '../config/db.php';

// Set security headers
setSecurityHeaders();

// SECURITY GUARD: Restrict access to Mayor's Office, LGU departments & Super Admins only
$department = html_entity_decode($_SESSION['department'] ?? '', ENT_QUOTES);
if (!isset($_SESSION['role']) || ($department !== "Mayor's Office" && $department !== 'Mayor Office' && $department !== 'LGU' && $_SESSION['role'] !== 'SUPER_ADMIN')) {
    logSecurityEvent('unauthorized_access', $_SESSION['id'] ?? null, ['endpoint' => 'update_scholarship_status', 'department' => $department]);
    header('Location: /login.php?unauthorized=1');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die(json_encode(['success' => false, 'error' => 'Invalid request method']));
}

$id = intval($_POST['id'] ?? 0);
$status = $_POST['status'] ?? '';
$notes = $_POST['notes'] ?? '';

if (!$id || !in_array($status, ['PENDING', 'UNDER_REVIEW', 'APPROVED', 'REJECTED'])) {
    die(json_encode(['success' => false, 'error' => 'Invalid parameters']));
}

$stmt = $conn->prepare("
    UPDATE scholarship_applications 
    SET status = ?, admin_notes = ?, reviewed_at = NOW(), reviewed_by = ?
    WHERE id = ?
");

if ($conn instanceof PDO) {
    // PostgreSQL/PDO
    if ($stmt->execute([$status, $notes, $_SESSION['id'], $id])) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => implode(', ', $stmt->errorInfo())]);
    }
} else {
    // MySQLi
    $stmt->bind_param("ssii", $status, $notes, $_SESSION['id'], $id);
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => $stmt->error]);
    }
    $stmt->close();
    $conn->close();
}
?>
