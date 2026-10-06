<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Municipality of Cervantes - Mayor's Office</title>
    <link rel="stylesheet" href="styles.css">
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
                    <a href="home.html" class="nav-item"><i class="fas fa-home"></i> HOME</a>

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
        <!-- Left Sidebar - Mayor's Profile -->
        <aside class="fb-left-sidebar">
            <div class="profile-card">
                <div class="profile-image-container">
                    <img src="https://tse1.mm.bing.net/th/id/OIP.YNr_SYktStEzQN7ChwfglgHaFP?pid=Api&P=0&h=180" alt="Mayor of Cervantes" class="mayor-profile-img">
                </div>
                <h3>Profile Of the Mayor</h3>
                <button class="btn-blue">View Profile</button>
            </div>

            <div class="info-card">
                <div class="card-header">
                    <div class="small-icon"><i class="fas fa-user"></i></div>
                    <h3>Mayor Of Cervantes</h3>
                </div>
                <div class="card-line"></div>
                <p>Learn more about the leadership, vision, and initiatives of our municipality.</p>
                <button class="btn-blue">Learn More</button>
            </div>

            <div class="info-card">
                <div class="card-header">
                    <div class="small-icon info-icon">i</div>
                    <h3>Mayor's info</h3>
                </div>
                <div class="card-line"></div>
                <p>Stay updated with the latest announcements, programs, and activities from the Mayor's Office.</p>
                <button class="btn-blue">View Updates</button>
            </div>
        </aside>

        <!-- Center - News Feed -->
        <section class="fb-feed">
            <div class="feed-header">
                <h2>Latest News & Announcements</h2>
            </div>
            <div class="feed-container">
                <?php
                require_once '../config/db.php';
                require_once '../config/app_config.php';

                $posts_stmt = $conn->prepare("
                    SELECT *
                    FROM news_posts
                    ORDER BY created_at DESC
                ");

                if ($conn instanceof PDO) {
                    $posts_stmt->execute();
                    $posts = $posts_stmt->fetchAll();
                } else {
                    $posts_stmt->execute();
                    $posts = $posts_stmt->get_result();
                    $posts_stmt->close();
                }
                ?>

                <?php if($conn instanceof PDO): ?>
                    <?php if(count($posts) > 0): ?>
                        <?php foreach($posts as $row): ?>
                <div class="fb-post">
                    <div class="post-header">
                        <div class="post-author">
                            <div class="author-avatar">
                                <img src="https://tse2.mm.bing.net/th/id/OIP.XFNzT2MillEjgkKjmkiyHQHaHa?pid=Api&P=0&h=180" alt="LGU Logo">
                            </div>
                            <div class="author-info">
                                <h4>Municipality of Cervantes</h4>
                                <span class="post-date"><?= date("F d, Y h:i A", strtotime($row['created_at'])) ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="post-content">
                        <h3><?= htmlspecialchars($row['title']) ?></h3>
                        <?php if(!empty($row['image'])): ?>
                        <img src="<?= AppConfig::newsUploads($row['image']) ?>" alt="News Image" class="post-image" onerror="this.style.display='none'">
                        <?php endif; ?>
                        <p><?= nl2br(htmlspecialchars($row['content'])) ?></p>
                    </div>
                </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                <div class="empty-feed">No news has been posted yet.</div>
                    <?php endif; ?>
                <?php else: ?>
                    <?php if($posts->num_rows > 0): ?>
                        <?php while($row = $posts->fetch_assoc()): ?>
                <div class="fb-post">
                    <div class="post-header">
                        <div class="post-author">
                            <div class="author-avatar">
                                <img src="https://tse2.mm.bing.net/th/id/OIP.XFNzT2MillEjgkKjmkiyHQHaHa?pid=Api&P=0&h=180" alt="LGU Logo">
                            </div>
                            <div class="author-info">
                                <h4>Municipality of Cervantes</h4>
                                <span class="post-date"><?= date("F d, Y h:i A", strtotime($row['created_at'])) ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="post-content">
                        <h3><?= htmlspecialchars($row['title']) ?></h3>
                        <?php if(!empty($row['image'])): ?>
                        <img src="<?= AppConfig::newsUploads($row['image']) ?>" alt="News Image" class="post-image" onerror="this.style.display='none'">
                        <?php endif; ?>
                        <p><?= nl2br(htmlspecialchars($row['content'])) ?></p>
                    </div>
                </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                <div class="empty-feed">No news has been posted yet.</div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </section>

        <!-- Right Sidebar - Logo Banner -->
        <aside class="fb-right-sidebar">
            <div class="logo-banner">
                <div class="logo-image-container">
                    <img src="https://tse2.mm.bing.net/th/id/OIP.XFNzT2MillEjgkKjmkiyHQHaHa?pid=Api&P=0&h=180" alt="Cervantes Municipal Logo" class="muni-logo-img">
                </div>
            </div>
        </aside>
    </main>

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