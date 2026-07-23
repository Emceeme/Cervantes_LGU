<?php
include 'config/db.php';

// check if exists
$check = $conn->query("SELECT id FROM users WHERE role='SUPER_ADMIN'");

if ($check->num_rows > 0) {
    die("Super Admin already exists.");
}

create_user(
    $conn,
    "System",
    "Admin",
    "superadmin",
    "admin@lgu.local",
    password_hash("admin123", PASSWORD_DEFAULT),
    "SUPER_ADMIN"
);

echo "Super Admin created successfully!";
?>
