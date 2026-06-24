<?php
include '../config/db.php';

$posts = $conn->query("
    SELECT *
    FROM procurement_posts
    ORDER BY created_at DESC
");

if(!$posts){
    die("Query Error: " . $conn->error);
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Public Procurement Notices</title>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<style>
body{
    margin:0;
    font-family:Poppins;
    background:linear-gradient(135deg,#0f172a,#1e293b,#2563eb);
    color:white;
}

/* HEADER */
.header{
    padding:30px;
    text-align:center;
}

.header h1{
    margin:0;
    font-size:28px;
}

/* CONTAINER */
.container{
    max-width:1100px;
    margin:auto;
    padding:20px;
}

/* CARD */
.card{
    background:rgba(255,255,255,0.12);
    backdrop-filter:blur(10px);
    border-radius:15px;
    padding:20px;
}

/* TABLE */
table{
    width:100%;
    border-collapse:collapse;
}

th,td{
    padding:14px;
    text-align:left;
    border-bottom:1px solid rgba(255,255,255,0.1);
}

th{
    background:rgba(255,255,255,0.15);
}

/* STATUS */
.status-open{
    color:#facc15;
    font-weight:600;
}

.status-awarded{
    color:#22c55e;
    font-weight:600;
}

/* FILE BUTTON */
.file-link{
    display:inline-block;
    padding:6px 10px;
    background:rgba(59,130,246,0.3);
    border-radius:8px;
    color:white;
    text-decoration:none;
    font-size:13px;
}

.file-link:hover{
    background:rgba(59,130,246,0.5);
}

/* EMPTY */
.empty{
    text-align:center;
    padding:40px;
    opacity:0.7;
}

/* RESPONSIVE */
@media(max-width:768px){
    table{
        font-size:13px;
    }
}
</style>

</head>

<body>

<div class="header">
    <h1>🏛️ LGU Procurement Notices</h1>
    <p>Official public bidding and procurement announcements</p>
</div>

<div class="container">

<div class="card">

<?php if($posts->num_rows > 0): ?>

<table>

<thead>
<tr>
    <th>Title</th>
    <th>Status</th>
    <th>File</th>
    <th>Date</th>
</tr>
</thead>

<tbody>

<?php while($row = $posts->fetch_assoc()): ?>

<tr>

    <td>
        <?= htmlspecialchars($row['title']) ?>
    </td>

    <td>
        <?php if(!empty($row['award_winner'])): ?>
            <span class="status-awarded">AWARDED</span>
        <?php else: ?>
            <span class="status-open"><?= htmlspecialchars($row['status']) ?></span>
        <?php endif; ?>
    </td>

    <td>
        <a class="file-link"
           href="../uploads/procurement/<?= htmlspecialchars($row['file_path']) ?>"
           target="_blank">
            View / Download
        </a>
    </td>

    <td>
        <?= $row['created_at'] ?>
    </td>

</tr>

<?php endwhile; ?>

</tbody>

</table>

<?php else: ?>

<div class="empty">
    No procurement notices available.
</div>

<?php endif; ?>

</div>

</div>

</body>
</html>