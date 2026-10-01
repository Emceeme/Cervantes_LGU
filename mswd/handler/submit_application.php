<?php
// Disable error output to prevent HTML in JSON response
error_reporting(0);
ini_set('display_errors', 0);

session_start();
require_once __DIR__ . '/../../config/security.php';
require_once __DIR__ . '/../../config/db.php';

setSecurityHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("HTTP/1.0 405 Method Not Allowed");
    exit();
}

if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Security validation failed']);
    exit();
}

try {
    // Validate required fields
    $required_fields = ['assistance_type_id', 'first_name', 'last_name', 'birthdate', 'gender', 'civil_status', 'contact_number', 'barangay', 'street_address'];
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            throw new Exception("Field '$field' is required");
        }
    }

    // Generate tracking number
    $tracking_number = 'MSWD-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));

    // Start transaction
    if ($conn instanceof PDO) {
        $conn->beginTransaction();
    } else {
        $conn->begin_transaction();
    }

    // Insert application
    $stmt = $conn->prepare("
        INSERT INTO applications (
            tracking_number, assistance_type_id, applicant_id,
            first_name, middle_name, last_name, birthdate, gender, civil_status,
            contact_number, email, barangay, street_address, status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')
    ");

    $applicant_id = isset($_SESSION['id']) && $_SESSION['role'] === 'APPLICANT' ? $_SESSION['id'] : null;
    $middle_name = $_POST['middle_name'] ?? '';
    $email = $_POST['email'] ?? '';

    if ($conn instanceof PDO) {
        $stmt->execute([
            $tracking_number,
            $_POST['assistance_type_id'],
            $applicant_id,
            $_POST['first_name'],
            $middle_name,
            $_POST['last_name'],
            $_POST['birthdate'],
            $_POST['gender'],
            $_POST['civil_status'],
            $_POST['contact_number'],
            $email,
            $_POST['barangay'],
            $_POST['street_address']
        ]);
        $application_id = $conn->lastInsertId();
    } else {
        $stmt->bind_param("sisssssssssss",
            $tracking_number,
            $_POST['assistance_type_id'],
            $applicant_id,
            $_POST['first_name'],
            $middle_name,
            $_POST['last_name'],
            $_POST['birthdate'],
            $_POST['gender'],
            $_POST['civil_status'],
            $_POST['contact_number'],
            $email,
            $_POST['barangay'],
            $_POST['street_address']
        );
        $stmt->execute();
        $application_id = $conn->insert_id;
        $stmt->close();
    }

    // Handle document uploads
    if (isset($_FILES['documents']) && !empty($_FILES['documents']['name'][0])) {
        // Upload directory outside web root
        $upload_dir = __DIR__ . '/../../../uploads/mswd_documents/';
        
        // Create upload directory if it doesn't exist
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0750, true);
            // Create .htaccess to prevent direct access
            file_put_contents($upload_dir . '.htaccess', 'Deny from all');
        }
        
        // Allowed file extensions and MIME types
        $allowed_extensions = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'gif'];
        $allowed_mime_types = [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'image/jpeg',
            'image/png',
            'image/gif'
        ];
        
        foreach ($_FILES['documents']['name'] as $key => $name) {
            if ($_FILES['documents']['error'][$key] === UPLOAD_ERR_OK) {
                $tmp_name = $_FILES['documents']['tmp_name'][$key];
                $file_size = $_FILES['documents']['size'][$key];
                $mime_type = $_FILES['documents']['type'][$key];
                
                // Validate file size (max 10MB)
                if ($file_size > 10 * 1024 * 1024) {
                    throw new Exception("File '$name' exceeds maximum size of 10MB");
                }
                
                // Validate file extension
                $file_ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                if (!in_array($file_ext, $allowed_extensions)) {
                    throw new Exception("File '$name' has invalid extension. Allowed: " . implode(', ', $allowed_extensions));
                }
                
                // Validate actual MIME type using server-side inspection
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $actual_mime = $finfo->file($tmp_name);
                
                if (!in_array($actual_mime, $allowed_mime_types)) {
                    throw new Exception("File '$name' has invalid content type");
                }
                
                // Generate unique filename with original extension
                $file_name = bin2hex(random_bytes(16)) . '.' . $file_ext;
                $file_path = 'uploads/mswd_documents/' . $file_name;
                
                // Move file
                if (move_uploaded_file($tmp_name, $upload_dir . $file_name)) {
                    // Insert document record
                    $doc_stmt = $conn->prepare("
                        INSERT INTO application_documents (application_id, document_type, file_name, file_path, file_size, mime_type)
                        VALUES (?, ?, ?, ?, ?, ?)
                    ");
                    
                    $document_type = $_POST['document_types'][$key] ?? 'Other';
                    
                    if ($conn instanceof PDO) {
                        $doc_stmt->execute([$application_id, $document_type, $name, $file_path, $file_size, $actual_mime]);
                    } else {
                        $doc_stmt->bind_param("isssis", $application_id, $document_type, $name, $file_path, $file_size, $actual_mime);
                        $doc_stmt->execute();
                        $doc_stmt->close();
                    }
                } else {
                    throw new Exception("Failed to upload file '$name'");
                }
            }
        }
    }

    $conn->commit();

    // Log security event
    logSecurityEvent('mswd_application_submitted', $applicant_id ?? null, [
        'tracking_number' => $tracking_number,
        'assistance_type_id' => $_POST['assistance_type_id']
    ]);

    echo json_encode([
        'success' => true,
        'tracking_number' => $tracking_number,
        'redirect' => "confirmation.php?tracking=" . urlencode($tracking_number)
    ]);

} catch (Exception $e) {
    if ($conn instanceof PDO) {
        $conn->rollBack();
    } else {
        $conn->rollback();
    }
    logError('MSWD application submission failed: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
