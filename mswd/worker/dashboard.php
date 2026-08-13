<?php
session_start();
require_once __DIR__ . '/../../config/security.php';
require_once __DIR__ . '/../../config/db.php';

setSecurityHeaders();

// SECURITY GUARD: MSWD Department only
if (!isset($_SESSION['department']) || $_SESSION['department'] !== 'MSWD') {
    logSecurityEvent('unauthorized_access', $_SESSION['id'] ?? null, ['endpoint' => 'mswd_worker_dashboard']);
    http_response_code(403);
    die("Access Denied: MSWD Department privileges required.");
}

$worker_id = $_SESSION['id'];
$csrf_token = generateCsrfToken();

// Get filter parameters
$status_filter = $_GET['status'] ?? '';
$barangay_filter = $_GET['barangay'] ?? '';
$assistance_filter = $_GET['assistance_type'] ?? '';

// Build query with filters
$where_conditions = ["1=1"];
$params = [];
$types = "";

if (!empty($status_filter)) {
    $where_conditions[] = "a.status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

if (!empty($barangay_filter)) {
    $where_conditions[] = "a.barangay = ?";
    $params[] = $barangay_filter;
    $types .= "s";
}

if (!empty($assistance_filter)) {
    $where_conditions[] = "a.assistance_type_id = ?";
    $params[] = $assistance_filter;
    $types .= "i";
}

$where_clause = implode(" AND ", $where_conditions);

// Fetch applications
$applications_stmt = $conn->prepare("
    SELECT a.*, at.name as assistance_type_name,
           CONCAT(u.first_name, ' ', u.last_name) as assigned_worker_name
    FROM applications a
    JOIN assistance_types at ON a.assistance_type_id = at.id
    LEFT JOIN users u ON a.assigned_worker_id = u.id
    WHERE $where_clause
    ORDER BY a.submitted_at DESC
");

if (!empty($params)) {
    $applications_stmt->bind_param($types, ...$params);
}

if ($applications_stmt) {
    $applications_stmt->execute();
    $applications = $applications_stmt->get_result();
} else {
    $applications = false;
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
$stats_stmt->execute();
$stats = $stats_stmt->get_result()->fetch_assoc();

// Fetch assistance types for filter
$types_stmt = $conn->prepare("SELECT id, name FROM assistance_types WHERE is_active = 1 ORDER BY name");
$types_stmt->execute();
$assistance_types = $types_stmt->get_result();

// Fetch unique barangays
$barangays_stmt = $conn->prepare("SELECT DISTINCT barangay FROM applications ORDER BY barangay");
$barangays_stmt->execute();
$barangays_result = $barangays_stmt->get_result();

// Fetch barangays into array for reuse
$barangays_list = [];
if ($barangays_result) {
    while ($barangay = $barangays_result->fetch_assoc()) {
        $barangays_list[] = $barangay['barangay'];
    }
}

// Fallback barangay list if no applications exist
$fallback_barangays = [
    'Barangay 1', 'Barangay 2', 'Barangay 3', 'Barangay 4', 'Barangay 5',
    'Barangay 6', 'Barangay 7', 'Barangay 8', 'Barangay 9', 'Barangay 10'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Social Worker Dashboard - MSWD Portal</title>
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
    <!-- Statistics -->
    <div class="stats-grid">
        <div class="stat-card pending">
            <h3><?= $stats['pending'] ?></h3>
            <p>Pending</p>
        </div>
        <div class="stat-card under_review">
            <h3><?= $stats['under_review'] ?></h3>
            <p>Under Review</p>
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
    
    <!-- Filters -->
    <div class="filters">
        <div class="filter-group">
            <label>Status</label>
            <select onchange="window.location.href='?status='+this.value+'&barangay=<?= urlencode($barangay_filter) ?>&assistance_type=<?= urlencode($assistance_filter) ?>'">
                <option value="">All Status</option>
                <option value="pending" <?= $status_filter === 'pending' ? 'selected' : '' ?>>Pending</option>
                <option value="under_review" <?= $status_filter === 'under_review' ? 'selected' : '' ?>>Under Review</option>
                <option value="approved" <?= $status_filter === 'approved' ? 'selected' : '' ?>>Approved</option>
                <option value="rejected" <?= $status_filter === 'rejected' ? 'selected' : '' ?>>Rejected</option>
            </select>
        </div>
        <div class="filter-group">
            <label>Barangay</label>
            <select onchange="window.location.href='?status=<?= urlencode($status_filter) ?>&barangay='+this.value+'&assistance_type=<?= urlencode($assistance_filter) ?>'">
                <option value="">All Barangays</option>
                <?php if (!empty($barangays_list)): ?>
                    <?php foreach ($barangays_list as $barangay): ?>
                    <option value="<?= htmlspecialchars($barangay) ?>" <?= $barangay_filter === $barangay ? 'selected' : '' ?>>
                        <?= htmlspecialchars($barangay) ?>
                    </option>
                    <?php endforeach; ?>
                <?php else: ?>
                    <?php foreach ($fallback_barangays as $fallback_barangay): ?>
                    <option value="<?= htmlspecialchars($fallback_barangay) ?>" <?= $barangay_filter === $fallback_barangay ? 'selected' : '' ?>>
                        <?= htmlspecialchars($fallback_barangay) ?>
                    </option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
        </div>
        <div class="filter-group">
            <label>Assistance Type</label>
            <select onchange="window.location.href='?status=<?= urlencode($status_filter) ?>&barangay=<?= urlencode($barangay_filter) ?>&assistance_type='+this.value">
                <option value="">All Types</option>
                <?php while ($type = $assistance_types->fetch_assoc()): ?>
                <option value="<?= $type['id'] ?>" <?= $assistance_filter == $type['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($type['name']) ?>
                </option>
                <?php endwhile; ?>
            </select>
        </div>
        <a href="dashboard.php" class="clear-filters">
            <i class="fas fa-times"></i> Clear Filters
        </a>
    </div>
    
    <!-- Applications Table -->
    <div class="applications-section">
        <div class="section-header">
            <h2>Applications (<?= $applications ? $applications->num_rows : 0 ?>)</h2>
        </div>
        
        <?php if ($applications && $applications->num_rows > 0): ?>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Tracking #</th>
                        <th>Applicant</th>
                        <th>Type</th>
                        <th>Barangay</th>
                        <th>Status</th>
                        <th>Submitted</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($app = $applications->fetch_assoc()): ?>
                    <tr>
                        <td><span class="tracking-number"><?= htmlspecialchars($app['tracking_number']) ?></span></td>
                        <td><?= htmlspecialchars($app['first_name'] . ' ' . $app['last_name']) ?></td>
                        <td><?= htmlspecialchars($app['assistance_type_name']) ?></td>
                        <td><?= htmlspecialchars($app['barangay']) ?></td>
                        <td>
                            <span class="status-badge status-<?= str_replace('_', '-', $app['status']) ?>">
                                <?= ucfirst(str_replace('_', ' ', $app['status'])) ?>
                            </span>
                        </td>
                        <td><?= date('M j, Y', strtotime($app['submitted_at'])) ?></td>
                        <td>
                            <a href="review.php?id=<?= $app['id'] ?>" class="action-btn">
                                <i class="fas fa-eye"></i> Review
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-inbox"></i>
            <h3>No Applications Found</h3>
            <p>Try adjusting your filters or check back later.</p>
        </div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
