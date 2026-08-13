<?php
include '../../config/db.php';
include '../../config/app_config.php';

$id = intval($_GET['id'] ?? 0);

if (!$id) {
    die("Invalid file ID");
}

// Get file info
$stmt = $conn->prepare("SELECT file_path, original_file_name, view_count FROM procurement_posts WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("File not found");
}

$row = $result->fetch_assoc();
$stmt->close();

// Increment view count
$update_stmt = $conn->prepare("UPDATE procurement_posts SET view_count = view_count + 1 WHERE id = ?");
$update_stmt->bind_param("i", $id);
$update_stmt->execute();
$update_stmt->close();

// Serve file
$file_path = __DIR__ . '/../../lgu/uploads/procurement/' . $row['file_path'];
$original_name = $row['original_file_name'];

if (!file_exists($file_path)) {
    die("File not found on server");
}

// Get file extension
$file_extension = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));

// Set appropriate content type
$content_types = [
    'pdf' => 'application/pdf',
    'doc' => 'application/msword',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'xls' => 'application/vnd.ms-excel',
    'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png' => 'image/png',
];

$content_type = $content_types[$file_extension] ?? 'application/octet-stream';

// Set headers
header('Content-Type: ' . $content_type);
header('Content-Disposition: inline; filename="' . $original_name . '"');
header('Content-Length: ' . filesize($file_path));
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Output file
readfile($file_path);
exit();
?>
