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

<style>

/* =========================
   GLOBAL RESET
========================= */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Poppins', sans-serif;
}

/* =========================
   BODY
========================= */
body {
    min-height: 100vh;
    background: linear-gradient(135deg, #0f172a, #1e293b, #2563eb);
    color: white;
}

/* =========================
   BLUR BACKGROUND
========================= */
.bg-blur {
    position: fixed;
    border-radius: 50%;
    filter: blur(120px);
    z-index: -1;
}

.blur1 {
    width: 350px;
    height: 350px;
    background: #3b82f6;
    top: -100px;
    left: -100px;
}

.blur2 {
    width: 450px;
    height: 450px;
    background: #8b5cf6;
    bottom: -150px;
    right: -150px;
}

/* =========================
   LAYOUT
========================= */
.container {
    display: flex;
    min-height: 100vh;
}

/* =========================
   SIDEBAR
========================= */
.sidebar {
    width: 260px;
    padding: 25px 20px;

    background: rgba(255,255,255,0.12);
    backdrop-filter: blur(20px);

    border-right: 1px solid rgba(255,255,255,0.2);
}

.sidebar .logo {
    font-size: 26px;
    font-weight: 700;
    text-align: center;
    margin-bottom: 30px;
}

.sidebar a {
    display: block;
    text-decoration: none;
    color: white;

    padding: 14px;
    margin-bottom: 10px;

    border-radius: 12px;

    background: rgba(255,255,255,0.10);

    transition: 0.3s;
}

.sidebar a:hover {
    transform: translateX(5px);
    background: rgba(255,255,255,0.20);
}

.sidebar a.active {
    background: white;
    color: #2563eb;
    font-weight: 600;
}

/* =========================
   MAIN CONTENT
========================= */
.main-content {
    flex: 1;
    padding: 40px;
}

/* TITLE */
.page-title {
    font-size: 26px;
    margin-bottom: 20px;
}

/* =========================
   CARD
========================= */
.card {
    background: rgba(255,255,255,0.12);
    backdrop-filter: blur(20px);

    border: 1px solid rgba(255,255,255,0.2);
    border-radius: 18px;

    padding: 25px;
}

/* =========================
   TABLE
========================= */
.applicants-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 10px;
}

.applicants-table th,
.applicants-table td {
    padding: 15px;
    text-align: left;
    vertical-align: top;
}

.applicants-table thead {
    background: rgba(255,255,255,0.15);
}

.applicants-table tbody tr {
    border-bottom: 1px solid rgba(255,255,255,0.1);
    transition: 0.3s;
}

.applicants-table tbody tr:hover {
    background: rgba(255,255,255,0.05);
}

/* JOB TAG */
.job-tag {
    display: inline-block;
    padding: 5px 10px;
    background: rgba(139,92,246,0.25);
    border-radius: 10px;
    font-size: 12px;
}

/* TEXT */
.applicant-text {
    font-size: 14px;
    opacity: 0.9;
}

/* RESUME LINK */
.resume-link {
    display: inline-block;
    padding: 6px 10px;

    background: rgba(59,130,246,0.25);
    color: white;

    border-radius: 8px;
    text-decoration: none;

    font-size: 12px;

    transition: 0.3s;
}

.resume-link:hover {
    background: rgba(59,130,246,0.45);
}

/* EMPTY */
.empty-box {
    text-align: center;
    padding: 40px;
    opacity: 0.7;
}

/* =========================
   RESPONSIVE
========================= */
@media (max-width: 768px) {

    .container {
        flex-direction: column;
    }

    .sidebar {
        width: 100%;
        display: flex;
        overflow-x: auto;
    }

    .sidebar a {
        flex: 1;
        min-width: 120px;
        text-align: center;
    }

    .main-content {
        padding: 20px;
    }

    .applicants-table {
        font-size: 13px;
    }
}

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
        <a href="#" class="active">Jobs</a>
        <a href="applicants.php">Applicants</a>
<<<<<<< HEAD
<<<<<<< HEAD
        <a href="procurement.php">Procurement</a>
=======
>>>>>>> 0b3fa6079a9c5adc408cef2ff7364f1e35f8d539
=======
>>>>>>> 0b3fa6079a9c5adc408cef2ff7364f1e35f8d539
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