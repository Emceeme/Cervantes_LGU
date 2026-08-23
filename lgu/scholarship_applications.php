<?php
session_start();
require_once '../config/security.php';
require_once '../config/db.php';
require_once '../config/app_config.php';

// Set security headers
setSecurityHeaders();

// SECURITY GUARD: Restrict access to Mayor's Office, LGU departments & Super Admins only
$department = html_entity_decode($_SESSION['department'] ?? '', ENT_QUOTES);
if (!isset($_SESSION['role']) || ($department !== "Mayor's Office" && $department !== 'Mayor Office' && $department !== 'LGU' && $_SESSION['role'] !== 'SUPER_ADMIN')) {
    logSecurityEvent('unauthorized_access', $_SESSION['id'] ?? null, ['endpoint' => 'scholarship_applications', 'department' => $department]);
    header('Location: /login.php?unauthorized=1');
    exit();
}

// Get filter parameters
$status_filter = $_GET['status'] ?? '';

// Build query
$sql = "SELECT * FROM scholarship_applications";
$params = [];
$types = "";

if (!empty($status_filter)) {
    $sql .= " WHERE status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

$sql .= " ORDER BY submitted_at DESC";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Prepare Error: " . $conn->error . " SQL: " . $sql . "<br><br>NOTE: Make sure to run the migration: <a href='../migrations/create_scholarship_applications.php'>Click here to create scholarship_applications table</a>");
}

if ($conn instanceof PDO) {
    // PostgreSQL/PDO
    if (!empty($params)) {
        $stmt->execute($params);
    } else {
        $stmt->execute();
    }
    $applications = $stmt->fetchAll();
    // Empty result is not an error for PDO
} else {
    // MySQLi
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $applications = $stmt->get_result();
    
    if (!$applications) {
        die("Query Error: " . $conn->error);
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Scholarship Applications</title>
<link rel="stylesheet" href="procurement.css">
</head>
<body>

<div class="container">

    <!-- SIDEBAR -->
    <aside class="sidebar">
        <div class="logo">LGU <span>Portal</span></div>
        <nav class="sidebar-nav">
            <a href="dashboard.php">Dashboard</a>
            <a href="applicants.php">Applicants</a>
            <a href="procurement.php">Procurement</a>
            <a href="newsfeed.php">News Feed</a>
            <a href="scholarship_applications.php">Scholarship Applications</a>
            <a href="settings.php">Settings</a>
            <a href="../logout.php">Logout</a>
        </nav>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="main-content">

        <div class="top-bar">
            <h2>Scholarship Applications</h2>
            <p>Review and manage scholarship applications</p>
        </div>

        <!-- Filter -->
        <form method="GET" action="scholarship_applications.php" style="display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap;">
            <select name="status" style="padding: 10px 15px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px;">
                <option value="">All Status</option>
                <option value="PENDING" <?= $status_filter === 'PENDING' ? 'selected' : '' ?>>Pending</option>
                <option value="UNDER_REVIEW" <?= $status_filter === 'UNDER_REVIEW' ? 'selected' : '' ?>>Under Review</option>
                <option value="APPROVED" <?= $status_filter === 'APPROVED' ? 'selected' : '' ?>>Approved</option>
                <option value="REJECTED" <?= $status_filter === 'REJECTED' ? 'selected' : '' ?>>Rejected</option>
            </select>
            <button type="submit" style="padding: 10px 20px; background: #3b82f6; color: white; border: none; border-radius: 8px; cursor: pointer;">Filter</button>
            <?php if(!empty($status_filter)): ?>
                <a href="scholarship_applications.php" style="padding: 10px 20px; background: #64748b; color: white; text-decoration: none; border-radius: 8px;">Clear</a>
            <?php endif; ?>
        </form>

        <section class="card">
            <div class="card-header">
                <h3>Applications List</h3>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Applicant</th>
                        <th>School</th>
                        <th>GPA</th>
                        <th>Family Income</th>
                        <th>Status</th>
                        <th>Submitted</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($conn instanceof PDO): ?>
                        <?php foreach($applications as $row): ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars($row['full_name']) ?></strong><br>
                            <small><?= htmlspecialchars($row['email']) ?></small><br>
                            <small><?= htmlspecialchars($row['phone']) ?></small>
                        </td>
                        <td>
                            <?= htmlspecialchars($row['school_name']) ?><br>
                            <small><?= htmlspecialchars($row['course']) ?> - <?= htmlspecialchars($row['year_level']) ?></small>
                        </td>
                        <td><?= htmlspecialchars($row['gpa']) ?></td>
                        <td>₱<?= number_format($row['family_income'], 2) ?></td>
                        <td>
                            <?php
                            $status_class = '';
                            switch($row['status']) {
                                case 'PENDING': $status_class = 'status-open'; break;
                                case 'UNDER_REVIEW': $status_class = 'status-open'; break;
                                case 'APPROVED': $status_class = 'status-awarded'; break;
                                case 'REJECTED': $status_class = 'status-rejected'; break;
                            }
                            ?>
                            <span class="<?= $status_class ?>"><?= htmlspecialchars($row['status']) ?></span>
                        </td>
                        <td><?= date('M d, Y', strtotime($row['submitted_at'])) ?></td>
                        <td>
                            <button class="view-btn" onclick='viewApplication(<?= json_encode($row) ?>)'>View Details</button>
                        </td>
                    </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <?php while($row = $applications->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars($row['full_name']) ?></strong><br>
                            <small><?= htmlspecialchars($row['email']) ?></small><br>
                            <small><?= htmlspecialchars($row['phone']) ?></small>
                        </td>
                        <td>
                            <?= htmlspecialchars($row['school_name']) ?><br>
                            <small><?= htmlspecialchars($row['course']) ?> - <?= htmlspecialchars($row['year_level']) ?></small>
                        </td>
                        <td><?= htmlspecialchars($row['gpa']) ?></td>
                        <td>₱<?= number_format($row['family_income'], 2) ?></td>
                        <td>
                            <?php
                            $status_class = '';
                            switch($row['status']) {
                                case 'PENDING': $status_class = 'status-open'; break;
                                case 'UNDER_REVIEW': $status_class = 'status-open'; break;
                                case 'APPROVED': $status_class = 'status-awarded'; break;
                                case 'REJECTED': $status_class = 'status-rejected'; break;
                            }
                            ?>
                            <span class="<?= $status_class ?>"><?= htmlspecialchars($row['status']) ?></span>
                        </td>
                        <td><?= date('M d, Y', strtotime($row['submitted_at'])) ?></td>
                        <td>
                            <button class="view-btn" onclick='viewApplication(<?= json_encode($row) ?>)'>View Details</button>
                        </td>
                    </tr>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <?php if($conn instanceof PDO): ?>
                <?php if(count($applications) === 0): ?>
            <div style="text-align: center; padding: 40px; opacity: 0.7;">No scholarship applications found.</div>
                <?php endif; ?>
            <?php else: ?>
                <?php if($applications->num_rows === 0): ?>
            <div style="text-align: center; padding: 40px; opacity: 0.7;">No scholarship applications found.</div>
                <?php endif; ?>
            <?php endif; ?>
        </section>
    </main>
</div>

<!-- Application Modal -->
<div id="applicationModal" class="modal">
    <div class="modal-content" style="max-width: 900px;">
        <span class="close" onclick="closeModal()">&times;</span>
        <h2 id="modalTitle">Application Details</h2>
        <div id="modalBody"></div>
        <div style="margin-top: 20px; display: flex; gap: 10px; flex-wrap: wrap;">
            <button class="view-btn" onclick="updateStatus('UNDER_REVIEW')" style="background: #f59e0b;">Mark as Under Review</button>
            <button class="view-btn" onclick="updateStatus('APPROVED')" style="background: #22c55e;">Approve</button>
            <button class="view-btn" onclick="updateStatus('REJECTED')" style="background: #ef4444;">Reject</button>
        </div>
    </div>
</div>

<script>
const BASE_URL = "<?= AppConfig::getBaseUrl() ?>";
let currentApplicationId = null;

function viewApplication(data) {
    currentApplicationId = data.id;
    document.getElementById("modalTitle").innerText = data.full_name;
    
    let html = `
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 20px;">
            <div><strong>Email:</strong> ${data.email}</div>
            <div><strong>Phone:</strong> ${data.phone}</div>
            <div><strong>Birth Date:</strong> ${data.birth_date}</div>
            <div><strong>Gender:</strong> ${data.gender}</div>
            <div><strong>Civil Status:</strong> ${data.civil_status}</div>
        </div>
        <div style="margin-bottom: 15px;"><strong>Address:</strong> ${data.address}</div>
        
        <h4 style="margin: 20px 0 10px 0; color: #1e3a5f;">School Information</h4>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 15px;">
            <div><strong>School:</strong> ${data.school_name}</div>
            <div><strong>Course:</strong> ${data.course}</div>
            <div><strong>Year Level:</strong> ${data.year_level}</div>
            <div><strong>GPA:</strong> ${data.gpa}</div>
        </div>
        
        <h4 style="margin: 20px 0 10px 0; color: #1e3a5f;">Family Information</h4>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 15px;">
            <div><strong>Family Income:</strong> ₱${parseFloat(data.family_income).toLocaleString()}</div>
            <div><strong>Family Members:</strong> ${data.family_members}</div>
            <div><strong>Parent Name:</strong> ${data.parent_name}</div>
            <div><strong>Parent Contact:</strong> ${data.parent_contact}</div>
            <div><strong>Parent Occupation:</strong> ${data.parent_occupation}</div>
        </div>
        
        <h4 style="margin: 20px 0 10px 0; color: #1e3a5f;">Essay</h4>
        <div style="background: #f8fafc; padding: 15px; border-radius: 8px; margin-bottom: 15px;">${data.essay || 'No essay provided.'}</div>
    `;
    
    if (data.file_path) {
        html += `
            <h4 style="margin: 20px 0 10px 0; color: #1e3a5f;">Uploaded Documents</h4>
            <a href="${BASE_URL}/uploads/scholarship/${data.file_path}" target="_blank" class="view-btn">Download ${data.original_file_name}</a>
        `;
    }
    
    html += `
        <h4 style="margin: 20px 0 10px 0; color: #1e3a5f;">Admin Notes</h4>
        <textarea id="adminNotes" style="width: 100%; padding: 10px; border: 1px solid #e2e8f0; border-radius: 8px; min-height: 80px;">${data.admin_notes || ''}</textarea>
    `;
    
    document.getElementById("modalBody").innerHTML = html;
    document.getElementById("applicationModal").style.display = "flex";
}

function closeModal() {
    document.getElementById("applicationModal").style.display = "none";
    currentApplicationId = null;
}

function updateStatus(status) {
    if (!currentApplicationId) return;
    
    const notes = document.getElementById("adminNotes").value;
    
    fetch('update_scholarship_status.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `id=${currentApplicationId}&status=${status}&notes=${encodeURIComponent(notes)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Status updated successfully!');
            closeModal();
            location.reload();
        } else {
            alert('Error: ' + data.error);
        }
    })
    .catch(error => {
        alert('Error: ' + error);
    });
}

window.onclick = function(event) {
    const modal = document.getElementById("applicationModal");
    if (event.target == modal) {
        closeModal();
    }
}
</script>

<style>
.status-rejected {
    background: #fecaca;
    color: #dc2626;
    padding: 4px 8px;
    border-radius: 4px;
    font-weight: 600;
    font-size: 12px;
}
</style>
</body>
</html>
