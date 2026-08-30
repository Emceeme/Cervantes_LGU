<?php
require_once __DIR__ . '/../../config/security.php';
// require_once __DIR__ . '/../../config/db.php'; // TEMPORARILY DISABLED

setSecurityHeaders();

$tracking_number = '';
$application = null;
$status_history = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $tracking_number = sanitizeInput($_POST['tracking_number'] ?? '');
    
    if (empty($tracking_number)) {
        $error = "Please enter a tracking number";
    } else {
        // Fetch application details
        $app_stmt = $conn->prepare("
            SELECT a.*, at.name as assistance_type_name
            FROM applications a
            JOIN assistance_types at ON a.assistance_type_id = at.id
            WHERE a.tracking_number = ?
        ");
        
        if ($app_stmt) {
            if ($conn instanceof PDO) {
                $app_stmt->execute([$tracking_number]);
                $application = $app_stmt->fetch(PDO::FETCH_ASSOC);
            } else {
                $app_stmt->bind_param("s", $tracking_number);
                $app_stmt->execute();
                $result = $app_stmt->get_result();
                $application = $result ? $result->fetch_assoc() : false;
            }
        }
        
        if (!$application) {
            $error = "No application found with this tracking number";
        } else {
            // Fetch status history
            $history_stmt = $conn->prepare("
                SELECT ash.*, CONCAT(u.first_name, ' ', u.last_name) as changed_by_name
                FROM application_status_history ash
                LEFT JOIN users u ON ash.changed_by = u.id
                WHERE ash.application_id = ?
                ORDER BY ash.changed_at DESC
            ");
            
            if ($conn instanceof PDO) {
                $history_stmt->execute([$application['id']]);
                $status_history = $history_stmt->fetchAll(PDO::FETCH_ASSOC);
            } else {
                $history_stmt->bind_param("i", $application['id']);
                $history_stmt->execute();
                $status_history = $history_stmt->get_result();
            }
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
            <a href="track.php">Track Application</a>
        </nav>
    </div>
</header>

<div class="container">
    <div class="search-section">
        <h2>Track Your Application</h2>
        <p>Enter your tracking number to check your application status</p>
        
        <form method="POST" class="search-form">
            <input type="text" name="tracking_number" placeholder="Enter tracking number (e.g., MSWD-2026-XXXXXX)" value="<?= htmlspecialchars($tracking_number) ?>">
            <button type="submit">
                <i class="fas fa-search"></i> Track
            </button>
        </form>
        
        <?php if ($error): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
    </div>
    
    <?php if ($application): ?>
    <div class="results-section">
        <div class="status-header">
            <div>
                <h3>Application Status</h3>
                <div class="tracking-number"><?= htmlspecialchars($application['tracking_number']) ?></div>
            </div>
            <span class="status-badge status-<?= str_replace('_', '-', $application['status']) ?>">
                <?= ucfirst(str_replace('_', ' ', $application['status'])) ?>
            </span>
        </div>
        
        <div class="info-grid">
            <div class="info-card">
                <h4>Applicant Name</h4>
                <p><?= htmlspecialchars($application['first_name'] . ' ' . $application['last_name']) ?></p>
            </div>
            <div class="info-card">
                <h4>Assistance Type</h4>
                <p><?= htmlspecialchars($application['assistance_type_name']) ?></p>
            </div>
            <div class="info-card">
                <h4>Submitted On</h4>
                <p><?= date('F j, Y', strtotime($application['submitted_at'])) ?></p>
            </div>
            <div class="info-card">
                <h4>Barangay</h4>
                <p><?= htmlspecialchars($application['barangay']) ?></p>
            </div>
        </div>
        
        <?php if ($application['remarks']): ?>
        <div class="remarks-section">
            <h4>Remarks</h4>
            <p><?= htmlspecialchars($application['remarks']) ?></p>
        </div>
        <?php endif; ?>
        
        <div class="timeline">
            <h4>Status History</h4>
            <?php if ($status_history && ($conn instanceof PDO ? count($status_history) > 0 : $status_history->num_rows > 0)): ?>
                <?php if ($conn instanceof PDO): ?>
                    <?php foreach ($status_history as $history): ?>
                <div class="timeline-item">
                    <div class="timeline-content">
                        <h5><?= ucfirst(str_replace('_', ' ', $history['new_status'])) ?></h5>
                        <p>
                            Changed by: <?= htmlspecialchars($history['changed_by_name'] ?? 'System') ?>
                            <?php if ($history['remarks']): ?>
                            <br>Remarks: <?= htmlspecialchars($history['remarks']) ?>
                            <?php endif; ?>
                        </p>
                        <div class="timeline-date"><?= date('F j, Y, g:i a', strtotime($history['changed_at'])) ?></div>
                    </div>
                </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <?php while ($history = $status_history->fetch_assoc()): ?>
                <div class="timeline-item">
                    <div class="timeline-content">
                        <h5><?= ucfirst(str_replace('_', ' ', $history['new_status'])) ?></h5>
                        <p>
                            Changed by: <?= htmlspecialchars($history['changed_by_name'] ?? 'System') ?>
                            <?php if ($history['remarks']): ?>
                            <br>Remarks: <?= htmlspecialchars($history['remarks']) ?>
                            <?php endif; ?>
                        </p>
                        <div class="timeline-date"><?= date('F j, Y, g:i a', strtotime($history['changed_at'])) ?></div>
                    </div>
                </div>
                    <?php endwhile; ?>
                <?php endif; ?>
            <?php else: ?>
                <p style="opacity: 0.7;">No status history available</p>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

</body>
</html>
