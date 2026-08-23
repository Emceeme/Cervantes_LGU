<?php
session_start();
require_once '../config/security.php';
include '../config/db.php';

// Set security headers
setSecurityHeaders();

// SECURITY GUARD: Restrict access to Mayor's Office, LGU departments & Super Admins only
$department = html_entity_decode($_SESSION['department'] ?? '', ENT_QUOTES);
if (!isset($_SESSION['role']) || ($department !== "Mayor's Office" && $department !== 'Mayor Office' && $department !== 'LGU' && $_SESSION['role'] !== 'SUPER_ADMIN')) {
    logSecurityEvent('unauthorized_access', $_SESSION['id'] ?? null, ['endpoint' => 'lgu_dashboard', 'department' => $department]);
    header('Location: /login.php?unauthorized=1');
    exit();
}

// Fetch job listings
$jobs_stmt = $conn->prepare("SELECT * FROM jobs ORDER BY id DESC");

if ($conn instanceof PDO) {
    // PostgreSQL/PDO
    $jobs_stmt->execute();
    $jobs = $jobs_stmt->fetchAll();
} else {
    // MySQLi
    $jobs_stmt->execute();
    $jobs = $jobs_stmt->get_result();
    $jobs_stmt->close();
}

// Generate CSRF token
$csrf_token = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Job Management</title>
<link rel="stylesheet" href="jobs.css">
</head>

<body>

<?php if(isset($_GET['success'])): ?>
<div class="success-toast">
    ✓ Job posted successfully!
</div>
<?php endif; ?>

<div class="container">

    <!-- SIDEBAR -->
    <aside class="sidebar">
        <div class="logo">LGU <span>Portal</span></div>
        <nav class="sidebar-nav">
            <a href="dashboard.php" class="active">Dashboard</a>
            <a href="applicants.php">Applicants</a>
            <a href="procurement.php">Procurement</a>
            <a href="newsfeed.php">News Feed</a>
            <a href="scholarship_applications.php">Scholarship Applications</a>
            <a href="settings.php">Settings</a>
            <a href="../logout.php">Logout</a>
        </nav>
    </aside>

    <!-- MAIN -->
    <main class="main-content">

        <div class="top-bar">
            <h2>Job Management</h2>
            <p>Manage all job postings and vacancies</p>
        </div>

        <button id="openModal" class="add-btn">+</button>

        <section class="card">
            <div class="card-header">
                <h3>Posted Jobs</h3>
            </div>

            <table class="table">

                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Department</th>
                        <th>Type</th>
                        <th>Salary</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>

                <?php if($conn instanceof PDO): ?>
                    <?php foreach($jobs as $row): ?>
                <tr>
                    <td><?= htmlspecialchars($row['job_title']) ?></td>
                    <td><?= htmlspecialchars($row['department']) ?></td>
                    <td><?= htmlspecialchars($row['employment_type']) ?></td>
                    <td><?= htmlspecialchars($row['salary']) ?></td>

                    <td>
                        <span class="status <?= strtolower($row['status']) ?>">
                            <?= htmlspecialchars($row['status']) ?>
                        </span>
                    </td>

                    <td>
                        <a class="btn-danger"
                           href="handler/delete_job.php?id=<?= $row['id'] ?>"
                           onclick="return confirm('Delete this job?')">
                           Delete
                        </a>
                    </td>
                </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <?php while($row = $jobs->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['job_title']) ?></td>
                    <td><?= htmlspecialchars($row['department']) ?></td>
                    <td><?= htmlspecialchars($row['employment_type']) ?></td>
                    <td><?= htmlspecialchars($row['salary']) ?></td>

                    <td>
                        <span class="status <?= strtolower($row['status']) ?>">
                            <?= htmlspecialchars($row['status']) ?>
                        </span>
                    </td>

                    <td>
                        <a class="btn-danger"
                           href="handler/delete_job.php?id=<?= $row['id'] ?>"
                           onclick="return confirm('Delete this job?')">
                           Delete
                        </a>
                    </td>
                </tr>
                    <?php endwhile; ?>
                <?php endif; ?>

                </tbody>

            </table>

        </section>

    </main>

</div>

<!-- MODAL -->
<div id="jobModal" class="modal">

    <div class="modal-content">
        <div class="modal-header">
            <h3>Create Job Posting</h3>
            <span id="closeModal" class="close">&times;</span>
        </div>

        <!-- FIXED PATH -->
        <form action="handler/post_job.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

            <div class="form-group">
                <label>Job Title</label>
                <input type="text" name="job_title" placeholder="Enter job title" required>
            </div>

            <div class="form-group">
                <label>Department</label>
                <input type="text" name="department" placeholder="Enter department" required>
            </div>

            <div class="form-group">
                <label>Employment Type</label>
                <select name="employment_type" required>
                    <option value="">Select Employment Type</option>
                    <option value="Permanent">Permanent</option>
                    <option value="Contractual">Contractual</option>
                    <option value="Job Order">Job Order</option>
                    <option value="Part-Time">Part-Time</option>
                </select>
            </div>

            <div class="form-group">
                <label>Salary</label>
                <input type="text" name="salary" placeholder="Enter salary range">
            </div>

            <div class="form-group">
                <label>Location</label>
                <input type="text" name="location" placeholder="Enter location">
            </div>

            <div class="form-group">
                <label>Job Description</label>
                <textarea name="description" rows="5" placeholder="Enter job description" required></textarea>
            </div>

            <button class="btn btn-primary" type="submit">
                Post Job
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
    if(e.target === modal){
        modal.style.display = "none";
    }
};

// success popup
const popup = document.getElementById("successPopup");

if(popup){
    setTimeout(() => {
        popup.style.opacity = "0";
        setTimeout(() => popup.remove(), 500);
    }, 3000);
}
</script>

</body>
</html>