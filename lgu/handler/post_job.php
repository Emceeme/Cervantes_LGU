<?php
session_start();
require_once '../../config/security.php';
include '../../config/db.php';

// Set security headers
setSecurityHeaders();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        logSecurityEvent('csrf_validation_failed', $_SESSION['id'] ?? null, ['endpoint' => 'post_job']);
        header("Location: ../dashboard.php?error=Security+validation+failed");
        exit();
    }

    // Validate required fields
    $required_fields = ['job_title', 'department', 'employment_type', 'salary', 'location', 'description'];
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            header("Location: ../dashboard.php?error=Missing+required+field:+" . urlencode($field));
            exit();
        }
    }

    // GET FORM DATA with sanitization
    $title = sanitizeInput($_POST['job_title']);
    $dept = sanitizeInput($_POST['department']);
    $type = sanitizeInput($_POST['employment_type']);
    $salary = sanitizeInput($_POST['salary']);
    $location = sanitizeInput($_POST['location']);
    $desc = sanitizeInput($_POST['description']);

    // Validate job title length
    if (strlen($title) < 5 || strlen($title) > 200) {
        header("Location: ../dashboard.php?error=Job+title+must+be+between+5+and+200+characters");
        exit();
    }

    // Validate department length
    if (strlen($dept) < 2 || strlen($dept) > 100) {
        header("Location: ../dashboard.php?error=Department+must+be+between+2+and+100+characters");
        exit();
    }

    // Validate employment type
    $allowed_types = ['full-time', 'part-time', 'contract', 'temporary'];
    if (!in_array(strtolower($type), $allowed_types)) {
        header("Location: ../dashboard.php?error=Invalid+employment+type");
        exit();
    }

    // Validate salary format (allow numbers, commas, and currency symbols)
    if (!preg_match('/^[\d,₱\.]+$/', $salary)) {
        header("Location: ../dashboard.php?error=Invalid+salary+format");
        exit();
    }

    // Validate location length
    if (strlen($location) < 2 || strlen($location) > 100) {
        header("Location: ../dashboard.php?error=Location+must+be+between+2+and+100+characters");
        exit();
    }

    // Validate description length
    if (strlen($desc) < 10 || strlen($desc) > 5000) {
        header("Location: ../dashboard.php?error=Description+must+be+between+10+and+5000+characters");
        exit();
    }

    // FORCE STATUS TO MATCH PUBLIC PAGE
    $status = "OPEN";

    // INSERT QUERY (SAFE PREPARED STATEMENT)
    $stmt = $conn->prepare("
        INSERT INTO jobs
        (job_title, department, employment_type, salary, location, description, status, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
    ");

    if (!$stmt) {
        if ($conn instanceof PDO) {
            die("Prepare failed: " . implode(", ", $conn->errorInfo()));
        } else {
            die("Prepare failed: " . $conn->error);
        }
    }

    if ($conn instanceof PDO) {
        // PostgreSQL/PDO
        $stmt->execute([$title, $dept, $type, $salary, $location, $desc, $status]);
        // SUCCESS → go back to dashboard with popup
        header("Location: ../dashboard.php?success=1");
        exit();
    } else {
        // MySQLi
        $stmt->bind_param(
            "sssssss",
            $title,
            $dept,
            $type,
            $salary,
            $location,
            $desc,
            $status
        );
        if ($stmt->execute()) {
            // SUCCESS → go back to dashboard with popup
            header("Location: ../dashboard.php?success=1");
            exit();
        } else {
            die("Insert failed: " . $stmt->error);
        }
    }

} else {
    header("Location: ../dashboard.php");
    exit();
}
?>