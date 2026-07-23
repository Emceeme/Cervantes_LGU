<?php
/**
 * Shared LGU sidebar.
 *
 * Set $activePage (dashboard|applicants|procurement|newsfeed) before
 * including this file to highlight the current link.
 */
$activePage = $activePage ?? '';
?>
<aside class="sidebar">
    <div class="logo">🏛️</div>

    <a href="dashboard.php"<?= $activePage === 'dashboard' ? ' class="active"' : '' ?>>Dashboard</a>
    <a href="applicants.php"<?= $activePage === 'applicants' ? ' class="active"' : '' ?>>Applicants</a>
    <a href="procurement.php"<?= $activePage === 'procurement' ? ' class="active"' : '' ?>>Procurement</a>
    <a href="newsfeed.php"<?= $activePage === 'newsfeed' ? ' class="active"' : '' ?>>News Feed</a>
    <a href="../logout.php">Logout</a>
</aside>
