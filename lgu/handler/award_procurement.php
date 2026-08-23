<?php
session_start();
require_once '../../config/security.php';
include '../../config/db.php';

// Set security headers
setSecurityHeaders();

if($_SERVER['REQUEST_METHOD'] == 'POST'){
    
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        logSecurityEvent('csrf_validation_failed', $_SESSION['id'] ?? null, ['endpoint' => 'award_procurement']);
        header("Location: ../procurement.php?error=Security+validation+failed");
        exit();
    }

    // Validate required fields
    $required_fields = ['id', 'winner'];
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            header("Location: ../procurement.php?error=Missing+required+field:+" . urlencode($field));
            exit();
        }
    }

    $id = intval($_POST['id']);
    $winner = sanitizeInput($_POST['winner']);

    // Validate procurement ID
    if ($id <= 0) {
        header("Location: ../procurement.php?error=Invalid+procurement+ID");
        exit();
    }

    // Validate winner name (letters, spaces, hyphens, apostrophes, numbers only)
    if (!preg_match('/^[a-zA-Z0-9\s\-\'\.]+$/', $winner)) {
        header("Location: ../procurement.php?error=Invalid+winner+name+format");
        exit();
    }

    // Validate winner length
    if (strlen($winner) < 2 || strlen($winner) > 200) {
        header("Location: ../procurement.php?error=Winner+name+must+be+between+2+and+200+characters");
        exit();
    }

    $stmt = $conn->prepare("
        UPDATE procurement_posts
        SET award_winner = ?, awarded_at = NOW(), status = 'AWARDED'
        WHERE id = ?
    ");

    if ($conn instanceof PDO) {
        // PostgreSQL/PDO
        $stmt->execute([$winner, $id]);
    } else {
        // MySQLi
        $stmt->bind_param("si", $winner, $id);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: ../procurement.php?status=success");
    exit();
}
?>