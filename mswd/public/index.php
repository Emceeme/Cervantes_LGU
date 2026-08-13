<?php
require_once __DIR__ . '/../../config/security.php';
require_once __DIR__ . '/../../config/db.php';

setSecurityHeaders();

$csrf_token = generateCsrfToken();

// Fetch all active assistance types
$assistance_types_stmt = $conn->prepare("
    SELECT id, name, description, eligibility_requirements, required_documents 
    FROM assistance_types 
    WHERE is_active = 1 
    ORDER BY name ASC
");

if ($assistance_types_stmt) {
    $assistance_types_stmt->execute();
    $assistance_types = $assistance_types_stmt->get_result();
} else {
    $assistance_types = false;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MSWD Portal - Municipal Social Welfare and Development</title>
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
    <div class="hero">
        <h2>Social Welfare Services</h2>
        <p>We provide various assistance programs to support our community members in need. Apply for assistance and track your application online.</p>
        <div class="hero-buttons">
            <a href="apply.php" class="btn btn-primary">
                <i class="fas fa-file-alt"></i> Apply for Assistance
            </a>
            <a href="track.php" class="btn btn-secondary">
                <i class="fas fa-search"></i> Track Application
            </a>
        </div>
    </div>

    <div class="services">
        <div class="section-title">
            <h3>Available Assistance Programs</h3>
            <p>Choose from our various social welfare assistance programs</p>
        </div>

        <?php if ($assistance_types && $assistance_types->num_rows > 0): ?>
            <div class="services-grid">
                <?php while ($type = $assistance_types->fetch_assoc()): ?>
                    <div class="service-card">
                        <div class="service-icon">
                            <i class="fas fa-hand-holding-heart"></i>
                        </div>
                        <h4><?= htmlspecialchars($type['name']) ?></h4>
                        <p><?= htmlspecialchars($type['description']) ?></p>
                        <div class="eligibility">
                            <strong>Eligibility:</strong>
                            <?= htmlspecialchars(substr($type['eligibility_requirements'] ?? 'Contact MSWD office for eligibility details', 0, 100)) ?>...
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="error-message">
                <i class="fas fa-exclamation-triangle"></i>
                <h3>Database Migration Required</h3>
                <p>The MSWD database tables have not been created yet. Please run the migration script.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
