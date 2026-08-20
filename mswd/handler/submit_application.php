<?php
session_start();
require_once __DIR__ . '/../../config/security.php';
require_once __DIR__ . '/../../config/db.php';

setSecurityHeaders();

// Only accept POST requests
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../public/apply.php?error=Invalid+request+method");
    exit();
}

// CSRF validation
if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
    logSecurityEvent('csrf_validation_failed', $_SESSION['id'] ?? null, ['endpoint' => 'submit_application']);
    header("Location: ../public/apply.php?error=Security+validation+failed");
    exit();
}

// Validate required fields
$required_fields = ['assistance_type_id', 'first_name', 'last_name', 'birthdate', 'gender', 'civil_status', 'contact_number', 'barangay', 'street_address'];
$missing_fields = [];
foreach ($required_fields as $field) {
    if (empty($_POST[$field])) {
        $missing_fields[] = $field;
    }
}

if (!empty($missing_fields)) {
    header("Location: ../public/apply.php?error=Please+fill+in+all+required+fields");
    exit();
}

// Sanitize inputs
$assistance_type_id = intval($_POST['assistance_type_id']);
$first_name = sanitizeInput($_POST['first_name']);
$middle_name = sanitizeInput($_POST['middle_name'] ?? '');
$last_name = sanitizeInput($_POST['last_name']);
$birthdate = sanitizeInput($_POST['birthdate']);
$gender = sanitizeInput($_POST['gender']);
$civil_status = sanitizeInput($_POST['civil_status']);
$contact_number = sanitizeInput($_POST['contact_number']);
$email = sanitizeInput($_POST['email'] ?? '');
$barangay = sanitizeInput($_POST['barangay']);
$street_address = sanitizeInput($_POST['street_address']);

// Check if user is logged in as applicant
$applicant_id = isset($_SESSION['id']) && $_SESSION['role'] === 'APPLICANT' ? $_SESSION['id'] : null;

// Validate email if provided
if (!empty($email) && !validateEmail($email)) {
    header("Location: ../public/apply.php?error=Invalid+email+address");
    exit();
}

// Generate unique tracking number
$tracking_number = 'MSWD-' . date('Y') . '-' . strtoupper(substr(md5(uniqid()), 0, 6));

// Start transaction for atomic operation
if ($conn instanceof PDO) {
    $conn->beginTransaction();
} else {
    $conn->begin_transaction();
}

try {
    // Insert application
    $insert_app = $conn->prepare("
        INSERT INTO applications (
            tracking_number, assistance_type_id, applicant_id, first_name, middle_name, last_name,
            birthdate, gender, civil_status, contact_number, email, barangay, street_address,
            status, submitted_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
    ");
    
    if (!$insert_app) {
        $error = $conn instanceof PDO ? implode(' ', $conn->errorInfo()) : $conn->error;
        throw new Exception("Failed to prepare statement: " . $error);
    }
    
    if ($conn instanceof PDO) {
        $insert_app->execute([
            $tracking_number, $assistance_type_id, $applicant_id, $first_name, $middle_name, $last_name,
            $birthdate, $gender, $civil_status, $contact_number, $email, $barangay, $street_address
        ]);
        
        if ($insert_app->errorCode() !== '00000') {
            throw new Exception("Failed to insert application: " . implode(' ', $insert_app->errorInfo()));
        }
        
        $application_id = $conn->lastInsertId();
    } else {
        $insert_app->bind_param(
            "sisssssssssss",
            $tracking_number, $assistance_type_id, $applicant_id, $first_name, $middle_name, $last_name,
            $birthdate, $gender, $civil_status, $contact_number, $email, $barangay, $street_address
        );
        
        if (!$insert_app->execute()) {
            throw new Exception("Failed to insert application: " . $insert_app->error);
        }
        
        $application_id = $conn->insert_id;
        $insert_app->close();
    }
    
    // Handle file uploads
    if (isset($_FILES['documents']) && !empty($_FILES['documents']['name'][0])) {
        $upload_dir = __DIR__ . '/../../storage/mswd_documents/' . $application_id . '/';
        
        // Create directory if it doesn't exist
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $allowed_types = ['application/pdf', 'image/jpeg', 'image/png', 'image/jpg'];
        $max_size = 5 * 1024 * 1024; // 5MB
        
        foreach ($_FILES['documents']['tmp_name'] as $key => $tmp_name) {
            if ($_FILES['documents']['error'][$key] === UPLOAD_ERR_OK) {
                $file_name = $_FILES['documents']['name'][$key];
                $file_size = $_FILES['documents']['size'][$key];
                
                // Validate file size
                if ($file_size > $max_size) {
                    throw new Exception("File too large: $file_name. Maximum 5MB allowed.");
                }
                
                // Validate actual file content using finfo (security fix)
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mime_type = $finfo->file($tmp_name);
                
                if (!in_array($mime_type, $allowed_types)) {
                    throw new Exception("Security Alert: Invalid file content detected for $file_name. Only PDF, PNG, and JPG allowed.");
                }
                
                // Generate secure filename
                $file_extension = pathinfo($file_name, PATHINFO_EXTENSION);
                $secure_filename = uniqid() . '_' . time() . '.' . $file_extension;
                $file_path = $upload_dir . $secure_filename;
                
                // Move file to secure storage
                if (!move_uploaded_file($tmp_name, $file_path)) {
                    throw new Exception("Failed to upload file: $file_name");
                }
                
                // Determine document type based on filename
                $document_type = pathinfo($file_name, PATHINFO_FILENAME);
                
                // Insert document record
                $insert_doc = $conn->prepare("
                    INSERT INTO application_documents (
                        application_id, document_type, file_name, file_path, file_size
                    ) VALUES (?, ?, ?, ?, ?)
                ");
                
                $relative_path = 'storage/mswd_documents/' . $application_id . '/' . $secure_filename;
                
                if ($conn instanceof PDO) {
                    $insert_doc->execute([
                        $application_id, $document_type, $file_name, $relative_path, $file_size
                    ]);
                    
                    if ($insert_doc->errorCode() !== '00000') {
                        throw new Exception("Failed to save document record: " . implode(' ', $insert_doc->errorInfo()));
                    }
                } else {
                    $insert_doc->bind_param(
                        "isssi",
                        $application_id, $document_type, $file_name, $relative_path, $file_size
                    );
                    
                    if (!$insert_doc->execute()) {
                        throw new Exception("Failed to save document record: " . $insert_doc->error);
                    }
                    
                    $insert_doc->close();
                }
            }
        }
    }
    
    // Commit transaction
    $conn->commit();
    
    // Log successful submission
    logSecurityEvent('application_submitted', null, [
        'tracking_number' => $tracking_number,
        'assistance_type_id' => $assistance_type_id,
        'applicant_name' => $first_name . ' ' . $last_name
    ]);
    
    // Redirect to confirmation page
    header("Location: ../public/confirmation.php?tracking=" . urlencode($tracking_number));
    exit();
    
} catch (Exception $e) {
    // Rollback transaction on error
    if ($conn instanceof PDO) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
    } else {
        $conn->rollback();
    }
    
    // Log error
    logError('Application submission failed: ' . $e->getMessage());
    
    // Redirect with error
    header("Location: ../public/apply.php?error=" . urlencode($e->getMessage()));
    exit();
}
