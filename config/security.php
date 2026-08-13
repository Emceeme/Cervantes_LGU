<?php
// Load environment variables first
require_once __DIR__ . '/env.php';

// Security Configuration and Helper Functions

// Start secure session
function startSecureSession() {
    if (session_status() === PHP_SESSION_NONE) {
        // Detect if HTTPS is enabled
        $is_https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || 
                    ($_SERVER['SERVER_PORT'] == 443) ||
                    (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
        
        // Allow environment override for load balancers/proxies
        $force_https = env('FORCE_HTTPS', false);
        
        // Secure session settings
        ini_set('session.cookie_httponly', 1);
        ini_set('session.cookie_secure', ($is_https || $force_https) ? 1 : 0);
        ini_set('session.use_strict_mode', 1);
        ini_set('session.cookie_samesite', ($is_https || $force_https) ? 'Strict' : 'Lax');
        
        session_start();
        
        // Regenerate session ID periodically
        if (!isset($_SESSION['created'])) {
            $_SESSION['created'] = time();
        } else if (time() - $_SESSION['created'] > 1800) {
            // Session started more than 30 minutes ago
            session_regenerate_id(true);
            $_SESSION['created'] = time();
        }
    }
}

// CSRF Token Generation
function generateCsrfToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// CSRF Token Validation
function validateCsrfToken($token) {
    if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        return false;
    }
    return true;
}

// Input Sanitization
function sanitizeInput($input) {
    if (is_array($input)) {
        return array_map('sanitizeInput', $input);
    }
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// Input Validation
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function validateUsername($username) {
    return preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username);
}

function validatePassword($password) {
    $min_length = env('PASSWORD_MIN_LENGTH', 8);
    $require_upper = env('PASSWORD_REQUIRE_UPPERCASE', true);
    $require_lower = env('PASSWORD_REQUIRE_LOWERCASE', true);
    $require_number = env('PASSWORD_REQUIRE_NUMBER', true);
    $require_special = env('PASSWORD_REQUIRE_SPECIAL', true);
    
    if (strlen($password) < $min_length) {
        return false;
    }
    
    if ($require_upper && !preg_match('/[A-Z]/', $password)) {
        return false;
    }
    
    if ($require_lower && !preg_match('/[a-z]/', $password)) {
        return false;
    }
    
    if ($require_number && !preg_match('/[0-9]/', $password)) {
        return false;
    }
    
    if ($require_special && !preg_match('/[^a-zA-Z0-9]/', $password)) {
        return false;
    }
    
    return true;
}

// Rate Limiting
function checkRateLimit($identifier, $max_requests = null, $window = null) {
    $max_requests = $max_requests ?? env('RATE_LIMIT_REQUESTS', 100);
    $window = $window ?? env('RATE_LIMIT_WINDOW', 60);
    
    $rate_limit_file = __DIR__ . '/../storage/rate_limits/' . md5($identifier) . '.json';
    $rate_dir = dirname($rate_limit_file);
    
    // Create directory if it doesn't exist
    if (!is_dir($rate_dir)) {
        mkdir($rate_dir, 0755, true);
    }
    
    $current_time = time();
    $data = ['requests' => [], 'blocked_until' => 0];
    
    if (file_exists($rate_limit_file)) {
        $data = json_decode(file_get_contents($rate_limit_file), true);
    }
    
    // Check if currently blocked
    if ($data['blocked_until'] > $current_time) {
        return [
            'allowed' => false,
            'remaining' => 0,
            'retry_after' => $data['blocked_until'] - $current_time
        ];
    }
    
    // Clean old requests outside the window
    $data['requests'] = array_filter($data['requests'], function($timestamp) use ($current_time, $window) {
        return $current_time - $timestamp < $window;
    });
    
    // Check if limit exceeded
    if (count($data['requests']) >= $max_requests) {
        $data['blocked_until'] = $current_time + ($window * 2); // Block for 2x the window
        file_put_contents($rate_limit_file, json_encode($data));
        
        return [
            'allowed' => false,
            'remaining' => 0,
            'retry_after' => $data['blocked_until'] - $current_time
        ];
    }
    
    // Add current request
    $data['requests'][] = $current_time;
    file_put_contents($rate_limit_file, json_encode($data));
    
    $remaining = $max_requests - count($data['requests']);
    
    return [
        'allowed' => true,
        'remaining' => $remaining,
        'retry_after' => 0
    ];
}

// Login Attempt Tracking
function trackLoginAttempt($identifier, $success = false) {
    $attempt_file = __DIR__ . '/../storage/login_attempts/' . md5($identifier) . '.json';
    $attempt_dir = dirname($attempt_file);
    
    if (!is_dir($attempt_dir)) {
        mkdir($attempt_dir, 0755, true);
    }
    
    $current_time = time();
    $window = env('LOGIN_ATTEMPT_WINDOW', 900); // 15 minutes default
    $max_attempts = env('MAX_LOGIN_ATTEMPTS', 5);
    
    $data = ['attempts' => [], 'blocked_until' => 0];
    
    if (file_exists($attempt_file)) {
        $data = json_decode(file_get_contents($attempt_file), true);
    }
    
    // Clean old attempts outside the window
    $data['attempts'] = array_filter($data['attempts'], function($timestamp) use ($current_time, $window) {
        return $current_time - $timestamp < $window;
    });
    
    if (!$success) {
        $data['attempts'][] = $current_time;
        
        // Check if should be blocked
        if (count($data['attempts']) >= $max_attempts) {
            $data['blocked_until'] = $current_time + $window;
        }
    } else {
        // Reset on successful login
        $data['attempts'] = [];
        $data['blocked_until'] = 0;
    }
    
    file_put_contents($attempt_file, json_encode($data));
    
    return $data['blocked_until'] > $current_time;
}

function isLoginBlocked($identifier) {
    $attempt_file = __DIR__ . '/../storage/login_attempts/' . md5($identifier) . '.json';
    
    if (!file_exists($attempt_file)) {
        return false;
    }
    
    $data = json_decode(file_get_contents($attempt_file), true);
    $current_time = time();
    
    return $data['blocked_until'] > $current_time;
}

// Security Headers
function setSecurityHeaders() {
    $is_https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || 
                ($_SERVER['SERVER_PORT'] == 443) ||
                (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
    
    header("X-Frame-Options: DENY");
    header("X-Content-Type-Options: nosniff");
    header("X-XSS-Protection: 1; mode=block");
    
    // Only set HSTS if HTTPS is enabled
    if ($is_https) {
        header("Strict-Transport-Security: max-age=31536000; includeSubDomains; preload");
    }
    
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdnjs.cloudflare.com https://fonts.googleapis.com https://www.google.com https://www.gstatic.com; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdnjs.cloudflare.com; font-src 'self' https://fonts.gstatic.com https://cdnjs.cloudflare.com; img-src 'self' data: https: http:; connect-src 'self' https://cdnjs.cloudflare.com; frame-ancestors 'none';");
    header("Referrer-Policy: strict-origin-when-cross-origin");
    header("Permissions-Policy: geolocation=(self), microphone=(), camera=(), payment=()");
}

// Error Logging
function logError($message, $context = []) {
    $log_file = __DIR__ . '/../storage/logs/error_' . date('Y-m-d') . '.log';
    $log_dir = dirname($log_file);
    
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $log_entry = "[$timestamp] $message";
    
    if (!empty($context)) {
        $log_entry .= " | Context: " . json_encode($context);
    }
    
    $log_entry .= "\n";
    
    file_put_contents($log_file, $log_entry, FILE_APPEND);
}

// Security Audit Log
function logSecurityEvent($event_type, $user_id = null, $details = []) {
    $log_file = __DIR__ . '/../storage/logs/security_' . date('Y-m-d') . '.log';
    $log_dir = dirname($log_file);
    
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    
    $log_entry = json_encode([
        'timestamp' => $timestamp,
        'event_type' => $event_type,
        'user_id' => $user_id,
        'ip_address' => $ip_address,
        'user_agent' => $user_agent,
        'details' => $details
    ]) . "\n";
    
    file_put_contents($log_file, $log_entry, FILE_APPEND);
}

// HTTPS Redirect Middleware
function enforceHttps() {
    // Check if HTTPS enforcement is enabled
    if (!env('FORCE_HTTPS', false)) {
        return;
    }
    
    // Check if already on HTTPS
    $is_https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || 
                ($_SERVER['SERVER_PORT'] == 443) ||
                (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
    
    if (!$is_https) {
        // Redirect to HTTPS
        $host = $_SERVER['HTTP_HOST'];
        $uri = $_SERVER['REQUEST_URI'];
        $https_url = 'https://' . $host . $uri;
        
        header('HTTP/1.1 301 Moved Permanently');
        header('Location: ' . $https_url);
        exit();
    }
}
