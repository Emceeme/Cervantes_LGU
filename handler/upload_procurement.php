<?php
session_start();
include '../config/db.php';

if (!isset($_SESSION['id'])) {
    header("Location: ../login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $title = trim($_POST['title'] ?? '');
    $status = $_POST['status'] ?? '';

    if ($title === '' || empty($_FILES['file']['name'])) {
        header("Location: ../lgu/procurement.php?error=missing");
        exit();
    }

    $file = basename($_FILES['file']['name']);
    $tmp = $_FILES['file']['tmp_name'];

    $folder = "../uploads/procurement/";

    if (!is_dir($folder) && !mkdir($folder, 0777, true) && !is_dir($folder)) {
        error_log("Failed to create upload directory: $folder");
        header("Location: ../lgu/procurement.php?error=upload");
        exit();
    }

    $filename = time() . "_" . $file;

    if (!move_uploaded_file($tmp, $folder . $filename)) {
        error_log("Procurement file upload failed: $file");
        header("Location: ../lgu/procurement.php?error=upload");
        exit();
    }

    try {
        $stmt = $conn->prepare("
            INSERT INTO procurement_posts
            (title, file_path, original_file_name, status)
            VALUES (?,?,?,?)
        ");

        $stmt->bind_param("ssss", $title, $filename, $file, $status);
        $stmt->execute();
    } catch (mysqli_sql_exception $e) {
        error_log("Failed to insert procurement post: " . $e->getMessage());
        header("Location: ../lgu/procurement.php?error=save");
        exit();
    }

    header("Location: ../lgu/procurement.php?success=1");
    exit();
}

header("Location: ../lgu/procurement.php");
exit();
?>
