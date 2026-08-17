<?php
session_start();
require_once '../../config/security.php';
include '../../config/db.php';

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['ADMIN', 'SUPER_ADMIN'], true)) {
    die("Access denied.");
}

if (isset($_GET['id'])) {
    $emp_id = intval($_GET['id']);
    $admin_dept = $_SESSION['department'];

    // Sub Admins can only delete EMPLOYEE accounts from their own department
    if ($_SESSION['role'] === 'ADMIN') {
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND role = 'EMPLOYEE' AND department = ?");
        if ($conn instanceof PDO) {
            $stmt->execute([$emp_id, $admin_dept]);
        } else {
            $stmt->bind_param("is", $emp_id, $admin_dept);
            $stmt->execute();
            $stmt->close();
        }
    } else {
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND role = 'EMPLOYEE'");
        if ($conn instanceof PDO) {
            $stmt->execute([$emp_id]);
        } else {
            $stmt->bind_param("i", $emp_id);
            $stmt->execute();
            $stmt->close();
        }
    }
}

header("Location: ../manage_employees.php");
exit();
?>