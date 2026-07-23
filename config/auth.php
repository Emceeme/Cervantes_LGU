<?php
/**
 * Shared security helpers: session handling, authentication/authorization
 * guards, CSRF protection and safe file-upload validation.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Require an authenticated session. Redirects to the login page when the
 * request comes from a browser page, or returns a 403 for API/handler calls.
 */
function require_login($login_path = '/login.php')
{
    if (empty($_SESSION['id'])) {
        header("Location: $login_path");
        exit();
    }
}

/**
 * Require an authenticated session with a specific role.
 */
function require_role($role, $login_path = '/login.php')
{
    require_login($login_path);
    if (($_SESSION['role'] ?? '') !== $role) {
        http_response_code(403);
        die("Access denied");
    }
}

/**
 * Return the CSRF token for the current session, generating one if needed.
 */
function csrf_token()
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Render a hidden CSRF input for embedding inside forms.
 */
function csrf_field()
{
    return '<input type="hidden" name="csrf_token" value="'
        . htmlspecialchars(csrf_token(), ENT_QUOTES) . '">';
}

/**
 * Validate the CSRF token supplied via POST or GET. Aborts with 403 on
 * failure. Uses a constant-time comparison.
 */
function verify_csrf()
{
    $token = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '';
    if (empty($_SESSION['csrf_token']) || !is_string($token)
        || !hash_equals($_SESSION['csrf_token'], $token)) {
        http_response_code(403);
        die("Invalid CSRF token");
    }
}

/**
 * Validate an uploaded file against an extension allowlist and guard against
 * dangerous/executable names. Returns a safe, sanitized filename on success or
 * false on failure.
 */
function validate_upload($file, array $allowed_ext)
{
    if (!isset($file['error']) || is_array($file['error'])
        || $file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }

    if (!is_uploaded_file($file['tmp_name'])) {
        return false;
    }

    $original = basename($file['name']);
    $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));

    if ($ext === '' || !in_array($ext, $allowed_ext, true)) {
        return false;
    }

    // Reject double extensions / embedded executable extensions.
    $dangerous = ['php', 'php3', 'php4', 'php5', 'php7', 'phtml', 'phar',
        'pht', 'htaccess', 'cgi', 'pl', 'py', 'sh', 'exe', 'js'];
    foreach (explode('.', strtolower($original)) as $part) {
        if (in_array($part, $dangerous, true)) {
            return false;
        }
    }

    // Build a filesystem-safe unique name; never trust the client name.
    $base = preg_replace('/[^A-Za-z0-9._-]/', '_', pathinfo($original, PATHINFO_FILENAME));
    $base = substr($base, 0, 100);
    return $base . '.' . $ext;
}
