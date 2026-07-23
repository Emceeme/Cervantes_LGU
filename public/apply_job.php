<?php

require '../config/auth.php';
include '../config/db.php';

if($_SERVER['REQUEST_METHOD'] == 'POST'){

    $job_id = intval($_POST['job_id']);

    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if (empty($full_name) || !filter_var($email, FILTER_VALIDATE_EMAIL) || empty($phone)) {
        http_response_code(400);
        die("Please provide a valid name, email and phone number.");
    }

    if(!isset($_FILES['resume'])){
        die("Resume file missing.");
    }

    $safeName = validate_upload($_FILES['resume'], ['pdf', 'doc', 'docx']);
    if ($safeName === false) {
        http_response_code(400);
        die("Invalid resume file. Allowed types: PDF, DOC, DOCX.");
    }

    $fileName = time() . "_" . $safeName;

    $uploadDir = "uploads/";
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