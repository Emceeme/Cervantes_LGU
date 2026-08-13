<?php
require_once __DIR__ . '/../../config/security.php';
require_once __DIR__ . '/../../config/db.php';

setSecurityHeaders();

$tracking_number = $_GET['tracking'] ?? '';

if (empty($tracking_number)) {
    header("Location: index.php");
    exit();
}

// Fetch application details
$app_stmt = $conn->prepare("
    SELECT a.*, at.name as assistance_type_name
    FROM applications a
    JOIN assistance_types at ON a.assistance_type_id = at.id
    WHERE a.tracking_number = ?
");

if ($app_stmt) {
    $app_stmt->bind_param("s", $tracking_number);
    $app_stmt->execute();
    $application = $app_stmt->get_result()->fetch_assoc();
} else {
    $application = false;
}

if (!$application) {
    header("Location: index.php?error=application_not_found");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Application Submitted - MSWD Portal</title>
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
    <div class="confirmation-page">
        <div class="success-icon">
            <i class="fas fa-check-circle"></i>
        </div>
        
        <h1>Application Submitted Successfully!</h1>
        <p class="subtitle">Your application has been received and is now being processed by our social welfare team.</p>
        
        <div class="tracking-number-box">
            <h3>Your Tracking Number</h3>
            <div class="number"><?= htmlspecialchars($tracking_number) ?></div>
            <p class="note">Please save this number to track your application status</p>
        </div>
        
        <div class="info-box">
            <h4>Application Details</h4>
            <div class="detail-row">
                <span class="label">Assistance Type:</span>
                <span class="value"><?= htmlspecialchars($application['assistance_type_name']) ?></span>
            </div>
            <div class="detail-row">
                <span class="label">Applicant Name:</span>
                <span class="value"><?= htmlspecialchars($application['first_name'] . ' ' . $application['last_name']) ?></span>
            </div>
            <div class="detail-row">
                <span class="label">Submitted On:</span>
                <span class="value"><?= date('F j, Y, g:i a', strtotime($application['submitted_at'])) ?></span>
            </div>
            <div class="detail-row">
                <span class="label">Status:</span>
                <span class="value status-pending">Pending Review</span>
            </div>
        </div>
        
        <div class="action-buttons">
            <a href="track.php?tracking=<?= urlencode($tracking_number) ?>" class="btn btn-primary">
                <i class="fas fa-search"></i> Track Status
            </a>
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-home"></i> Back to Home
            </a>
            <button onclick="window.print()" class="btn btn-outline">
                <i class="fas fa-print"></i> Print
            </button>
        </div>
    </div>
</div>

</body>
</html>
