<?php
session_start();
require_once '../../config/security.php';
require_once '../../config/db.php';

// Set security headers
setSecurityHeaders();

if($_SERVER['REQUEST_METHOD'] == 'POST'){
    
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        logSecurityEvent('csrf_validation_failed', $_SESSION['id'] ?? null, ['endpoint' => 'apply_job']);
        die("Security validation failed. Please try again.");
    }

    // Validate required fields
    $required_fields = ['job_id', 'full_name', 'email', 'phone', 'message'];
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            die("Missing required field: $field");
        }
    }

    $job_id = intval($_POST['job_id']);
    if ($job_id <= 0) {
        die("Invalid job ID.");
    }

    $full_name = sanitizeInput(trim($_POST['full_name']));
    $email = sanitizeInput(trim($_POST['email']));
    $phone = sanitizeInput(trim($_POST['phone']));
    $message = sanitizeInput(trim($_POST['message']));

    // Validate email format
    if (!validateEmail($email)) {
        die("Invalid email address format.");
    }

    // Validate phone number (Philippine format: 11 digits, starts with 09)
    if (!preg_match('/^09\d{9}$/', $phone)) {
        die("Invalid phone number. Please use 11-digit format starting with 09 (e.g., 09123456789).");
    }

    // Validate name (letters, spaces, hyphens, apostrophes only)
    if (!preg_match('/^[a-zA-Z\s\-\'\.]+$/', $full_name)) {
        die("Invalid name format. Only letters, spaces, hyphens, and apostrophes are allowed.");
    }

    // Validate message length
    if (strlen($message) < 10) {
        die("Message must be at least 10 characters long.");
    }
    if (strlen($message) > 2000) {
        die("Message is too long. Maximum 2000 characters allowed.");
    }

    if(!isset($_FILES['resume']) || $_FILES['resume']['error'] !== UPLOAD_ERR_OK){
        die("Resume file missing or upload error.");
    }

    // Validate file size (5MB max)
    if ($_FILES['resume']['size'] > 5 * 1024 * 1024) {
        die("Resume file size exceeds 5MB limit.");
    }

    // Validate file type using MIME type
    $allowed_types = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime_type = $finfo->file($_FILES['resume']['tmp_name']);
    
    if (!in_array($mime_type, $allowed_types)) {
        die("Invalid file type. Only PDF, DOC, and DOCX files are allowed.");
    }

    // Validate file extension
    $file_extension = strtolower(pathinfo($_FILES['resume']['name'], PATHINFO_EXTENSION));
    $allowed_extensions = ['pdf', 'doc', 'docx'];
    if (!in_array($file_extension, $allowed_extensions)) {
        die("Invalid file extension. Only .pdf, .doc, and .docx files are allowed.");
    }

    $fileName = time() . "_" . bin2hex(random_bytes(8)) . "." . $file_extension;

    // Use lgu/uploads/resumes/ for job applications
    $uploadDir = __DIR__ . "/../../lgu/uploads/resumes/";
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    $uploadPath = $uploadDir . $fileName;

    if(!move_uploaded_file(
        $_FILES['resume']['tmp_name'],
        $uploadPath
    )){
        die("Failed to upload resume.");
    }

    $stmt = $conn->prepare("
        INSERT INTO applicants
        (
            job_id,
            full_name,
            email,
            phone,
            message,
            resume
        )
        VALUES
        (?,?,?,?,?,?)
    ");

    if ($conn instanceof PDO) {
        // PostgreSQL/PDO - store only filename
        if($stmt->execute([$job_id, $full_name, $email, $phone, $message, $fileName])){
            echo "
            <script>
                alert('Application submitted successfully!');
                window.location='public.php';
            </script>
            ";
        } else {
            echo "Database Error: " . implode(', ', $stmt->errorInfo());
        }
    } else {
        // MySQLi - store only filename
        $stmt->bind_param(
            "isssss",
            $job_id,
            $full_name,
            $email,
            $phone,
            $message,
            $fileName
        );
        if($stmt->execute()){
            echo "
            <script>
                alert('Application submitted successfully!');
                window.location='public.php';
            </script>
            ";
        } else {
            echo "Database Error: " . $stmt->error;
        }
        $stmt->close();
    }

}
?>