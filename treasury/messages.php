<?php
require_once '../config/security.php';
include '../config/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set security headers
setSecurityHeaders();

// 🔒 SECURITY GUARD: Treasury Staff/Sub-Admin or Super Admin only
if (!isset($_SESSION['name']) || ($_SESSION['department'] !== 'Treasury' && $_SESSION['role'] !== 'SUPER_ADMIN')) {
    logSecurityEvent('unauthorized_access', $_SESSION['id'] ?? null, ['endpoint' => 'treasury_messages']);
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'] ?? 0;

// Generate CSRF token
$csrf_token = generateCsrfToken();

// Selected thread from URL query
$selected_thread_id = isset($_GET['thread_id']) ? intval($_GET['thread_id']) : null;

// Query all conversations/tickets relevant to Treasury
$threads = false;
$threads_query = "
    SELECT m.id, m.sender_id, m.receiver_id, m.subject, m.message, m.created_at, m.status,
           u.first_name, u.last_name, u.role
    FROM messages m
    JOIN users u ON (u.id = IF(m.sender_id = ?, m.receiver_id, m.sender_id))
    WHERE (m.department = 'Treasury' OR m.receiver_id = ? OR m.sender_id = ?) 
      AND (m.parent_id IS NULL OR m.parent_id = 0)
    ORDER BY m.created_at DESC
";

$stmt = $conn->prepare($threads_query);
if ($stmt) {
    $stmt->bind_param("iii", $user_id, $user_id, $user_id);
    $stmt->execute();
    $threads = $stmt->get_result();
} else {
    $db_error = "Database Error: " . $conn->error;
}

// Fetch all public users (citizens only) for the "New Message" autocomplete search dropdown
$all_users_stmt = $conn->prepare("SELECT id, first_name, last_name, username FROM users WHERE role = 'CITIZEN' AND id != ? ORDER BY last_name ASC");
$all_users_stmt->bind_param("i", $user_id);
$all_users_stmt->execute();
$all_users_result = $all_users_stmt->get_result();
$users_list = [];
if ($all_users_result) {
    while ($row = $all_users_result->fetch_assoc()) {
        $users_list[] = $row;
    }
}
$all_users_stmt->close();

// If a thread is selected, mark as READ & load conversation details
$active_messages = false;
if ($selected_thread_id) {
    // Mark incoming messages in this thread as READ
    $mark_read = $conn->prepare("UPDATE messages SET status = 'READ' WHERE (id = ? OR parent_id = ?) AND receiver_id = ?");
    if ($mark_read) {
        $mark_read->bind_param("iii", $selected_thread_id, $selected_thread_id, $user_id);
        $mark_read->execute();
        $mark_read->close();
    }

    // Fetch conversation thread history
    $msg_stmt = $conn->prepare("
        SELECT m.*, u.first_name, u.last_name, u.role 
        FROM messages m
        JOIN users u ON m.sender_id = u.id
        WHERE m.id = ? OR m.parent_id = ?
        ORDER BY m.created_at ASC
    ");
    if ($msg_stmt) {
        $msg_stmt->bind_param("ii", $selected_thread_id, $selected_thread_id);
        $msg_stmt->execute();
        $active_messages = $msg_stmt->get_result();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Treasury - Citizen Inquiries & Disputes</title>

<!-- Font Awesome 6 CDN -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<!-- Google Fonts -->
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<!-- Treasury Stylesheet -->
<link rel="stylesheet" href="styles.css">

<style>
.inbox-container {
    display: grid;
    grid-template-columns: 320px 1fr;
    gap: 20px;
    height: calc(100vh - 200px);
    min-height: 550px;
}

@media (max-width: 900px) {
    .inbox-container {
        grid-template-columns: 1fr;
        height: auto;
    }
}

.thread-list {
    background: rgba(15, 23, 42, 0.6);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 14px;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
}

.thread-item {
    padding: 16px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
    text-decoration: none;
    color: inherit;
    transition: background 0.2s ease;
    display: block;
}

.thread-item:hover, .thread-item.active {
    background: rgba(56, 189, 248, 0.1);
    border-left: 3px solid #38bdf8;
}

.thread-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 4px;
}

.thread-name {
    font-weight: 600;
    font-size: 0.9rem;
    color: #f8fafc;
}

.thread-date {
    font-size: 0.75rem;
    color: #64748b;
}

.thread-subject {
    font-size: 0.825rem;
    color: #38bdf8;
    font-weight: 500;
    margin-bottom: 4px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.thread-preview {
    font-size: 0.8rem;
    color: #94a3b8;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.chat-box {
    background: rgba(15, 23, 42, 0.6);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 14px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}

.chat-messages {
    padding: 20px;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    gap: 16px;
    flex-grow: 1;
}

.message-bubble {
    max-width: 75%;
    padding: 12px 16px;
    border-radius: 12px;
    font-size: 0.9rem;
    line-height: 1.5;
}

.message-bubble.incoming {
    align-self: flex-start;
    background: rgba(30, 41, 59, 0.8);
    border: 1px solid rgba(255, 255, 255, 0.08);
    color: #f1f5f9;
}

.message-bubble.outgoing {
    align-self: flex-end;
    background: #2563eb;
    color: #ffffff;
}

.message-meta {
    font-size: 0.7rem;
    margin-top: 4px;
    opacity: 0.7;
    text-align: right;
}

.chat-input-area {
    padding: 16px;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
    display: flex;
    gap: 12px;
}

.chat-input {
    flex-grow: 1;
    background: rgba(15, 23, 42, 0.8);
    border: 1px solid rgba(255, 255, 255, 0.12);
    border-radius: 10px;
    padding: 12px 16px;
    color: #f8fafc;
    outline: none;
    resize: none;
}

.btn-send {
    padding: 0 20px;
    background: #38bdf8;
    color: #0f172a;
    border: none;
    border-radius: 10px;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.2s ease;
}

.btn-send:hover {
    background: #0284c7;
    color: #fff;
}

.status-badge-UNREAD {
    background: rgba(239, 68, 68, 0.2);
    color: #f87171;
    font-size: 0.7rem;
    padding: 2px 6px;
    border-radius: 4px;
}

/* 🔍 SEARCH AND RECOMMENDATIONS STYLING */
.search-wrapper {
    position: relative;
}

.suggestions-box {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: #0f172a;
    border: 1px solid rgba(56, 189, 248, 0.4);
    border-radius: 8px;
    max-height: 180px;
    overflow-y: auto;
    z-index: 2000;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.6);
    display: none;
}

.suggestion-item {
    padding: 10px 14px;
    cursor: pointer;
    color: #cbd5e1;
    font-size: 0.85rem;
    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.suggestion-item:hover {
    background: rgba(56, 189, 248, 0.15);
    color: #38bdf8;
}

/* MODAL OVERLAY STYLING */
.modal-overlay {
    position: fixed;
    top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(0, 0, 0, 0.7);
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 1500;
}

.modal-content {
    background: #0f172a;
    border: 1px solid rgba(255, 255, 255, 0.15);
    border-radius: 14px;
    padding: 24px;
    width: 100%;
    max-width: 480px;
    box-shadow: 0 20px 40px rgba(0,0,0,0.5);
}
</style>
</head>

<body>

<div class="bg-blur blur1"></div>
<div class="bg-blur blur2"></div>

<div class="container">

    <!-- SIDEBAR -->
    <aside class="sidebar">
        <div class="logo">
            <i class="fas fa-landmark"></i> Treasury
        </div>

        <a href="dashboard.php">
            <i class="fas fa-chart-line"></i> Dashboard
        </a>
        <a href="messages.php" class="active">
            <i class="fas fa-envelope"></i> Messages
        </a>
        <a href="update_balance.php">
            <i class="fas fa-wallet"></i> Update Balance
        </a>
        <a href="manage_employees.php">
            <i class="fas fa-user-plus"></i> Create Employee
        </a>
        <a href="create_public_user.php">
            <i class="fas fa-users"></i> Create Public User
        </a>
        <a href="../logout.php" style="margin-top: auto; color: #f87171;">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="main-content">

        <div class="top-bar" style="display: flex; justify-content: space-between; align-items: flex-start;">
            <div>
                <h2>Taxpayer Inquiry & Support Inbox</h2>
                <p class="muted">Respond to payment clarifications, clearance requests, and tax dispute inquiries.</p>
            </div>
            <button onclick="openNewMsgModal()" class="btn-send" style="padding: 10px 16px; white-space: nowrap;">
                <i class="fas fa-edit"></i> New Message
            </button>
        </div>

        <?php if (isset($db_error)): ?>
            <div style="background: rgba(239, 68, 68, 0.2); border: 1px solid #ef4444; color: #f87171; padding: 12px 16px; border-radius: 10px; margin-bottom: 20px;">
                <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($db_error) ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['success'])): ?>
            <div style="background: rgba(34, 197, 94, 0.2); border: 1px solid #22c55e; color: #4ade80; padding: 12px 16px; border-radius: 10px; margin-bottom: 20px;">
                <i class="fas fa-check-circle"></i> <?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <div class="inbox-container">

            <!-- THREAD LIST -->
            <div class="thread-list">
                <div style="padding: 12px 16px; border-bottom: 1px solid rgba(255, 255, 255, 0.1);">
                    <!-- Filter Inbox Search Field -->
                    <div style="position: relative;">
                        <input type="text" id="thread_filter_input" placeholder="Search threads or users..." 
                               style="width: 100%; background: rgba(15, 23, 42, 0.8); border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; padding: 8px 12px; color: #fff; font-size: 0.8rem; outline: none;">
                    </div>
                </div>

                <div id="thread_items_container">
                <?php if ($threads && $threads->num_rows > 0): ?>
                    <?php while ($t = $threads->fetch_assoc()): ?>
                        <a href="messages.php?thread_id=<?= $t['id'] ?>" 
                           class="thread-item <?= $selected_thread_id == $t['id'] ? 'active' : '' ?>"
                           data-name="<?= htmlspecialchars(strtolower($t['first_name'] . ' ' . $t['last_name'])) ?>"
                           data-subject="<?= htmlspecialchars(strtolower($t['subject'] ?? '')) ?>">
                            <div class="thread-header">
                                <span class="thread-name"><?= htmlspecialchars($t['first_name'] . ' ' . $t['last_name']) ?></span>
                                <span class="thread-date"><?= date('M d', strtotime($t['created_at'])) ?></span>
                            </div>
                            <div class="thread-subject"><?= htmlspecialchars($t['subject'] ?? 'Tax Inquiry') ?></div>
                            <div class="thread-preview"><?= htmlspecialchars($t['message']) ?></div>
                            <?php if ($t['status'] === 'UNREAD' && $t['receiver_id'] == $user_id): ?>
                                <span class="status-badge-UNREAD" style="margin-top: 6px; display: inline-block;">NEW</span>
                            <?php endif; ?>
                        </a>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div style="padding: 30px; text-align: center; color: #64748b; font-size: 0.85rem;">
                        No messages or disputes found.
                    </div>
                <?php endif; ?>
                </div>
            </div>

            <!-- CHAT VIEW AREA -->
            <div class="chat-box">
                <?php if ($selected_thread_id && $active_messages && $active_messages->num_rows > 0): ?>
                    <div class="chat-messages" id="chat_messages_box">
                        <?php 
                        $reply_recipient_id = null;
                        $reply_subject = '';
                        while ($msg = $active_messages->fetch_assoc()): 
                            $is_outgoing = ($msg['sender_id'] == $user_id);
                            
                            // Determine correct recipient ID for reply
                            if (!$is_outgoing) {
                                $reply_recipient_id = $msg['sender_id'];
                            } else if (!$reply_recipient_id) {
                                $reply_recipient_id = $msg['receiver_id'];
                            }
                            
                            $reply_subject = (strpos($msg['subject'] ?? '', 'Re:') === 0) ? $msg['subject'] : 'Re: ' . ($msg['subject'] ?? 'Tax Inquiry');
                        ?>
                            <div class="message-bubble <?= $is_outgoing ? 'outgoing' : 'incoming' ?>">
                                <div style="font-size: 0.75rem; font-weight: 600; margin-bottom: 4px; opacity: 0.9;">
                                    <?= htmlspecialchars($msg['first_name'] . ' ' . $msg['last_name']) ?> (<?= htmlspecialchars($msg['role']) ?>)
                                </div>
                                <?= nl2br(htmlspecialchars($msg['message'])) ?>
                                <div class="message-meta">
                                    <?= date('M d, Y h:i A', strtotime($msg['created_at'])) ?>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>

                    <!-- REPLY FORM SUBMITTING TO handler/post_reply_message.php -->
                    <form action="handler/post_reply_message.php" method="POST" class="chat-input-area">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                        <input type="hidden" name="parent_id" value="<?= $selected_thread_id ?>">
                        <input type="hidden" name="receiver_id" value="<?= $reply_recipient_id ?>">
                        <input type="hidden" name="subject" value="<?= htmlspecialchars($reply_subject) ?>">
                        
                        <textarea name="message" class="chat-input" rows="2" placeholder="Type your response to the taxpayer..." required></textarea>
                        <button type="submit" class="btn-send">
                            <i class="fas fa-paper-plane"></i> Reply
                        </button>
                    </form>
                <?php else: ?>
                    <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100%; color: #64748b; gap: 10px;">
                        <i class="fas fa-comments" style="font-size: 2.5rem; color: #334155;"></i>
                        <p>Select an inquiry thread from the left menu to view and reply.</p>
                    </div>
                <?php endif; ?>
            </div>

        </div>

    </main>

</div>

<!-- 📩 NEW MESSAGE MODAL WITH AUTOCOMPLETE SEARCH -->
<div id="newMsgModal" class="modal-overlay">
    <div class="modal-content">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
            <h3 style="color:#f8fafc; font-size:1.1rem;"><i class="fas fa-paper-plane" style="color:#38bdf8;"></i> Send New Message</h3>
            <span onclick="closeNewMsgModal()" style="cursor:pointer; color:#64748b;"><i class="fas fa-times"></i></span>
        </div>

        <form action="handler/post_reply_message.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
            <input type="hidden" name="receiver_id" id="modal_recipient_id" required>

            <div class="search-wrapper" style="margin-bottom:15px;">
                <label style="color:#cbd5e1; font-size:0.85rem; display:block; margin-bottom:5px;">Recipient:</label>
                <input type="text" id="modal_user_search" placeholder="Type name or @username..." 
                       style="width:100%; background:rgba(15,23,42,0.8); border:1px solid rgba(255,255,255,0.15); border-radius:8px; padding:10px; color:#fff; outline:none;" autocomplete="off" required>
                <div id="modal_suggestions_box" class="suggestions-box"></div>
            </div>

            <div style="margin-bottom:15px;">
                <label style="color:#cbd5e1; font-size:0.85rem; display:block; margin-bottom:5px;">Subject:</label>
                <input type="text" name="subject" placeholder="Subject / Inquiry Title" 
                       style="width:100%; background:rgba(15,23,42,0.8); border:1px solid rgba(255,255,255,0.15); border-radius:8px; padding:10px; color:#fff; outline:none;" required>
            </div>

            <div style="margin-bottom:20px;">
                <label style="color:#cbd5e1; font-size:0.85rem; display:block; margin-bottom:5px;">Message:</label>
                <textarea name="message" rows="4" placeholder="Write your message here..." 
                          style="width:100%; background:rgba(15,23,42,0.8); border:1px solid rgba(255,255,255,0.15); border-radius:8px; padding:10px; color:#fff; outline:none;" required></textarea>
            </div>

            <button type="submit" class="btn-send" style="width:100%; padding:12px;">Send Message</button>
        </form>
    </div>
</div>

<script>
// Load users payload from PHP
const usersData = <?= json_encode($users_list); ?>;

// Elements for New Message Modal
const modal = document.getElementById('newMsgModal');
const modalSearchInput = document.getElementById('modal_user_search');
const modalRecipientId = document.getElementById('modal_recipient_id');
const modalSuggestionsBox = document.getElementById('modal_suggestions_box');

function openNewMsgModal() {
    modal.style.display = 'flex';
}

function closeNewMsgModal() {
    modal.style.display = 'none';
    modalSearchInput.value = '';
    modalRecipientId.value = '';
    modalSuggestionsBox.style.display = 'none';
}

// 🔍 Autocomplete user recommendations
modalSearchInput.addEventListener('input', function(e) {
    modalRecipientId.value = '';
    const query = e.target.value.toLowerCase().trim().replace(/^@/, '');
    modalSuggestionsBox.innerHTML = '';

    if (!query) {
        modalSuggestionsBox.style.display = 'none';
        return;
    }

    const matches = usersData.filter(user => {
        const firstName = (user.first_name || '').toLowerCase();
        const lastName = (user.last_name || '').toLowerCase();
        const fullName = `${firstName} ${lastName}`.trim();
        const username = (user.username || '').toLowerCase();

        return fullName.includes(query) || username.includes(query);
    });

    if (matches.length === 0) {
        modalSuggestionsBox.innerHTML = '<div class="suggestion-item" style="color:#64748b;">No user found</div>';
        modalSuggestionsBox.style.display = 'block';
        return;
    }

    matches.forEach(user => {
        const item = document.createElement('div');
        item.className = 'suggestion-item';
        item.innerHTML = `
            <span><strong>${user.first_name} ${user.last_name}</strong></span>
            <span style="font-size:0.75rem; color:#64748b;">@${user.username}</span>
        `;
        item.addEventListener('click', () => {
            modalSearchInput.value = `${user.first_name} ${user.last_name} (@${user.username})`;
            modalRecipientId.value = user.id;
            modalSuggestionsBox.style.display = 'none';
        });
        modalSuggestionsBox.appendChild(item);
    });

    modalSuggestionsBox.style.display = 'block';
});

// Hide dropdown when clicking outside
document.addEventListener('click', function(e) {
    if (!e.target.closest('.search-wrapper')) {
        modalSuggestionsBox.style.display = 'none';
    }
});

// 🔍 Thread List Real-time Search Filter
const threadFilterInput = document.getElementById('thread_filter_input');
if (threadFilterInput) {
    threadFilterInput.addEventListener('input', function(e) {
        const query = e.target.value.toLowerCase().trim();
        const items = document.querySelectorAll('#thread_items_container .thread-item');

        items.forEach(item => {
            const name = item.getAttribute('data-name') || '';
            const subject = item.getAttribute('data-subject') || '';

            if (name.includes(query) || subject.includes(query)) {
                item.style.display = 'block';
            } else {
                item.style.display = 'none';
            }
        });
    });
}

// Auto-scroll chat box to latest message on load
const chatBox = document.getElementById('chat_messages_box');
if (chatBox) {
    chatBox.scrollTop = chatBox.scrollHeight;
}
</script>

</body>
</html>