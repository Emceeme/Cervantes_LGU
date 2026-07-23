<?php
session_start();
include '../config/db.php';

if (!isset($_SESSION['id'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['id'];

$stmt = $conn->prepare("
    SELECT *
    FROM news_posts
    WHERE user_id = ?
    ORDER BY id DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$posts = $stmt->get_result();

$notice = "";
if (isset($_GET['success'])) {
    $notice = "News post published successfully.";
} elseif (isset($_GET['deleted'])) {
    $notice = "News post deleted.";
} elseif (isset($_GET['error'])) {
    $errors = [
        'missing' => "Please fill in the title and content.",
        'upload'  => "Image upload failed. Please try again.",
        'save'    => "Could not save the news post. Please try again.",
        'delete'  => "Could not delete the news post. Please try again.",
        'invalid' => "Invalid request.",
    ];
    $notice = $errors[$_GET['error']] ?? "Something went wrong.";
}
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>News Feed Management</title>

<link rel="stylesheet" href="newsfeed.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>

<body>

<div class="container">

    <aside class="sidebar">

        <div class="logo">🏛️</div>

        <a href="dashboard.php">Dashboard</a>
        <a href="applicants.php">Applicants</a>
        <a href="procurement.php">Procurement</a>
        <a href="#" class="active">News Feed</a>
        <a href="../logout.php">Logout</a>

    </aside>

    <main class="main-content">

        <h2>News Feed Management</h2>

        <?php if($notice): ?>
        <div style="padding:12px 16px;margin-bottom:15px;border-radius:8px;background:#eef;border:1px solid #ccd;color:#334;">
            <?= htmlspecialchars($notice) ?>
        </div>
        <?php endif; ?>

        <button class="add-btn"
        onclick="document.getElementById('modal').style.display='flex'">
            +
        </button>

        <?php while($row = $posts->fetch_assoc()): ?>

        <div class="card">

<?php
$imagePath = "../uploads/news/" . $row['image'];

if (!empty($row['image']) && file_exists($imagePath)) {
?>
    <img src="<?= htmlspecialchars($imagePath) ?>" class="news-image">
<?php
} else {
?>
    <div style="
        background:#fff3cd;
        color:#856404;
        padding:10px;
        border-radius:8px;
        margin-bottom:15px;
        border:1px solid #ffeeba;
    ">
        <strong>⚠ Image not found</strong><br><br>

        <strong>Database Value:</strong><br>
        <?= htmlspecialchars($row['image']) ?><br><br>

        <strong>Looking For:</strong><br>
        <?= htmlspecialchars($imagePath) ?><br><br>

        <strong>file_exists():</strong>
        <?= file_exists($imagePath) ? "TRUE" : "FALSE"; ?>
    </div>
<?php
}
?>
            <h3><?= htmlspecialchars($row['title']) ?></h3>

            <p><?= nl2br(htmlspecialchars($row['content'])) ?></p>

            <small><?= $row['created_at'] ?></small>

            <br><br>

            <a
            class="delete-btn"
            href="../handler/delete_news.php?id=<?= $row['id'] ?>"
            onclick="return confirm('Delete post?')">
            Delete
            </a>

        </div>

        <?php endwhile; ?>

    </main>

</div>

<!-- MODAL -->

<div id="modal" class="modal">

    <div class="modal-content">

        <span
        class="close"
        onclick="document.getElementById('modal').style.display='none'">
        ×
        </span>

        <h2>Create News</h2>

        <form
        action="../handler/post_news.php"
        method="POST"
        enctype="multipart/form-data">

            <input
            type="text"
            name="title"
            placeholder="Title"
            required>

            <textarea
            name="content"
            rows="8"
            placeholder="News Content"
            required></textarea>

            <input
            type="file"
            name="image">

            <button type="submit">
                Publish News
            </button>

        </form>

    </div>

</div>

</body>
</html>