<?php
/**
 * Shared helper functions used across the LGU system.
 *
 * This file is loaded automatically by config/db.php, so any script that
 * includes the database connection also gets these helpers.
 */

if (!function_exists('redirect')) {
    /**
     * Send a Location header and stop execution.
     */
    function redirect(string $location): void
    {
        header("Location: $location");
        exit();
    }
}

if (!function_exists('start_session')) {
    /**
     * Start the session only if one is not already active.
     */
    function start_session(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
}

if (!function_exists('require_login')) {
    /**
     * Ensure a user is logged in, otherwise redirect to the login page.
     */
    function require_login(string $loginPath = '../login.php'): void
    {
        start_session();

        if (!isset($_SESSION['id'])) {
            redirect($loginPath);
        }
    }
}

if (!function_exists('require_super_admin')) {
    /**
     * Ensure the logged-in user is a SUPER_ADMIN, otherwise deny access.
     */
    function require_super_admin(): void
    {
        start_session();

        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'SUPER_ADMIN') {
            die('Access denied');
        }
    }
}

if (!function_exists('save_uploaded_file')) {
    /**
     * Store an uploaded file in $dir using a timestamp-prefixed name.
     *
     * @return string|false|null Stored filename on success, false when the
     *                           move fails, null when no file was uploaded.
     */
    function save_uploaded_file(string $field, string $dir)
    {
        if (empty($_FILES[$field]['name'])) {
            return null;
        }

        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $filename = time() . '_' . basename($_FILES[$field]['name']);

        if (!move_uploaded_file($_FILES[$field]['tmp_name'], $dir . $filename)) {
            return false;
        }

        return $filename;
    }
}

if (!function_exists('create_user')) {
    /**
     * Insert a user record using a prepared statement.
     */
    function create_user(
        mysqli $conn,
        string $firstName,
        string $lastName,
        string $username,
        string $email,
        string $passwordHash,
        string $role
    ): bool {
        $stmt = $conn->prepare("
            INSERT INTO users (first_name, last_name, username, email, password, role)
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        $stmt->bind_param(
            "ssssss",
            $firstName,
            $lastName,
            $username,
            $email,
            $passwordHash,
            $role
        );

        return $stmt->execute();
    }
}
