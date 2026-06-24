<?php
include '../config/db.php';

$id = $_GET['id'];

$stmt = $conn->prepare("DELETE FROM jobs WHERE id=?");
$stmt->bind_param("i", $id);
$stmt->execute();

header("Location: jobs.php");
exit();
?>