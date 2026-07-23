<?php
require '../config/auth.php';
require_login('../login.php');
include '../config/db.php';

if($_SERVER['REQUEST_METHOD'] == 'POST'){

    verify_csrf();

    $title = $_POST['title'];
    $status = $_POST['status'];

    $allowed = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'png', 'jpg', 'jpeg'];
    $safeName = validate_upload($_FILES['file'] ?? [], $allowed);
    if ($safeName === false) {
        http_response_code(400);
        die("Invalid or disallowed file type.");
    }

    $file = basename($_FILES['file']['name']);
    $tmp = $_FILES['file']['tmp_name'];

    $folder = "../uploads/procurement/";

    if(!is_dir($folder)){
        mkdir($folder, 0755, true);
    }

    $filename = time() . "_" . $safeName;

    if (!move_uploaded_file($tmp, $folder . $filename)) {
        http_response_code(500);
        die("File upload failed.");
    }

    $stmt = $conn->prepare("
        INSERT INTO procurement_posts
        (title, file_path, original_file_name, status)
        VALUES (?,?,?,?)
    ");

    $stmt->bind_param("ssss",
        $title,
        $filename,
        $file,
        $status
    );

    $stmt->execute();

    header("Location: ../lgu/procurement.php");
    exit();
}