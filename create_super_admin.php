<?php
include 'config/db.php';

// check if exists
$check = $conn->query("SELECT id FROM users WHERE role='SUPER_ADMIN'");

if ($check->num_rows > 0) {
    die("Super Admin already exists.");
}

$first_name = "System";
$last_name = "Admin";
$username = "superadmin";
$email = "admin@lgu.local";
$password = password_hash("admin123", PASSWORD_DEFAULT);
$role = "SUPER_ADMIN";

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

echo "Super Admin created successfully!";
?>