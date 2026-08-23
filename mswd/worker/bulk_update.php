<?php
session_start();
require_once '../../config/security.php';
require_once '../../config/db.php';

// Set security headers
setSecurityHeaders();

// SECURITY GUARD: Restrict access to MSWD department only
$department = html_entity_decode($_SESSION['department'] ?? '', ENT_QUOTES);
if (!isset($_SESSION['role']) || $department !== 'MSWD') {
    logSecurityEvent('unauthorized_access', $_SESSION['id'] ?? null, ['endpoint' => 'mswd/bulk_update', 'department' => $department]);
    header('Location: /login.php?unauthorized=1');
    exit();
}

// Handle bulk status update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        $error_message = "Security validation failed.";
    } else {
        $application_ids = $_POST['application_ids'] ?? [];
        $new_status = $_POST['new_status'];
        $remarks = $_POST['remarks'] ?? '';
        
        if (empty($application_ids) || empty($new_status)) {
            $error_message = "Please select applications and a new status.";
        } else {
            $conn->begin_transaction();
            
            try {
                $updated_count = 0;
                
                foreach ($application_ids as $app_id) {
                    // Get current status
                    $stmt = $conn->prepare("SELECT status, email, first_name, last_name, tracking_number, at.name as assistance_type FROM applications a JOIN assistance_types at ON a.assistance_type_id = at.id WHERE a.id = ?");
                    $stmt->bind_param("i", $app_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    
                    if ($result->num_rows > 0) {
                        $row = $result->fetch_assoc();
                        $old_status = $row['status'];
                        
                        // Update status
                        $update_stmt = $conn->prepare("UPDATE applications SET status = ?, remarks = ?, reviewed_at = NOW() WHERE id = ?");
                        $update_stmt->bind_param("ssi", $new_status, $remarks, $app_id);
                        
                        if ($update_stmt->execute()) {
                            // Log status change
                            $log_stmt = $conn->prepare("INSERT INTO application_status_history (application_id, old_status, new_status, changed_by, remarks, changed_at) VALUES (?, ?, ?, ?, ?, NOW())");
                            $worker_id = $_SESSION['id'];
                            $log_stmt->bind_param("issis", $app_id, $old_status, $new_status, $worker_id, $remarks);
                            $log_stmt->execute();
                            $log_stmt->close();
                            
                            $updated_count++;
                            
                            // Send email notification if enabled
                            if (!empty($row['email'])) {
                                require_once '../../config/email_notifications.php';
                                $notifier = new EmailNotifier();
                                $notifier->sendStatusUpdate(
                                    $row['email'],
                                    $row['first_name'] . ' ' . $row['last_name'],
                                    $row['tracking_number'],
                                    $row['assistance_type'],
                                    $old_status,
                                    $new_status,
                                    $remarks
                                );
                            }
                        }
                        $update_stmt->close();
                    }
                    $stmt->close();
                }
                
                $conn->commit();
                $success_message = "Successfully updated {$updated_count} applications.";
                
                logSecurityEvent('bulk_status_update', $_SESSION['id'], [
                    'count' => $updated_count,
                    'new_status' => $new_status
                ]);
                
            } catch (Exception $e) {
                $conn->rollback();
                $error_message = "Bulk update failed: " . $e->getMessage();
                logError('Bulk status update failed: ' . $e->getMessage());
            }
        }
    }
}

// Get filter parameters
$status_filter = $_GET['status'] ?? '';
$barangay_filter = $_GET['barangay'] ?? '';
$assistance_type_filter = $_GET['assistance_type'] ?? '';

// Build query
$where_conditions = ["1=1"];
$params = [];
$types = "";

if (!empty($status_filter)) {
    $where_conditions[] = "status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

if (!empty($barangay_filter)) {
    $where_conditions[] = "barangay LIKE ?";
    $params[] = "%$barangay_filter%";
    $types .= "s";
}

if (!empty($assistance_type_filter)) {
    $where_conditions[] = "assistance_type_id = ?";
    $params[] = $assistance_type_filter;
    $types .= "i";
}

$where_clause = implode(' AND ', $where_conditions);

$sql = "SELECT a.*, at.name as assistance_type_name FROM applications a JOIN assistance_types at ON a.assistance_type_id = at.id WHERE {$where_clause} ORDER BY submitted_at DESC LIMIT 100";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$applications = $stmt->get_result();
$stmt->close();

// Get all assistance types for filter
$types_result = $conn->query("SELECT id, name FROM assistance_types WHERE is_active = 1 ORDER BY name ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bulk Update - MSWD Portal</title>
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
                <a href="reports.php" class="nav-item">Reports</a>
                <a href="bulk_update.php" class="nav-item active">Bulk Update</a>
                <a href="settings.php" class="nav-item">Settings</a>
                <a href="../../logout.php" class="nav-item">Logout</a>
            </nav>
        </div>
        
        <div class="main-content">
            <div class="page-header">
                <h1>Bulk Status Update</h1>
                <p>Update multiple applications at once</p>
            </div>
            
            <?php if (isset($success_message)): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
            <div class="alert alert-error"><?php echo $error_message; ?></div>
            <?php endif; ?>
            
            <div class="filters">
                <form method="GET" action="">
                    <div class="filter-group">
                        <label>Status:</label>
                        <select name="status">
                            <option value="">All Status</option>
                            <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="under_review" <?php echo $status_filter === 'under_review' ? 'selected' : ''; ?>>Under Review</option>
                            <option value="approved" <?php echo $status_filter === 'approved' ? 'selected' : ''; ?>>Approved</option>
                            <option value="rejected" <?php echo $status_filter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>Barangay:</label>
                        <input type="text" name="barangay" value="<?php echo htmlspecialchars($barangay_filter); ?>">
                    </div>
                    <div class="filter-group">
                        <label>Assistance Type:</label>
                        <select name="assistance_type">
                            <option value="">All Types</option>
                            <?php while ($type = $types_result->fetch_assoc()): ?>
                            <option value="<?php echo $type['id']; ?>" <?php echo $assistance_type_filter == $type['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($type['name']); ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Filter</button>
                </form>
            </div>
            
            <form method="POST" action="" id="bulkUpdateForm">
                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                
                <div class="bulk-actions">
                    <div class="select-all">
                        <label>
                            <input type="checkbox" id="selectAll" onchange="toggleAll()">
                            Select All
                        </label>
                    </div>
                    <div class="action-buttons">
                        <select name="new_status" required>
                            <option value="">Select New Status...</option>
                            <option value="pending">Pending</option>
                            <option value="under_review">Under Review</option>
                            <option value="approved">Approved</option>
                            <option value="rejected">Rejected</option>
                        </select>
                        <input type="text" name="remarks" placeholder="Remarks (optional)">
                        <button type="submit" class="btn btn-primary">Update Selected</button>
                    </div>
                </div>
                
                <div class="applications-list">
                    <?php if ($applications->num_rows === 0): ?>
                    <div class="empty-state">
                        <h3>No applications found</h3>
                        <p>Try adjusting your filters.</p>
                    </div>
                    <?php else: ?>
                    
                    <table class="bulk-table">
                        <thead>
                            <tr>
                                <th>Select</th>
                                <th>Tracking #</th>
                                <th>Applicant</th>
                                <th>Type</th>
                                <th>Barangay</th>
                                <th>Current Status</th>
                                <th>Submitted</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $applications->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <input type="checkbox" name="application_ids[]" value="<?php echo $row['id']; ?>" class="app-checkbox">
                                </td>
                                <td><?php echo htmlspecialchars($row['tracking_number']); ?></td>
                                <td><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['assistance_type_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['barangay']); ?></td>
                                <td>
                                    <span class="status status-<?php echo strtolower($row['status']); ?>">
                                        <?php echo str_replace('_', ' ', ucfirst($row['status'])); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($row['submitted_at'])); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
    
    <script>
    function toggleAll() {
        const selectAll = document.getElementById('selectAll');
        const checkboxes = document.querySelectorAll('.app-checkbox');
        checkboxes.forEach(cb => cb.checked = selectAll.checked);
    }
    </script>
</body>
</html>
