<?php
include '../../config/db.php';
include '../../config/app_config.php';

$posts_stmt = $conn->prepare("
SELECT *
FROM news_posts
ORDER BY created_at DESC
");
$posts_stmt->execute();
$posts = $posts_stmt->get_result();
$posts_stmt->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Municipality News</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="procurement.css">
</head>
<body>
<div class="page-container">
    <?php $active_page = 'news'; include 'sidebar.php'; ?>
    <main class="main-content">
        <div class="header">
            <h1>📰 Latest News & Announcements</h1>
            <p>Stay updated with the latest announcements, activities and events of the Municipality of Cervantes</p>
        </div>
        <div class="container">
            <div class="card">
                <?php if($posts->num_rows > 0): ?>
                <div class="news-grid">
                    <?php while($row = $posts->fetch_assoc()): ?>
                    <div class="news-card">
                        <?php if(!empty($row['image'])): ?>
                        <img src="<?= AppConfig::uploads('news/' . $row['image']) ?>" alt="News Image">
                        <?php endif; ?>
                        <div class="news-content">
                            <h3><?= htmlspecialchars($row['title']) ?></h3>
                            <p class="date"><?= date("F d, Y h:i A", strtotime($row['created_at'])) ?></p>
                            <p><?= substr(strip_tags($row['content']), 0, 120) ?>...</p>
                            <button class="file-link" onclick='openNews(<?= json_encode($row["title"]) ?>, <?= json_encode($row["content"]) ?>, <?= json_encode($row["image"]) ?>, <?= json_encode(date("F d, Y h:i A", strtotime($row["created_at"]))) ?>)'>View</button>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
                <?php else: ?>
                <div class="empty">No news has been posted yet.</div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>
<div id="newsModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <div id="modalGallery"></div>
        <h2 id="modalTitle"></h2>
        <p id="modalDate"></p>
        <hr>
        <div id="modalContent"></div>
    </div>
</div>
<script>
const BASE_URL = "<?= AppConfig::getBaseUrl() ?>";
</script>
<script src="news.js"></script>
</body>
</html>