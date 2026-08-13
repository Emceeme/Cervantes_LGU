<?php
session_start();
require_once '../../config/security.php';
require_once '../../config/db.php';

// Set security headers
setSecurityHeaders();

// Generate CSRF token
if (!isset($_SESSION['applicant_csrf_token'])) {
    $_SESSION['applicant_csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['applicant_csrf_token'];

// Handle registration
$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['applicant_csrf_token']) {
        $error_message = "Security validation failed. Please try again.";
    } else {
        $first_name = trim($_POST['first_name']);
        $last_name = trim($_POST['last_name']);
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        $contact_number = trim($_POST['contact_number']);
        $barangay = trim($_POST['barangay']);

        // Validation
        if (empty($first_name) || empty($last_name) || empty($username) || empty($email) || empty($password)) {
            $error_message = "All required fields must be filled.";
        } elseif ($password !== $confirm_password) {
            $error_message = "Passwords do not match.";
        } elseif (!validatePassword($password)) {
            $error_message = "Password must be at least 8 characters with uppercase, lowercase, number, and special character.";
        } elseif (!validateEmail($email)) {
            $error_message = "Invalid email format.";
        } else {
            // Check if username exists
            $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                $error_message = "Username already exists.";
                $stmt->close();
            } else {
                $stmt->close();
                
                // Check if email exists
                $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
                $stmt->bind_param("s", $email);
                $stmt->execute();
                if ($stmt->get_result()->num_rows > 0) {
                    $error_message = "Email already registered.";
                    $stmt->close();
                } else {
                    $stmt->close();
                    
                    // Create user account
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $full_name = $first_name . ' ' . $last_name;
                    
                    $stmt = $conn->prepare("INSERT INTO users (username, email, password, first_name, last_name, role, department, contact_number, barangay) VALUES (?, ?, ?, ?, ?, 'APPLICANT', 'MSWD', ?, ?)");
                    $stmt->bind_param("ssssss", $username, $email, $hashed_password, $first_name, $last_name, $contact_number, $barangay);
                    
                    if ($stmt->execute()) {
                        $success_message = "Registration successful! You can now log in to track your applications.";
                        unset($_SESSION['applicant_csrf_token']);
                    } else {
                        $error_message = "Registration failed: " . $stmt->error;
                    }
                    $stmt->close();
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - MSWD Portal</title>
    <link rel="stylesheet" href="../assets/css/mswd.css">
</head>
<body>
    <div class="container">
        <div class="auth-card">
            <div class="auth-header">
                <h1>MSWD Portal</h1>
                <p>Municipal Social Welfare and Development</p>
                <h2>Create Account</h2>
            </div>
            
            <?php if ($error_message): ?>
            <div class="alert alert-error"><?php echo $error_message; ?></div>
            <?php endif; ?>
            
            <?php if ($success_message): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
            <div class="auth-footer">
                <a href="../../login.php">Go to Login</a>
            </div>
            <?php else: ?>
            
            <form method="POST" action="" class="auth-form">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                
                <div class="form-group">
                    <label>First Name *</label>
                    <input type="text" name="first_name" required value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label>Last Name *</label>
                    <input type="text" name="last_name" required value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label>Username *</label>
                    <input type="text" name="username" required value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                    <small>3-20 characters, letters and numbers only</small>
                </div>
                
                <div class="form-group">
                    <label>Email *</label>
                    <input type="email" name="email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label>Contact Number *</label>
                    <input type="text" name="contact_number" required value="<?php echo htmlspecialchars($_POST['contact_number'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label>Barangay *</label>
                    <input type="text" name="barangay" required value="<?php echo htmlspecialchars($_POST['barangay'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label>Password *</label>
                    <input type="password" name="password" required>
                    <small>Min 8 characters with uppercase, lowercase, number, and special character</small>
                </div>
                
                <div class="form-group">
                    <label>Confirm Password *</label>
                    <input type="password" name="confirm_password" required>
                </div>
                
                <button type="submit" class="btn btn-primary">Create Account</button>
            </form>
            
            <div class="auth-footer">
                <p>Already have an account? <a href="../../login.php">Login here</a></p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
