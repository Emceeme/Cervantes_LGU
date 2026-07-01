<?php
include '../config/db.php';

$applicants = $conn->query("
    SELECT 
        a.*,
        j.job_title
    FROM applicants a
    LEFT JOIN jobs j ON a.job_id = j.id
    ORDER BY a.id DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Applicants</title>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="jobs.css"
<style>

</style>
</head>

<body>

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

        <h2 class="page-title">Applicants</h2>

        <div class="card">

        <?php if($applicants->num_rows > 0): ?>

            <table class="applicants-table">

                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Job</th>
                        <th>Message</th>
                        <th>Resume</th>
                        <th>Date</th>
                    </tr>
                </thead>

                <tbody>

                <?php while($row = $applicants->fetch_assoc()): ?>

                    <tr>

                        <td class="applicant-text">
                            <?= htmlspecialchars($row['full_name']); ?>
                        </td>

                        <td class="applicant-text">
                            <?= htmlspecialchars($row['email']); ?>
                        </td>

                        <td class="applicant-text">
                            <?= htmlspecialchars($row['phone']); ?>
                        </td>

                        <td>
                            <span class="job-tag">
                                <?= htmlspecialchars($row['job_title']); ?>
                            </span>
                        </td>

                        <td class="applicant-text">
                            <?= htmlspecialchars(substr($row['message'], 0, 80)); ?>...
                        </td>

                        <td>
                            <a class="resume-link"
                                href="../public/<?= $row['resume']; ?>"
                                target="_blank">
                                View Resume
                            </a>
                        </td>

                        <td class="applicant-text">
                            <?= $row['created_at']; ?>
                        </td>

                    </tr>

                <?php endwhile; ?>

                </tbody>

            </table>

        <?php else: ?>

            <div class="empty-box">
                No applicants yet.
            </div>

        <?php endif; ?>

        </div>

    </main>

</div>

</body>
</html>