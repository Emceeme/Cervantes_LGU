<?php
session_start();
include '../config/db.php';

if (!isset($_SESSION['name'])) {
    header("Location: ../login.php");
    exit();
}

$result = $conn->query("SELECT * FROM users WHERE role='LGU' ORDER BY id DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>LGU Accounts</title>
<link rel="stylesheet" href="styles.css">
</head>

<body>

<div class="bg-blur blur1"></div>
<div class="bg-blur blur2"></div>

<div class="container">

    <!-- SIDEBAR -->
    <aside class="sidebar">
        <div class="logo">🏛️</div>

        <a class="menu-btn" href="dashboard.php">Dashboard</a>
        <a class="menu-btn active" href="lgu_list.php">LGU Accounts</a>
        <a class="menu-btn logout" href="../logout.php">Logout</a>
    </aside>

    <!-- MAIN -->
    <main class="main-content">

        <div class="top-bar">
            <h2>LGU ACCOUNTS</h2>
        </div>

        <section class="card">

            <h3>Registered LGU Employees</h3>
            <p class="muted">All LGU accounts created by Super Admin</p>

            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>
                    <?php while ($row = $result->fetch_assoc()) { ?>
                        <tr>
                            <td><?php echo $row['id']; ?></td>
                            <td><?php echo $row['first_name'] . " " . $row['last_name']; ?></td>
                            <td><?php echo $row['username']; ?></td>
                            <td><?php echo $row['email']; ?></td>
                            <td><?php echo $row['role']; ?></td>
                            <td>
                                <a href="delete_user.php?id=<?php echo $row['id']; ?>" class="btn-danger">Delete</a>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>

            </table>

        </section>

    </main>

</div>

</body>
</html>