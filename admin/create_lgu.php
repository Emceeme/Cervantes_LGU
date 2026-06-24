<?php
session_start();
include '../config/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != "SUPER_ADMIN") {
    die("Access denied");
}

if (isset($_POST['create'])) {

    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = "LGU";

    $stmt = $conn->prepare("
        INSERT INTO users (first_name,last_name,username,email,password,role)
        VALUES (?,?,?,?,?,?)
    ");

    $stmt->bind_param("ssssss",
        $first_name,
        $last_name,
        $username,
        $email,
        $password,
        $role
    );

    $stmt->execute();

    header("Location: dashboard.php");
}
?>