<?php
// Disable error output to prevent HTML from corrupting the page
error_reporting(0);
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Municipality of Cervantes - Mayor's Office</title>
    <style>
        /* --- FACEBOOK-STYLE LAYOUT --- */
        .fb-layout {
            display: grid;
            grid-template-columns: 260px 1fr 300px;
            gap: 20px;
            max-width: 1400px;
            margin: 20px auto;
            padding: 0 20px;
        }

        .fb-left-sidebar {
            position: sticky;
            top: 90px;
            height: calc(100vh - 110px);
            overflow-y: auto;
        }

        .sidebar-nav {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .sidebar-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            border-radius: 8px;
            text-decoration: none;
            color: #444;
            font-weight: 500;
            font-size: 0.9rem;
            transition: all 0.2s ease;
        }

        .sidebar-link:hover {
            background-color: #f0f4f8;
            color: #0056b3;
        }

        .sidebar-link.active {
            background-color: #0056b3;
            color: white;
        }

        .sidebar-link i {
            width: 20px;
            text-align: center;
        }

        .fb-feed {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .feed-composer {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .composer-header {
            padding: 15px 20px;
            border-bottom: 1px solid #eee;
        }

        .composer-header h3 {
            margin: 0;
            color: #333;
            font-size: 1rem;
            font-weight: 600;
        }

        .composer-body {
            padding: 15px 20px;
        }

        .composer-placeholder {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 15px;
            background-color: #f8f9fa;
            border-radius: 8px;
            color: #666;
            font-size: 0.9rem;
        }

        .composer-placeholder i {
            color: #999;
        }

        .composer-actions {
            display: flex;
            gap: 15px;
            padding: 10px 20px;
            border-top: 1px solid #eee;
        }

        .composer-action {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            border-radius: 20px;
            background-color: #f0f4f8;
            color: #666;
            font-size: 0.85rem;
            font-weight: 500;
            cursor: default;
        }

        .composer-action i {
            color: #0056b3;
        }

        .feed-container {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .fb-post {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .post-header {
            padding: 15px 20px;
            border-bottom: 1px solid #eee;
        }

        .post-author {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .author-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            overflow: hidden;
            background: #eaf2fc;
            flex-shrink: 0;
        }

        .author-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .author-info {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .author-info h4 {
            margin: 0;
            font-size: 0.95rem;
            color: #333;
            font-weight: 600;
        }

        .post-category {
            font-size: 0.75rem;
            color: #0056b3;
            font-weight: 600;
            text-transform: uppercase;
        }

        .post-date {
            font-size: 0.75rem;
            color: #888;
        }

        .post-content {
            padding: 20px;
        }

        .post-content h3 {
            color: #0056b3;
            margin: 0 0 15px;
            font-size: 1.1rem;
            font-weight: 600;
        }

        .post-image {
            width: 100%;
            max-height: 400px;
            object-fit: cover;
            border-radius: 8px;
            margin-bottom: 15px;
        }

        .post-content p {
            color: #444;
            line-height: 1.6;
            font-size: 0.95rem;
            margin: 0;
        }

        .post-actions {
            display: flex;
            gap: 10px;
            padding: 15px 20px;
            border-top: 1px solid #eee;
        }

        .post-action {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            border-radius: 20px;
            background: none;
            border: 1px solid #e0e0e0;
            color: #666;
            font-size: 0.85rem;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .post-action:hover {
            background-color: #0056b3;
            color: white;
            border-color: #0056b3;
        }

        .post-action i {
            font-size: 0.9rem;
        }

        .empty-feed {
            background: white;
            border-radius: 12px;
            padding: 40px;
            text-align: center;
            color: #888;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .fb-right-sidebar {
            position: sticky;
            top: 90px;
            height: calc(100vh - 110px);
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .mayor-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            text-align: center;
        }

        .mayor-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            overflow: hidden;
            margin: 0 auto 15px;
            background: #eaf2fc;
        }

        .mayor-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .mayor-card h4 {
            margin: 0 0 5px;
            color: #0056b3;
            font-size: 1rem;
            font-weight: 600;
        }

        .mayor-card p {
            margin: 0 0 15px;
            color: #666;
            font-size: 0.85rem;
        }

        .btn-link {
            color: #0056b3;
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .btn-link:hover {
            text-decoration: underline;
        }

        .quick-services {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .quick-services h4 {
            margin: 0 0 15px;
            color: #333;
            font-size: 0.95rem;
            font-weight: 600;
        }

        .service-links {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .service-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 12px;
            border-radius: 8px;
            text-decoration: none;
            color: #444;
            font-size: 0.85rem;
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .service-link:hover {
            background-color: #f0f4f8;
            color: #0056b3;
        }

        .service-link i {
            width: 18px;
            color: #0056b3;
        }

        .logo-banner {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            text-align: center;
        }

        @media (max-width: 768px) {
            .fb-layout {
                grid-template-columns: 1fr;
            }
            
            .fb-left-sidebar, .fb-right-sidebar {
                display: none;
            }
        }

        @media (max-width: 1024px) {
            .fb-layout {
                grid-template-columns: 1fr 280px;
            }
            
            .fb-left-sidebar {
                display: none;
            }
            
            .fb-right-sidebar {
                position: static;
                height: auto;
            }
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="overlay"></div>
    <div class="bg"></div>

    <!-- MATCHED DESIGN NAVBAR -->
    <header class="navbar-wrapper">
        <nav class="navbar">
            <div class="nav-left">
                <div class="logo-area">
                    <div class="seal-placeholder"></div>
                    <div class="logo-text">
                        <span class="muni-title">MUNICIPALITY OF</span>
                        <h1 class="muni-name">CERVANTES</h1>
                        <span class="muni-subtitle">ILOCOS SUR</span>
                    </div>
                </div>

                <div class="nav-links">
                    <a href="home.php" class="nav-item"><i class="fas fa-home"></i> HOME</a>

                    <div class="dropdown">
                        <button class="dropdown-toggle nav-item">ABOUT <span class="arrow">▼</span></button>
                        <div class="dropdown-content">
                            <a href="#">Historical Background</a>
                            <a href="map.html">Cervantes Map</a>
                            <a href="#">Barangays</a>
                        </div>
                    </div>

                    <div class="dropdown">
                        <button class="dropdown-toggle nav-item active">TOURISM <span class="arrow">▼</span></button>
                        <div class="dropdown-content">
                            <a href="tourism.html">Destinations</a>
                            <a href="accomodation.html">Where to stay</a>
                            <a href="#">Bessang Pass History</a>
                            <a href="#">Events</a>
                            <a href="#">Resort</a>
                        </div>
                    </div>

                    <div class="dropdown">
                        <button class="dropdown-toggle nav-item">SERVICES <span class="arrow">▼</span></button>
                        <div class="dropdown-content">
                            <a href="public/public.php">Job Posting</a>
                            <a href="public/procurement.php">Procurement Notice</a>
                            <a href="public/philgeps.php">PhilGEPS</a>
                            <a href="public/bids_awards.php">Bids and Awards</a>
                            <a href="public/invitation_to_bid.php">Invitation to Bid</a>
                            <a href="public/bid_bulletin.php">Bid Bulletin</a>
                            <a href="public/notice_of_award.php">Notice of Award</a>
                            <a href="public/notice_to_proceed.php">Notice to Proceed</a>
                            <a href="public/news.php">News</a>
                            <a href="public/scholarship.php">Scholarship</a>
                            <a href="../mswd/public/index.php">MSWD</a>
                        </div>
                    </div>

                    <a href="#" class="nav-item contacts-btn"><i class="fas fa-phone"></i> CONTACTS</a>
                </div>
            </div>

            <div class="nav-right">
                <button class="hamburger" aria-label="Toggle menu">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
            </div>
        </nav>
    </header>

    <!-- Facebook-style Layout -->
    <main class="fb-layout">
        <!-- Left Sidebar - Navigation -->
        <aside class="fb-left-sidebar">
            <nav class="sidebar-nav">
                <a href="home.php" class="sidebar-link active">
                    <i class="fas fa-home"></i> Home
                </a>
                <a href="public/news.php" class="sidebar-link">
                    <i class="fas fa-newspaper"></i> News & Announcements
                </a>
                <a href="#" class="sidebar-link">
                    <i class="fas fa-user-tie"></i> Mayor's Office
                </a>
                <a href="public/public.php" class="sidebar-link">
                    <i class="fas fa-briefcase"></i> Jobs
                </a>
                <a href="public/scholarship.php" class="sidebar-link">
                    <i class="fas fa-graduation-cap"></i> Scholarships
                </a>
                <a href="public/philgeps.php" class="sidebar-link">
                    <i class="fas fa-file-contract"></i> PhilGEPS
                </a>
                <a href="public/bids_awards.php" class="sidebar-link">
                    <i class="fas fa-gavel"></i> Bids & Awards
                </a>
                <a href="tourism.html" class="sidebar-link">
                    <i class="fas fa-map-marked-alt"></i> Tourism
                </a>
                <a href="accomodation.html" class="sidebar-link">
                    <i class="fas fa-bed"></i> Accommodations
                </a>
                <a href="../mswd/public/index.php" class="sidebar-link">
                    <i class="fas fa-hands-helping"></i> MSWD Services
                </a>
                <a href="#" class="sidebar-link">
                    <i class="fas fa-calendar-alt"></i> Events
                </a>
                <a href="#" class="sidebar-link">
                    <i class="fas fa-phone"></i> Contact Us
                </a>
            </nav>
        </aside>

        <!-- Center - News Feed -->
        <section class="fb-feed">
            <!-- Feed Composer (Informational Only) -->
            <div class="feed-composer">
                <div class="composer-header">
                    <h3>What's happening in Cervantes?</h3>
                </div>
                <div class="composer-body">
                    <div class="composer-placeholder">
                        <i class="fas fa-info-circle"></i>
                        <span>Stay updated with the latest municipal announcements and news</span>
                    </div>
                </div>
                <div class="composer-actions">
                    <span class="composer-action"><i class="fas fa-bullhorn"></i> Announcement</span>
                    <span class="composer-action"><i class="fas fa-image"></i> Photo</span>
                    <span class="composer-action"><i class="fas fa-calendar"></i> Event</span>
                </div>
            </div>

            <div class="feed-container">
                <?php
                try {
                    require_once __DIR__ . '/../config/db.php';
                    require_once __DIR__ . '/../config/app_config.php';

                    if (!isset($conn) || $conn === null) {
                        echo '<div class="empty-feed">Database connection not available. Please check configuration.</div>';
                    } else {
                        // Fetch all content types for the feed
                        $feed_items = [];

                        // News posts
                        $news_stmt = $conn->prepare("SELECT id, title, content, image, created_at, 'news' as type, 'ANNOUNCEMENT' as category FROM news_posts ORDER BY created_at DESC LIMIT 20");
                        if ($conn instanceof PDO) {
                            $news_stmt->execute();
                            $news = $news_stmt->fetchAll();
                        } else {
                            $news_stmt->execute();
                            $news = $news_stmt->get_result();
                            $news = $news->fetch_all(MYSQLI_ASSOC);
                            $news_stmt->close();
                        }
                        $feed_items = array_merge($feed_items, $news);

                        // Jobs
                        $jobs_stmt = $conn->prepare("SELECT id, job_title as title, description as content, department, created_at, 'job' as type, 'JOB' as category FROM jobs WHERE status = 'OPEN' ORDER BY created_at DESC LIMIT 10");
                        if ($conn instanceof PDO) {
                            $jobs_stmt->execute();
                            $jobs = $jobs_stmt->fetchAll();
                        } else {
                            $jobs_stmt->execute();
                            $jobs = $jobs_stmt->get_result();
                            $jobs = $jobs->fetch_all(MYSQLI_ASSOC);
                            $jobs_stmt->close();
                        }
                        $feed_items = array_merge($feed_items, $jobs);

                        // Scholarships
                        $scholarship_stmt = $conn->prepare("SELECT id, title, description as content, image, created_at, 'scholarship' as type, 'SCHOLARSHIP' as category FROM scholarship_posts ORDER BY created_at DESC LIMIT 10");
                        if ($conn instanceof PDO) {
                            $scholarship_stmt->execute();
                            $scholarships = $scholarship_stmt->fetchAll();
                        } else {
                            $scholarship_stmt->execute();
                            $scholarships = $scholarship_stmt->get_result();
                            $scholarships = $scholarships->fetch_all(MYSQLI_ASSOC);
                            $scholarship_stmt->close();
                        }
                        $feed_items = array_merge($feed_items, $scholarships);

                        // Procurement
                        $procurement_stmt = $conn->prepare("SELECT id, title, description as content, category, created_at, 'procurement' as type, UPPER(category) as category FROM procurement_posts WHERE status = 'OPEN' ORDER BY created_at DESC LIMIT 10");
                        if ($conn instanceof PDO) {
                            $procurement_stmt->execute();
                            $procurements = $procurement_stmt->fetchAll();
                        } else {
                            $procurement_stmt->execute();
                            $procurements = $procurement_stmt->get_result();
                            $procurements = $procurements->fetch_all(MYSQLI_ASSOC);
                            $procurement_stmt->close();
                        }
                        $feed_items = array_merge($feed_items, $procurements);

                        // Sort by created_at
                        usort($feed_items, function($a, $b) {
                            return strtotime($b['created_at']) - strtotime($a['created_at']);
                        });

                        if (count($feed_items) > 0): ?>
                            <?php foreach($feed_items as $item): ?>
                <div class="fb-post">
                    <div class="post-header">
                        <div class="post-author">
                            <div class="author-avatar">
                                <img src="https://tse2.mm.bing.net/th/id/OIP.XFNzT2MillEjgkKjmkiyHQHaHa?pid=Api&P=0&h=180" alt="LGU Logo">
                            </div>
                            <div class="author-info">
                                <h4>Municipality of Cervantes</h4>
                                <span class="post-category"><?= htmlspecialchars($item['category']) ?></span>
                                <span class="post-date"><?= time_elapsed_string($item['created_at']) ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="post-content">
                        <h3><?= htmlspecialchars($item['title']) ?></h3>
                        <?php if(!empty($item['image'])): ?>
                        <img src="<?= AppConfig::newsUploads($item['image']) ?>" alt="Post Image" class="post-image" onerror="this.style.display='none'">
                        <?php endif; ?>
                        <p><?= nl2br(htmlspecialchars(substr($item['content'], 0, 300))) ?><?php if(strlen($item['content']) > 300) echo '...'; ?></p>
                    </div>
                    <div class="post-actions">
                        <?php if($item['type'] === 'news'): ?>
                            <a href="public/news.php" class="post-action"><i class="fas fa-book-open"></i> Read More</a>
                        <?php elseif($item['type'] === 'job'): ?>
                            <a href="public/public.php" class="post-action"><i class="fas fa-briefcase"></i> Apply Now</a>
                        <?php elseif($item['type'] === 'scholarship'): ?>
                            <a href="public/scholarship.php" class="post-action"><i class="fas fa-graduation-cap"></i> View Details</a>
                        <?php elseif($item['type'] === 'procurement'): ?>
                            <a href="public/procurement.php" class="post-action"><i class="fas fa-file-contract"></i> View Details</a>
                        <?php endif; ?>
                        <button class="post-action"><i class="fas fa-share-alt"></i> Share</button>
                    </div>
                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                <div class="empty-feed">No announcements or updates at this time. Check back soon!</div>
                        <?php endif; ?>
                    <?php }
                } catch (Exception $e) {
                    echo '<div class="empty-feed">Unable to load feed. Please try again later.</div>';
                }
                ?>
            </div>
        </section>

        <!-- Right Sidebar - Quick Info -->
        <aside class="fb-right-sidebar">
            <!-- Mayor Profile (Small) -->
            <div class="mayor-card">
                <div class="mayor-avatar">
                    <img src="https://tse1.mm.bing.net/th/id/OIP.YNr_SYktStEzQN7ChwfglgHaFP?pid=Api&P=0&h=180" alt="Mayor of Cervantes">
                </div>
                <h4>Mayor's Office</h4>
                <p>Municipality of Cervantes</p>
                <a href="#" class="btn-link">View Profile</a>
            </div>

            <!-- Quick Services -->
            <div class="quick-services">
                <h4>Quick Services</h4>
                <div class="service-links">
                    <a href="public/public.php" class="service-link">
                        <i class="fas fa-briefcase"></i> Job Openings
                    </a>
                    <a href="public/scholarship.php" class="service-link">
                        <i class="fas fa-graduation-cap"></i> Scholarships
                    </a>
                    <a href="public/philgeps.php" class="service-link">
                        <i class="fas fa-file-contract"></i> PhilGEPS
                    </a>
                    <a href="public/bids_awards.php" class="service-link">
                        <i class="fas fa-gavel"></i> Bids & Awards
                    </a>
                    <a href="../mswd/public/index.php" class="service-link">
                        <i class="fas fa-hands-helping"></i> MSWD Services
                    </a>
                </div>
            </div>

            <!-- Municipal Logo -->
            <div class="logo-banner">
                <div class="logo-image-container">
                    <img src="https://tse2.mm.bing.net/th/id/OIP.XFNzT2MillEjgkKjmkiyHQHaHa?pid=Api&P=0&h=180" alt="Cervantes Municipal Logo" class="muni-logo-img">
                </div>
            </div>
        </aside>
    </main>

    <?php
    // Helper function for time elapsed
    function time_elapsed_string($datetime) {
        $time = time() - strtotime($datetime);
        
        if ($time < 60) return 'Just now';
        if ($time < 3600) return floor($time / 60) . ' min ago';
        if ($time < 86400) return floor($time / 3600) . ' hours ago';
        if ($time < 604800) return floor($time / 86400) . ' days ago';
        
        return date('F d, Y', strtotime($datetime));
    }
    ?>

    <div id="infoModal" class="modal-overlay">
        <div class="modal-content">
            <button class="modal-close-btn">&times;</button>
            <div class="modal-body">
                <div class="modal-icon-container"></div>
                <h2 id="modalTitle">Modal Title</h2>
                <div class="modal-divider"></div>
                <div id="modalText">Modal body text goes here...</div>
            </div>
        </div>
    </div>

    <script>
        // Mobile menu toggle
        const hamburger = document.querySelector('.hamburger');
        const navLinks = document.querySelector('.nav-links');

        if (hamburger) {
            hamburger.addEventListener('click', function() {
                this.classList.toggle('active');
                navLinks.classList.toggle('active');
            });
        }

        // Dropdown toggle - works for both desktop and mobile
        document.querySelectorAll('.dropdown-toggle').forEach(button => {
            button.addEventListener('click', function(e) {
                e.stopPropagation();
                const dropdown = this.parentElement;
                
                // Close other dropdowns
                document.querySelectorAll('.dropdown').forEach(d => {
                    if (d !== dropdown) {
                        d.classList.remove('hovered', 'active');
                    }
                });
                
                // Toggle current dropdown
                dropdown.classList.toggle('hovered');
                dropdown.classList.toggle('active');
            });
        });

        document.addEventListener('click', function(event) {
            document.querySelectorAll('.dropdown').forEach(dropdown => {
                if (!dropdown.contains(event.target)) {
                    dropdown.classList.remove('hovered');
                    dropdown.classList.remove('active');
                }
            });
        });
    </script>
</body>
</html>