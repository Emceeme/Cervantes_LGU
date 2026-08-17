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

    // GET FORM DATA
    $title = $_POST['job_title'];
    $dept = $_POST['department'];
    $type = $_POST['employment_type'];
    $salary = $_POST['salary'];
    $location = $_POST['location'];
    $desc = $_POST['description'];

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