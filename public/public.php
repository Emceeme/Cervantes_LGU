<?php
include '../config/db.php';

$jobs = $conn->query("
    SELECT *
    FROM jobs
    WHERE status = 'OPEN'
    ORDER BY id DESC
");

if (!$jobs) {
    die("SQL Error: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Career Opportunities</title>

<link rel="stylesheet" href="styles.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>

<body>

<!-- BACKGROUND -->
<div class="blur blur1"></div>
<div class="blur blur2"></div>

<!-- NAV -->
<nav>
    <div class="logo">🏛️ LGU Careers</div>

    <div class="nav-links">
        <a href="index.php">Home</a>
        <a href="#">Jobs</a>
        <a href="#">Contact</a>
    </div>
</nav>

<!-- HERO -->
<section class="hero">
    <h1>Career Opportunities</h1>
    <p>Join our team and help serve the community.</p>

    <div class="search-box">
        <input type="text" id="searchInput" placeholder="Search jobs...">
        <button>Search</button>
    </div>
</section>

<!-- JOBS -->
<section class="jobs-container">

<?php if($jobs->num_rows > 0): ?>

    <?php while($row = $jobs->fetch_assoc()): ?>

    <div class="job-card">

        <span class="badge">
            <?= htmlspecialchars($row['employment_type']); ?>
        </span>

        <h3><?= htmlspecialchars($row['job_title']); ?></h3>

        <p class="department">
            <?= htmlspecialchars($row['department']); ?>
        </p>

        <p class="description">
            <?= htmlspecialchars(substr($row['description'], 0, 120)); ?>...
        </p>

        <div class="job-footer">
            <span>📍 <?= htmlspecialchars($row['location']); ?></span>
            <span>💰 <?= htmlspecialchars($row['salary']); ?></span>
        </div>

        <div class="actions">

            <!-- VIEW -->
            <button class="view-btn"
                onclick="openModal(
                    '<?= addslashes($row['job_title']) ?>',
                    '<?= addslashes($row['department']) ?>',
                    '<?= addslashes($row['description']) ?>'
                )">
                View Details
            </button>

            <!-- APPLY -->
            <button class="apply-btn"
                onclick="openApply(<?= $row['id']; ?>)">
                Apply Now
            </button>

        </div>

    </div>

    <?php endwhile; ?>

<?php else: ?>

    <div class="empty-state">
        No available job postings.
    </div>

<?php endif; ?>

</section>

<!-- VIEW MODAL -->
<div id="jobModal" class="modal">

    <div class="modal-content">

        <span class="close" onclick="closeModal()">&times;</span>

        <h2 id="modalTitle"></h2>
        <p id="modalDepartment"></p>
        <hr>
        <div id="modalDescription"></div>

    </div>

</div>

<!-- APPLY MODAL -->
<div id="applyModal" class="modal">

    <div class="modal-content">

        <span class="close" onclick="closeApply()">&times;</span>

        <h2>Apply for this Job</h2>

       <form action="apply_job.php"
      method="POST"
      enctype="multipart/form-data">

    <input type="hidden"
           name="job_id"
           id="apply_job_id">

    <input type="text"
           name="full_name"
           placeholder="Full Name"
           required>

    <input type="email"
           name="email"
           placeholder="Email Address"
           required>

    <input type="text"
           name="phone"
           placeholder="Phone Number"
           required>

    <textarea
        name="message"
        placeholder="Cover Letter / Message"
        required></textarea>

    <input type="file"
           name="resume"
           accept=".pdf,.doc,.docx"
           required>

    <button type="submit">
        Submit Application
    </button>

</form>`

    </div>

</div>

<!-- SCRIPTS -->
<script>

// VIEW MODAL
function openModal(title, dept, desc){
    document.getElementById("modalTitle").innerText = title;
    document.getElementById("modalDepartment").innerText = dept;
    document.getElementById("modalDescription").innerText = desc;

    document.getElementById("jobModal").style.display = "flex";
}

function closeModal(){
    document.getElementById("jobModal").style.display = "none";
}

// APPLY MODAL
function openApply(jobId){
    document.getElementById("apply_job_id").value = jobId;
    document.getElementById("applyModal").style.display = "flex";
}

function closeApply(){
    document.getElementById("applyModal").style.display = "none";
}

// CLOSE ON OUTSIDE CLICK
window.onclick = function(e){

    let jobModal = document.getElementById("jobModal");
    let applyModal = document.getElementById("applyModal");

    if(e.target === jobModal){
        jobModal.style.display = "none";
    }

    if(e.target === applyModal){
        applyModal.style.display = "none";
    }
}

// SEARCH FILTER
document.getElementById("searchInput").addEventListener("keyup", function(){

    let filter = this.value.toLowerCase();
    let jobs = document.querySelectorAll(".job-card");

    jobs.forEach(job => {
        job.style.display =
            job.innerText.toLowerCase().includes(filter)
            ? "block"
            : "none";
    });

});

</script>

</body>
</html>