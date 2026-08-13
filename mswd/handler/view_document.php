<?php
session_start();
require_once __DIR__ . '/../../config/security.php';
require_once __DIR__ . '/../../config/db.php';

setSecurityHeaders();

// Access Control - MSWD Department only
if (!isset($_SESSION['department']) || $_SESSION['department'] !== 'MSWD') {
    logSecurityEvent('unauthorized_document_access', $_SESSION['id'] ?? null, ['endpoint' => 'view_document']);
    http_response_code(403);
    die("Access Denied.");
}

$doc_id = intval($_GET['id'] ?? 0);

if ($doc_id > 0) {
    $stmt = $conn->prepare("SELECT file_path, file_name FROM application_documents WHERE id = ?");
    $stmt->bind_param("i", $doc_id);
    $stmt->execute();
    $doc = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($doc) {
        $full_path = __DIR__ . '/../../' . $doc['file_path'];

        if (file_exists($full_path)) {
            $mime_type = mime_content_type($full_path);
            header("Content-Type: " . $mime_type);
            header("Content-Disposition: inline; filename=\"" . basename($doc['file_name']) . "\"");
            readfile($full_path);
            exit();
        }
    }
}

http_response_code(404);
echo "Document not found.";
