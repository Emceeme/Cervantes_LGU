<?php
session_start();
include '../../config/db.php';
include '../../config/app_config.php';

// Validate CSRF token
if (!isset($_POST['csrf_token']) || !isset($_SESSION['scholarship_csrf_token']) || $_POST['csrf_token'] !== $_SESSION['scholarship_csrf_token']) {
    die("Security validation failed. Please try again.");
}

// Clean up CSRF token
unset($_SESSION['scholarship_csrf_token']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: scholarship.php");
    exit();
}

// Validate required fields
$required_fields = [
    'full_name', 'email', 'phone', 'birth_date', 'gender', 'civil_status',
    'address', 'school_name', 'course', 'year_level', 'gpa',
    'family_income', 'family_members', 'parent_name', 'parent_contact', 'parent_occupation'
];

foreach ($required_fields as $field) {
    if (empty($_POST[$field])) {
        die("Missing required field: $field");
    }
}

// File upload handling
$file_path = '';
$original_file_name = '';

if (isset($_FILES['requirements_file']) && $_FILES['requirements_file']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['requirements_file'];
    
    // Validate file size (5MB max)
    if ($file['size'] > 5 * 1024 * 1024) {
        die("File size exceeds 5MB limit.");
    }
    
    // Validate file type
    $allowed_types = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'image/jpeg', 'image/png', 'image/jpg'];
    if (!in_array($file['type'], $allowed_types)) {
        die("Invalid file type. Allowed: PDF, DOC, DOCX, JPG, PNG.");
    }
    
    // Create upload directory if it doesn't exist
    $upload_dir = __DIR__ . '/../../lgu/uploads/scholarship/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    // Generate unique filename
    $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $file_name = time() . '_' . bin2hex(random_bytes(8)) . '.' . $file_extension;
    $file_path = $file_name;
    $original_file_name = $file['name'];
    
    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $upload_dir . $file_name)) {
        die("Failed to upload file.");
    }
}

// Insert into database
$stmt = $conn->prepare("
    INSERT INTO scholarship_applications
    (full_name, email, phone, address, birth_date, gender, civil_status,
     school_name, course, year_level, gpa, family_income, family_members,
     parent_name, parent_contact, parent_occupation, essay, file_path, original_file_name)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");

$stmt->bind_param(
    "sssssssssdississss",
    $_POST['full_name'],
    $_POST['email'],
    $_POST['phone'],
    $_POST['address'],
    $_POST['birth_date'],
    $_POST['gender'],
    $_POST['civil_status'],
    $_POST['school_name'],
    $_POST['course'],
    $_POST['year_level'],
    $_POST['gpa'],
    $_POST['family_income'],
    $_POST['family_members'],
    $_POST['parent_name'],
    $_POST['parent_contact'],
    $_POST['parent_occupation'],
    $_POST['essay'] ?? '',
    $file_path,
    $original_file_name
);

if ($stmt->execute()) {
    header("Location: scholarship.php?status=success");
    exit();
} else {
    die("Database Error: " . $stmt->error);
}

$stmt->close();
$conn->close();
?>
