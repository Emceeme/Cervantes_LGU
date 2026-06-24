<?php
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] != "SUPER_ADMIN") {
    die("Access denied");
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
        <a class="menu-btn" href="#create">Create LGU</a>
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
            <h3>Welcome, <?php echo $_SESSION['name']; ?> 👋</h3>
            <p class="muted">Manage LGU accounts, users, and system access efficiently.</p>
        </section>

        <!-- CREATE LGU -->
        <section class="card" id="create">

            <div class="form-header">
                <h3>Create LGU Employee</h3>
                <p class="muted">Fill out the form to add a new LGU account.</p>
            </div>

            <form method="POST" action="create_lgu.php" class="form-grid">

                <div class="input-box">
                    <label>First Name</label>
                    <input type="text" name="first_name" placeholder="FirstName" required>
                </div>

                <div class="input-box">
                    <label>Last Name</label>
                    <input type="text" name="last_name" placeholder="LastName" required>
                </div>

                <div class="input-box">
                    <label>Username</label>
                    <input type="text" name="username" placeholder="Username" required>
                </div>

                <div class="input-box">
                    <label>Email</label>
                    <input type="email" name="email" placeholder="Email" required>
                </div>

                <div class="input-box full">
                    <label>Password</label>
                    <input type="password" name="password" placeholder="••••••••" required>
                </div>

                <button class="btn-primary" type="submit" name="create">
                    Create LGU Account
                </button>

            </form>

        </section>

    </main>

</div>

</body>
</html>