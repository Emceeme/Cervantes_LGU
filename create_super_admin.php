<?php
// This is a one-time bootstrap script. It must be run from the command line
// (never exposed over the web) and requires the initial password to be passed
// via the SUPER_ADMIN_PASSWORD environment variable so no credential is
// hardcoded in the repository.
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    die("This script can only be run from the command line.");
}

include 'config/db.php';

$initial_password = getenv('SUPER_ADMIN_PASSWORD');
if ($initial_password === false || strlen($initial_password) < 12) {
    die("Set SUPER_ADMIN_PASSWORD (min 12 chars) before running this script.\n");
}

// check if exists
$check = $conn->query("SELECT id FROM users WHERE role='SUPER_ADMIN'");

if ($check->num_rows > 0) {
    die("Super Admin already exists.");
}

$first_name = "System";
$last_name = "Admin";
$username = "superadmin";
$email = "admin@lgu.local";
$password = password_hash($initial_password, PASSWORD_DEFAULT);
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