<?php
session_start();
require_once __DIR__ . '/../../config/security.php';
require_once __DIR__ . '/../../config/db.php';

setSecurityHeaders();

// SECURITY GUARD: MSWD Department only
if (!isset($_SESSION['department']) || $_SESSION['department'] !== 'MSWD') {
    logSecurityEvent('unauthorized_access', $_SESSION['id'] ?? null, ['endpoint' => 'mswd_review']);
    header('Location: /login.php?unauthorized=1');
    exit();
}

$worker_id = $_SESSION['id'];
$application_id = $_GET['id'] ?? 0;

if (!$application_id) {
    header("Location: dashboard.php");
    exit();
}

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        $error_message = "Security validation failed.";
    } else {
        $new_status = $_POST['status'];
        $remarks = $_POST['remarks'] ?? '';
        
        $conn->begin_transaction();
        
        try {
            // Get current status
            $stmt = $conn->prepare("SELECT status FROM applications WHERE id = ?");
            if ($conn instanceof PDO) {
                $stmt->execute([$application_id]);
                $row = $stmt->fetch();
                $old_status = $row['status'];
            } else {
                $stmt->bind_param("i", $application_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $row = $result->fetch_assoc();
                $old_status = $row['status'];
                $stmt->close();
            }
            
            // Update status
            $update_stmt = $conn->prepare("UPDATE applications SET status = ?, remarks = ?, reviewed_at = NOW(), assigned_worker_id = ? WHERE id = ?");
            if ($conn instanceof PDO) {
                $update_stmt->execute([$new_status, $remarks, $worker_id, $application_id]);
            } else {
                $update_stmt->bind_param("ssii", $new_status, $remarks, $worker_id, $application_id);
                $update_stmt->execute();
                $update_stmt->close();
            }
            
            // Log status change
            $log_stmt = $conn->prepare("INSERT INTO application_status_history (application_id, old_status, new_status, changed_by, remarks, changed_at) VALUES (?, ?, ?, ?, ?, NOW())");
            if ($conn instanceof PDO) {
                $log_stmt->execute([$application_id, $old_status, $new_status, $worker_id, $remarks]);
            } else {
                $log_stmt->bind_param("issis", $application_id, $old_status, $new_status, $worker_id, $remarks);
                $log_stmt->execute();
                $log_stmt->close();
            }
            
            $conn->commit();
            $success_message = "Status updated successfully!";
            
            logSecurityEvent('application_status_updated', $worker_id, [
                'application_id' => $application_id,
                'old_status' => $old_status,
                'new_status' => $new_status
            ]);
            
        } catch (Exception $e) {
            $conn->rollback();
            $error_message = "Failed to update status: " . $e->getMessage();
        }
    }
}

// Fetch application details
$stmt = $conn->prepare("
    SELECT a.*, at.name as assistance_type_name, at.eligibility_requirements, at.required_documents,
           CONCAT(u.first_name, ' ', u.last_name) as assigned_worker_name
    FROM applications a
    JOIN assistance_types at ON a.assistance_type_id = at.id
    LEFT JOIN users u ON a.assigned_worker_id = u.id
    WHERE a.id = ?
");

if ($conn instanceof PDO) {
    $stmt->execute([$application_id]);
    $application = $stmt->fetch();
} else {
    $stmt->bind_param("i", $application_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $application = $result ? $result->fetch_assoc() : false;
    $stmt->close();
}

if (!$application) {
    header("Location: dashboard.php");
    exit();
}

// Fetch documents
$doc_stmt = $conn->prepare("SELECT * FROM application_documents WHERE application_id = ? ORDER BY uploaded_at DESC");
if ($conn instanceof PDO) {
    $doc_stmt->execute([$application_id]);
    $documents = $doc_stmt->fetchAll();
} else {
    $doc_stmt->bind_param("i", $application_id);
    $doc_stmt->execute();
    $documents = $doc_stmt->get_result();
    $doc_stmt->close();
}

// Fetch status history
$history_stmt = $conn->prepare("
    SELECT h.*, CONCAT(u.first_name, ' ', u.last_name) as changed_by_name
    FROM application_status_history h
    LEFT JOIN users u ON h.changed_by = u.id
    WHERE h.application_id = ?
    ORDER BY h.changed_at DESC
");
if ($conn instanceof PDO) {
    $history_stmt->execute([$application_id]);
    $history = $history_stmt->fetchAll();
} else {
    $history_stmt->bind_param("i", $application_id);
    $history_stmt->execute();
    $history = $history_stmt->get_result();
    $history_stmt->close();
}

$csrf_token = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Review Application - MSWD Portal</title>
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
        <h2>Review Application</h2>
        <p>Tracking #: <?= htmlspecialchars($application['tracking_number']) ?></p>
    </div>

    <?php if (isset($success_message)): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success_message) ?></div>
    <?php endif; ?>

    <?php if (isset($error_message)): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error_message) ?></div>
    <?php endif; ?>

    <div class="review-container">
        <!-- Applicant Information -->
        <div class="card">
            <div class="card-header">
                <h3>Applicant Information</h3>
                <span class="status-badge status-<?= str_replace('_', '-', $application['status']) ?>">
                    <?= ucfirst(str_replace('_', ' ', $application['status'])) ?>
                </span>
            </div>
            <div class="card-body">
                <div class="info-grid">
                    <div class="info-item">
                        <label>Name:</label>
                        <span><?= htmlspecialchars($application['first_name'] . ' ' . $application['middle_name'] . ' ' . $application['last_name']) ?></span>
                    </div>
                    <div class="info-item">
                        <label>Birthdate:</label>
                        <span><?= date('F j, Y', strtotime($application['birthdate'])) ?></span>
                    </div>
                    <div class="info-item">
                        <label>Gender:</label>
                        <span><?= htmlspecialchars($application['gender']) ?></span>
                    </div>
                    <div class="info-item">
                        <label>Civil Status:</label>
                        <span><?= htmlspecialchars($application['civil_status']) ?></span>
                    </div>
                    <div class="info-item">
                        <label>Contact:</label>
                        <span><?= htmlspecialchars($application['contact_number']) ?></span>
                    </div>
                    <div class="info-item">
                        <label>Email:</label>
                        <span><?= htmlspecialchars($application['email'] ?? 'N/A') ?></span>
                    </div>
                    <div class="info-item full-width">
                        <label>Address:</label>
                        <span><?= htmlspecialchars($application['barangay']) ?>, <?= htmlspecialchars($application['street_address']) ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Assistance Details -->
        <div class="card">
            <div class="card-header">
                <h3>Assistance Details</h3>
            </div>
            <div class="card-body">
                <div class="info-item">
                    <label>Type:</label>
                    <span><?= htmlspecialchars($application['assistance_type_name']) ?></span>
                </div>
                <div class="info-item">
                    <label>Submitted:</label>
                    <span><?= date('F j, Y g:i A', strtotime($application['submitted_at'])) ?></span>
                </div>
                <div class="info-item full-width">
                    <label>Eligibility Requirements:</label>
                    <p><?= nl2br(htmlspecialchars($application['eligibility_requirements'])) ?></p>
                </div>
            </div>
        </div>

        <!-- Documents -->
        <div class="card">
            <div class="card-header">
                <h3>Submitted Documents</h3>
            </div>
            <div class="card-body">
                <?php if ($documents && ($conn instanceof PDO ? count($documents) > 0 : $documents->num_rows > 0)): ?>
                <div class="documents-list">
                    <?php if ($conn instanceof PDO): ?>
                        <?php foreach ($documents as $doc): ?>
                    <div class="document-item">
                        <i class="fas fa-file"></i>
                        <span><?= htmlspecialchars($doc['document_type']) ?> - <?= htmlspecialchars($doc['file_name']) ?></span>
                        <a href="../handler/view_document.php?id=<?= $doc['id'] ?>" target="_blank" class="btn btn-secondary">
                            <i class="fas fa-eye"></i> View
                        </a>
                    </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <?php while ($doc = $documents->fetch_assoc()): ?>
                    <div class="document-item">
                        <i class="fas fa-file"></i>
                        <span><?= htmlspecialchars($doc['document_type']) ?> - <?= htmlspecialchars($doc['file_name']) ?></span>
                        <a href="../handler/view_document.php?id=<?= $doc['id'] ?>" target="_blank" class="btn btn-secondary">
                            <i class="fas fa-eye"></i> View
                        </a>
                    </div>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </div>
                <?php else: ?>
                <p class="no-documents">No documents submitted.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Status Update -->
        <div class="card">
            <div class="card-header">
                <h3>Update Status</h3>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                    <div class="form-group">
                        <label>New Status:</label>
                        <select name="status" required>
                            <option value="pending" <?= $application['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="under_review" <?= $application['status'] === 'under_review' ? 'selected' : '' ?>>Under Review</option>
                            <option value="approved" <?= $application['status'] === 'approved' ? 'selected' : '' ?>>Approved</option>
                            <option value="rejected" <?= $application['status'] === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Remarks:</label>
                        <textarea name="remarks" rows="3"><?= htmlspecialchars($application['remarks'] ?? '') ?></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Update Status</button>
                </form>
            </div>
        </div>

        <!-- Status History -->
        <div class="card">
            <div class="card-header">
                <h3>Status History</h3>
            </div>
            <div class="card-body">
                <?php if ($history && ($conn instanceof PDO ? count($history) > 0 : $history->num_rows > 0)): ?>
                <div class="history-list">
                    <?php if ($conn instanceof PDO): ?>
                        <?php foreach ($history as $h): ?>
                    <div class="history-item">
                        <div class="history-status">
                            <?= ucfirst(str_replace('_', ' ', $h['old_status'])) ?> → 
                            <strong><?= ucfirst(str_replace('_', ' ', $h['new_status'])) ?></strong>
                        </div>
                        <div class="history-meta">
                            <span><?= htmlspecialchars($h['changed_by_name'] ?? 'Unknown') ?></span>
                            <span><?= date('M j, Y g:i A', strtotime($h['changed_at'])) ?></span>
                        </div>
                        <?php if ($h['remarks']): ?>
                        <div class="history-remarks"><?= htmlspecialchars($h['remarks']) ?></div>
                        <?php endif; ?>
                    </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <?php while ($h = $history->fetch_assoc()): ?>
                    <div class="history-item">
                        <div class="history-status">
                            <?= ucfirst(str_replace('_', ' ', $h['old_status'])) ?> → 
                            <strong><?= ucfirst(str_replace('_', ' ', $h['new_status'])) ?></strong>
                        </div>
                        <div class="history-meta">
                            <span><?= htmlspecialchars($h['changed_by_name'] ?? 'Unknown') ?></span>
                            <span><?= date('M j, Y g:i A', strtotime($h['changed_at'])) ?></span>
                        </div>
                        <?php if ($h['remarks']): ?>
                        <div class="history-remarks"><?= htmlspecialchars($h['remarks']) ?></div>
                        <?php endif; ?>
                    </div>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </div>
                <?php else: ?>
                <p class="no-history">No status history available.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

</body>
</html>
