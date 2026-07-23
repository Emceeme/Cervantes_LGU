<?php
include '../config/db.php';

require_super_admin();

if (isset($_POST['create'])) {

    create_user(
        $conn,
        $_POST['first_name'],
        $_POST['last_name'],
        $_POST['username'],
        $_POST['email'],
        password_hash($_POST['password'], PASSWORD_DEFAULT),
        "LGU"
    );

    redirect('dashboard.php');
}
?>
