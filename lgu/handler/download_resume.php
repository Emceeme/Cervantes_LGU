<?php
session_start();
require_once '../../config/security.php';
require_once '../../config/upload_helper.php';
require_once '../../config/db.php';
require_once '../../config/app_config.php';

// Set security headers
setSecurityHeaders();

$id = intval($_GET['id'] ?? 0);

if (!$id) {
    die("Invalid applicant ID");
}

// Get applicant info
$stmt = $conn->prepare("SELECT resume, full_name FROM applicants WHERE id = ?");

if ($conn instanceof PDO) {
    $stmt->execute([$id]);
    $row = $stmt->fetch();
} else {
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
}

if (!$row || empty($row['resume'])) {
    die("Resume not found");
}

$resume_file = $row['resume'];
$file_path = UploadHelper::getUploadDir('resumes') . '/' . $resume_file;

if (!file_exists($file_path)) {
    die("File not found: " . htmlspecialchars($resume_file));
}

// Get file extension
$file_extension = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));

// Set appropriate content type
$content_types = [
    'pdf' => 'application/pdf',
    'doc' => 'application/msword',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
];

$content_type = $content_types[$file_extension] ?? 'application/octet-stream';

// Set download filename
$download_name = $row['full_name'] . '_resume.' . $file_extension;
$download_name = preg_replace('/[^a-zA-Z0-9._-]/', '_', $download_name);

// Serve file
header('Content-Type: ' . $content_type);
header('Content-Disposition: attachment; filename="' . $download_name . '"');
header('Content-Length: ' . filesize($file_path));
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

readfile($file_path);
exit();
?>
