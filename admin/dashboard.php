<?php
session_start();
require_once '../config/security.php';
include '../config/db.php';

// Set security headers
setSecurityHeaders();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'SUPER_ADMIN') {
    logSecurityEvent('unauthorized_access', $_SESSION['id'] ?? null, ['endpoint' => 'admin_dashboard']);
    http_response_code(403);
    die("Access Denied: Super Admin privileges required.");
}

// Generate CSRF token
$csrf_token = generateCsrfToken();

$msg = $_SESSION['msg'] ?? '';
$msg_type = $_SESSION['msg_type'] ?? '';
unset($_SESSION['msg'], $_SESSION['msg_type']);

// Handle form submission
if (isset($_POST['create'])) {
    // CSRF validation
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        logSecurityEvent('csrf_validation_failed', $_SESSION['id'] ?? null, ['endpoint' => 'dashboard']);
        $_SESSION['msg'] = "Security validation failed";
        $_SESSION['msg_type'] = "error";
        header("Location: dashboard.php");
        exit();
    }

    // Validate required fields
    $required_fields = ['first_name', 'last_name', 'username', 'email', 'password', 'role', 'department'];
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            $_SESSION['msg'] = "Please fill in all required fields";
            $_SESSION['msg_type'] = "error";
            header("Location: dashboard.php");
            exit();
        }
    }

    // Sanitize inputs
    $first_name = sanitizeInput($_POST['first_name']);
    $last_name = sanitizeInput($_POST['last_name']);
    $username = sanitizeInput($_POST['username']);
    $email = sanitizeInput($_POST['email']);
    $password = $_POST['password'];
    $role = sanitizeInput($_POST['role']);
    $department = sanitizeInput($_POST['department']);

    // Validate email
    if (!validateEmail($email)) {
        $_SESSION['msg'] = "Invalid email address";
        $_SESSION['msg_type'] = "error";
        header("Location: dashboard.php");
        exit();
    }

    // Validate username
    if (!validateUsername($username)) {
        $_SESSION['msg'] = "Username must be 3-20 characters and contain only letters, numbers, and underscores";
        $_SESSION['msg_type'] = "error";
        header("Location: dashboard.php");
        exit();
    }

    // Validate password
    if (!validatePassword($password)) {
        $_SESSION['msg'] = "Password must be at least 8 characters with uppercase, lowercase, number, and special character";
        $_SESSION['msg_type'] = "error";
        header("Location: dashboard.php");
        exit();
    }

    // Validate permitted roles
    $allowed_roles = ['ADMIN', 'EMPLOYEE'];
    if (!in_array($role, $allowed_roles, true)) {
        $_SESSION['msg'] = "Invalid role selected.";
        $_SESSION['msg_type'] = "error";
        header("Location: dashboard.php");
        exit();
    }

    // Check if username already exists
    $check_username = $conn->prepare("SELECT id FROM users WHERE username = ?");
    if ($conn instanceof PDO) {
        // PostgreSQL/PDO
        $check_username->execute([$username]);
        $result = $check_username->fetchAll();
        if (count($result) > 0) {
            $_SESSION['msg'] = "Username already exists";
            $_SESSION['msg_type'] = "error";
            header("Location: dashboard.php");
            exit();
        }
    } else {
        // MySQLi
        $check_username->bind_param("s", $username);
        $check_username->execute();
        if ($check_username->get_result()->num_rows > 0) {
            $_SESSION['msg'] = "Username already exists";
            $_SESSION['msg_type'] = "error";
            $check_username->close();
            header("Location: dashboard.php");
            exit();
        }
        $check_username->close();
    }

    // Check if email already exists
    $check_email = $conn->prepare("SELECT id FROM users WHERE email = ?");
    if ($conn instanceof PDO) {
        // PostgreSQL/PDO
        $check_email->execute([$email]);
        $result = $check_email->fetchAll();
        if (count($result) > 0) {
            $_SESSION['msg'] = "Email already exists";
            $_SESSION['msg_type'] = "error";
            header("Location: dashboard.php");
            exit();
        }
    } else {
        // MySQLi
        $check_email->bind_param("s", $email);
        $check_email->execute();
        if ($check_email->get_result()->num_rows > 0) {
            $_SESSION['msg'] = "Email already exists";
            $_SESSION['msg_type'] = "error";
            $check_email->close();
            header("Location: dashboard.php");
            exit();
        }
        $check_email->close();
    }

    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Insert user into database
    $stmt = $conn->prepare("
        INSERT INTO users (first_name, last_name, username, email, password, role, department)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");

    if ($conn instanceof PDO) {
        // PostgreSQL/PDO
        $result = $stmt->execute([
            $first_name,
            $last_name,
            $username,
            $email,
            $hashed_password,
            $role,
            $department
        ]);
    } else {
        // MySQLi
        $stmt->bind_param("sssssss",
            $first_name,
            $last_name,
            $username,
            $email,
            $hashed_password,
            $role,
            $department
        );
        $result = $stmt->execute();
    }

    if ($result) {
        logSecurityEvent('user_created', $_SESSION['id'], [
            'username' => $username,
            'email' => $email,
            'role' => $role,
            'department' => $department
        ]);
        
        $_SESSION['msg'] = "Account for {$first_name} {$last_name} ({$role}) created successfully!";
        $_SESSION['msg_type'] = "success";
    } else {
        if ($conn instanceof PDO) {
            $error = $stmt->errorInfo();
            logError('Failed to create user: ' . ($error[2] ?? 'Unknown error'));
            $_SESSION['msg'] = "Database error: " . ($error[2] ?? 'Unknown error');
        } else {
            logError('Failed to create user: ' . $stmt->error);
            $_SESSION['msg'] = "Database error: " . $stmt->error;
        }
        $_SESSION['msg_type'] = "error";
    }

    if (!($conn instanceof PDO)) {
        $stmt->close();
        $conn->close();
    }

    header("Location: dashboard.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Super Admin Dashboard</title>
<link rel="stylesheet" href="styles.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>

<body>

<div class="bg-blur blur1"></div>
<div class="bg-blur blur2"></div>

<div class="container">

    <!-- SIDEBAR -->
    <aside class="sidebar">
        <div class="logo">🏛️</div>

        <a class="menu-btn active" href="dashboard.php">Dashboard</a>
        <a class="menu-btn" href="lgu_list.php">LGU Accounts</a>
        <a class="menu-btn logout" href="../logout.php">Logout</a>
    </aside>

    <!-- MAIN -->
    <main class="main-content">

        <!-- TOP -->
        <div class="top-bar">
            <h2>SUPER ADMIN DASHBOARD</h2>
        </div>

        <!-- WELCOME -->
        <section class="card">
            <h3>Welcome, <?= htmlspecialchars($_SESSION['name'] ?? 'Admin'); ?> 👋</h3>
            <p class="muted">Manage Sub Admin accounts, LGU employees, and system privileges efficiently.</p>
        </section>

        <!-- ALERTS -->
        <?php if (!empty($msg)): ?>
            <div class="card" style="border-left: 5px solid <?= $msg_type === 'success' ? '#28a745' : '#dc3545'; ?>;">
                <p style="color: <?= $msg_type === 'success' ? '#28a745' : '#dc3545'; ?>; margin: 0; font-weight: 500;">
                    <?= htmlspecialchars($msg); ?>
                </p>
            </div>
        <?php endif; ?>

        <!-- CREATE ACCOUNT FORM -->
        <section class="card" id="create">

            <div class="form-header">
                <h3>Create New User Account</h3>
                <p class="muted">Create Sub Admin (ADMIN) or LGU Employee (EMPLOYEE) accounts. Department determines the dashboard they access.</p>
            </div>

            <form method="POST" class="form-grid">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

                <div class="input-box">
                    <label>First Name</label>
                    <input type="text" name="first_name" placeholder="First Name" required>
                </div>

                <div class="input-box">
                    <label>Last Name</label>
                    <input type="text" name="last_name" placeholder="Last Name" required>
                </div>

                <div class="input-box">
                    <label>Username</label>
                    <input type="text" name="username" placeholder="Username" required>
                </div>

                <div class="input-box">
                    <label>Email Address</label>
                    <input type="email" name="email" placeholder="email@lgu.gov.ph" required>
                </div>

                <div class="input-box">
                    <label>Account Role</label>
                    <select name="role" required style="width: 100%; padding: 10px; border-radius: 8px;">
                        <option value="ADMIN">Sub Admin (ADMIN)</option>
                        <option value="EMPLOYEE" selected>LGU Employee (EMPLOYEE)</option>
                    </select>
                </div>

                <!-- DEPARTMENT DROPDOWN -->
                <div class="input-box">
                    <label>Department</label>
                    <select name="department" required style="width: 100%; padding: 10px; border-radius: 8px;">
                        <option value="" disabled selected>Select Department</option>
                        <option value="Treasury">Treasury</option>
                        <option value="Mayor's Office">Mayor's Office</option>
                        <option value="MSWD">MSWD (Social Welfare)</option>
                        <option value="HR">HR</option>
                        <option value="BAC">BAC</option>
                        <option value="IT">IT</option>
                        <option value="Health">Health Services</option>
                    </select>
                </div>

                <div class="input-box full">
                    <label>Password</label>
                    <input type="password" name="password" placeholder="Minimum 8 characters with uppercase, lowercase, number, and special character" required>
                </div>

                <button class="btn-primary" type="submit" name="create">
                    Create Account
                </button>

            </form>

        </section>

        <!-- EXISTING USERS -->
        <section class="card">
            <h3><i class="fas fa-users-cog"></i> Existing Users</h3>
            
            <?php
            $users_stmt = $conn->prepare("
                SELECT id, first_name, last_name, username, email, role, department, created_at 
                FROM users 
                WHERE role IN ('ADMIN', 'EMPLOYEE') 
                ORDER BY created_at DESC
            ");
            if ($conn instanceof PDO) {
                // PostgreSQL/PDO
                $users_stmt->execute();
                $users = $users_stmt->fetchAll();
            } else {
                // MySQLi
                $users_stmt->execute();
                $users = $users_stmt->get_result();
            }
            
            if ($conn instanceof PDO) {
                if (count($users) > 0):
            ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Department</th>
                            <th>Created</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></strong></td>
                            <td><?= htmlspecialchars($user['username']) ?></td>
                            <td><?= htmlspecialchars($user['email']) ?></td>
                            <td>
                                <span style="font-size: 0.85rem; font-weight: 600; 
                                    color: <?= $user['role'] === 'ADMIN' ? '#3b82f6' : '#22c55e' ?>;">
                                    <?= htmlspecialchars($user['role']) ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($user['department']) ?></td>
                            <td><?= date('M j, Y', strtotime($user['created_at'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="muted">No users created yet.</p>
            <?php endif; ?>
            <?php } else { ?>
                <?php if ($users && $users->num_rows > 0): ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Department</th>
                            <th>Created</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($user = $users->fetch_assoc()): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></strong></td>
                            <td><?= htmlspecialchars($user['username']) ?></td>
                            <td><?= htmlspecialchars($user['email']) ?></td>
                            <td>
                                <span style="font-size: 0.85rem; font-weight: 600; 
                                    color: <?= $user['role'] === 'ADMIN' ? '#3b82f6' : '#22c55e' ?>;">
                                    <?= htmlspecialchars($user['role']) ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($user['department']) ?></td>
                            <td><?= date('M j, Y', strtotime($user['created_at'])) ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="muted">No users created yet.</p>
            <?php endif; ?>
            <?php } ?>
            <?php if (!($conn instanceof PDO)) { $users_stmt->close(); } ?>
        </section>

    </main>

</div>

</body>
</html>