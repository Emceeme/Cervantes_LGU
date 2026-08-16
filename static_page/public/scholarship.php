<?php
include '../../config/db.php';
include '../../config/app_config.php';

// Get scholarship programs
$posts_stmt = $conn->prepare("
    SELECT *
    FROM scholarship_posts
    ORDER BY created_at DESC
");

if ($conn instanceof PDO) {
    // PostgreSQL/PDO
    $posts_stmt->execute();
    $posts = $posts_stmt->fetchAll();
    // Empty result is not an error for PDO
} else {
    // MySQLi
    $posts_stmt->execute();
    $posts = $posts_stmt->get_result();
    $posts_stmt->close();
    
    if(!$posts){
        die("Query Error: " . $conn->error);
    }
}

// Generate CSRF token
$csrf_token = bin2hex(random_bytes(32));
$_SESSION['scholarship_csrf_token'] = $csrf_token;

$success_message = isset($_GET['status']) && $_GET['status'] === 'success';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Scholarship Programs</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="procurement.css">
</head>
<body>
<div class="page-container">
    <?php $active_page = 'scholarship'; include 'sidebar.php'; ?>
    <main class="main-content">
        <div class="header">
            <h1>🎓 Scholarship Programs</h1>
            <p>Education assistance programs for deserving students</p>
        </div>
        <div class="container">
            <?php if($success_message): ?>
            <div class="success-message">✅ Application submitted successfully! Please bring original documents to the LGU office for verification.</div>
            <?php endif; ?>
            
            <div class="card">
                <h3 style="margin-bottom: 20px;">Available Scholarship Programs</h3>
                <?php if($conn instanceof PDO): ?>
                    <?php if(count($posts) > 0): ?>
                <div class="news-grid">
                    <?php foreach($posts as $row): ?>
                    <div class="news-card">
                        <?php if(!empty($row['image'])): ?>
                        <img src="<?= AppConfig::uploads('scholarship/' . $row['image']) ?>" alt="Scholarship Image">
                        <?php endif; ?>
                        <div class="news-content">
                            <h3><?= htmlspecialchars($row['title']) ?></h3>
                            <p class="date"><?= date("F d, Y", strtotime($row['created_at'])) ?></p>
                            <p><?= substr(strip_tags($row['description']), 0, 120) ?>...</p>
                            <button class="file-link" onclick='openScholarship(<?= json_encode($row["title"]) ?>, <?= json_encode($row["description"]) ?>, <?= json_encode($row["image"]) ?>, <?= json_encode(date("F d, Y", strtotime($row["created_at"]))) ?>)'>View Details</button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                    <?php else: ?>
                <div class="empty">No scholarship programs available at this time.</div>
                    <?php endif; ?>
                <?php else: ?>
                    <?php if($posts->num_rows > 0): ?>
                <div class="news-grid">
                    <?php while($row = $posts->fetch_assoc()): ?>
                    <div class="news-card">
                        <?php if(!empty($row['image'])): ?>
                        <img src="<?= AppConfig::uploads('scholarship/' . $row['image']) ?>" alt="Scholarship Image">
                        <?php endif; ?>
                        <div class="news-content">
                            <h3><?= htmlspecialchars($row['title']) ?></h3>
                            <p class="date"><?= date("F d, Y", strtotime($row['created_at'])) ?></p>
                            <p><?= substr(strip_tags($row['description']), 0, 120) ?>...</p>
                            <button class="file-link" onclick='openScholarship(<?= json_encode($row["title"]) ?>, <?= json_encode($row["description"]) ?>, <?= json_encode($row["image"]) ?>, <?= json_encode(date("F d, Y", strtotime($row["created_at"]))) ?>)'>View Details</button>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
                    <?php else: ?>
                <div class="empty">No scholarship programs available at this time.</div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <div class="card" style="margin-top: 30px;">
                <h3 style="margin-bottom: 20px;">Apply for Scholarship</h3>
                <p style="margin-bottom: 20px; color: #64748b;">Fill out the form below to apply. You will still need to submit original documents to the LGU office for verification.</p>
                
                <form action="apply_scholarship.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                    
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 20px;">
                        <div>
                            <label style="display: block; margin-bottom: 5px; color: #1e3a5f; font-weight: 500;">Full Name *</label>
                            <input type="text" name="full_name" required style="width: 100%; padding: 10px; border: 2px solid #e0f2fe; border-radius: 8px;">
                        </div>
                        <div>
                            <label style="display: block; margin-bottom: 5px; color: #1e3a5f; font-weight: 500;">Email *</label>
                            <input type="email" name="email" required style="width: 100%; padding: 10px; border: 2px solid #e0f2fe; border-radius: 8px;">
                        </div>
                        <div>
                            <label style="display: block; margin-bottom: 5px; color: #1e3a5f; font-weight: 500;">Phone *</label>
                            <input type="text" name="phone" required style="width: 100%; padding: 10px; border: 2px solid #e0f2fe; border-radius: 8px;">
                        </div>
                        <div>
                            <label style="display: block; margin-bottom: 5px; color: #1e3a5f; font-weight: 500;">Birth Date *</label>
                            <input type="date" name="birth_date" required style="width: 100%; padding: 10px; border: 2px solid #e0f2fe; border-radius: 8px;">
                        </div>
                        <div>
                            <label style="display: block; margin-bottom: 5px; color: #1e3a5f; font-weight: 500;">Gender *</label>
                            <select name="gender" required style="width: 100%; padding: 10px; border: 2px solid #e0f2fe; border-radius: 8px;">
                                <option value="">Select Gender</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                            </select>
                        </div>
                        <div>
                            <label style="display: block; margin-bottom: 5px; color: #1e3a5f; font-weight: 500;">Civil Status *</label>
                            <select name="civil_status" required style="width: 100%; padding: 10px; border: 2px solid #e0f2fe; border-radius: 8px;">
                                <option value="">Select Status</option>
                                <option value="Single">Single</option>
                                <option value="Married">Married</option>
                                <option value="Widowed">Widowed</option>
                            </select>
                        </div>
                    </div>

                    <div style="margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 5px; color: #1e3a5f; font-weight: 500;">Address *</label>
                        <textarea name="address" required style="width: 100%; padding: 10px; border: 2px solid #e0f2fe; border-radius: 8px; min-height: 80px;"></textarea>
                    </div>

                    <h4 style="margin: 20px 0 15px 0; color: #1e3a5f;">School Information</h4>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 20px;">
                        <div>
                            <label style="display: block; margin-bottom: 5px; color: #1e3a5f; font-weight: 500;">School Name *</label>
                            <input type="text" name="school_name" required style="width: 100%; padding: 10px; border: 2px solid #e0f2fe; border-radius: 8px;">
                        </div>
                        <div>
                            <label style="display: block; margin-bottom: 5px; color: #1e3a5f; font-weight: 500;">Course/Major *</label>
                            <input type="text" name="course" required style="width: 100%; padding: 10px; border: 2px solid #e0f2fe; border-radius: 8px;">
                        </div>
                        <div>
                            <label style="display: block; margin-bottom: 5px; color: #1e3a5f; font-weight: 500;">Year Level *</label>
                            <select name="year_level" required style="width: 100%; padding: 10px; border: 2px solid #e0f2fe; border-radius: 8px;">
                                <option value="">Select Year</option>
                                <option value="1st Year">1st Year</option>
                                <option value="2nd Year">2nd Year</option>
                                <option value="3rd Year">3rd Year</option>
                                <option value="4th Year">4th Year</option>
                                <option value="5th Year">5th Year</option>
                            </select>
                        </div>
                        <div>
                            <label style="display: block; margin-bottom: 5px; color: #1e3a5f; font-weight: 500;">GPA *</label>
                            <input type="number" name="gpa" step="0.01" min="0" max="5" required style="width: 100%; padding: 10px; border: 2px solid #e0f2fe; border-radius: 8px;">
                        </div>
                    </div>

                    <h4 style="margin: 20px 0 15px 0; color: #1e3a5f;">Family Information</h4>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 20px;">
                        <div>
                            <label style="display: block; margin-bottom: 5px; color: #1e3a5f; font-weight: 500;">Annual Family Income *</label>
                            <input type="number" name="family_income" required style="width: 100%; padding: 10px; border: 2px solid #e0f2fe; border-radius: 8px;">
                        </div>
                        <div>
                            <label style="display: block; margin-bottom: 5px; color: #1e3a5f; font-weight: 500;">Number of Family Members *</label>
                            <input type="number" name="family_members" required style="width: 100%; padding: 10px; border: 2px solid #e0f2fe; border-radius: 8px;">
                        </div>
                        <div>
                            <label style="display: block; margin-bottom: 5px; color: #1e3a5f; font-weight: 500;">Parent/Guardian Name *</label>
                            <input type="text" name="parent_name" required style="width: 100%; padding: 10px; border: 2px solid #e0f2fe; border-radius: 8px;">
                        </div>
                        <div>
                            <label style="display: block; margin-bottom: 5px; color: #1e3a5f; font-weight: 500;">Parent Contact *</label>
                            <input type="text" name="parent_contact" required style="width: 100%; padding: 10px; border: 2px solid #e0f2fe; border-radius: 8px;">
                        </div>
                        <div>
                            <label style="display: block; margin-bottom: 5px; color: #1e3a5f; font-weight: 500;">Parent Occupation *</label>
                            <input type="text" name="parent_occupation" required style="width: 100%; padding: 10px; border: 2px solid #e0f2fe; border-radius: 8px;">
                        </div>
                    </div>

                    <div style="margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 5px; color: #1e3a5f; font-weight: 500;">Why do you deserve this scholarship? (Essay)</label>
                        <textarea name="essay" style="width: 100%; padding: 10px; border: 2px solid #e0f2fe; border-radius: 8px; min-height: 150px;"></textarea>
                    </div>

                    <div style="margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 5px; color: #1e3a5f; font-weight: 500;">Upload Requirements (PDF, DOC, DOCX, JPG, PNG - Max 5MB)</label>
                        <input type="file" name="requirements_file" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" style="width: 100%; padding: 10px; border: 2px solid #e0f2fe; border-radius: 8px;">
                        <small style="color: #64748b;">Include: Birth certificate, grades, income certificate, and other required documents</small>
                    </div>

                    <button type="submit" class="file-link" style="width: 100%; text-align: center; padding: 15px;">Submit Application</button>
                </form>
            </div>
        </div>
    </main>
</div>
<div id="scholarshipModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeScholarship()">&times;</span>
        <div id="modalGallery"></div>
        <h2 id="modalTitle"></h2>
        <p id="modalDate"></p>
        <hr>
        <div id="modalContent"></div>
    </div>
</div>
<script>
const BASE_URL = "<?= AppConfig::getBaseUrl() ?>";
function openScholarship(title, description, image, date){
    document.getElementById("modalTitle").innerText = title;
    document.getElementById("modalDate").innerText = date;
    document.getElementById("modalContent").innerHTML = description;
    if(image){
        document.getElementById("modalGallery").innerHTML = '<img src="' + BASE_URL + '/uploads/scholarship/' + image + '" alt="Scholarship Image">';
    } else {
        document.getElementById("modalGallery").innerHTML = '';
    }
    document.getElementById("scholarshipModal").style.display = "flex";
}
function closeScholarship(){
    document.getElementById("scholarshipModal").style.display = "none";
}
window.onclick = function(event){
    const modal = document.getElementById("scholarshipModal");
    if(event.target == modal){
        modal.style.display = "none";
    }
}
</script>
</body>
</html>
