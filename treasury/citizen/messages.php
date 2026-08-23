<?php
require_once '../../config/security.php';
include '../../config/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set security headers
setSecurityHeaders();

//  SECURITY GUARD: Only CITIZEN role allowed
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'CITIZEN') {
    logSecurityEvent('unauthorized_access', $_SESSION['id'] ?? null, ['endpoint' => 'citizen_messages']);
    header("Location: /login.php?unauthorized=1");
    exit();
}

$user_id = $_SESSION['user_id'];
$selected_thread_id = isset($_GET['thread_id']) ? intval($_GET['thread_id']) : null;

// Query inquiry threads for logged-in citizen
$threads_query = "
    SELECT m.id, m.sender_id, m.receiver_id, m.subject, m.message, m.created_at, m.status,
           u.first_name, u.last_name, u.role
    FROM messages m
    LEFT JOIN users u ON u.id = IF(m.sender_id = ?, m.receiver_id, m.sender_id)
    WHERE (m.sender_id = ? OR m.receiver_id = ?) 
      AND (m.parent_id IS NULL OR m.parent_id = 0)
    ORDER BY m.created_at DESC
";

$stmt = $conn->prepare($threads_query);
$stmt->bind_param("iii", $user_id, $user_id, $user_id);
$stmt->execute();
$threads = $stmt->get_result();

// Active thread details
$active_messages = false;
if ($selected_thread_id) {
    // Mark Treasury's messages as READ
    $mark_read = $conn->prepare("UPDATE messages SET status = 'READ' WHERE (id = ? OR parent_id = ?) AND receiver_id = ?");
    $mark_read->bind_param("iii", $selected_thread_id, $selected_thread_id, $user_id);
    $mark_read->execute();
    $mark_read->close();

    // Retrieve active thread history
    $msg_stmt = $conn->prepare("
        SELECT m.*, u.first_name, u.last_name, u.role 
        FROM messages m
        JOIN users u ON m.sender_id = u.id
        WHERE m.id = ? OR m.parent_id = ?
        ORDER BY m.created_at ASC
    ");
    $msg_stmt->bind_param("ii", $selected_thread_id, $selected_thread_id);
    $msg_stmt->execute();
    $active_messages = $msg_stmt->get_result();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Citizen Messages & Support</title>

<!-- Font Awesome 6 CDN -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<!-- Google Fonts -->
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: 'Poppins', sans-serif; background: #0f172a; color: #f8fafc; padding: 24px; }

.header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 16px; }
.header h2 { font-size: 1.4rem; color: #f8fafc; display: flex; align-items: center; gap: 10px; }
.nav-links { display: flex; gap: 16px; align-items: center; }
.nav-link { color: #94a3b8; text-decoration: none; font-size: 0.9rem; font-weight: 500; transition: color 0.2s; }
.nav-link:hover, .nav-link.active { color: #38bdf8; }

.portal-grid { display: grid; grid-template-columns: 360px 1fr; gap: 24px; min-height: 580px; height: calc(100vh - 120px); }

@media (max-width: 900px) {
    .portal-grid { grid-template-columns: 1fr; height: auto; }
}

.panel { background: rgba(30, 41, 59, 0.7); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 14px; display: flex; flex-direction: column; overflow: hidden; }

/* INQUIRY FORM & THREAD LIST */
.panel-header { padding: 16px; border-bottom: 1px solid rgba(255, 255, 255, 0.1); background: rgba(15, 23, 42, 0.4); }
.inquiry-form { padding: 16px; border-bottom: 1px solid rgba(255,255,255,0.1); display: flex; flex-direction: column; gap: 10px; }
.form-input, .form-textarea { width: 100%; background: rgba(15, 23, 42, 0.8); border: 1px solid rgba(255,255,255,0.15); border-radius: 8px; padding: 10px; color: #fff; font-family: inherit; font-size: 0.85rem; outline: none; }
.form-input:focus, .form-textarea:focus { border-color: #38bdf8; }

.btn-primary { background: #38bdf8; color: #0f172a; border: none; padding: 10px 16px; border-radius: 8px; font-weight: 600; cursor: pointer; transition: background 0.2s; font-size: 0.85rem; }
.btn-primary:hover { background: #0284c7; color: #fff; }

.thread-list { overflow-y: auto; flex-grow: 1; }
.thread-card { padding: 14px 16px; border-bottom: 1px solid rgba(255,255,255,0.05); text-decoration: none; color: inherit; display: block; transition: background 0.2s; }
.thread-card:hover, .thread-card.active { background: rgba(56, 189, 248, 0.12); border-left: 3px solid #38bdf8; }

.badge-new { background: rgba(239, 68, 68, 0.2); color: #f87171; font-size: 0.65rem; padding: 2px 6px; border-radius: 4px; font-weight: 600; }

/* CONVERSATION CHAT */
.chat-messages { padding: 20px; overflow-y: auto; flex-grow: 1; display: flex; flex-direction: column; gap: 14px; }
.bubble { max-width: 75%; padding: 12px 16px; border-radius: 12px; font-size: 0.875rem; line-height: 1.4; }
.bubble.me { align-self: flex-end; background: #0284c7; color: #fff; border-bottom-right-radius: 2px; }
.bubble.them { align-self: flex-start; background: rgba(51, 65, 85, 0.8); color: #f1f5f9; border-bottom-left-radius: 2px; }

.bubble-meta { font-size: 0.68rem; opacity: 0.7; margin-top: 4px; text-align: right; }
.chat-footer { padding: 16px; border-top: 1px solid rgba(255,255,255,0.1); display: flex; gap: 10px; background: rgba(15,23,42,0.4); }
</style>
</head>
<body>

<div class="header">
    <h2><i class="fas fa-envelope" style="color: #38bdf8;"></i> Citizen Support & Messages</h2>
    <div class="nav-links">
        <a href="dashboard.php" class="nav-link"><i class="fas fa-chart-line"></i> Dashboard</a>
        <a href="messages.php" class="nav-link active"><i class="fas fa-comments"></i> Messages</a>
        <a href="../../logout.php" class="nav-link" style="color: #f87171;"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
</div>

<?php if (isset($_SESSION['success'])): ?>
    <div style="background: rgba(34, 197, 94, 0.2); border: 1px solid #22c55e; color: #4ade80; padding: 12px; border-radius: 8px; margin-bottom: 20px; font-size: 0.85rem;">
        <i class="fas fa-check-circle"></i> <?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
    </div>
<?php endif; ?>

<div class="portal-grid">

    <!-- LEFT PANEL: NEW INQUIRY & HISTORY -->
    <div class="panel">
        <div class="panel-header">
            <h3 style="font-size: 0.95rem; font-weight: 600;"><i class="fas fa-pen-to-square" style="color: #38bdf8;"></i> Submit New Inquiry</h3>
        </div>

        <form action="../handler/post_reply_message.php" method="POST" class="inquiry-form">
            <input type="text" name="subject" class="form-input" placeholder="Inquiry Subject (e.g. Balance Question)" required>
            <textarea name="message" class="form-textarea" rows="3" placeholder="State your inquiry or dispute detail..." required></textarea>
            <button type="submit" class="btn-primary"><i class="fas fa-paper-plane"></i> Send to Treasury</button>
        </form>

        <div class="panel-header" style="border-top: 1px solid rgba(255,255,255,0.1);">
            <h3 style="font-size: 0.9rem; font-weight: 600;"><i class="fas fa-history" style="color: #38bdf8;"></i> My Inquiries</h3>
        </div>

        <div class="thread-list">
            <?php if ($threads && $threads->num_rows > 0): ?>
                <?php while ($t = $threads->fetch_assoc()): ?>
                    <a href="messages.php?thread_id=<?= $t['id'] ?>" class="thread-card <?= $selected_thread_id == $t['id'] ? 'active' : '' ?>">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                            <span style="font-size: 0.85rem; font-weight: 600; color: #38bdf8;"><?= htmlspecialchars($t['subject']) ?></span>
                            <span style="font-size: 0.7rem; color: #64748b;"><?= date('M d', strtotime($t['created_at'])) ?></span>
                        </div>
                        <div style="font-size: 0.78rem; color: #94a3b8; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?= htmlspecialchars($t['message']) ?></div>
                        <?php if ($t['status'] === 'UNREAD' && $t['receiver_id'] == $user_id): ?>
                            <span class="badge-new" style="margin-top: 6px; display: inline-block;">NEW REPLY</span>
                        <?php endif; ?>
                    </a>
                <?php endwhile; ?>
            <?php else: ?>
                <div style="padding: 24px; text-align: center; color: #64748b; font-size: 0.85rem;">
                    No inquiries submitted yet.
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- RIGHT PANEL: CHAT DETAIL VIEW -->
    <div class="panel">
        <?php if ($selected_thread_id && $active_messages && $active_messages->num_rows > 0): ?>
            <div class="chat-messages" id="chat_messages_box">
                <?php 
                $reply_recipient_id = null;
                $reply_subject = 'Re: Support Request';

                while ($m = $active_messages->fetch_assoc()): 
                    $is_me = ($m['sender_id'] == $user_id);
                    
                    if (!$is_me) {
                        $reply_recipient_id = $m['sender_id'];
                    } else if (!$reply_recipient_id) {
                        $reply_recipient_id = $m['receiver_id'];
                    }
                    if (!empty($m['subject'])) {
                        $reply_subject = (strpos($m['subject'], 'Re:') === 0) ? $m['subject'] : 'Re: ' . $m['subject'];
                    }
                ?>
                    <div class="bubble <?= $is_me ? 'me' : 'them' ?>">
                        <div style="font-size: 0.72rem; font-weight: 600; margin-bottom: 2px; opacity: 0.8;">
                            <?= htmlspecialchars($m['first_name'] . ' ' . $m['last_name']) ?> (<?= htmlspecialchars($m['role']) ?>)
                        </div>
                        <?= nl2br(htmlspecialchars($m['message'])) ?>
                        <div class="bubble-meta"><?= date('M d, Y h:i A', strtotime($m['created_at'])) ?></div>
                    </div>
                <?php endwhile; ?>
            </div>

            <form action="../handler/post_reply_message.php" method="POST" class="chat-footer">
                <input type="hidden" name="parent_id" value="<?= $selected_thread_id ?>">
                <input type="hidden" name="receiver_id" value="<?= $reply_recipient_id ?>">
                <input type="hidden" name="subject" value="<?= htmlspecialchars($reply_subject) ?>">
                
                <input type="text" name="message" class="form-input" placeholder="Type your response..." required autocomplete="off">
                <button type="submit" class="btn-primary" style="white-space: nowrap;"><i class="fas fa-paper-plane"></i> Reply</button>
            </form>

        <?php else: ?>
            <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100%; color: #64748b; gap: 10px;">
                <i class="fas fa-comments" style="font-size: 2.5rem; color: #334155;"></i>
                <p style="font-size: 0.9rem;">Select an inquiry thread from the left menu to view messages.</p>
            </div>
        <?php endif; ?>
    </div>

</div>

<script>
const chatBox = document.getElementById('chat_messages_box');
if (chatBox) {
    chatBox.scrollTop = chatBox.scrollHeight;
}
</script>

</body>
</html>