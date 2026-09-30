<?php
session_start();
require_once __DIR__ . '/../../config/security.php';
require_once __DIR__ . '/../../config/db.php';

setSecurityHeaders();

// Security check: must be logged in
if (!isset($_SESSION['id'])) {
    logSecurityEvent('unauthorized_access', null, ['endpoint' => 'view_document', 'reason' => 'not_logged_in']);
    header('Location: /login.php?unauthorized=1');
    exit();
}

$doc_id = $_GET['id'] ?? 0;

if (!$doc_id || !is_numeric($doc_id)) {
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
    $stmt->execute([$doc_id]);
    $doc = $stmt->fetch();
} else {
    $stmt->bind_param("i", $doc_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $doc = $result ? $result->fetch_assoc() : false;
    $stmt->close();
}

if (!$doc) {
    logSecurityEvent('document_not_found', $_SESSION['id'], ['document_id' => $doc_id]);
    http_response_code(404);
    echo 'Document not found';
    exit();
}

// Authorization check: user must be the applicant, assigned worker, or MSWD worker/admin
$user_id = $_SESSION['id'];
$user_role = $_SESSION['role'] ?? '';
$user_department = $_SESSION['department'] ?? '';

$is_owner = ($doc['applicant_id'] == $user_id);
$is_assigned_worker = ($doc['assigned_worker_id'] == $user_id);
$is_mswd_worker = ($user_role === 'MSWD_WORKER' || $user_department === 'MSWD');
$is_super_admin = ($user_role === 'SUPER_ADMIN');

if (!$is_owner && !$is_assigned_worker && !$is_mswd_worker && !$is_super_admin) {
    logSecurityEvent('unauthorized_access', $user_id, ['endpoint' => 'view_document', 'document_id' => $doc_id]);
    http_response_code(403);
    echo 'Access denied';
    exit();
}

// Get file path outside web root
$file_path = __DIR__ . '/../../../uploads/' . $doc['file_path'];

if (!file_exists($file_path)) {
    logSecurityEvent('document_file_not_found', $user_id, ['document_id' => $doc_id, 'file_path' => $doc['file_path']]);
    http_response_code(404);
    echo 'File not found on server';
    exit();
}

// Log view
logSecurityEvent('document_viewed', $user_id, ['document_id' => $doc_id]);

header('Content-Type: ' . $doc['mime_type']);
header('Content-Disposition: inline; filename="' . htmlspecialchars($doc['file_name']) . '"');
header('Content-Length: ' . filesize($file_path));
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');
readfile($file_path);
exit();
?>
