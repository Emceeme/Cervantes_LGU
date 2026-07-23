<?php
include '../config/db.php';

require_login();

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    redirect('../lgu/dashboard.php?error=invalid');
}

$id = (int) $_GET['id'];

$stmt = $conn->prepare("DELETE FROM jobs WHERE id = ?");

if (!$stmt) {
    die("Database Error: " . $conn->error);
}

$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    redirect('../lgu/dashboard.php?deleted=1');
} else {
    redirect('../lgu/dashboard.php?error=failed');
}
?>
