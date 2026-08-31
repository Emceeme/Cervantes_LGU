<?php
session_start();
require_once '../config/security.php';
include '../config/db.php';

// Set security headers
setSecurityHeaders();

// 🔒 SECURITY GUARD: Super Admin only
if (!isset($_SESSION['name']) || $_SESSION['role'] !== 'SUPER_ADMIN') {
    logSecurityEvent('unauthorized_access', $_SESSION['id'] ?? null, ['endpoint' => 'admin_lgu_list']);
    header("Location: /login.php?unauthorized=1");
    exit();
}

// Fetch all LGU users (Excluding Super Admins) to show Role and Department
$query = "SELECT id, first_name, last_name, username, email, role, department 
          FROM users 
          WHERE role != 'SUPER_ADMIN' 
          ORDER BY department ASC, id DESC";
$result_stmt = $conn->prepare($query);
$result_stmt->execute();

if ($conn instanceof PDO) {
    // PostgreSQL/PDO
    $result = $result_stmt->fetchAll();
} else {
    // MySQLi
    $result = $result_stmt->get_result();
    $result_stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>LGU Accounts</title>
<link rel="stylesheet" href="../static_page/styles.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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
            <p class="muted">All Sub-Admins and Staff accounts across different departments.</p>

            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Department</th> <!-- Added Department Column -->
                        <th>Role</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if ($conn instanceof PDO): ?>
                        <?php if (count($result) > 0): ?>
                            <?php foreach ($result as $row): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['id']); ?></td>
                                    <td><?php echo htmlspecialchars($row['first_name'] . " " . $row['last_name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['username']); ?></td>
                                    <td><?php echo htmlspecialchars($row['email']); ?></td>
                                    
                                    <!-- Department Column Output -->
                                    <td>
                                        <span style="font-weight: 500; color: #38bdf8;">
                                            <?php echo htmlspecialchars($row['department'] ?? 'Unassigned'); ?>
                                        </span>
                                    </td>
                                    
                                    <!-- Role Output -->
                                    <td>
                                        <span style="background: rgba(255,255,255,0.1); padding: 4px 8px; border-radius: 6px; font-size: 0.85rem;">
                                            <?php echo htmlspecialchars($row['role']); ?>
                                        </span>
                                    </td>
                                    
                                    <td>
                                        <a href="handler/delete_user.php?id=<?php echo $row['id']; ?>" class="btn-danger" onclick="return confirm('Are you sure you want to delete this user?');">Delete</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" style="text-align: center; color: #94a3b8;">No LGU accounts found.</td>
                            </tr>
                        <?php endif; ?>
                    <?php else: ?>
                        <?php if ($result->num_rows > 0): ?>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['id']); ?></td>
                                    <td><?php echo htmlspecialchars($row['first_name'] . " " . $row['last_name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['username']); ?></td>
                                    <td><?php echo htmlspecialchars($row['email']); ?></td>
                                    
                                    <!-- Department Column Output -->
                                    <td>
                                        <span style="font-weight: 500; color: #38bdf8;">
                                            <?php echo htmlspecialchars($row['department'] ?? 'Unassigned'); ?>
                                        </span>
                                    </td>
                                    
                                    <!-- Role Output -->
                                    <td>
                                        <span style="background: rgba(255,255,255,0.1); padding: 4px 8px; border-radius: 6px; font-size: 0.85rem;">
                                            <?php echo htmlspecialchars($row['role']); ?>
                                        </span>
                                    </td>
                                    
                                    <td>
                                        <a href="handler/delete_user.php?id=<?php echo $row['id']; ?>" class="btn-danger" onclick="return confirm('Are you sure you want to delete this user?');">Delete</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" style="text-align: center; color: #94a3b8;">No LGU accounts found.</td>
                            </tr>
                        <?php endif; ?>
                    <?php endif; ?>
                </tbody>

            </table>

        </section>

    </main>

</div>

</body>
</html>