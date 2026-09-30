<?php
session_start();
require_once __DIR__ . '/../../config/security.php';
require_once __DIR__ . '/../../config/db.php';

setSecurityHeaders();

// Security check: must be logged in
if (!isset($_SESSION['id'])) {
    logSecurityEvent('unauthorized_access', null, ['endpoint' => 'download_document', 'reason' => 'not_logged_in']);
    header('Location: /login.php?unauthorized=1');
    exit();
}

// Get document ID
$document_id = $_GET['id'] ?? null;

if (!$document_id || !is_numeric($document_id)) {
    http_response_code(400);
    echo 'Invalid document ID';
    exit();
}

// Fetch document with application info
$stmt = $conn->prepare("
    SELECT d.*, a.applicant_id, a.assigned_worker_id
    FROM application_documents d
    JOIN applications a ON d.application_id = a.id
    WHERE d.id = ?
");

if ($conn instanceof PDO) {
    $stmt->execute([$document_id]);
    $document = $stmt->fetch();
} else {
    $stmt->bind_param("i", $document_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $document = $result->fetch_assoc();
    $stmt->close();
}

if (!$document) {
    http_response_code(404);
    echo 'Document not found';
    exit();
}

// Authorization check: user must be the applicant, assigned worker, or MSWD worker/admin
$user_id = $_SESSION['id'];
$user_role = $_SESSION['role'] ?? '';
$user_department = $_SESSION['department'] ?? '';

$is_owner = ($document['applicant_id'] == $user_id);
$is_assigned_worker = ($document['assigned_worker_id'] == $user_id);
$is_mswd_worker = ($user_role === 'MSWD_WORKER' || $user_department === 'MSWD');
$is_super_admin = ($user_role === 'SUPER_ADMIN');

if (!$is_owner && !$is_assigned_worker && !$is_mswd_worker && !$is_super_admin) {
    logSecurityEvent('unauthorized_access', $user_id, ['endpoint' => 'download_document', 'document_id' => $document_id]);
    http_response_code(403);
    echo 'Access denied';
    exit();
}

// Get file path outside web root
$file_path = __DIR__ . '/../../../uploads/' . $document['file_path'];

if (!file_exists($file_path)) {
    http_response_code(404);
    echo 'File not found on server';
    exit();
}

// Log download
logSecurityEvent('document_downloaded', $user_id, ['document_id' => $document_id]);

// Set headers for download
header('Content-Type: ' . $document['mime_type']);
header('Content-Disposition: attachment; filename="' . htmlspecialchars($document['file_name']) . '"');
header('Content-Length: ' . filesize($file_path));
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Output file
readfile($file_path);
exit();
?>
