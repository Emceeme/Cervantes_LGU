<?php
include '../../config/db.php';

// Valid categories
$valid_categories = [
    'philgeps' => 'PhilGEPS',
    'bids_awards' => 'Bids and Awards',
    'invitation_to_bid' => 'Invitation to Bid',
    'bid_bulletin' => 'Bid Bulletin',
    'notice_of_award' => 'Notice of Award',
    'notice_to_proceed' => 'Notice to Proceed'
];

// Get category from query parameter
$category = $_GET['category'] ?? '';
$category_name = 'All Categories';

// Get search parameters
$search_query = $_GET['search'] ?? '';
$search_date = $_GET['date'] ?? '';

// Build query based on category filter and search
$where_clauses = [];
$params = [];
$types = "";

if (!empty($category) && array_key_exists($category, $valid_categories)) {
    $category_name = $valid_categories[$category];
    $where_clauses[] = "category = ?";
    $params[] = $category;
    $types .= "s";
} else {
    $category_name = 'All Categories';
}

// Add search conditions
if (!empty($search_query)) {
    $where_clauses[] = "(title LIKE ? OR original_file_name LIKE ?)";
    $search_term = "%$search_query%";
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= "ss";
}

if (!empty($search_date)) {
    $where_clauses[] = "custom_date = ?";
    $params[] = $search_date;
    $types .= "s";
}

// Build final SQL
$sql = "SELECT * FROM procurement_posts";
if (!empty($where_clauses)) {
    $sql .= " WHERE " . implode(" AND ", $where_clauses);
}
$sql .= " ORDER BY custom_date DESC, created_at DESC";

$posts_stmt = $conn->prepare($sql);
if (!$posts_stmt) {
    if ($conn instanceof PDO) {
        die("Prepare Error: " . implode(", ", $conn->errorInfo()) . " SQL: " . $sql);
    } else {
        die("Prepare Error: " . $conn->error . " SQL: " . $sql);
    }
}

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
    $posts_stmt->close();
    
    if(!$posts){
        die("Query Error: " . $conn->error);
    }
}

// Success message
$success_message = isset($_GET['status']) && $_GET['status'] === 'success';
?>


<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Public Procurement Notices</title>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="procurement.css">

</head>

<body>

<div class="page-container">
    <?php $active_page = 'procurement'; include 'sidebar.php'; ?>

    <!-- Main Content -->
    <main class="main-content">
        <div class="header">
            <h1>🏛️ LGU Procurement Notices</h1>
            <p>Official public bidding and procurement announcements</p>
        </div>

        <div class="container">

<div class="card">

<?php if($success_message): ?>
<div class="success-message">
    ✅ Document uploaded successfully!
</div>
<?php endif; ?>

<!-- Category Filters -->
<div class="category-filters">
    <a href="procurement.php" class="category-btn <?= empty($category) ? 'active' : '' ?>">All Categories</a>
    <?php foreach($valid_categories as $slug => $name): ?>
        <a href="procurement.php?category=<?= $slug ?>" class="category-btn <?= $category === $slug ? 'active' : '' ?>">
            <?= $name ?>
        </a>
    <?php endforeach; ?>
</div>

<!-- Search Box -->
<form method="GET" action="procurement.php" class="search-box">
    <input type="text" name="search" class="search-input" placeholder="Search by title or filename..." value="<?= htmlspecialchars($search_query) ?>">
    <input type="date" name="date" class="search-input" value="<?= htmlspecialchars($search_date) ?>">
    <?php if(!empty($category)): ?>
        <input type="hidden" name="category" value="<?= htmlspecialchars($category) ?>">
    <?php endif; ?>
    <button type="submit" class="search-btn">Search</button>
    <?php if(!empty($search_query) || !empty($search_date)): ?>
        <a href="procurement.php<?= !empty($category) ? '?category='.urlencode($category) : '' ?>" class="search-btn" style="text-decoration:none;">Clear</a>
    <?php endif; ?>
</form>

<h3 style="margin-bottom: 15px;"><?= htmlspecialchars($category_name) ?></h3>

<?php if($conn instanceof PDO): ?>
    <?php if(count($posts) > 0): ?>
<table>

<thead>
<tr>
    <th>Title</th>
    <th>Category</th>
    <th>Posting Date</th>
    <th>File</th>
</tr>
</thead>

<tbody>

<?php foreach($posts as $row): ?>
<tr>

    <td>
        <?= htmlspecialchars($row['title']) ?>
    </td>
    <td>
        <?= htmlspecialchars($row['category']) ?>
    </td>
    <td>
        <?= htmlspecialchars($row['custom_date'] ?? $row['created_at']) ?>
    </td>
    <td>
        <a href="<?= htmlspecialchars($row['file_path']) ?>" target="_blank" class="file-link">
            📄 <?= htmlspecialchars($row['title']) ?>
        </a>
    </td>

</tr>

<?php endforeach; ?>

</tbody>

</table>

    <?php else: ?>
<div class="empty">
    No procurement notices found.
</div>

    <?php endif; ?>
<?php else: ?>
    <?php if($posts->num_rows > 0): ?>
<table>

<thead>
<tr>
    <th>Title</th>
    <th>Category</th>
    <th>Posting Date</th>
    <th>File</th>
</tr>
</thead>

<tbody>

<?php while($row = $posts->fetch_assoc()): ?>
<tr>

    <td>
        <?= htmlspecialchars($row['title']) ?>
    </td>
    <td>
        <?= htmlspecialchars($row['category']) ?>
    </td>
    <td>
        <?= htmlspecialchars($row['custom_date'] ?? $row['created_at']) ?>
    </td>
    <td>
        <a href="<?= htmlspecialchars($row['file_path']) ?>" target="_blank" class="file-link">
            📄 <?= htmlspecialchars($row['title']) ?>
        </a>
    </td>

</tr>

<?php endwhile; ?>

</tbody>

</table>

    <?php else: ?>
<div class="empty">
    No procurement notices found for <?= htmlspecialchars(strtolower($category_name)) ?>.
</div>

    <?php endif; ?>
<?php endif; ?>

</div>

</div>

    </main>
</div>

</body>
</html>