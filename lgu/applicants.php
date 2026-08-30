<?php
session_start();
require_once '../config/security.php';
require_once '../config/app_config.php';
include '../config/db.php';

// Set security headers
setSecurityHeaders();

// SECURITY GUARD: Restrict access to Mayor's Office, LGU departments & Super Admins only
$department = html_entity_decode($_SESSION['department'] ?? '', ENT_QUOTES);
if (!isset($_SESSION['role']) || ($department !== "Mayor's Office" && $department !== 'Mayor Office' && $department !== 'LGU' && $_SESSION['role'] !== 'SUPER_ADMIN')) {
    logSecurityEvent('unauthorized_access', $_SESSION['id'] ?? null, ['endpoint' => 'applicants', 'department' => $department]);
    header('Location: /login.php?unauthorized=1');
    exit();
}

$applicants_stmt = $conn->prepare("
    SELECT 
        a.*,
        j.job_title
    FROM applicants a
    LEFT JOIN jobs j ON a.job_id = j.id
    ORDER BY a.id DESC
");

if ($conn instanceof PDO) {
    // PostgreSQL/PDO
    $applicants_stmt->execute();
    $applicants = $applicants_stmt->fetchAll();
} else {
    // MySQLi
    $applicants_stmt->execute();
    $applicants = $applicants_stmt->get_result();
    $applicants_stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Applicants</title>
<link rel="stylesheet" href="jobs.css">
</head>

<body>

<div class="container">

    <!-- SIDEBAR -->
    <aside class="sidebar">
        <div class="logo">LGU <span>Portal</span></div>
        <nav class="sidebar-nav">
            <a href="dashboard.php">Dashboard</a>
            <a href="applicants.php" class="active">Applicants</a>
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
            <h2>Job Applicants</h2>
            <p>View and manage all job applications</p>
        </div>

        <section class="card">
            <div class="card-header">
                <h3>Applicant List</h3>
            </div>

        <?php if($conn instanceof PDO): ?>
            <?php if(count($applicants) > 0): ?>

            <table class="table">

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

                <?php foreach($applicants as $row): ?>

                    <tr>
                        <td><?= htmlspecialchars($row['full_name']); ?></td>
                        <td><?= htmlspecialchars($row['email']); ?></td>
                        <td><?= htmlspecialchars($row['phone']); ?></td>
                        <td>
                            <span class="status active">
                                <?= htmlspecialchars($row['job_title']); ?>
                            </span>
                        </td>
                        <td><?= htmlspecialchars(substr($row['message'], 0, 80)); ?>...</td>
                        <td>
                            <a class="btn btn-secondary"
                                href="<?= AppConfig::resumeUploads($row['resume']); ?>"
                                target="_blank"
                                download>
                                View Resume
                            </a>
                        </td>
                        <td><?= $row['created_at']; ?></td>
                    </tr>

                <?php endforeach; ?>

                </tbody>

            </table>

        <?php else: ?>

            <div style="text-align: center; padding: 40px; color: #64748B;">
                <p>No applicants yet.</p>
            </div>

        <?php endif; ?>
        <?php else: ?>
            <?php if($applicants->num_rows > 0): ?>

            <table class="table">

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
                        <td><?= htmlspecialchars($row['full_name']); ?></td>
                        <td><?= htmlspecialchars($row['email']); ?></td>
                        <td><?= htmlspecialchars($row['phone']); ?></td>
                        <td>
                            <span class="status active">
                                <?= htmlspecialchars($row['job_title']); ?>
                            </span>
                        </td>
                        <td><?= htmlspecialchars(substr($row['message'], 0, 80)); ?>...</td>
                        <td>
                            <a class="btn btn-secondary"
                                href="<?= AppConfig::resumeUploads($row['resume']); ?>"
                                target="_blank"
                                download>
                                View Resume
                            </a>
                        </td>
                        <td><?= $row['created_at']; ?></td>
                    </tr>

                <?php endwhile; ?>

                </tbody>

            </table>

        <?php else: ?>

            <div style="text-align: center; padding: 40px; color: #64748B;">
                <p>No applicants yet.</p>
            </div>

        <?php endif; ?>
        <?php endif; ?>

        </section>

    </main>

</div>

</body>
</html>