<?php
include '../config/db.php';

if($_SERVER['REQUEST_METHOD'] == 'POST'){

    $id = $_POST['id'];
    $winner = $_POST['winner'];

    $stmt = $conn->prepare("
        UPDATE procurement_posts
        SET award_winner = ?, awarded_at = NOW(), status = 'AWARDED'
        WHERE id = ?
    ");

    $stmt->bind_param("si", $winner, $id);
    $stmt->execute();

    header("Location: procurement.php");
    exit();
}