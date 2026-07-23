<?php

include '../config/db.php';

if($_SERVER['REQUEST_METHOD'] == 'POST'){

    $job_id = intval($_POST['job_id']);

    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $message = trim($_POST['message']);

    if(empty($_FILES['resume']['name'])){
        die("Resume file missing.");
    }

    $fileName = time() . "_" . basename($_FILES['resume']['name']);

    $uploadDir = "uploads/";

    if(!is_dir($uploadDir) && !mkdir($uploadDir, 0777, true) && !is_dir($uploadDir)){
        error_log("Failed to create upload directory: $uploadDir");
        die("Failed to upload resume. Please try again later.");
    }

    $uploadPath = $uploadDir . $fileName;

    if(!move_uploaded_file(
        $_FILES['resume']['tmp_name'],
        $uploadPath
    )){
        error_log("Resume upload failed: " . $_FILES['resume']['name']);
        die("Failed to upload resume.");
    }

    try {
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

        $stmt->bind_param(
            "isssss",
            $job_id,
            $full_name,
            $email,
            $phone,
            $message,
            $uploadPath
        );

        $stmt->execute();
    } catch (mysqli_sql_exception $e) {
        error_log("Failed to insert applicant: " . $e->getMessage());
        die("Sorry, your application could not be submitted. Please try again later.");
    }

    echo "
    <script>
        alert('Application submitted successfully!');
        window.location='public.php';
    </script>
    ";

}
?>