<?php
session_start();
require_once '../config/security.php';
require_once '../config/app_config.php';
include '../config/db.php';

// Set security headers
setSecurityHeaders();

// SECURITY GUARD: Restrict access to Mayor's Office, LGU departments & Super Admins only
$department = html_entity_decode($_SESSION['department'] ?? '', ENT_QUOTES);
if (!isset($_SESSION['role']) || ($department !== "Mayor's Office" && $department !== 'Mayor Office' && $department !== 'LGU' && $_SESSION['role'] !== 'SUPER_ADMIN')) {
    logSecurityEvent('unauthorized_access', $_SESSION['id'] ?? null, ['endpoint' => 'procurement', 'department' => $department]);
    header('Location: /login.php?unauthorized=1');
    exit();
}

// Get search parameters
$search_query = $_GET['search'] ?? '';
$search_date = $_GET['date'] ?? '';

// Build query with search conditions
$sql = "SELECT * FROM procurement_posts";
$params = [];
$types = "";

if (!empty($search_query)) {
    $sql .= " WHERE (title LIKE ? OR original_file_name LIKE ?)";
    $search_term = "%$search_query%";
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= "ss";
}

if (!empty($search_date)) {
    if (empty($search_query)) {
        $sql .= " WHERE custom_date = ?";
    } else {
        $sql .= " AND custom_date = ?";
    }
    $params[] = $search_date;
    $types .= "s";
}

$sql .= " ORDER BY id DESC";

$posts_stmt = $conn->prepare($sql);

if ($conn instanceof PDO) {
    // PostgreSQL/PDO
    if (!empty($params)) {
        $posts_stmt->execute($params);
    } else {
        $posts_stmt->execute();
    }
    $posts = $posts_stmt->fetchAll();
    // Empty result is not an error for PDO
} else {
    // MySQLi
    if (!empty($params)) {
        $posts_stmt->bind_param($types, ...$params);
    }
    $posts_stmt->execute();
    $posts = $posts_stmt->get_result();
    
    if(!$posts){
        die("Query Error: " . $conn->error);
    }
    $posts_stmt->close();
}

// Generate CSRF token
$csrf_token = generateCsrfToken();
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Procurement Management</title>
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
            <a href="procurement.php" class="active">Procurement</a>
            <a href="newsfeed.php">News Feed</a>
            <a href="scholarship_applications.php">Scholarship Applications</a>
            <a href="settings.php">Settings</a>
            <a href="../logout.php">Logout</a>
        </nav>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="main-content">

        <div class="top-bar">
            <h2>Procurement Management</h2>
            <p>Manage bidding and procurement processes</p>
        </div>

        <button class="add-btn"
        onclick="document.getElementById('modal').style.display='flex'">
            + Add Procurement
        </button>

        <!-- Search Box -->
        <form method="GET" action="procurement.php" style="display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap;">
            <input type="text" name="search" placeholder="Search by title or filename..." value="<?= htmlspecialchars($search_query) ?>" 
                   style="padding: 10px 15px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; flex: 1; min-width: 200px;">
            <input type="date" name="date" value="<?= htmlspecialchars($search_date) ?>" 
                   style="padding: 10px 15px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px;">
            <button type="submit" 
                    style="padding: 10px 20px; background: #3b82f6; color: white; border: none; border-radius: 8px; cursor: pointer;">
                Search
            </button>
            <?php if(!empty($search_query) || !empty($search_date)): ?>
                <a href="procurement.php" 
                   style="padding: 10px 20px; background: #64748b; color: white; text-decoration: none; border-radius: 8px;">
                    Clear
                </a>
            <?php endif; ?>
        </form>

        <section class="card">
            <div class="card-header">
                <h3>Procurement List</h3>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Category</th>
                        <th>File</th>
                        <th>Award Winner</th>
                        <th>Views</th>
                        <th>Date</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if($conn instanceof PDO): ?>
                        <?php foreach($posts as $row): ?>

                    <tr>
                        <td><?= htmlspecialchars($row['title']) ?></td>
                        <td><?= htmlspecialchars($row['category'] ?? 'N/A') ?></td>
                        <td>
                            <a href="<?= AppConfig::procurementUploads($row['file_path']) ?>" target="_blank">
                                View File
                            </a>
                        </td>

                        <td>
                            <?= $row['award_winner'] ? htmlspecialchars($row['award_winner']) : '-' ?>
                        </td>

                        <td>
                            <span class="view-count"><?= number_format($row['view_count'] ?? 0) ?></span>
                        </td>

                        <td><?= $row['created_at'] ?></td>

                        <td>
                            <a href="handler/delete_procurement.php?id=<?= $row['id'] ?>" 
                               class="delete-btn"
                               onclick="return confirm('Are you sure you want to delete this procurement post?')">
                                Delete
                            </a>
                        </td>
                    </tr>

                        <?php endforeach; ?>
                    <?php else: ?>
                        <?php while($row=$posts->fetch_assoc()): ?>

                    <tr>
                        <td><?= htmlspecialchars($row['title']) ?></td>
                        <td><?= htmlspecialchars($row['category'] ?? 'N/A') ?></td>
                        <td>
                            <a href="<?= AppConfig::procurementUploads($row['file_path']) ?>" target="_blank">
                                View File
                            </a>
                        </td>

                        <td>
                            <?= $row['award_winner'] ? htmlspecialchars($row['award_winner']) : '-' ?>
                        </td>

                        <td>
                            <span class="view-count"><?= number_format($row['view_count'] ?? 0) ?></span>
                        </td>

                        <td><?= $row['created_at'] ?></td>

                        <td>
                            <a href="handler/delete_procurement.php?id=<?= $row['id'] ?>" 
                               class="delete-btn"
                               onclick="return confirm('Are you sure you want to delete this procurement post?')">
                                Delete
                            </a>
                        </td>
                    </tr>

                        <?php endwhile; ?>
                    <?php endif; ?>
                </tbody>

            </table>

        </section>

    </main>

</div>

<!-- ADD MODAL -->
<div id="modal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Add Procurement</h3>
            <span class="close" onclick="document.getElementById('modal').style.display='none'">×</span>
        </div>

        <form action="handler/upload_procurement.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

            <div class="form-group">
                <label>Title</label>
                <input type="text" name="title" placeholder="Enter procurement title" required>
            </div>

            <div class="form-group">
                <label>Category</label>
                <select name="category" required>
                    <option value="">Select Category</option>
                    <option value="philgeps">PhilGEPS</option>
                    <option value="bids_awards">Bids and Awards</option>
                    <option value="invitation_to_bid">Invitation to Bid</option>
                    <option value="bid_bulletin">Bid Bulletin</option>
                    <option value="notice_of_award">Notice of Award</option>
                    <option value="notice_to_proceed">Notice to Proceed</option>
                </select>
            </div>

            <div class="form-group">
                <label>Posting / Notice Date</label>
                <input type="date" name="custom_date" value="<?= date('Y-m-d') ?>">
            </div>

            <div class="form-group">
                <label>Status</label>
                <select name="status">
                    <option value="OPEN">OPEN</option>
                    <option value="CLOSED">CLOSED</option>
                </select>
            </div>

            <div class="form-group">
                <label>Document File (PDF, DOC, DOCX only - Max 10MB)</label>
                <input type="file" name="document_file" accept=".pdf,.doc,.docx" required>
            </div>

            <button class="btn btn-primary" type="submit">Upload</button>

        </form>

    </div>
</div>

<style>
.view-count {
    background: #e0f2fe;
    color: #0369a1;
    padding: 4px 8px;
    border-radius: 4px;
    font-weight: 600;
    font-size: 12px;
}
</style>

<script>
    window.onclick = function(event){
        const modal = document.getElementById("modal");
        if(event.target == modal){
            modal.style.display = "none";
        }
    }
</script>
</html>