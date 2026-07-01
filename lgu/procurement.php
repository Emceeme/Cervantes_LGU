<?php
include '../config/db.php';

$posts = $conn->query("SELECT * FROM procurement_posts ORDER BY id DESC");

if(!$posts){
    die("Query Error: " . $conn->error);
}
?>

<style>
    close-btn {
        position: absolute;
        top: 10px;
        right: 10px;
        background: none;
        border: none;
        font-size: 24px;
        cursor: pointer;
    }
</style>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Procurement Management</title>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="procurement.css">
</head>

<body>

<div class="container">

    <!-- SIDEBAR -->
    <aside class="sidebar">
        <div class="logo">🏛️</div>

        <a href="dashboard.php">Dashboard</a>
        <a href="applicants.php">Applicants</a>
        <a href="procurement.php">Procurement</a>
        <a href="newsfeed.php">News Feed</a>
        <a href="../logout.php">Logout</a>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="main-content">

        <h2>🏛️ Procurement Management</h2>

<button class="add-btn"
onclick="document.getElementById('modal').style.display='block'">
+ Add Procurement
</button>

<div class="card">

<table>
<tr>
    <th>Title</th>
    <th>File</th>
    <th>Status</th>
    <th>Award Winner</th>
    <th>Date</th>
    <th>Action</th>
</tr>

<?php while($row=$posts->fetch_assoc()): ?>

<tr>
    <td><?= htmlspecialchars($row['title']) ?></td>

    <td>
        <a href="../uploads/procurement/<?= $row['file_path'] ?>" target="_blank">
            View File
        </a>
    </td>

    <td>
        <?php if($row['award_winner']) : ?>
            <span class="status-awarded">AWARDED</span>
        <?php else: ?>
            <span class="status-open"><?= $row['status'] ?></span>
        <?php endif; ?>
    </td>

    <td>
        <?= $row['award_winner'] ? htmlspecialchars($row['award_winner']) : '-' ?>
    </td>

    <td><?= $row['created_at'] ?></td>

    <td>
        <?php if(!$row['award_winner']): ?>
            <form action="../handler/award_procurement.php" method="POST">
                <input type="hidden" name="id" value="<?= $row['id'] ?>">
                <input type="text" name="winner" placeholder="Winner Name" required>
                <button class="award-btn">Award</button>
            </form>
        <?php else: ?>
            ✔ Finalized
        <?php endif; ?>
    </td>
</tr>

<?php endwhile; ?>

</table>

</div>

</div>

<!-- ADD MODAL -->
<div id="modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.7);">
    <div style="background:white;color:black;width:400px;margin:10% auto;padding:20px;border-radius:10px;">
            
        <form action="../handler/upload_procurement.php" method="POST" enctype="multipart/form-data">

            <input type="text" name="title" placeholder="Title" required><br><br>

            <select name="status">
                <option value="OPEN">OPEN</option>
                <option value="CLOSED">CLOSED</option>
            </select><br><br>

            <input type="file" name="file" required><br><br>

            <button type="submit">Upload</button>

        </form>

    </div>
</div>

</body>

<script>
    function openModal(){
    document.getElementById("modal").style.display = "flex";
}

function closeModal(){
    document.getElementById("modal").style.display = "none";
}

window.onclick = function(event){
    const modal = document.getElementById("modal");

    if(event.target == modal){
        closeModal();
    }
}
</script>
</html>