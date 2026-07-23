<?php
session_start();
include '../config/db.php';

if (!isset($_SESSION['id'])) {
    header("Location: ../login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $winner = trim($_POST['winner'] ?? '');

    if (!isset($_POST['id']) || !is_numeric($_POST['id']) || $winner === '') {
        header("Location: ../lgu/procurement.php?error=missing");
        exit();
    }

    $id = (int) $_POST['id'];

    try {
        $stmt = $conn->prepare("
            UPDATE procurement_posts
            SET award_winner = ?, awarded_at = NOW(), status = 'AWARDED'
            WHERE id = ?
        ");

        $stmt->bind_param("si", $winner, $id);
        $stmt->execute();
    } catch (mysqli_sql_exception $e) {
        error_log("Failed to award procurement $id: " . $e->getMessage());
        header("Location: ../lgu/procurement.php?error=save");
        exit();
    }

    header("Location: ../lgu/procurement.php?awarded=1");
    exit();
}

header("Location: ../lgu/procurement.php");
exit();
?>
