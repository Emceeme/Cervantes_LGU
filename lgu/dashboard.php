<?php
session_start();
include '../config/db.php';

$jobs = $conn->query("SELECT * FROM jobs ORDER BY id DESC");
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
<div id="successPopup" class="success-popup">
    Job posted successfully!
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
        <a href="../logout.php">Logout</a>
    </aside>

    <!-- MAIN -->
    <main class="main-content">

        <div class="top-bar">
            <h2>Job Management</h2>
        </div>

        <button id="openModal" class="add-btn">+</button>

        <section class="card">

            <h3>Posted Jobs</h3>
            <p class="muted">Manage all job postings here</p>

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

                <?php while($row = $jobs->fetch_assoc()): ?>

                <tr>
                    <td><?= htmlspecialchars($row['job_title']) ?></td>
                    <td><?= htmlspecialchars($row['department']) ?></td>
                    <td><?= htmlspecialchars($row['employment_type']) ?></td>
                    <td><?= htmlspecialchars($row['salary']) ?></td>

                    <td>
                        <span class="status">
                            <?= htmlspecialchars($row['status']) ?>
                        </span>
                    </td>

                    <td>
                        <a class="btn-danger"
                           href="../handler/delete_job.php?id=<?= $row['id'] ?>"
                           onclick="return confirm('Delete this job?')">
                           Delete
                        </a>
                    </td>
                </tr>

                <?php endwhile; ?>

                </tbody>

            </table>

        </section>

    </main>

</div>

<!-- MODAL -->
<div id="jobModal" class="modal">

    <div class="modal-content">

        <span id="closeModal" class="close">&times;</span>

        <h2>Create Job Posting</h2>

        <!-- FIXED PATH -->
        <form action="../handler/post_job.php" method="POST">

            <input type="text" name="job_title" placeholder="Job Title" required>

            <input type="text" name="department" placeholder="Department" required>

            <select name="employment_type" required>
                <option value="">Select Employment Type</option>
                <option value="Permanent">Permanent</option>
                <option value="Contractual">Contractual</option>
                <option value="Job Order">Job Order</option>
                <option value="Part-Time">Part-Time</option>
            </select>

            <input type="text" name="salary" placeholder="Salary">

            <input type="text" name="location" placeholder="Location">

            <textarea name="description" rows="5" placeholder="Job Description" required></textarea>

            <button class="post-btn" type="submit">
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