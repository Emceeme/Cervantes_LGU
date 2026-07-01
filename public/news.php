<?php
include '../config/db.php';

$posts = $conn->query("
SELECT *
FROM news_posts
ORDER BY created_at DESC
");
?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<title>Municipality News</title>

<link rel="stylesheet" href="news.css">

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

</head>

<body>

<div class="blur blur1"></div>
<div class="blur blur2"></div>

<section class="hero">

    <h1>Latest News & Announcements</h1>

    <p>
        Stay updated with the latest announcements,
        activities and events of the Municipality of Cervantes.
    </p>

</section>

<section class="news-container">

<?php if($posts->num_rows > 0): ?>

<?php while($row = $posts->fetch_assoc()): ?>

<div class="news-card">

<?php

$image="";

if(!empty($row['image'])){

$image="../uploads/news/".$row['image'];

?>

<img src="<?= $image ?>">

<?php } ?>

<div class="news-content">

<h2>

<?= htmlspecialchars($row['title']) ?>

</h2>

<p class="date">

<?= date("F d, Y h:i A",strtotime($row['created_at'])) ?>

</p>

<p>

<?= substr(strip_tags($row['content']),0,120) ?>

...

</p>

<button

class="view-btn"

onclick='openNews(

<?= json_encode($row["title"]) ?>,

<?= json_encode($row["content"]) ?>,

<?= json_encode($row["image"]) ?>,

<?= json_encode(date("F d, Y h:i A",strtotime($row["created_at"]))) ?>

)'

>

View

</button>

</div>

</div>

<?php endwhile; ?>

<?php else: ?>

<div class="empty-state">

No news has been posted yet.

</div>

<?php endif; ?>

</section>

<!-- MODAL -->

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

<script src="news.js"></script>

</body>

</html>