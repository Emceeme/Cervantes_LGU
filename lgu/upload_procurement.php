<?php
include '../config/db.php';

if($_SERVER['REQUEST_METHOD'] == 'POST'){

    $title = $_POST['title'];
    $status = $_POST['status'];

    $file = $_FILES['file']['name'];
    $tmp = $_FILES['file']['tmp_name'];

    $folder = "../uploads/procurement/";

    if(!is_dir($folder)){
        mkdir($folder, 0777, true);
    }

    $filename = time() . "_" . $file;

    move_uploaded_file($tmp, $folder . $filename);

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

    header("Location: procurement.php");
    exit();
}