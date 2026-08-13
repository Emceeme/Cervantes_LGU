<?php
session_start();
require_once '../../config/security.php';
require_once '../../config/db.php';

// Set security headers
setSecurityHeaders();

$jobs_stmt = $conn->prepare("
    SELECT *
    FROM jobs
    WHERE status = 'OPEN'
    ORDER BY id DESC
");
$jobs_stmt->execute();
$jobs = $jobs_stmt->get_result();

if (!$jobs) {
    die("SQL Error: " . $conn->error);
}
$jobs_stmt->close();

// Generate CSRF token
$csrf_token = generateCsrfToken();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Career Opportunities</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="procurement.css">
</head>
<body>
<div class="page-container">
    <?php $active_page = 'jobs'; include 'sidebar.php'; ?>
    <main class="main-content">
        <div class="header">
            <h1>💼 Career Opportunities</h1>
            <p>Join our team and help serve the community</p>
        </div>
        <div class="container">
            <div class="card">
                <h3 style="margin-bottom: 15px;">Available Positions</h3>
                <?php if($jobs->num_rows > 0): ?>
                <div class="jobs-grid">
                    <?php while($row = $jobs->fetch_assoc()): ?>
                    <div class="job-card">
                        <span class="status-open"><?= htmlspecialchars($row['employment_type']) ?></span>
                        <h3><?= htmlspecialchars($row['job_title']) ?></h3>
                        <p class="department"><?= htmlspecialchars($row['department']) ?></p>
                        <p><?= htmlspecialchars(substr($row['description'], 0, 120)) ?>...</p>
                        <div class="job-footer">
                            <span>📍 <?= htmlspecialchars($row['location']) ?></span>
                            <span>💰 <?= htmlspecialchars($row['salary']) ?></span>
                        </div>
                        <div class="job-actions">
                            <button class="file-link" onclick="openModal('<?= addslashes($row['job_title']) ?>', '<?= addslashes($row['department']) ?>', '<?= addslashes($row['description']) ?>')">View Details</button>
                            <button class="file-link" onclick="openApply(<?= $row['id']; ?>)">Apply Now</button>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
                <?php else: ?>
                <div class="empty">No available job postings.</div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>
<div id="jobModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal()">&times;</span>
        <h2 id="modalTitle"></h2>
        <p id="modalDepartment"></p>
        <hr>
        <div id="modalDescription"></div>
    </div>
</div>
<div id="applyModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeApply()">&times;</span>
        <h2>Apply for this Job</h2>
        <form action="apply_job.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
            <input type="hidden" name="job_id" id="apply_job_id">
            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px; color: #1e3a5f;">Full Name</label>
                <input type="text" name="full_name" placeholder="Full Name" required style="width: 100%; padding: 10px; border: 2px solid #e0f2fe; border-radius: 8px;">
            </div>
            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px; color: #1e3a5f;">Email Address</label>
                <input type="email" name="email" placeholder="Email Address" required style="width: 100%; padding: 10px; border: 2px solid #e0f2fe; border-radius: 8px;">
            </div>
            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px; color: #1e3a5f;">Phone Number</label>
                <input type="text" name="phone" placeholder="Phone Number" required style="width: 100%; padding: 10px; border: 2px solid #e0f2fe; border-radius: 8px;">
            </div>
            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px; color: #1e3a5f;">Cover Letter / Message</label>
                <textarea name="message" placeholder="Cover Letter / Message" required style="width: 100%; padding: 10px; border: 2px solid #e0f2fe; border-radius: 8px; min-height: 100px;"></textarea>
            </div>
            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px; color: #1e3a5f;">Resume (PDF, DOC, DOCX)</label>
                <input type="file" name="resume" accept=".pdf,.doc,.docx" required style="width: 100%; padding: 10px; border: 2px solid #e0f2fe; border-radius: 8px;">
            </div>
            <button type="submit" class="file-link" style="width: 100%; text-align: center;">Submit Application</button>
        </form>
    </div>
</div>
<script>
function openModal(title, dept, desc){
    document.getElementById("modalTitle").innerText = title;
    document.getElementById("modalDepartment").innerText = dept;
    document.getElementById("modalDescription").innerText = desc;
    document.getElementById("jobModal").style.display = "flex";
}
function closeModal(){
    document.getElementById("jobModal").style.display = "none";
}
function openApply(jobId){
    document.getElementById("apply_job_id").value = jobId;
    document.getElementById("applyModal").style.display = "flex";
}
function closeApply(){
    document.getElementById("applyModal").style.display = "none";
}
window.onclick = function(event){
    const jobModal = document.getElementById("jobModal");
    const applyModal = document.getElementById("applyModal");
    if(event.target == jobModal){
        jobModal.style.display = "none";
    }
    if(event.target == applyModal){
        applyModal.style.display = "none";
    }
}
</script>
</body>
</html>