<?php
session_start();

if (!isset($_SESSION['id'])) {
    header("Location: ../login.php");
    exit();
}

include '../config/db.php';
require_once __DIR__ . '/../src/Helpers.php';

use Cervantes\Helpers;

if (!Helpers::isValidId($_GET['id'] ?? null)) {
    header("Location: ../lgu/dashboard.php?error=invalid");
    exit();
}

$id = Helpers::toId($_GET['id']);

$stmt = $conn->prepare("DELETE FROM jobs WHERE id = ?");

if (!$stmt) {
    die("Database Error: " . $conn->error);
}

$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    header("Location: ../lgu/dashboard.php?deleted=1");
} else {
    header("Location: ../lgu/dashboard.php?error=failed");
}

$stmt->close();
$conn->close();
exit();
?>