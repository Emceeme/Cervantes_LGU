<?php
session_start();
require_once __DIR__ . '/../../config/security.php';
require_once __DIR__ . '/../../config/db.php';

setSecurityHeaders();

$csrf_token = generateCsrfToken();

// Check if assistance type is pre-selected from URL
$preselected_type_id = $_GET['type'] ?? null;

// Fetch assistance types
$types_stmt = $conn->prepare("SELECT id, name, description, eligibility_requirements, required_documents, process_steps FROM assistance_types WHERE is_active = 1 ORDER BY name");

if ($conn instanceof PDO) {
    $types_stmt->execute();
    $assistance_types = $types_stmt->fetchAll();
} else {
    $types_stmt->execute();
    $assistance_types = $types_stmt->get_result();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Apply for Assistance - MSWD Portal</title>
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
        <h2>Apply for Assistance</h2>
        <p>Fill out the form below to submit your application</p>
    </div>

    <div class="application-form">
        <form id="applicationForm" method="POST" action="../handler/submit_application.php" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
            
            <div class="form-container">
                <!-- Assistance Type Selection -->
                <div class="form-row">
                    <div class="form-group">
                        <label>Assistance Type *</label>
                        <select name="assistance_type_id" id="assistance_type_id" required onchange="updateRequiredDocs()">
                            <option value="">Select</option>
                            <?php if ($conn instanceof PDO): ?>
                                <?php foreach ($assistance_types as $type): ?>
                            <option value="<?= $type['id'] ?>" <?= $preselected_type_id == $type['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($type['name']) ?>
                            </option>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <?php while ($type = $assistance_types->fetch_assoc()): ?>
                            <option value="<?= $type['id'] ?>" <?= $preselected_type_id == $type['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($type['name']) ?>
                            </option>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>&nbsp;</label>
                        <div id="assistanceDetails" style="display:none; padding: 8px; background: #f8fafc; border-radius: 4px; font-size: 12px;">
                            <div id="assistanceDescription"></div>
                            <div id="requiredDocs"></div>
                        </div>
                    </div>
                </div>

                <!-- Personal Information -->
                <div class="form-row">
                    <div class="form-group">
                        <label>First Name *</label>
                        <input type="text" name="first_name" required>
                    </div>
                    <div class="form-group">
                        <label>Middle Name</label>
                        <input type="text" name="middle_name">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Last Name *</label>
                        <input type="text" name="last_name" required>
                    </div>
                    <div class="form-group">
                        <label>Birthdate *</label>
                        <input type="date" name="birthdate" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Gender *</label>
                        <select name="gender" required>
                            <option value="">Select Gender</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Civil Status *</label>
                        <select name="civil_status" required>
                            <option value="">Select Civil Status</option>
                            <option value="Single">Single</option>
                            <option value="Married">Married</option>
                            <option value="Widowed">Widowed</option>
                            <option value="Separated">Separated</option>
                            <option value="Divorced">Divorced</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Contact Number *</label>
                        <input type="tel" name="contact_number" required>
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Barangay *</label>
                        <input type="text" name="barangay" required>
                    </div>
                    <div class="form-group">
                        <label>Street Address *</label>
                        <input type="text" name="street_address" required>
                    </div>
                </div>

                <!-- Upload Documents -->
                <div class="form-group">
                    <label>Upload Documents</label>
                    <input type="file" name="documents[]" multiple accept=".pdf,.jpg,.jpeg,.png">
                    <small>PDF, JPG, PNG (Max 5MB each)</small>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Submit Application</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
const assistanceTypes = <?php 
    if ($conn instanceof PDO) {
        echo json_encode($assistance_types);
    } else {
        $types_array = [];
        while ($type = $assistance_types->fetch_assoc()) {
            $types_array[] = $type;
        }
        echo json_encode($types_array);
    }
?>;

function updateRequiredDocs() {
    const select = document.getElementById('assistance_type_id');
    const typeId = select.value;
    const detailsDiv = document.getElementById('assistanceDetails');
    const descDiv = document.getElementById('assistanceDescription');
    const docsDiv = document.getElementById('requiredDocs');
    
    if (!typeId) {
        detailsDiv.style.display = 'none';
        return;
    }
    
    const selectedType = assistanceTypes.find(t => t.id == typeId);
    if (selectedType) {
        detailsDiv.style.display = 'block';
        descDiv.innerHTML = '<p style="margin-bottom: 10px;"><strong>Description:</strong> ' + selectedType.description + '</p>';
        
        const docs = JSON.parse(selectedType.required_documents || '[]');
        if (docs.length > 0) {
            const docsHtml = docs.map(doc => `<div class="doc-item" style="padding: 8px 0; border-bottom: 1px solid #e2e8f0;"><i class="fas fa-file"></i> ${doc}</div>`).join('');
            docsDiv.innerHTML = '<h4 style="margin: 15px 0 10px 0;">Required Documents:</h4><div class="docs-list">' + docsHtml + '</div>';
        } else {
            docsDiv.innerHTML = '';
        }
    }
}

// Initialize if type is pre-selected
<?php if ($preselected_type_id): ?>
updateRequiredDocs();
<?php endif; ?>

document.getElementById('applicationForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    formData.append('document_types[]', 'Other');
    
    fetch('../handler/submit_application.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.href = data.redirect;
        } else {
            alert('Error: ' + (data.error || 'Submission failed'));
        }
    })
    .catch(error => {
        console.error('Submission error:', error);
        alert('Error submitting application: ' + error.message);
    });
});
</script>

</body>
</html>
