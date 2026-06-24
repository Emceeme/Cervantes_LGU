<?php
$conn = new mysqli("localhost", "root", "", "db_test");

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}
?>