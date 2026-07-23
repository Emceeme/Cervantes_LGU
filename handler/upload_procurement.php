<?php
include '../config/db.php';

if($_SERVER['REQUEST_METHOD'] == 'POST'){

    $title = $_POST['title'];
    $status = $_POST['status'];

    $originalName = $_FILES['file']['name'];

    $filename = save_uploaded_file('file', "../uploads/procurement/");

    if(!$filename){
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
        $originalName,
        $status
    );

    $stmt->execute();

    redirect('../lgu/procurement.php');
}
