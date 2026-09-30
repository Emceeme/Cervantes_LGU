<?php
session_start();
require_once __DIR__ . '/../../config/security.php';
require_once __DIR__ . '/../../config/db.php';

setSecurityHeaders();

// SECURITY GUARD: Applicant only
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'APPLICANT') {
    logSecurityEvent('unauthorized_access', $_SESSION['id'] ?? null, ['endpoint' => 'mswd_my_applications']);
    header('Location: /login.php?unauthorized=1');
    exit();
}

$applicant_id = $_SESSION['id'];

// Fetch applications
$stmt = $conn->prepare("
    SELECT a.*, at.name as assistance_type_name
    FROM applications a
    JOIN assistance_types at ON a.assistance_type_id = at.id
    WHERE a.applicant_id = ?
    ORDER BY a.submitted_at DESC
");

if ($conn instanceof PDO) {
    $stmt->execute([$applicant_id]);
    $applications = $stmt->fetchAll();
} else {
    $stmt->bind_param("i", $applicant_id);
    $stmt->execute();
    $applications = $stmt->get_result();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Applications - MSWD Portal</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/mswd.css">
</head>
<body>

<header>
    <div class="container header-content">
        <div class="logo">
            <i class="fas fa-hands-helping"></i>
            <div>
                <h1>MSWD Portal</h1>
                <p>Municipal Social Welfare and Development</p>
            </div>
        </div>
        <nav class="nav-links">
            <a href="../public/index.php">Home</a>
            <a href="../public/apply.php" class="primary">Apply Now</a>
            <a href="my-applications.php" class="active">My Applications</a>
            <a href="../../logout.php">Logout</a>
        </nav>
    </div>
</header>

<div class="container">
    <div class="page-header">
        <h2>My Applications</h2>
        <p>View and track your assistance applications</p>
    </div>

    <?php if ($applications && ($conn instanceof PDO ? count($applications) > 0 : $applications->num_rows > 0)): ?>
    <div class="applications-list">
        <?php if ($conn instanceof PDO): ?>
            <?php foreach ($applications as $row): ?>
        <div class="application-card">
            <div class="card-header">
                <span class="tracking-number"><?= htmlspecialchars($row['tracking_number']) ?></span>
                <span class="status-badge status-<?= str_replace('_', '-', $row['status']) ?>">
                    <?= ucfirst(str_replace('_', ' ', $row['status'])) ?>
                </span>
            </div>
            <div class="card-body">
                <h3><?= htmlspecialchars($row['assistance_type_name']) ?></h3>
                <p class="submitted-date">Submitted: <?= date('F j, Y', strtotime($row['submitted_at'])) ?></p>
                <?php if ($row['remarks']): ?>
                <p class="remarks"><strong>Remarks:</strong> <?= htmlspecialchars($row['remarks']) ?></p>
                <?php endif; ?>
            </div>
            <div class="card-footer">
                <a href="../public/track.php?tracking=<?= urlencode($row['tracking_number']) ?>" class="btn btn-secondary">
                    <i class="fas fa-search"></i> Track Status
                </a>
            </div>
        </div>
            <?php endforeach; ?>
        <?php else: ?>
            <?php while ($row = $applications->fetch_assoc()): ?>
        <div class="application-card">
            <div class="card-header">
                <span class="tracking-number"><?= htmlspecialchars($row['tracking_number']) ?></span>
                <span class="status-badge status-<?= str_replace('_', '-', $row['status']) ?>">
                    <?= ucfirst(str_replace('_', ' ', $row['status'])) ?>
                </span>
            </div>
            <div class="card-body">
                <h3><?= htmlspecialchars($row['assistance_type_name']) ?></h3>
                <p class="submitted-date">Submitted: <?= date('F j, Y', strtotime($row['submitted_at'])) ?></p>
                <?php if ($row['remarks']): ?>
                <p class="remarks"><strong>Remarks:</strong> <?= htmlspecialchars($row['remarks']) ?></p>
                <?php endif; ?>
            </div>
            <div class="card-footer">
                <a href="../public/track.php?tracking=<?= urlencode($row['tracking_number']) ?>" class="btn btn-secondary">
                    <i class="fas fa-search"></i> Track Status
                </a>
            </div>
        </div>
            <?php endwhile; ?>
        <?php endif; ?>
    </div>
    <?php else: ?>
    <div class="empty-state">
        <i class="fas fa-inbox"></i>
        <h3>No Applications Yet</h3>
        <p>You haven't submitted any assistance applications yet.</p>
        <a href="../public/apply.php" class="btn btn-primary">Apply Now</a>
    </div>
    <?php endif; ?>
</div>

</body>
</html>
