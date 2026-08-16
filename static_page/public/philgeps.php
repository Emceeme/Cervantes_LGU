<?php
include '../../config/db.php';

$category = 'philgeps';
$category_name = 'PhilGEPS';

$posts_stmt = $conn->prepare("
    SELECT *
    FROM procurement_posts
    WHERE category = ?
    ORDER BY custom_date DESC, created_at DESC
");

if ($conn instanceof PDO) {
    // PostgreSQL/PDO
    $posts_stmt->execute([$category]);
    $posts = $posts_stmt->fetchAll();
} else {
    // MySQLi
    $posts_stmt->bind_param("s", $category);
    $posts_stmt->execute();
    $posts = $posts_stmt->get_result();
    $posts_stmt->close();
}

if(!$posts){
    if ($conn instanceof PDO) {
        die("Query Error: " . implode(", ", $conn->errorInfo()));
    } else {
        die("Query Error: " . $conn->error);
    }
}

$success_message = isset($_GET['status']) && $_GET['status'] === 'success';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>PhilGEPS</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="procurement.css">
</head>
<body>
<div class="page-container">
    <?php $active_page = 'philgeps'; include 'sidebar.php'; ?>
    <main class="main-content">
        <div class="header">
            <h1>🏛️ <?= $category_name ?></h1>
            <p>Official <?= $category_name ?> announcements</p>
        </div>
        <div class="container">
            <div class="card">
                <?php if($success_message): ?>
                <div class="success-message">✅ Document uploaded successfully!</div>
                <?php endif; ?>
                <h3 style="margin-bottom: 15px;"><?= $category_name ?> Posts</h3>
                <?php if($conn instanceof PDO): ?>
                    <?php if(count($posts) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Date</th>
                            <th>File</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($posts as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['title']) ?></td>
                            <td><?= $row['custom_date'] ? date('M d, Y', strtotime($row['custom_date'])) : date('M d, Y', strtotime($row['created_at'])) ?></td>
                            <td>
                                <a class="file-link" href="download_procurement.php?id=<?= $row['id'] ?>" target="_blank">View / Download</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                    <?php else: ?>
                <div class="empty">No <?= strtolower($category_name) ?> posts available.</div>
                    <?php endif; ?>
                <?php else: ?>
                    <?php if($posts->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Date</th>
                            <th>File</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = $posts->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['title']) ?></td>
                            <td><?= $row['custom_date'] ? date('M d, Y', strtotime($row['custom_date'])) : date('M d, Y', strtotime($row['created_at'])) ?></td>
                            <td>
                                <a class="file-link" href="download_procurement.php?id=<?= $row['id'] ?>" target="_blank">View / Download</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                    <?php else: ?>
                <div class="empty">No <?= strtolower($category_name) ?> posts available.</div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>
</body>
</html>
