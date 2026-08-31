<?php
// Load environment variables first
require_once 'config/env.php';

// Load security functions
require_once 'config/security.php';

// Start secure session
startSecureSession();

// Set security headers
setSecurityHeaders();

// Load database connection
include 'config/db.php';

$error = "";

// Generate CSRF token for login form
$csrf_token = generateCsrfToken();

if (isset($_POST['login'])) {

    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        $error = "Security validation failed. Please try again.";
        logSecurityEvent('csrf_validation_failed', null, ['ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown']);
    } else {
        $username_or_email = trim($_POST['username']);
        $password = $_POST['password'];
        
        // Get client IP for rate limiting
        $client_ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

        // Check if IP is blocked due to too many failed attempts
        if (isLoginBlocked($client_ip)) {
            $error = "Too many failed login attempts. Please try again later.";
            logSecurityEvent('login_blocked_ip', null, ['ip' => $client_ip, 'reason' => 'rate_limit']);
        } 
        // Check if username/email is blocked
        elseif (isLoginBlocked($username_or_email)) {
            $error = "Too many failed login attempts for this account. Please try again later.";
            logSecurityEvent('login_blocked_user', null, ['username' => $username_or_email, 'reason' => 'rate_limit']);
        }
        else {
            // 1. Fetch user by username OR email
            if ($conn instanceof PDO) {
                // PostgreSQL/PDO
                $stmt = $conn->prepare("SELECT id, first_name, last_name, username, email, password, role, department FROM users WHERE username = ? OR email = ?");
                $stmt->execute([$username_or_email, $username_or_email]);
                $user = $stmt->fetch();
                
                if ($user) {
                    // 2. Verify password hash
                    if (password_verify($password, $user['password'])) {

                        // Track successful login (resets attempt counter)
                        trackLoginAttempt($username_or_email, true);
                        trackLoginAttempt($client_ip, true);

                        // Store full session parameters
                        $_SESSION['id']         = $user['id'];
                        $_SESSION['user_id']    = $user['id']; 
                        $_SESSION['username']   = $user['username'];
                        $_SESSION['name']       = $user['first_name'] . ' ' . $user['last_name'];
                        $_SESSION['role']       = $user['role'];
                        $_SESSION['department'] = html_entity_decode($user['department'], ENT_QUOTES);
                        $_SESSION['login_time'] = time();

                        // Log successful login
                        logSecurityEvent('login_success', $user['id'], ['ip' => $client_ip, 'username' => $username_or_email]);

                        // 3. ROUTING LOGIC (LGU ONLY - MSWD and Treasury disabled)

                        // A. Super Admins (highest priority)
                        if ($user['role'] === 'SUPER_ADMIN') {
                            header("Location: admin/dashboard.php");
                            exit();
                        }

                        // B. Mayor's Office and LGU departments ONLY
                        if ($user['department'] === "Mayor's Office" || $user['department'] === 'Mayor Office' || $user['department'] === 'LGU') {
                            header("Location: lgu/dashboard.php");
                            exit();
                        }

                        // C. All other users redirect to LGU (MSWD and Treasury disabled)
                        header("Location: lgu/dashboard.php");
                        exit();

                    } else {
                        $error = "Invalid password.";
                        // Track failed login attempt
                        $is_blocked = trackLoginAttempt($username_or_email, false);
                        trackLoginAttempt($client_ip, false);
                        
                        logSecurityEvent('login_failed', null, ['ip' => $client_ip, 'username' => $username_or_email, 'reason' => 'invalid_password']);
                        
                        if ($is_blocked) {
                            $error = "Too many failed login attempts. Please try again later.";
                        }
                    }
                } else {
                    $error = "User not found.";
                    // Track failed login attempt
                    $is_blocked = trackLoginAttempt($username_or_email, false);
                    trackLoginAttempt($client_ip, false);
                    
                    logSecurityEvent('login_failed', null, ['ip' => $client_ip, 'username' => $username_or_email, 'reason' => 'user_not_found']);
                    
                    if ($is_blocked) {
                        $error = "Too many failed login attempts. Please try again later.";
                    }
                }
            } else {
                // MySQLi
                $stmt = $conn->prepare("SELECT id, first_name, last_name, username, email, password, role, department FROM users WHERE username = ? OR email = ?");
                $stmt->bind_param("ss", $username_or_email, $username_or_email);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows === 1) {

                    $user = $result->fetch_assoc();

                    // 2. Verify password hash
                    if (password_verify($password, $user['password'])) {

                        // Track successful login (resets attempt counter)
                        trackLoginAttempt($username_or_email, true);
                        trackLoginAttempt($client_ip, true);

                        // Store full session parameters
                        $_SESSION['id']         = $user['id'];
                        $_SESSION['user_id']    = $user['id']; 
                        $_SESSION['username']   = $user['username'];
                        $_SESSION['name']       = $user['first_name'] . ' ' . $user['last_name'];
                        $_SESSION['role']       = $user['role'];
                        $_SESSION['department'] = html_entity_decode($user['department'], ENT_QUOTES);
                        $_SESSION['login_time'] = time();

                        // Log successful login
                        logSecurityEvent('login_success', $user['id'], ['ip' => $client_ip, 'username' => $username_or_email]);

                        // 3. ROUTING LOGIC (LGU ONLY - MSWD and Treasury disabled)

                        // A. Super Admins (highest priority)
                        if ($user['role'] === 'SUPER_ADMIN') {
                            header("Location: admin/dashboard.php");
                            exit();
                        }

                        // B. Mayor's Office and LGU departments ONLY
                        if ($user['department'] === "Mayor's Office" || $user['department'] === 'Mayor Office' || $user['department'] === 'LGU') {
                            header("Location: lgu/dashboard.php");
                            exit();
                        }

                        // C. All other users redirect to LGU (MSWD and Treasury disabled)
                        header("Location: lgu/dashboard.php");
                        exit();

                    } else {
                        $error = "Invalid password.";
                        // Track failed login attempt
                        $is_blocked = trackLoginAttempt($username_or_email, false);
                        trackLoginAttempt($client_ip, false);
                        
                        logSecurityEvent('login_failed', null, ['ip' => $client_ip, 'username' => $username_or_email, 'reason' => 'invalid_password']);
                        
                        if ($is_blocked) {
                            $error = "Too many failed login attempts. Please try again later.";
                        }
                    }

                } else {
                    $error = "User not found.";
                    // Track failed login attempt
                    $is_blocked = trackLoginAttempt($username_or_email, false);
                    trackLoginAttempt($client_ip, false);
                    
                    logSecurityEvent('login_failed', null, ['ip' => $client_ip, 'username' => $username_or_email, 'reason' => 'user_not_found']);
                    
                    if ($is_blocked) {
                        $error = "Too many failed login attempts. Please try again later.";
                    }
                }

                $stmt->close();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - LGU System</title>
    <link rel="stylesheet" href="static_page/styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>

<div class="bg-blur blur1"></div>
<div class="bg-blur blur2"></div>

<div class="login-container">

    <div class="login-card">

        <div class="logo"><i class="fas fa-building"></i></div>

        <h1>LGU System</h1>
        <p>Login to continue</p>

        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

            <div class="input-group">
                <label>Username or Email</label>
                <input type="text" name="username" placeholder="Enter username or email" required autocomplete="username">
            </div>

            <div class="input-group">
                <label>Password</label>
                <input type="password" name="password" placeholder="Enter password" required autocomplete="current-password">
            </div>

            <button class="login-btn" type="submit" name="login">
                Login
            </button>

        </form>

        <?php if (!empty($error)): ?>
            <br>
            <p style="color:#ffb4b4; text-align: center; font-weight: 500;">
                <?php echo htmlspecialchars($error); ?>
            </p>
        <?php endif; ?>

    </div>

</div>

</body>
</html>