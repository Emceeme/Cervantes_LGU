<?php
session_start();
require_once __DIR__ . '/../../config/security.php';
require_once __DIR__ . '/../../config/db.php';

setSecurityHeaders();

// SECURITY GUARD: MSWD Department only
if (!isset($_SESSION['department']) || $_SESSION['department'] !== 'MSWD') {
    logSecurityEvent('unauthorized_access', $_SESSION['id'] ?? null, ['endpoint' => 'mswd_reports']);
    header('Location: /login.php?unauthorized=1');
    exit();
}

// Fetch statistics
$stats_stmt = $conn->prepare("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'under_review' THEN 1 ELSE 0 END) as under_review,
        SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
        SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
    FROM applications
");
if ($conn instanceof PDO) {
    $stats_stmt->execute();
    $stats_result = $stats_stmt->fetchAll();
    $stats = $stats_result[0];
} else {
    $stats_stmt->execute();
    $stats = $stats_stmt->get_result()->fetch_assoc();
}

// Fetch applications by assistance type
$type_stmt = $conn->prepare("
    SELECT at.name, COUNT(a.id) as count
    FROM assistance_types at
    LEFT JOIN applications a ON at.id = a.assistance_type_id
    WHERE at.is_active = 1
    GROUP BY at.id, at.name
    ORDER BY count DESC
");
if ($conn instanceof PDO) {
    $type_stmt->execute();
    $by_type = $type_stmt->fetchAll();
} else {
    $type_stmt->execute();
    $by_type = $type_stmt->get_result();
}

// Fetch applications by barangay
$barangay_stmt = $conn->prepare("
    SELECT barangay, COUNT(*) as count
    FROM applications
    GROUP BY barangay
    ORDER BY count DESC
    LIMIT 10
");
if ($conn instanceof PDO) {
    $barangay_stmt->execute();
    $by_barangay = $barangay_stmt->fetchAll();
} else {
    $barangay_stmt->execute();
    $by_barangay = $barangay_stmt->get_result();
}

// Fetch daily trends
$trend_stmt = $conn->prepare("
    SELECT DATE(submitted_at) as date, COUNT(*) as count
    FROM applications
    WHERE submitted_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    GROUP BY DATE(submitted_at)
    ORDER BY date DESC
");
if ($conn instanceof PDO) {
    $trend_stmt->execute();
    $daily_trends = $trend_stmt->fetchAll();
} else {
    $trend_stmt->execute();
    $daily_trends = $trend_stmt->get_result();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - MSWD Portal</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/mswd.css">
</head>
<body>

<header>
    <div class="container header-content">
        <div class="logo">
            <i class="fas fa-hands-helping"></i>
            <h1>MSWD Worker Portal</h1>
        </div>
        <div class="user-info">
            <span>Welcome, <?= htmlspecialchars($_SESSION['name']) ?></span>
            <a href="../../logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>
</header>

<div class="container">
    <div class="page-header">
        <a href="dashboard.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
        <h2>Reports & Analytics</h2>
        <p>Application statistics and trends</p>
    </div>

    <!-- Overview Stats -->
    <div class="stats-grid">
        <div class="stat-card">
            <h3><?= $stats['total'] ?></h3>
            <p>Total Applications</p>
        </div>
        <div class="stat-card pending">
            <h3><?= $stats['pending'] ?></h3>
            <p>Pending</p>
        </div>
        <div class="stat-card approved">
            <h3><?= $stats['approved'] ?></h3>
            <p>Approved</p>
        </div>
        <div class="stat-card rejected">
            <h3><?= $stats['rejected'] ?></h3>
            <p>Rejected</p>
        </div>
    </div>

    <!-- By Assistance Type -->
    <div class="card">
        <div class="card-header">
            <h3>Applications by Assistance Type</h3>
        </div>
        <div class="card-body">
            <table class="table">
                <thead>
                    <tr>
                        <th>Assistance Type</th>
                        <th>Count</th>
                        <th>Percentage</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($conn instanceof PDO): ?>
                        <?php foreach ($by_type as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['name']) ?></td>
                        <td><?= $row['count'] ?></td>
                        <td><?= $stats['total'] > 0 ? round(($row['count'] / $stats['total']) * 100, 1) : 0 ?>%</td>
                    </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <?php while ($row = $by_type->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['name']) ?></td>
                        <td><?= $row['count'] ?></td>
                        <td><?= $stats['total'] > 0 ? round(($row['count'] / $stats['total']) * 100, 1) : 0 ?>%</td>
                    </tr>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- By Barangay -->
    <div class="card">
        <div class="card-header">
            <h3>Top 10 Barangays</h3>
        </div>
        <div class="card-body">
            <table class="table">
                <thead>
                    <tr>
                        <th>Barangay</th>
                        <th>Count</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($conn instanceof PDO): ?>
                        <?php foreach ($by_barangay as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['barangay']) ?></td>
                        <td><?= $row['count'] ?></td>
                    </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <?php while ($row = $by_barangay->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['barangay']) ?></td>
                        <td><?= $row['count'] ?></td>
                    </tr>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Daily Trends -->
    <div class="card">
        <div class="card-header">
            <h3>Daily Application Trends (Last 30 Days)</h3>
        </div>
        <div class="card-body">
            <table class="table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Applications</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($conn instanceof PDO): ?>
                        <?php foreach ($daily_trends as $row): ?>
                    <tr>
                        <td><?= date('M j, Y', strtotime($row['date'])) ?></td>
                        <td><?= $row['count'] ?></td>
                    </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <?php while ($row = $daily_trends->fetch_assoc()): ?>
                    <tr>
                        <td><?= date('M j, Y', strtotime($row['date'])) ?></td>
                        <td><?= $row['count'] ?></td>
                    </tr>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</body>
</html>
