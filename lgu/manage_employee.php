<?php
session_start();
include '../config/db.php';

// Only ADMIN (Sub Admin) and SUPER_ADMIN are authorized to access employee management
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['ADMIN', 'SUPER_ADMIN'], true)) {
    die("Access denied: You do not have permissions to manage employees.");
}

// Fetch session info
$admin_dept = $_SESSION['department'] ?? '';
$user_role  = $_SESSION['role'];

// Fetch employees: SUPER_ADMIN sees all; Sub Admin sees employees in their department
if ($user_role === 'SUPER_ADMIN') {
    $stmt = $conn->prepare("SELECT id, first_name, last_name, username, email, department, role, created_at FROM users WHERE role = 'EMPLOYEE' ORDER BY id DESC");
} else {
    $stmt = $conn->prepare("SELECT id, first_name, last_name, username, email, department, role, created_at FROM users WHERE role = 'EMPLOYEE' AND department = ? ORDER BY id DESC");
    $stmt->bind_param("s", $admin_dept);
}

$stmt->execute();
$employees = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Employee Management</title>
<link rel="stylesheet" href="jobs.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>

<body>

<?php if (isset($_GET['success'])): ?>
<div id="successPopup" class="success-popup">
    Employee account created successfully!
</div>
<?php endif; ?>

<?php if (isset($_GET['error'])): ?>
<div id="successPopup" class="success-popup" style="background: #dc3545;">
    <?= htmlspecialchars($_GET['error']); ?>
</div>
<?php endif; ?>

<div class="bg-blur blur1"></div>
<div class="bg-blur blur2"></div>

<div class="container">

    <!-- SIDEBAR -->
    <aside class="sidebar">
        <div class="logo">🏛️</div>

        <a href="dashboard.php">Dashboard</a>
        <a href="applicants.php">Applicants</a>
        <a href="procurement.php">Procurement</a>
        <a href="newsfeed.php">News Feed</a>
        
        <?php if ($_SESSION['role'] === 'ADMIN' || $_SESSION['role'] === 'SUPER_ADMIN'): ?>
            <a href="manage_employees.php" class="active">Employees</a>
        <?php endif; ?>

        <a href="../logout.php">Logout</a>
    </aside>

    <!-- MAIN -->
    <main class="main-content">

        <div class="top-bar">
            <h2>Employee Management</h2>
        </div>

        <button id="openModal" class="add-btn">+</button>

        <section class="card">

            <h3>Department Staff</h3>
            <p class="muted">
                Manage staff members for 
                <strong><?= htmlspecialchars($admin_dept ? $admin_dept : 'All Departments'); ?></strong>
            </p>

            <table class="table">

                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Department</th>
                        <th>Role</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>

                <?php if ($employees->num_rows > 0): ?>
                    <?php while ($row = $employees->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?></td>
                        <td><?= htmlspecialchars($row['username']) ?></td>
                        <td><?= htmlspecialchars($row['email']) ?></td>
                        <td><?= htmlspecialchars($row['department']) ?></td>

                        <td>
                            <span class="status">
                                <?= htmlspecialchars($row['role']) ?>
                            </span>
                        </td>

                        <td>
                            <a class="btn-danger"
                               href="handler/delete_employee.php?id=<?= $row['id'] ?>"
                               onclick="return confirm('Delete this employee account?')">
                               Delete
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="text-align: center;" class="muted">No employees registered yet.</td>
                    </tr>
                <?php endif; ?>

                </tbody>

            </table>

        </section>

    </main>

</div>

<!-- MODAL FOR CREATING EMPLOYEE -->
<div id="jobModal" class="modal">

    <div class="modal-content">

        <span id="closeModal" class="close">&times;</span>

        <h2>Add New Employee</h2>

        <form action="handler/post_employee.php" method="POST">

            <input type="text" name="first_name" placeholder="First Name" required>

            <input type="text" name="last_name" placeholder="Last Name" required>

            <input type="text" name="username" placeholder="Username" required>

            <input type="email" name="email" placeholder="Email Address" required>

            <input type="password" name="password" placeholder="Account Password" required>

            <!-- Department Selection: Locked to Admin's Department if Sub Admin -->
            <?php if ($user_role === 'ADMIN'): ?>
                <input type="text" name="department" value="<?= htmlspecialchars($admin_dept); ?>" readonly style="opacity: 0.8; cursor: not-allowed;">
            <?php else: ?>
                <input type="text" name="department" placeholder="Department (e.g. HR, IT, BAC)" required>
            <?php endif; ?>

            <button class="post-btn" type="submit">
                Create Account
            </button>

        </form>

    </div>

</div>

<script>
const modal = document.getElementById("jobModal");
const openBtn = document.getElementById("openModal");
const closeBtn = document.getElementById("closeModal");

openBtn.onclick = () => {
    modal.style.display = "flex";
};

closeBtn.onclick = () => {
    modal.style.display = "none";
};

window.onclick = (e) => {
    if (e.target === modal) {
        modal.style.display = "none";
    }
};

// Success Popup Auto-Fade
const popup = document.getElementById("successPopup");

if (popup) {
    setTimeout(() => {
        popup.style.opacity = "0";
        setTimeout(() => popup.remove(), 500);
    }, 3000);
}
</script>

</body>
</html>