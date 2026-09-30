<?php
session_start();
require_once __DIR__ . '/../../config/security.php';
require_once __DIR__ . '/../../config/db.php';

setSecurityHeaders();

$csrf_token = generateCsrfToken();

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        $error_message = "Security validation failed.";
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $email = trim($_POST['email'] ?? '');
        $first_name = trim($_POST['first_name'] ?? '');
        $last_name = trim($_POST['last_name'] ?? '');

        // Validate input
        if (empty($username) || empty($password) || empty($email) || empty($first_name) || empty($last_name)) {
            $error_message = "All fields are required.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error_message = "Invalid email address.";
        } elseif (strlen($password) < 8) {
            $error_message = "Password must be at least 8 characters.";
        } else {
            // Check if username already exists
            $check_stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            
            if ($conn instanceof PDO) {
                $check_stmt->execute([$username, $email]);
                $exists = $check_stmt->fetch();
            } else {
                $check_stmt->bind_param("ss", $username, $email);
                $check_stmt->execute();
                $result = $check_stmt->get_result();
                $exists = $result->num_rows > 0;
                $check_stmt->close();
            }

            if ($exists) {
                $error_message = "Username or email already exists.";
            } else {
                // Create user
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $insert_stmt = $conn->prepare("
                    INSERT INTO users (username, password, email, first_name, last_name, role, department)
                    VALUES (?, ?, ?, ?, ?, 'APPLICANT', 'MSWD')
                ");

                if ($conn instanceof PDO) {
                    $insert_stmt->execute([$username, $hashed_password, $email, $first_name, $last_name]);
                } else {
                    $insert_stmt->bind_param("sssss", $username, $hashed_password, $email, $first_name, $last_name);
                    $insert_stmt->execute();
                    $insert_stmt->close();
                }

                $success_message = "Registration successful! You can now login.";
                logSecurityEvent('user_registered', null, ['username' => $username, 'role' => 'APPLICANT']);
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/mswd.css">
</head>
<body>

<div class="container">
    <div class="auth-container">
        <div class="auth-header">
            <i class="fas fa-hands-helping"></i>
            <h1>MSWD Portal</h1>
            <p>Create your applicant account</p>
        </div>

        <?php if ($error_message): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <?php if ($success_message): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
        <div class="auth-links">
            <a href="../../login.php">Go to Login</a>
        </div>
        <?php else: ?>
        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" required>
            </div>

            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" required>
            </div>

            <div class="form-group">
                <label>First Name</label>
                <input type="text" name="first_name" required>
            </div>

            <div class="form-group">
                <label>Last Name</label>
                <input type="text" name="last_name" required>
            </div>

            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required minlength="8">
            </div>

            <button type="submit" class="btn btn-primary">Register</button>
        </form>

        <div class="auth-links">
            <p>Already have an account? <a href="../../login.php">Login here</a></p>
        </div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
