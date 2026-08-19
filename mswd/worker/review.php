<?php
session_start();
require_once __DIR__ . '/../../config/security.php';
require_once __DIR__ . '/../../config/db.php';

setSecurityHeaders();

// SECURITY GUARD: MSWD Department only
if (!isset($_SESSION['department']) || $_SESSION['department'] !== 'MSWD') {
    logSecurityEvent('unauthorized_access', $_SESSION['id'] ?? null, ['endpoint' => 'mswd_worker_review']);
    http_response_code(403);
    die("Access Denied: MSWD Department privileges required.");
}

$worker_id = $_SESSION['id'];
$application_id = intval($_GET['id'] ?? 0);
$csrf_token = generateCsrfToken();
$error = null;

// Fetch application details
$app_stmt = $conn->prepare("
    SELECT a.*, at.name as assistance_type_name,
           CONCAT(u.first_name, ' ', u.last_name) as assigned_worker_name
    FROM applications a
    JOIN assistance_types at ON a.assistance_type_id = at.id
    LEFT JOIN users u ON a.assigned_worker_id = u.id
    WHERE a.id = ?
");

if ($conn instanceof PDO) {
    // PostgreSQL/PDO
    $app_stmt->execute([$application_id]);
    $result = $app_stmt->fetchAll();
    $application = $result[0] ?? false;
} else {
    // MySQLi
    if ($app_stmt) {
        $app_stmt->bind_param("i", $application_id);
        $app_stmt->execute();
        $application = $app_stmt->get_result()->fetch_assoc();
    } else {
        $application = false;
    }
}

if (!$application) {
    header("Location: dashboard.php?error=application_not_found");
    exit();
}

// Fetch documents
$docs_stmt = $conn->prepare("
    SELECT * FROM application_documents 
    WHERE application_id = ? 
    ORDER BY uploaded_at DESC
");
if ($conn instanceof PDO) {
    // PostgreSQL/PDO
    $docs_stmt->execute([$application_id]);
    $documents = $docs_stmt->fetchAll();
} else {
    // MySQLi
    $docs_stmt->bind_param("i", $application_id);
    $docs_stmt->execute();
    $documents = $docs_stmt->get_result();
}

// Fetch status history
$history_stmt = $conn->prepare("
    SELECT ash.*, CONCAT(u.first_name, ' ', u.last_name) as changed_by_name
    FROM application_status_history ash
    LEFT JOIN users u ON ash.changed_by = u.id
    WHERE ash.application_id = ?
    ORDER BY ash.changed_at DESC
");
if ($conn instanceof PDO) {
    // PostgreSQL/PDO
    $history_stmt->execute([$application_id]);
    $status_history = $history_stmt->fetchAll();
} else {
    // MySQLi
    $history_stmt->bind_param("i", $application_id);
    $history_stmt->execute();
    $status_history = $history_stmt->get_result();
}

// Handle status update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        logSecurityEvent('csrf_validation_failed', $_SESSION['id'] ?? null, ['endpoint' => 'update_application_status']);
        $error = "Security validation failed";
    } else {
        $new_status = sanitizeInput($_POST['status']);
        $remarks = sanitizeInput($_POST['remarks'] ?? '');
        
        $valid_statuses = ['pending', 'under_review', 'approved', 'rejected'];
        if (!in_array($new_status, $valid_statuses)) {
            $error = "Invalid status";
        } else {
            if ($conn instanceof PDO) {
                // PostgreSQL/PDO
                $conn->beginTransaction();
            } else {
                // MySQLi
                $conn->begin_transaction();
            }
            
            try {
                $update_stmt = $conn->prepare("
                    UPDATE applications 
                    SET status = ?, remarks = ?, assigned_worker_id = ?, reviewed_at = NOW() 
                    WHERE id = ?
                ");
                if ($conn instanceof PDO) {
                    // PostgreSQL/PDO
                    $update_stmt->execute([$new_status, $remarks, $worker_id, $application_id]);
                } else {
                    // MySQLi
                    $update_stmt->bind_param("ssii", $new_status, $remarks, $worker_id, $application_id);
                    $update_stmt->execute();
                }
                
                $log_stmt = $conn->prepare("
                    INSERT INTO application_status_history 
                    (application_id, old_status, new_status, changed_by, remarks)
                    VALUES (?, ?, ?, ?, ?)
                ");
                if ($conn instanceof PDO) {
                    // PostgreSQL/PDO
                    $log_stmt->execute([$application_id, $application['status'], $new_status, $worker_id, $remarks]);
                } else {
                    // MySQLi
                    $log_stmt->bind_param("issis", $application_id, $application['status'], $new_status, $worker_id, $remarks);
                    $log_stmt->execute();
                }
                
                $conn->commit();
                
                logSecurityEvent('application_status_updated', $worker_id, [
                    'application_id' => $application_id,
                    'old_status' => $application['status'],
                    'new_status' => $new_status
                ]);
                
                header("Location: dashboard.php?success=Application+status+updated");
                exit();
                
            } catch (Exception $e) {
                $conn->rollback();
                $error = "Failed to update status: " . $e->getMessage();
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
    <title>Review Application - MSWD Worker Portal</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/mswd.css">
</head>
<body>

<header>
    <div class="container header-content">
        <div>
            <a href="dashboard.php" class="back-link">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
        <div class="tracking-number"><?= htmlspecialchars($application['tracking_number']) ?></div>
    </div>
</header>

<div class="container">
    <?php if (isset($error)): ?>
        <div class="error-message">
            <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>
    
    <div class="content-grid">
        <!-- Main Content -->
        <div>
            <!-- Applicant Information -->
            <div class="card">
                <h3><i class="fas fa-user"></i> Applicant Information</h3>
                
                <div class="info-row">
                    <div class="info-label">Name:</div>
                    <div class="info-value">
                        <?= htmlspecialchars($application['first_name'] . ' ' . $application['middle_name'] . ' ' . $application['last_name']) ?>
                    </div>
                </div>
                <div class="info-row">
                    <div class="info-label">Birthdate:</div>
                    <div class="info-value"><?= date('F j, Y', strtotime($application['birthdate'])) ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Gender:</div>
                    <div class="info-value"><?= htmlspecialchars($application['gender']) ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Civil Status:</div>
                    <div class="info-value"><?= htmlspecialchars($application['civil_status']) ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Contact:</div>
                    <div class="info-value"><?= htmlspecialchars($application['contact_number']) ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Email:</div>
                    <div class="info-value"><?= htmlspecialchars($application['email'] ?: 'Not provided') ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Address:</div>
                    <div class="info-value">
                        <?= htmlspecialchars($application['barangay']) ?>, <?= htmlspecialchars($application['street_address']) ?>
                    </div>
                </div>
            </div>
            
            <!-- Documents -->
            <div class="card">
                <h3><i class="fas fa-file-alt"></i> Uploaded Documents</h3>
                
                <?php if ($documents && ($conn instanceof PDO ? count($documents) > 0 : $documents->num_rows > 0)): ?>
                    <div class="document-list">
                        <?php if ($conn instanceof PDO): ?>
                            <?php foreach ($documents as $doc): ?>
                        <div class="document-item">
                            <div class="document-info">
                                <div class="document-icon">
                                    <i class="fas fa-file"></i>
                                </div>
                                <div>
                                    <div class="document-name"><?= htmlspecialchars($doc['document_type']) ?></div>
                                    <div class="document-size">
                                        <?= htmlspecialchars($doc['file_name']) ?> • <?= number_format($doc['file_size'] / 1024, 1) ?> KB
                                    </div>
                                </div>
                            </div>
                            <div class="document-actions">
                                <a href="../handler/view_document.php?id=<?= $doc['id'] ?>" target="_blank" class="doc-btn view">
                                    <i class="fas fa-eye"></i> View
                                </a>
                            </div>
                        </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <?php while ($doc = $documents->fetch_assoc()): ?>
                        <div class="document-item">
                            <div class="document-info">
                                <div class="document-icon">
                                    <i class="fas fa-file"></i>
                                </div>
                                <div>
                                    <div class="document-name"><?= htmlspecialchars($doc['document_type']) ?></div>
                                    <div class="document-size">
                                        <?= htmlspecialchars($doc['file_name']) ?> • <?= number_format($doc['file_size'] / 1024, 1) ?> KB
                                    </div>
                                </div>
                            </div>
                            <div class="document-actions">
                                <a href="../handler/view_document.php?id=<?= $doc['id'] ?>" target="_blank" class="doc-btn view">
                                    <i class="fas fa-eye"></i> View
                                </a>
                            </div>
                        </div>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <p style="opacity: 0.7;">No documents uploaded</p>
                <?php endif; ?>
            </div>
            
            <!-- Status History -->
            <div class="card">
                <h3><i class="fas fa-history"></i> Status History</h3>
                
                <div class="timeline">
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
        </div>
        
        <!-- Sidebar -->
        <div>
            <!-- Application Details -->
            <div class="card">
                <h3><i class="fas fa-info-circle"></i> Application Details</h3>
                
                <div class="info-row">
                    <div class="info-label">Type:</div>
                    <div class="info-value"><?= htmlspecialchars($application['assistance_type_name']) ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Status:</div>
                    <div class="info-value">
                        <span class="status-badge status-<?= str_replace('_', '-', $application['status']) ?>">
                            <?= ucfirst(str_replace('_', ' ', $application['status'])) ?>
                        </span>
                    </div>
                </div>
                <div class="info-row">
                    <div class="info-label">Submitted:</div>
                    <div class="info-value"><?= date('F j, Y, g:i a', strtotime($application['submitted_at'])) ?></div>
                </div>
                <?php if ($application['reviewed_at']): ?>
                <div class="info-row">
                    <div class="info-label">Reviewed:</div>
                    <div class="info-value"><?= date('F j, Y, g:i a', strtotime($application['reviewed_at'])) ?></div>
                </div>
                <?php endif; ?>
                <?php if ($application['assigned_worker_name']): ?>
                <div class="info-row">
                    <div class="info-label">Assigned To:</div>
                    <div class="info-value"><?= htmlspecialchars($application['assigned_worker_name']) ?></div>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Update Status -->
            <div class="card">
                <h3><i class="fas fa-edit"></i> Update Status</h3>
                
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                    
                    <div class="form-group">
                        <label>New Status</label>
                        <select name="status" required>
                            <option value="pending" <?= $application['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="under_review" <?= $application['status'] === 'under_review' ? 'selected' : '' ?>>Under Review</option>
                            <option value="approved" <?= $application['status'] === 'approved' ? 'selected' : '' ?>>Approved</option>
                            <option value="rejected" <?= $application['status'] === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Remarks</label>
                        <textarea name="remarks" placeholder="Add remarks for this status change..."><?= htmlspecialchars($application['remarks'] ?? '') ?></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update Status
                    </button>
                </form>
            </div>
            
            <?php if ($application['remarks']): ?>
            <!-- Current Remarks -->
            <div class="card">
                <h3><i class="fas fa-comment-alt"></i> Current Remarks</h3>
                <p style="opacity: 0.9; line-height: 1.6;"><?= htmlspecialchars($application['remarks']) ?></p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

</body>
</html>
