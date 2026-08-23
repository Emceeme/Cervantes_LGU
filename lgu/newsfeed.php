<?php
session_start();
require_once '../config/security.php';
include '../config/db.php';

// Set security headers
setSecurityHeaders();

// SECURITY GUARD: Restrict access to Mayor's Office, LGU departments & Super Admins only
$department = html_entity_decode($_SESSION['department'] ?? '', ENT_QUOTES);
if (!isset($_SESSION['role']) || ($department !== "Mayor's Office" && $department !== 'Mayor Office' && $department !== 'LGU' && $_SESSION['role'] !== 'SUPER_ADMIN')) {
    logSecurityEvent('unauthorized_access', $_SESSION['id'] ?? null, ['endpoint' => 'newsfeed', 'department' => $department]);
    header('Location: /login.php?unauthorized=1');
    exit();
}

$user_id = $_SESSION['id'];

$posts_stmt = $conn->prepare("
    SELECT *
    FROM news_posts
    WHERE user_id = ?
    ORDER BY id DESC
");

if ($conn instanceof PDO) {
    // PostgreSQL/PDO
    $posts_stmt->execute([$user_id]);
    $posts = $posts_stmt->fetchAll();
} else {
    // MySQLi
    $posts_stmt->bind_param("i", $user_id);
    $posts_stmt->execute();
    $posts = $posts_stmt->get_result();
    $posts_stmt->close();
}

// Generate CSRF token
$csrf_token = generateCsrfToken();
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>News Feed Management</title>
<link rel="stylesheet" href="newsfeed.css">
</head>

<body>

<div class="container">

    <aside class="sidebar">
        <div class="logo">LGU <span>Portal</span></div>
        <nav class="sidebar-nav">
            <a href="dashboard.php">Dashboard</a>
            <a href="applicants.php">Applicants</a>
            <a href="procurement.php">Procurement</a>
            <a href="newsfeed.php" class="active">News Feed</a>
            <a href="scholarship_applications.php">Scholarship Applications</a>
            <a href="settings.php">Settings</a>
            <a href="../logout.php">Logout</a>
        </nav>
    </aside>

    <main class="main-content">

        <h2>News Feed Management</h2>
        <p>Manage LGU announcements and updates</p>

        <button class="add-btn"
        onclick="document.getElementById('modal').style.display='flex'">
            +
        </button>

        <?php if($conn instanceof PDO): ?>
            <?php foreach($posts as $row): ?>

        <div class="card">

<?php
$imagePath = __DIR__ . "/uploads/news/" . $row['image'];
$imageUrl = "uploads/news/" . $row['image'];

if (!empty($row['image']) && file_exists($imagePath)) {
?>
    <img src="<?= htmlspecialchars($imageUrl) ?>" class="news-image">
<?php
} else {
?>
    <div style="
        background:#FEF3C7;
        color:#92400E;
        padding:15px;
        margin:20px;
        border-radius:8px;
        border:1px solid #FCD34D;
    ">
        <strong>⚠ Image not found</strong><br><br>
        <strong>Database Value:</strong> <?= htmlspecialchars($row['image']) ?><br>
        <strong>Looking For:</strong> <?= htmlspecialchars($imagePath) ?><br>
        <strong>Uploads Dir Exists:</strong> <?= is_dir(__DIR__ . '/uploads/news') ? 'Yes' : 'No' ?>
    </div>
<?php
}
?>
            <div class="card-content">
                <h3><?= htmlspecialchars($row['title']) ?></h3>
                <p><?= nl2br(htmlspecialchars($row['content'])) ?></p>
                <small><?= $row['created_at'] ?></small>
                <a
                class="delete-btn"
                href="handler/delete_news.php?id=<?= $row['id'] ?>"
                onclick="return confirm('Delete post?')">
                Delete
                </a>
            </div>
        </div>

            <?php endforeach; ?>
        <?php else: ?>
            <?php while($row = $posts->fetch_assoc()): ?>

        <div class="card">

<?php
$imagePath = __DIR__ . "/uploads/news/" . $row['image'];
$imageUrl = "uploads/news/" . $row['image'];

if (!empty($row['image']) && file_exists($imagePath)) {
?>
    <img src="<?= htmlspecialchars($imageUrl) ?>" class="news-image">
<?php
} else {
?>
    <div style="
        background:#FEF3C7;
        color:#92400E;
        padding:15px;
        margin:20px;
        border-radius:8px;
        border:1px solid #FCD34D;
    ">
        <strong>⚠ Image not found</strong><br><br>
        <strong>Database Value:</strong> <?= htmlspecialchars($row['image']) ?><br>
        <strong>Looking For:</strong> <?= htmlspecialchars($imagePath) ?><br>
        <strong>Uploads Dir Exists:</strong> <?= is_dir(__DIR__ . '/uploads/news') ? 'Yes' : 'No' ?>
    </div>
<?php
}
?>
            <div class="card-content">
                <h3><?= htmlspecialchars($row['title']) ?></h3>
                <p><?= nl2br(htmlspecialchars($row['content'])) ?></p>
                <small><?= $row['created_at'] ?></small>
                <a
                class="delete-btn"
                href="handler/delete_news.php?id=<?= $row['id'] ?>"
                onclick="return confirm('Delete post?')">
                Delete
                </a>
            </div>
        </div>

        <?php endwhile; ?>
        <?php endif; ?>

    </main>

</div>

<!-- MODAL -->

<div id="modal" class="modal">

    <div class="modal-content">
        <div class="modal-header">
            <h2>Create News Post</h2>
            <span class="close" onclick="document.getElementById('modal').style.display='none'">×</span>
        </div>

        <form
        action="handler/post_news.php"
        method="POST"
        enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

            <div class="form-group">
                <label>Title</label>
                <input type="text" name="title" placeholder="Enter news title" required>
            </div>

            <div class="form-group">
                <label>Content</label>
                <textarea name="content" rows="8" placeholder="Enter news content" required></textarea>
            </div>

            <div class="form-group">
                <label>Image</label>
                <input type="file" name="image">
            </div>

            <button class="btn btn-primary" type="submit">
                Publish News
            </button>

        </form>

    </div>

</div>

</body>
</html>