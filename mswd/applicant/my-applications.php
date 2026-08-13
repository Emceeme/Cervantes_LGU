<?php
session_start();
require_once '../../config/security.php';
require_once '../../config/db.php';

// Set security headers
setSecurityHeaders();

// SECURITY GUARD: Restrict access to applicants only
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'APPLICANT') {
    logSecurityEvent('unauthorized_access', $_SESSION['id'] ?? null, ['endpoint' => 'mswd/applicant/my-applications', 'role' => $_SESSION['role'] ?? 'none']);
    http_response_code(403);
    die("Access Denied: This page is for registered applicants only.");
}

$applicant_id = $_SESSION['id'];

// Get filter parameters
$status_filter = $_GET['status'] ?? '';

// Build query
$sql = "SELECT a.*, at.name as assistance_type_name 
        FROM applications a 
        JOIN assistance_types at ON a.assistance_type_id = at.id 
        WHERE a.applicant_id = ?";
$params = [$applicant_id];
$types = "i";

if (!empty($status_filter)) {
    $sql .= " AND a.status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

$sql .= " ORDER BY a.submitted_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$applications = $stmt->get_result();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Applications - MSWD Portal</title>
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
                <a href="../public/index.php" class="nav-item">Apply for Assistance</a>
                <a href="my-applications.php" class="nav-item active">My Applications</a>
                <a href="../public/track.php" class="nav-item">Track Application</a>
                <a href="../../logout.php" class="nav-item">Logout</a>
            </nav>
        </div>
        
        <div class="main-content">
            <div class="page-header">
                <h1>My Applications</h1>
                <p>View and track your assistance applications</p>
            </div>
            
            <div class="filters">
                <form method="GET" action="">
                    <label>Filter by Status:</label>
                    <select name="status" onchange="this.form.submit()">
                        <option value="">All Status</option>
                        <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="under_review" <?php echo $status_filter === 'under_review' ? 'selected' : ''; ?>>Under Review</option>
                        <option value="approved" <?php echo $status_filter === 'approved' ? 'selected' : ''; ?>>Approved</option>
                        <option value="rejected" <?php echo $status_filter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                    </select>
                </form>
            </div>
            
            <?php if ($applications->num_rows === 0): ?>
            <div class="empty-state">
                <h3>No applications found</h3>
                <p>You haven't submitted any assistance applications yet.</p>
                <a href="../public/index.php" class="btn btn-primary">Apply Now</a>
            </div>
            <?php else: ?>
            
            <div class="applications-list">
                <?php while ($row = $applications->fetch_assoc()): ?>
                <div class="application-card">
                    <div class="card-header">
                        <h3><?php echo htmlspecialchars($row['assistance_type_name']); ?></h3>
                        <span class="status status-<?php echo strtolower($row['status']); ?>">
                            <?php echo str_replace('_', ' ', ucfirst($row['status'])); ?>
                        </span>
                    </div>
                    <div class="card-body">
                        <div class="info-row">
                            <span class="label">Tracking Number:</span>
                            <span class="value"><?php echo htmlspecialchars($row['tracking_number']); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="label">Submitted:</span>
                            <span class="value"><?php echo date('F d, Y g:i A', strtotime($row['submitted_at'])); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="label">Applicant:</span>
                            <span class="value"><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></span>
                        </div>
                        <?php if (!empty($row['remarks'])): ?>
                        <div class="info-row">
                            <span class="label">Remarks:</span>
                            <span class="value"><?php echo htmlspecialchars($row['remarks']); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer">
                        <a href="../public/track.php?tracking=<?php echo urlencode($row['tracking_number']); ?>" class="btn btn-secondary">View Details</a>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
