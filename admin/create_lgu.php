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

    try {
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
    } catch (mysqli_sql_exception $e) {
        // 1062 = duplicate entry (username/email already taken)
        if ($e->getCode() == 1062) {
            header("Location: dashboard.php?error=duplicate");
            exit();
        }
        error_log("Failed to create LGU account: " . $e->getMessage());
        header("Location: dashboard.php?error=failed");
        exit();
    }

    header("Location: dashboard.php?success=1");
    exit();
}
?>