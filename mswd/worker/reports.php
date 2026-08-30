<?php
session_start();
require_once '../../config/security.php';
// require_once '../../config/db.php'; // TEMPORARILY DISABLED

// Set security headers
setSecurityHeaders();

// SECURITY GUARD: Restrict access to MSWD department only
$department = html_entity_decode($_SESSION['department'] ?? '', ENT_QUOTES);
if (!isset($_SESSION['role']) || $department !== 'MSWD') {
    logSecurityEvent('unauthorized_access', $_SESSION['id'] ?? null, ['endpoint' => 'mswd/reports', 'department' => $department]);
    header('Location: /login.php?unauthorized=1');
    exit();
}

// Get filter parameters
$date_from = $_GET['date_from'] ?? date('Y-m-01'); // First day of current month
$date_to = $_GET['date_to'] ?? date('Y-m-t'); // Last day of current month
$assistance_type = $_GET['assistance_type'] ?? '';
$status = $_GET['status'] ?? '';

// Build base query
$where_conditions = ["DATE(submitted_at) BETWEEN ? AND ?"];
$params = [$date_from, $date_to];
$types = "ss";

if (!empty($assistance_type)) {
    $where_conditions[] = "assistance_type_id = ?";
    $params[] = $assistance_type;
    $types .= "i";
}

if (!empty($status)) {
    $where_conditions[] = "status = ?";
    $params[] = $status;
    $types .= "s";
}

$where_clause = implode(' AND ', $where_conditions);

// Get statistics
$stats_sql = "
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'under_review' THEN 1 ELSE 0 END) as under_review,
        SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
        SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
    FROM applications 
    WHERE {$where_clause}
";

$stats_stmt = $conn->prepare($stats_sql);
$stats_stmt->bind_param($types, ...$params);
$stats_stmt->execute();
$stats = $stats_stmt->get_result()->fetch_assoc();
$stats_stmt->close();

// Get applications by assistance type
$type_sql = "
    SELECT at.name, COUNT(*) as count
    FROM applications a
    JOIN assistance_types at ON a.assistance_type_id = at.id
    WHERE {$where_clause}
    GROUP BY at.id, at.name
    ORDER BY count DESC
";

$type_stmt = $conn->prepare($type_sql);
$type_stmt->bind_param($types, ...$params);
$type_stmt->execute();
$by_type = $type_stmt->get_result();
$type_stmt->close();

// Get applications by barangay
$barangay_sql = "
    SELECT barangay, COUNT(*) as count
    FROM applications
    WHERE {$where_clause}
    GROUP BY barangay
    ORDER BY count DESC
    LIMIT 10
";

$barangay_stmt = $conn->prepare($barangay_sql);
$barangay_stmt->bind_param($types, ...$params);
$barangay_stmt->execute();
$by_barangay = $barangay_stmt->get_result();
$barangay_stmt->close();

// Get daily application trend
$trend_sql = "
    SELECT DATE(submitted_at) as date, COUNT(*) as count
    FROM applications
    WHERE {$where_clause}
    GROUP BY DATE(submitted_at)
    ORDER BY date ASC
";

$trend_stmt = $conn->prepare($trend_sql);
$trend_stmt->bind_param($types, ...$params);
$trend_stmt->execute();
$daily_trend = $trend_stmt->get_result();
$trend_stmt->close();

// Get all assistance types for filter
$types_result = $conn->query("SELECT id, name FROM assistance_types WHERE is_active = 1 ORDER BY name ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - MSWD Portal</title>
    <link rel="stylesheet" href="../assets/css/mswd.css">
</head>
<body>
    <div class="container">
        <div class="sidebar">
            <div class="sidebar-header">
                <h2>MSWD Portal</h2>
                <p>Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?></p>
            </div>
            <nav class="sidebar-nav">
                <a href="dashboard.php" class="nav-item">Dashboard</a>
                <a href="review.php" class="nav-item">Review Applications</a>
                <a href="reports.php" class="nav-item active">Reports</a>
                <a href="settings.php" class="nav-item">Settings</a>
                <a href="../../logout.php" class="nav-item">Logout</a>
            </nav>
        </div>
        
        <div class="main-content">
            <div class="page-header">
                <h1>Reports & Analytics</h1>
                <p>View application statistics and trends</p>
            </div>
            
            <div class="filters">
                <form method="GET" action="">
                    <div class="filter-group">
                        <label>Date From:</label>
                        <input type="date" name="date_from" value="<?php echo $date_from; ?>">
                    </div>
                    <div class="filter-group">
                        <label>Date To:</label>
                        <input type="date" name="date_to" value="<?php echo $date_to; ?>">
                    </div>
                    <div class="filter-group">
                        <label>Assistance Type:</label>
                        <select name="assistance_type">
                            <option value="">All Types</option>
                            <?php while ($type = $types_result->fetch_assoc()): ?>
                            <option value="<?php echo $type['id']; ?>" <?php echo $assistance_type == $type['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($type['name']); ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>Status:</label>
                        <select name="status">
                            <option value="">All Status</option>
                            <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="under_review" <?php echo $status === 'under_review' ? 'selected' : ''; ?>>Under Review</option>
                            <option value="approved" <?php echo $status === 'approved' ? 'selected' : ''; ?>>Approved</option>
                            <option value="rejected" <?php echo $status === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Apply Filters</button>
                </form>
            </div>
            
            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Total Applications</h3>
                    <div class="stat-value"><?php echo number_format($stats['total']); ?></div>
                </div>
                <div class="stat-card pending">
                    <h3>Pending</h3>
                    <div class="stat-value"><?php echo number_format($stats['pending']); ?></div>
                </div>
                <div class="stat-card under-review">
                    <h3>Under Review</h3>
                    <div class="stat-value"><?php echo number_format($stats['under_review']); ?></div>
                </div>
                <div class="stat-card approved">
                    <h3>Approved</h3>
                    <div class="stat-value"><?php echo number_format($stats['approved']); ?></div>
                </div>
                <div class="stat-card rejected">
                    <h3>Rejected</h3>
                    <div class="stat-value"><?php echo number_format($stats['rejected']); ?></div>
                </div>
            </div>
            
            <!-- Charts Section -->
            <div class="charts-section">
                <div class="chart-card">
                    <h3>Applications by Assistance Type</h3>
                    <div class="chart-content">
                        <?php if ($by_type->num_rows === 0): ?>
                        <p class="no-data">No data available for selected period</p>
                        <?php else: ?>
                        <table class="chart-table">
                            <thead>
                                <tr>
                                    <th>Assistance Type</th>
                                    <th>Count</th>
                                    <th>Percentage</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $total = $stats['total'];
                                while ($row = $by_type->fetch_assoc()): 
                                $percentage = $total > 0 ? round(($row['count'] / $total) * 100, 1) : 0;
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['name']); ?></td>
                                    <td><?php echo number_format($row['count']); ?></td>
                                    <td>
                                        <div class="progress-bar">
                                            <div class="progress-fill" style="width: <?php echo $percentage; ?>%;"></div>
                                        </div>
                                        <span><?php echo $percentage; ?>%</span>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="chart-card">
                    <h3>Top 10 Barangays by Applications</h3>
                    <div class="chart-content">
                        <?php if ($by_barangay->num_rows === 0): ?>
                        <p class="no-data">No data available for selected period</p>
                        <?php else: ?>
                        <table class="chart-table">
                            <thead>
                                <tr>
                                    <th>Barangay</th>
                                    <th>Count</th>
                                    <th>Percentage</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $barangay_total = 0;
                                $barangay_data = [];
                                while ($row = $by_barangay->fetch_assoc()) {
                                    $barangay_data[] = $row;
                                    $barangay_total += $row['count'];
                                }
                                foreach ($barangay_data as $row):
                                $percentage = $barangay_total > 0 ? round(($row['count'] / $barangay_total) * 100, 1) : 0;
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['barangay']); ?></td>
                                    <td><?php echo number_format($row['count']); ?></td>
                                    <td>
                                        <div class="progress-bar">
                                            <div class="progress-fill" style="width: <?php echo $percentage; ?>%;"></div>
                                        </div>
                                        <span><?php echo $percentage; ?>%</span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Daily Trend -->
            <div class="chart-card full-width">
                <h3>Daily Application Trend</h3>
                <div class="chart-content">
                    <?php if ($daily_trend->num_rows === 0): ?>
                    <p class="no-data">No data available for selected period</p>
                    <?php else: ?>
                    <table class="chart-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Applications</th>
                                <th>Trend</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $trend_data = [];
                            while ($row = $daily_trend->fetch_assoc()) {
                                $trend_data[] = $row;
                            }
                            foreach ($trend_data as $index => $row):
                            $prev_count = $index > 0 ? $trend_data[$index - 1]['count'] : 0;
                            $trend = $row['count'] - $prev_count;
                            $trend_class = $trend > 0 ? 'trend-up' : ($trend < 0 ? 'trend-down' : 'trend-flat');
                            ?>
                            <tr>
                                <td><?php echo date('F d, Y', strtotime($row['date'])); ?></td>
                                <td><?php echo number_format($row['count']); ?></td>
                                <td class="<?php echo $trend_class; ?>">
                                    <?php if ($trend > 0): ?>
                                    ↑ <?php echo number_format($trend); ?>
                                    <?php elseif ($trend < 0): ?>
                                    ↓ <?php echo number_format(abs($trend)); ?>
                                    <?php else: ?>
                                    → No change
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Export Button -->
            <div class="export-section">
                <button onclick="window.print()" class="btn btn-secondary">Print Report</button>
            </div>
        </div>
    </div>
</body>
</html>
