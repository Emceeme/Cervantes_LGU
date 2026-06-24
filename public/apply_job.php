<?php

include '../config/db.php';

if($_SERVER['REQUEST_METHOD'] == 'POST'){

    $job_id = intval($_POST['job_id']);

    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $message = trim($_POST['message']);

    if(!isset($_FILES['resume'])){
        die("Resume file missing.");
    }

    $fileName = time() . "_" . basename($_FILES['resume']['name']);

    $uploadDir = "uploads/";
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

    $stmt->bind_param(
        "isssss",
        $job_id,
        $full_name,
        $email,
        $phone,
        $message,
        $uploadPath
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

}
?>