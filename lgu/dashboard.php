<?php
session_start();
include '../config/db.php';

$jobs = $conn->query("SELECT * FROM jobs ORDER BY id DESC");
<<<<<<< HEAD
<<<<<<< HEAD

if(isset($_GET['success'])): ?>
<script>
alert("✅ Job posted successfully!");
</script>

=======
>>>>>>> 0b3fa6079a9c5adc408cef2ff7364f1e35f8d539
=======
>>>>>>> 0b3fa6079a9c5adc408cef2ff7364f1e35f8d539
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Job Management</title>
<link rel="stylesheet" href="jobs.css">
</head>

<body>

<div class="bg-blur blur1"></div>
<div class="bg-blur blur2"></div>

<div class="container">

    <!-- SIDEBAR -->
    <aside class="sidebar">
        <div class="logo">🏛️</div>

        <a href="dashboard.php">Dashboard</a>
        <a href="#" class="active">Jobs</a>
        <a href="applicants.php">Applicants</a>
        <a href="procurement.php">Procurement</a>
        <a href="../logout.php">Logout</a>
    </aside>

    <!-- MAIN -->
    <main class="main-content">

        <!-- HEADER -->
        <div class="top-bar">
            <h2>Job Management</h2>
        </div>

        <!-- ADD BUTTON -->
        <button id="openModal" class="add-btn">+</button>

        <!-- JOB TABLE -->
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

                <?php while($row = $jobs->fetch_assoc()) { ?>

                    <tr>
                        <td><?php echo $row['job_title']; ?></td>
                        <td><?php echo $row['department']; ?></td>
                        <td><?php echo $row['employment_type']; ?></td>
                        <td><?php echo $row['salary']; ?></td>
                        <td>
                            <span class="status">
                                <?php echo $row['status']; ?>
                            </span>
                        </td>
                        <td>
                            <a class="btn-danger"
                               href="delete_job.php?id=<?php echo $row['id']; ?>"
                               onclick="return confirm('Delete this job?')">
                               Delete
                            </a>
                        </td>
                    </tr>

                <?php } ?>

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

        <form action="post_job.php" method="POST">

            <input type="text" name="job_title" placeholder="Job Title" required>

            <input type="text" name="department" placeholder="Department" required>

            <select name="employment_type" required>
                <option>Permanent</option>
                <option>Contractual</option>
                <option>Job Order</option>
                <option>Part-Time</option>
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

openBtn.onclick = () => modal.style.display = "flex";
closeBtn.onclick = () => modal.style.display = "none";

window.onclick = (e) => {
    if(e.target === modal){
        modal.style.display = "none";
    }
};
</script>

</body>
</html>