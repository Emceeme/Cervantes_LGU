<?php
session_start();
include '../../config/security.php';
include '../../config/db.php';
include '../../config/app_config.php';

// Set security headers
setSecurityHeaders();

// Validate CSRF token
if (!isset($_POST['csrf_token']) || !isset($_SESSION['scholarship_csrf_token'])) {
    die("Security validation failed. Missing CSRF token.");
}

if ($_POST['csrf_token'] !== $_SESSION['scholarship_csrf_token']) {
    die("Security validation failed. Invalid CSRF token.");
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

// Sanitize and validate inputs
$full_name = sanitizeInput($_POST['full_name']);
$email = sanitizeInput($_POST['email']);
$phone = sanitizeInput($_POST['phone']);
$birth_date = sanitizeInput($_POST['birth_date']);
$gender = sanitizeInput($_POST['gender']);
$civil_status = sanitizeInput($_POST['civil_status']);
$address = sanitizeInput($_POST['address']);
$school_name = sanitizeInput($_POST['school_name']);
$course = sanitizeInput($_POST['course']);
$year_level = sanitizeInput($_POST['year_level']);
$gpa = sanitizeInput($_POST['gpa']);
$family_income = sanitizeInput($_POST['family_income']);
$family_members = sanitizeInput($_POST['family_members']);
$parent_name = sanitizeInput($_POST['parent_name']);
$parent_contact = sanitizeInput($_POST['parent_contact']);
$parent_occupation = sanitizeInput($_POST['parent_occupation']);
$essay = sanitizeInput($_POST['essay'] ?? '');

// Validate email format
if (!validateEmail($email)) {
    die("Invalid email address format.");
}

// Validate phone number (Philippine format: 11 digits, starts with 09)
if (!preg_match('/^09\d{9}$/', $phone)) {
    die("Invalid phone number. Please use 11-digit format starting with 09 (e.g., 09123456789).");
}

// Validate parent contact number
if (!preg_match('/^09\d{9}$/', $parent_contact)) {
    die("Invalid parent contact number. Please use 11-digit format starting with 09 (e.g., 09123456789).");
}

// Validate name (letters, spaces, hyphens, apostrophes only)
if (!preg_match('/^[a-zA-Z\s\-\'\.]+$/', $full_name)) {
    die("Invalid name format. Only letters, spaces, hyphens, and apostrophes are allowed.");
}

if (!preg_match('/^[a-zA-Z\s\-\'\.]+$/', $parent_name)) {
    die("Invalid parent name format. Only letters, spaces, hyphens, and apostrophes are allowed.");
}

// Validate birth date (must be a valid date and at least 5 years old)
$birth_date_obj = DateTime::createFromFormat('Y-m-d', $birth_date);
if (!$birth_date_obj || $birth_date_obj > new DateTime('-5 years')) {
    die("Invalid birth date. Applicant must be at least 5 years old.");
}

// Validate gender
$allowed_genders = ['male', 'female', 'other'];
if (!in_array(strtolower($gender), $allowed_genders)) {
    die("Invalid gender value.");
}

// Validate civil status
$allowed_civil_status = ['single', 'married', 'widowed', 'separated'];
if (!in_array(strtolower($civil_status), $allowed_civil_status)) {
    die("Invalid civil status value.");
}

// Validate GPA (must be between 1.0 and 5.0 or 75-100)
$gpa_float = floatval($gpa);
if (($gpa_float < 1.0 || $gpa_float > 5.0) && ($gpa_float < 75 || $gpa_float > 100)) {
    die("Invalid GPA. Must be between 1.0-5.0 or 75-100.");
}

// Validate year level (1-5)
$year_level_int = intval($year_level);
if ($year_level_int < 1 || $year_level_int > 5) {
    die("Invalid year level. Must be between 1 and 5.");
}

// Validate family members (must be positive integer)
$family_members_int = intval($family_members);
if ($family_members_int < 1 || $family_members_int > 50) {
    die("Invalid number of family members. Must be between 1 and 50.");
}

// Validate family income (must be positive number)
$family_income_float = floatval($family_income);
if ($family_income_float < 0) {
    die("Invalid family income. Must be a positive number.");
}

// Validate essay length
if (strlen($essay) > 5000) {
    die("Essay is too long. Maximum 5000 characters allowed.");
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
    
    // Validate file type using MIME type
    $allowed_types = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'image/jpeg', 'image/png', 'image/jpg'];
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime_type = $finfo->file($file['tmp_name']);
    
    if (!in_array($mime_type, $allowed_types)) {
        die("Invalid file type. Allowed: PDF, DOC, DOCX, JPG, PNG.");
    }
    
    // Validate file extension
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed_extensions = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];
    if (!in_array($file_extension, $allowed_extensions)) {
        die("Invalid file extension. Allowed: .pdf, .doc, .docx, .jpg, .jpeg, .png");
    }
    
    // Create upload directory if it doesn't exist
    $upload_dir = __DIR__ . '/../../lgu/uploads/scholarship/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    // Generate unique filename
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

if ($conn instanceof PDO) {
    // PostgreSQL/PDO
    $params = [
        $full_name,
        $email,
        $phone,
        $address,
        $birth_date,
        $gender,
        $civil_status,
        $school_name,
        $course,
        $year_level_int,
        $gpa_float,
        $family_income_float,
        $family_members_int,
        $parent_name,
        $parent_contact,
        $parent_occupation,
        $essay,
        $file_path,
        $original_file_name
    ];
    if ($stmt->execute($params)) {
        header("Location: scholarship.php?status=success");
        exit();
    } else {
        die("Database Error: " . implode(', ', $stmt->errorInfo()));
    }
} else {
    // MySQLi
    $stmt->bind_param(
        "sssssssssisssissss",
        $full_name,
        $email,
        $phone,
        $address,
        $birth_date,
        $gender,
        $civil_status,
        $school_name,
        $course,
        $year_level_int,
        $gpa_float,
        $family_income_float,
        $family_members_int,
        $parent_name,
        $parent_contact,
        $parent_occupation,
        $essay,
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
}
?>
