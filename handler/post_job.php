<?php
session_start();
include '../config/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // GET FORM DATA
    $title = $_POST['job_title'];
    $dept = $_POST['department'];
    $type = $_POST['employment_type'];
    $salary = $_POST['salary'];
    $location = $_POST['location'];
    $desc = $_POST['description'];

    // FORCE STATUS TO MATCH PUBLIC PAGE
    $status = "OPEN";

    try {
        // INSERT QUERY (SAFE PREPARED STATEMENT)
        $stmt = $conn->prepare("
            INSERT INTO jobs
            (job_title, department, employment_type, salary, location, description, status, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ");

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

        $stmt->execute();
    } catch (mysqli_sql_exception $e) {
        error_log("Failed to insert job: " . $e->getMessage());
        header("Location: ../lgu/dashboard.php?error=failed");
        exit();
    }

    // SUCCESS → go back to dashboard with popup
    header("Location: ../lgu/dashboard.php?success=1");
    exit();

} else {
    header("Location: ../lgu/dashboard.php");
    exit();
}
?>