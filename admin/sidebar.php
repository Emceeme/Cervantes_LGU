<?php
/**
 * Shared Super Admin sidebar.
 *
 * Set $activePage (dashboard|lgu_list) before including this file to
 * highlight the current link.
 */
$activePage = $activePage ?? '';
?>
<aside class="sidebar">
    <div class="logo">🏛️</div>

    <a class="menu-btn<?= $activePage === 'dashboard' ? ' active' : '' ?>" href="dashboard.php">Dashboard</a>
    <a class="menu-btn" href="dashboard.php#create">Create LGU</a>
    <a class="menu-btn<?= $activePage === 'lgu_list' ? ' active' : '' ?>" href="lgu_list.php">LGU Accounts</a>
    <a class="menu-btn logout" href="../logout.php">Logout</a>
</aside>
