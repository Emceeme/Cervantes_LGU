<?php
session_start();
require_once '../../config/security.php';
include '../../config/db.php';

// Set security headers
setSecurityHeaders();

// Valid categories
$valid_categories = [
    'philgeps' => 'PhilGEPS',
    'bids_awards' => 'Bids and Awards',
    'invitation_to_bid' => 'Invitation to Bid',
    'bid_bulletin' => 'Bid Bulletin',
    'notice_of_award' => 'Notice of Award',
    'notice_to_proceed' => 'Notice to Proceed'
];

if($_SERVER['REQUEST_METHOD'] == 'POST'){
    
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        logSecurityEvent('csrf_validation_failed', $_SESSION['id'] ?? null, ['endpoint' => 'upload_procurement']);
        header("Location: ../procurement.php?error=Security+validation+failed");
        exit();
    }

    // Validate category
    $category = $_POST['category'] ?? '';
    if (!array_key_exists($category, $valid_categories)) {
        logSecurityEvent('invalid_category', $_SESSION['id'] ?? null, ['category' => $category, 'endpoint' => 'upload_procurement']);
        header("Location: ../procurement.php?error=Invalid+category");
        exit();
    }

    // Validate and process custom_date
    $custom_date = $_POST['custom_date'] ?? '';
    if (empty($custom_date)) {
        $custom_date = date('Y-m-d');
    } else {
        // Validate date format
        $date_check = DateTime::createFromFormat('Y-m-d', $custom_date);
        if (!$date_check || $date_check->format('Y-m-d') !== $custom_date) {
            header("Location: ../procurement.php?error=Invalid+date+format");
            exit();
        }
    }

    $title = $_POST['title'] ?? '';
    $status = $_POST['status'] ?? 'OPEN';

    // File upload validation
    if (!isset($_FILES['document_file']) || $_FILES['document_file']['error'] !== UPLOAD_ERR_OK) {
        header("Location: ../procurement.php?error=File+upload+failed");
        exit();
    }

    $file = $_FILES['document_file'];
    $tmp = $file['tmp_name'];
    $file_size = $file['size'];
    $file_name = $file['name'];

    // File size validation (10MB max)
    if ($file_size > 10 * 1024 * 1024) {
        header("Location: ../procurement.php?error=File+too+large+(max+10MB)");
        exit();
    }

    // MIME type validation using finfo
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $tmp);
    finfo_close($finfo);

    $allowed_mime_types = [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
    ];

    if (!in_array($mime_type, $allowed_mime_types)) {
        logSecurityEvent('invalid_file_type', $_SESSION['id'] ?? null, ['mime_type' => $mime_type, 'endpoint' => 'upload_procurement']);
        header("Location: ../procurement.php?error=Invalid+file+type+(only+PDF,+DOC,+DOCX+allowed)");
        exit();
    }

    // Generate secure random filename
    $filename = bin2hex(random_bytes(16)) . '_' . time();

    // Get file extension
    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    $filename .= '.' . $file_ext;

    $folder = "../uploads/procurement/";

    if(!is_dir($folder)){
        mkdir($folder, 0755, true);
    }

    // Move uploaded file
    if (!move_uploaded_file($tmp, $folder . $filename)) {
        logError('File move failed', ['filename' => $filename, 'folder' => $folder]);
        header("Location: ../procurement.php?error=Failed+to+save+file");
        exit();
    }

    // Insert into database
    $stmt = $conn->prepare("
        INSERT INTO procurement_posts
        (title, category, file_path, original_file_name, status, custom_date)
        VALUES (?,?,?,?,?,?)
    ");

    if (!$stmt) {
        if ($conn instanceof PDO) {
            logError('Database prepare failed', ['error' => implode(", ", $conn->errorInfo())]);
        } else {
            logError('Database prepare failed', ['error' => $conn->error]);
        }
        header("Location: ../procurement.php?error=Database+error");
        exit();
    }

    if ($conn instanceof PDO) {
        // PostgreSQL/PDO
        $stmt->execute([$title, $category, $filename, $file_name, $status, $custom_date]);
    } else {
        // MySQLi
        $stmt->bind_param("ssssss",
            $title,
            $category,
            $filename,
            $file_name,
            $status,
            $custom_date
        );
        if (!$stmt->execute()) {
            logError('Database execute failed', ['error' => $stmt->error]);
            header("Location: ../procurement.php?error=Failed+to+save+record");
            exit();
        }
        $stmt->close();
    }

    // Redirect to admin procurement list page with success message
    header("Location: ../procurement.php?status=success");
    exit();
}
?>