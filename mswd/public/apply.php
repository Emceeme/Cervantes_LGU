<?php
session_start();
require_once __DIR__ . '/../../config/security.php';
// require_once __DIR__ . '/../../config/db.php'; // TEMPORARILY DISABLED

setSecurityHeaders();

$csrf_token = generateCsrfToken();

// Fetch all active assistance types
$assistance_types_stmt = $conn->prepare("
    SELECT id, name, description, required_documents 
    FROM assistance_types 
    WHERE is_active = 1 
    ORDER BY name ASC
");

if ($assistance_types_stmt) {
    $assistance_types_stmt->execute();
    
    // Support both PDO and MySQLi
    if ($conn instanceof PDO) {
        $assistance_types = $assistance_types_stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $assistance_types = $assistance_types_stmt->get_result();
    }
} else {
    $assistance_types = false;
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
        </nav>
    </div>
</header>

<div class="container">
    <div class="form-container">
        <div class="form-header">
            <h2>Application Form</h2>
            <p>Fill out the form below to apply for assistance</p>
        </div>

        <form action="../handler/submit_application.php" method="POST" enctype="multipart/form-data" id="applicationForm">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
            
            <!-- Progress Indicator -->
            <div class="progress-indicator">
                <div class="step-indicator active" id="step1-indicator">
                    <div class="step-number">1</div>
                    <div class="step-label">Assistance Type</div>
                </div>
                <div class="step-indicator" id="step2-indicator">
                    <div class="step-number">2</div>
                    <div class="step-label">Personal Info</div>
                </div>
                <div class="step-indicator" id="step3-indicator">
                    <div class="step-number">3</div>
                    <div class="step-label">Documents</div>
                </div>
                <div class="step-indicator" id="step4-indicator">
                    <div class="step-number">4</div>
                    <div class="step-label">Review</div>
                </div>
            </div>
            
            <!-- Step 1: Select Assistance Type -->
            <div class="form-step active" id="step1">
                <h3>Select Assistance Type</h3>
                
                <div class="assistance-types">
                    <?php if ($assistance_types && (is_array($assistance_types) || ($assistance_types instanceof mysqli_result && $assistance_types->num_rows > 0))): ?>
                        <?php 
                        if ($conn instanceof PDO) {
                            foreach ($assistance_types as $type): 
                        ?>
                        <label class="assistance-card">
                            <input type="radio" name="assistance_type_id" value="<?= $type['id'] ?>" required onchange="selectAssistanceType(this, <?= htmlspecialchars(json_encode($type)) ?>)">
                            <div class="card-icon"><i class="fas fa-hand-holding-heart"></i></div>
                            <h4><?= htmlspecialchars($type['name']) ?></h4>
                            <p><?= htmlspecialchars($type['description']) ?></p>
                        </label>
                        <?php 
                            endforeach;
                        } else {
                            while ($type = $assistance_types->fetch_assoc()):
                        ?>
                        <label class="assistance-card">
                            <input type="radio" name="assistance_type_id" value="<?= $type['id'] ?>" required onchange="selectAssistanceType(this, <?= htmlspecialchars(json_encode($type)) ?>)">
                            <div class="card-icon"><i class="fas fa-hand-holding-heart"></i></div>
                            <h4><?= htmlspecialchars($type['name']) ?></h4>
                            <p><?= htmlspecialchars($type['description']) ?></p>
                        </label>
                        <?php 
                            endwhile;
                        }
                        ?>
                    <?php else: ?>
                        <div class="error-message">
                            <i class="fas fa-exclamation-triangle"></i>
                            <h3>Database Migration Required</h3>
                            <p>The MSWD database tables have not been created yet. Please run the migration script.</p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div id="selected-assistance-info" class="info-box" style="display: none;">
                    <h4>Required Documents:</h4>
                    <ul id="required-documents-list"></ul>
                </div>
                
                <button type="button" class="btn btn-primary" onclick="nextStep(2)">
                    Next <i class="fas fa-arrow-right"></i>
                </button>
            </div>
            
            <!-- Step 2: Personal Information -->
            <div class="form-step" id="step2">
                <h3>Personal Information</h3>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label>First Name *</label>
                        <input type="text" name="first_name" required placeholder="Juan">
                    </div>
                    
                    <div class="form-group">
                        <label>Middle Name</label>
                        <input type="text" name="middle_name" placeholder="Optional">
                    </div>
                    
                    <div class="form-group">
                        <label>Last Name *</label>
                        <input type="text" name="last_name" required placeholder="Dela Cruz">
                    </div>
                    
                    <div class="form-group">
                        <label>Birthdate *</label>
                        <input type="date" name="birthdate" required>
                    </div>
                    
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
                    
                    <div class="form-group">
                        <label>Contact Number *</label>
                        <input type="tel" name="contact_number" required placeholder="09123456789">
                    </div>
                    
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" placeholder="juan@example.com">
                    </div>
                    
                    <div class="form-group">
                        <label>Barangay *</label>
                        <select name="barangay" required>
                            <option value="">Select Barangay</option>
                            <option value="Barangay 1">Barangay 1</option>
                            <option value="Barangay 2">Barangay 2</option>
                            <option value="Barangay 3">Barangay 3</option>
                            <option value="Barangay 4">Barangay 4</option>
                            <option value="Barangay 5">Barangay 5</option>
                            <option value="Barangay 6">Barangay 6</option>
                            <option value="Barangay 7">Barangay 7</option>
                            <option value="Barangay 8">Barangay 8</option>
                            <option value="Barangay 9">Barangay 9</option>
                            <option value="Barangay 10">Barangay 10</option>
                        </select>
                    </div>
                    
                    <div class="form-group full">
                        <label>Street Address *</label>
                        <input type="text" name="street_address" required placeholder="House number, street name">
                    </div>
                </div>
                
                <div class="form-buttons">
                    <button type="button" class="btn btn-secondary" onclick="prevStep(1)">
                        <i class="fas fa-arrow-left"></i> Previous
                    </button>
                    <button type="button" class="btn btn-primary" onclick="nextStep(3)">
                        Next <i class="fas fa-arrow-right"></i>
                    </button>
                </div>
            </div>
            
            <!-- Step 3: Upload Documents -->
            <div class="form-step" id="step3">
                <h3>Upload Documents</h3>
                <p class="subtitle">Upload required documents (PDF, PNG, JPG only, max 5MB each)</p>
                
                <div class="file-upload">
                    <input type="file" id="fileInput" name="documents[]" multiple accept=".pdf,.png,.jpg,.jpeg" onchange="handleFileSelect(event)">
                    <div class="upload-area" onclick="document.getElementById('fileInput').click()">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <p>Click to upload or drag and drop files here</p>
                        <span class="upload-btn">Choose Files</span>
                    </div>
                </div>
                
                <div class="file-list" id="fileList"></div>
                
                <div class="form-buttons">
                    <button type="button" class="btn btn-secondary" onclick="prevStep(2)">
                        <i class="fas fa-arrow-left"></i> Previous
                    </button>
                    <button type="button" class="btn btn-primary" onclick="nextStep(4)">
                        Next <i class="fas fa-arrow-right"></i>
                    </button>
                </div>
            </div>
            
            <!-- Step 4: Review -->
            <div class="form-step" id="step4">
                <h3>Review Your Application</h3>
                
                <div id="review-content" class="review-box">
                    <!-- Review content will be populated by JavaScript -->
                </div>
                
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="certification" required>
                        <span>I certify that all information provided is true and correct. I understand that false statements may result in disqualification.</span>
                    </label>
                </div>
                
                <div class="form-buttons">
                    <button type="button" class="btn btn-secondary" onclick="prevStep(3)">
                        <i class="fas fa-arrow-left"></i> Previous
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane"></i> Submit Application
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
let currentStep = 1;
let selectedAssistance = null;
let uploadedFiles = [];

function selectAssistanceType(radio, assistanceData) {
    document.querySelectorAll('.assistance-card').forEach(card => {
        card.classList.remove('selected');
    });
    radio.closest('.assistance-card').classList.add('selected');
    selectedAssistance = assistanceData;
    
    const infoDiv = document.getElementById('selected-assistance-info');
    const docsList = document.getElementById('required-documents-list');
    
    if (assistanceData.required_documents) {
        const docs = JSON.parse(assistanceData.required_documents);
        docsList.innerHTML = docs.map(doc => `<li>${doc}</li>`).join('');
        infoDiv.style.display = 'block';
    }
}

function nextStep(step) {
    if (step === 2 && !document.querySelector('input[name="assistance_type_id"]:checked')) {
        alert('Please select an assistance type');
        return;
    }

    if (step === 3) {
        const step2Inputs = document.querySelectorAll('#step2 input[required], #step2 select[required]');
        let step2Valid = true;
        step2Inputs.forEach(input => {
            if (!input.value.trim()) {
                input.style.borderColor = '#ef4444';
                step2Valid = false;
            } else {
                input.style.borderColor = 'rgba(255, 255, 255, 0.2)';
            }
        });
        
        if (!step2Valid) {
            alert('Please fill out all required fields in Step 2.');
            return;
        }
    }

    if (step === 4) {
        populateReview();
    }

    document.getElementById(`step${currentStep}`).classList.remove('active');
    document.getElementById(`step${currentStep}-indicator`).classList.remove('active');
    document.getElementById(`step${currentStep}-indicator`).classList.add('completed');

    currentStep = step;

    document.getElementById(`step${currentStep}`).classList.add('active');
    document.getElementById(`step${currentStep}-indicator`).classList.add('active');
}

function prevStep(step) {
    document.getElementById(`step${currentStep}`).classList.remove('active');
    document.getElementById(`step${currentStep}-indicator`).classList.remove('active');
    
    currentStep = step;
    
    document.getElementById(`step${currentStep}`).classList.add('active');
    document.getElementById(`step${currentStep}-indicator`).classList.add('active');
    document.getElementById(`step${currentStep}-indicator`).classList.remove('completed');
}

function handleFileSelect(event) {
    const files = event.target.files;
    
    for (let file of files) {
        if (file.size > 5 * 1024 * 1024) {
            alert(`File ${file.name} exceeds 5MB limit`);
            continue;
        }
        
        if (!uploadedFiles.some(f => f.name === file.name)) {
            uploadedFiles.push(file);
            addFileToList(file);
        }
    }
    
    syncFileInput();
}

function addFileToList(file) {
    const fileList = document.getElementById('fileList');
    const fileItem = document.createElement('div');
    fileItem.className = 'file-item';
    fileItem.innerHTML = `
        <div class="file-name">
            <i class="fas fa-file"></i>
            <span>${file.name}</span>
            <small>(${(file.size / 1024).toFixed(1)} KB)</small>
        </div>
        <span class="remove-file" onclick="removeFile(this, '${file.name}')">
            <i class="fas fa-times"></i>
        </span>
    `;
    fileList.appendChild(fileItem);
}

function removeFile(element, fileName) {
    uploadedFiles = uploadedFiles.filter(f => f.name !== fileName);
    element.closest('.file-item').remove();
    syncFileInput();
}

function syncFileInput() {
    const dt = new DataTransfer();
    uploadedFiles.forEach(file => dt.items.add(file));
    document.getElementById('fileInput').files = dt.files;
}

function populateReview() {
    const reviewContent = document.getElementById('review-content');
    
    reviewContent.innerHTML = `
        <div class="review-section">
            <h4>Assistance Type</h4>
            <p>${selectedAssistance ? selectedAssistance.name : 'Not selected'}</p>
        </div>
        
        <div class="review-section">
            <h4>Personal Information</h4>
            <p><strong>Name:</strong> ${document.querySelector('input[name="first_name"]').value} ${document.querySelector('input[name="middle_name"]').value} ${document.querySelector('input[name="last_name"]').value}</p>
            <p><strong>Birthdate:</strong> ${document.querySelector('input[name="birthdate"]').value}</p>
            <p><strong>Gender:</strong> ${document.querySelector('select[name="gender"]').value}</p>
            <p><strong>Civil Status:</strong> ${document.querySelector('select[name="civil_status"]').value}</p>
            <p><strong>Contact:</strong> ${document.querySelector('input[name="contact_number"]').value}</p>
            <p><strong>Email:</strong> ${document.querySelector('input[name="email"]').value}</p>
            <p><strong>Address:</strong> ${document.querySelector('select[name="barangay"]').value}, ${document.querySelector('input[name="street_address"]').value}</p>
        </div>
        
        <div class="review-section">
            <h4>Uploaded Documents</h4>
            <p>${uploadedFiles.length} file(s) uploaded</p>
        </div>
    `;
}

document.getElementById('applicationForm').addEventListener('submit', function(e) {
    syncFileInput();
    
    const requiredInputs = this.querySelectorAll('input[required], select[required]');
    let valid = true;
    
    requiredInputs.forEach(input => {
        if (input.type === 'checkbox') {
            if (!input.checked) {
                valid = false;
            }
        } else if (!input.value.trim()) {
            valid = false;
            input.style.borderColor = '#ef4444';
        } else {
            input.style.borderColor = 'rgba(255, 255, 255, 0.2)';
        }
    });
    
    if (!valid) {
        e.preventDefault();
        alert('Please fill in all required fields and check the certification box');
        return false;
    }
    
    return true;
});
</script>

</body>
</html>
