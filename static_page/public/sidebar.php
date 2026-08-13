<?php
$active_page = $active_page ?? '';
?>
<aside class="sidebar">
    <div class="sidebar-header">
        <h2>🏛️ LGU Services</h2>
    </div>
    <nav class="sidebar-nav">
        <a href="public.php" class="sidebar-link <?= $active_page === 'jobs' ? 'active' : '' ?>">
            <i class="fas fa-briefcase"></i> Job Posting
        </a>
        <a href="procurement.php" class="sidebar-link <?= $active_page === 'procurement' ? 'active' : '' ?>">
            <i class="fas fa-file-contract"></i> Procurement Notice
        </a>
        <a href="philgeps.php" class="sidebar-link <?= $active_page === 'philgeps' ? 'active' : '' ?>">
            <i class="fas fa-file-contract"></i> PhilGEPS
        </a>
        <a href="bids_awards.php" class="sidebar-link <?= $active_page === 'bids_awards' ? 'active' : '' ?>">
            <i class="fas fa-file-contract"></i> Bids and Awards
        </a>
        <a href="invitation_to_bid.php" class="sidebar-link <?= $active_page === 'invitation_to_bid' ? 'active' : '' ?>">
            <i class="fas fa-file-contract"></i> Invitation to Bid
        </a>
        <a href="bid_bulletin.php" class="sidebar-link <?= $active_page === 'bid_bulletin' ? 'active' : '' ?>">
            <i class="fas fa-file-contract"></i> Bid Bulletin
        </a>
        <a href="notice_of_award.php" class="sidebar-link <?= $active_page === 'notice_of_award' ? 'active' : '' ?>">
            <i class="fas fa-file-contract"></i> Notice of Award
        </a>
        <a href="notice_to_proceed.php" class="sidebar-link <?= $active_page === 'notice_to_proceed' ? 'active' : '' ?>">
            <i class="fas fa-file-contract"></i> Notice to Proceed
        </a>
        <a href="news.php" class="sidebar-link <?= $active_page === 'news' ? 'active' : '' ?>">
            <i class="fas fa-newspaper"></i> News
        </a>
        <a href="scholarship.php" class="sidebar-link <?= $active_page === 'scholarship' ? 'active' : '' ?>">
            <i class="fas fa-graduation-cap"></i> Scholarship
        </a>
        <a href="../home.html" class="sidebar-link">
            <i class="fas fa-home"></i> Back to Home
        </a>
    </nav>
</aside>
