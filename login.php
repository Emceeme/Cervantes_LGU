<?php
session_start();
include 'config/db.php';

$error = "";

if (isset($_POST['login'])) {

    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE username=?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {

        $user = $result->fetch_assoc();

        if (password_verify($password, $user['password'])) {

            $_SESSION['id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['name'] = $user['first_name'];

            // REDIRECT BASED ON ROLE
            if ($user['role'] == "SUPER_ADMIN") {
                header("Location: admin/dashboard.php");
            } else {
                header("Location: lgu/dashboard.php");
            }
            exit();

        } else {
            $error = "Wrong password";
        }

    } else {
        $error = "User not found";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
    <link rel="stylesheet" href="styles.css">
</head>

<body>

<!-- background blur -->
<div class="bg-blur blur1"></div>
<div class="bg-blur blur2"></div>

<div class="login-container">

    <div class="login-card">

        <div class="logo">🏛️</div>

        <h1>LGU System</h1>
        <p>Login to continue</p>

        <form method="POST">

            <div class="input-group">
                <label>Username</label>
                <input type="text" name="username" placeholder="Enter username" required>
            </div>

            <div class="input-group">
                <label>Password</label>
                <input type="password" name="password" placeholder="Enter password" required>
            </div>

            <button class="login-btn" type="submit" name="login">
                Login
            </button>

        </form>

        <br>

        <p style="color:#ffb4b4;">
            <?php echo $error; ?>
        </p>

    </div>

</div>

</body>
</html>