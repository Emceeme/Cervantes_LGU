<?php
require_once __DIR__ . '/../../config/security.php';
require_once __DIR__ . '/../../config/db.php';

setSecurityHeaders();

$tracking_number = $_GET['tracking'] ?? '';
$application = null;
$status_history = null;

if (!empty($tracking_number)) {
    // Fetch application details
    $app_stmt = $conn->prepare("
        SELECT a.*, at.name as assistance_type_name,
               CONCAT(u.first_name, ' ', u.last_name) as assigned_worker_name
        FROM applications a
        JOIN assistance_types at ON a.assistance_type_id = at.id
        LEFT JOIN users u ON a.assigned_worker_id = u.id
        WHERE a.tracking_number = ?
    ");

    if ($conn instanceof PDO) {
        $app_stmt->execute([$tracking_number]);
        $application = $app_stmt->fetch();
    } else {
        $app_stmt->bind_param("s", $tracking_number);
        $app_stmt->execute();
        $result = $app_stmt->get_result();
        $application = $result ? $result->fetch_assoc() : false;
        $app_stmt->close();
    }

    // Fetch status history
    if ($application) {
        $history_stmt = $conn->prepare("
            SELECT h.*, CONCAT(u.first_name, ' ', u.last_name) as changed_by_name
            FROM application_status_history h
            LEFT JOIN users u ON h.changed_by = u.id
            WHERE h.application_id = ?
            ORDER BY h.changed_at DESC
        ");
        
        if ($conn instanceof PDO) {
            $history_stmt->execute([$application['id']]);
            $status_history = $history_stmt->fetchAll();
        } else {
            $history_stmt->bind_param("i", $application['id']);
            $history_stmt->execute();
            $status_history = $history_stmt->get_result();
            $history_stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track Application - MSWD Portal</title>
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
            <a href="index.php">Home</a>
            <a href="apply.php" class="primary">Apply Now</a>
            <a href="track.php" class="active">Track Application</a>
            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'APPLICANT'): ?>
            <a href="../applicant/my-applications.php">My Applications</a>
            <a href="../../logout.php">Logout</a>
            <?php else: ?>
            <a href="../../login.php">Login</a>
            <a href="../applicant/register.php">Register</a>
            <?php endif; ?>
        </nav>
    </div>
</header>

<div class="container">
    <div class="page-header">
        <h2>Track Your Application</h2>
        <p>Enter your tracking number to check your application status</p>
    </div>

    <div class="track-form">
        <form method="GET" action="">
            <div class="form-group">
                <input type="text" name="tracking" placeholder="Enter tracking number (e.g., MSWD-20240924-ABC123)" value="<?= htmlspecialchars($tracking_number) ?>" required>
            </div>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-search"></i> Track
            </button>
        </form>
    </div>

    <?php if ($application): ?>
    <div class="tracking-result">
        <div class="result-header">
            <h3>Application Details</h3>
            <span class="tracking-number"><?= htmlspecialchars($application['tracking_number']) ?></span>
        </div>

        <div class="status-section">
            <div class="current-status">
                <span class="status-badge status-<?= str_replace('_', '-', $application['status']) ?>">
                    <?= ucfirst(str_replace('_', ' ', $application['status'])) ?>
                </span>
            </div>
        </div>

        <div class="info-grid">
            <div class="info-item">
                <label>Assistance Type:</label>
                <span><?= htmlspecialchars($application['assistance_type_name']) ?></span>
            </div>
            <div class="info-item">
                <label>Applicant Name:</label>
                <span><?= htmlspecialchars($application['first_name'] . ' ' . $application['last_name']) ?></span>
            </div>
            <div class="info-item">
                <label>Submitted:</label>
                <span><?= date('F j, Y g:i A', strtotime($application['submitted_at'])) ?></span>
            </div>
            <div class="info-item">
                <label>Last Updated:</label>
                <span><?= $application['reviewed_at'] ? date('F j, Y g:i A', strtotime($application['reviewed_at'])) : 'Not yet reviewed' ?></span>
            </div>
        </div>

        <?php if ($application['remarks']): ?>
        <div class="remarks-section">
            <h4>Remarks</h4>
            <p><?= htmlspecialchars($application['remarks']) ?></p>
        </div>
        <?php endif; ?>

        <?php if ($status_history && ($conn instanceof PDO ? count($status_history) > 0 : $status_history->num_rows > 0)): ?>
        <div class="history-section">
            <h4>Status History</h4>
            <div class="timeline">
                <?php if ($conn instanceof PDO): ?>
                    <?php foreach ($status_history as $h): ?>
                <div class="timeline-item">
                    <div class="timeline-dot"></div>
                    <div class="timeline-content">
                        <div class="timeline-status">
                            <?= ucfirst(str_replace('_', ' ', $h['old_status'])) ?> → 
                            <strong><?= ucfirst(str_replace('_', ' ', $h['new_status'])) ?></strong>
                        </div>
                        <div class="timeline-date"><?= date('F j, Y g:i A', strtotime($h['changed_at'])) ?></div>
                        <div class="timeline-worker">By: <?= htmlspecialchars($h['changed_by_name'] ?? 'Unknown') ?></div>
                        <?php if ($h['remarks']): ?>
                        <div class="timeline-remarks"><?= htmlspecialchars($h['remarks']) ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <?php while ($h = $status_history->fetch_assoc()): ?>
                <div class="timeline-item">
                    <div class="timeline-dot"></div>
                    <div class="timeline-content">
                        <div class="timeline-status">
                            <?= ucfirst(str_replace('_', ' ', $h['old_status'])) ?> → 
                            <strong><?= ucfirst(str_replace('_', ' ', $h['new_status'])) ?></strong>
                        </div>
                        <div class="timeline-date"><?= date('F j, Y g:i A', strtotime($h['changed_at'])) ?></div>
                        <div class="timeline-worker">By: <?= htmlspecialchars($h['changed_by_name'] ?? 'Unknown') ?></div>
                        <?php if ($h['remarks']): ?>
                        <div class="timeline-remarks"><?= htmlspecialchars($h['remarks']) ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                    <?php endwhile; ?>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <?php elseif (!empty($tracking_number)): ?>
    <div class="error-message">
        <i class="fas fa-exclamation-triangle"></i>
        <h3>Application Not Found</h3>
        <p>No application found with tracking number: <?= htmlspecialchars($tracking_number) ?></p>
        <p>Please check the tracking number and try again.</p>
    </div>
    <?php endif; ?>
</div>

</body>
</html>
