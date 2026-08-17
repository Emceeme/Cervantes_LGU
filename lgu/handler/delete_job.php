<?php
session_start();
require_once '../../config/security.php';
include '../../config/db.php';

if (!isset($_SESSION['id'])) {
    header("Location: ../login.php");
    exit();
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: ../dashboard.php?error=invalid");
    exit();
}

$id = (int) $_GET['id'];

$stmt = $conn->prepare("DELETE FROM jobs WHERE id = ?");

if (!$stmt) {
    if ($conn instanceof PDO) {
        die("Database Error: " . implode(", ", $conn->errorInfo()));
    } else {
        die("Database Error: " . $conn->error);
    }
}

if ($conn instanceof PDO) {
    // PostgreSQL/PDO
    if ($stmt->execute([$id])) {
        header("Location: ../dashboard.php?deleted=1");
    } else {
        header("Location: ../dashboard.php?error=failed");
    }
} else {
    // MySQLi
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        header("Location: ../dashboard.php?deleted=1");
    } else {
        header("Location: ../dashboard.php?error=failed");
    }
    $stmt->close();
    $conn->close();
}
exit();
?>