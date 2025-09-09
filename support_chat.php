<?php
session_start();
include 'config.php';
include 'notification_functions.php';
include 'support_functions.php';

// Admin veya öğrenci kontrolü
$is_admin = isset($_SESSION['admin_id']);
$is_student = isset($_SESSION['student_id']);

if (!$is_admin && !$is_student) {
    header('Location: student_login.php');
    exit;
}

$user_id = $is_admin ? $_SESSION['admin_id'] : $_SESSION['student_id'];
$user_type = $is_admin ? 'admin' : 'student';

// Ticket ID kontrolü
if (!isset($_GET['ticket']) || !is_numeric($_GET['ticket'])) {
    header('Location: ' . ($is_admin ? 'support.php' : 'student_support.php'));
    exit;
}

$ticket_id = $_GET['ticket'];

// Ticket bilgilerini al
$stmt = $pdo->prepare("
    SELECT st.*, s.full_name as student_name, s.class as student_class
    FROM support_tickets st 
    JOIN students s ON st.student_id = s.id 
    WHERE st.id = ?
");
$stmt->execute([$ticket_id]);
$ticket = $stmt->fetch();

if (!$ticket) {
    header('Location: ' . ($is_admin ? 'support.php' : 'student_support.php'));
    exit;
}

// Öğrenci sadece kendi ticketlarını görebilir
if ($is_student && $ticket['student_id'] != $user_id) {
    header('Location: student_support.php');
    exit;
}

$notification = '';
$notification_type = '';

// Mesaj gönderme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $message = trim($_POST['message']);
    
    if (!empty($message)) {
        if (sendSupportMessage($pdo, $ticket_id, $user_type, $user_id, $message)) {
            // Ticket durumunu güncelle
            if ($ticket['status'] === 'open' && $is_admin) {
                $stmt = $pdo->prepare("UPDATE support_tickets SET status = 'in_progress' WHERE id = ?");
                $stmt->execute([$ticket_id]);
            }
            
            // Karşı tarafa bildirim gönder
            if ($is_admin) {
                $title = "Destek yanıtı aldınız";
                $msg = "#{$ticket['id']} numaralı destek talebinize yanıt verildi.";
                createNotification($pdo, 'student', $ticket['student_id'], $title, $msg, 'support');
            } else {
                $title = "Destek talebine yeni mesaj";
                $msg = "{$ticket['student_name']} destek talebine yeni mesaj gönderdi.";
                createNotification($pdo, 'admin', 1, $title, $msg, 'support');
            }
            
            $notification = "Mesaj gönderildi";
            $notification_type = "success";
        } else {
            $notification = "Mesaj gönderilemedi";
            $notification_type = "error";
        }
    }
}

// Ticket durumu güncelleme (sadece admin)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status']) && $is_admin) {
    $new_status = $_POST['status'];
    
    $stmt = $pdo->prepare("UPDATE support_tickets SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
    $stmt->execute([$new_status, $ticket_id]);
    
    $notification = "Ticket durumu güncellendi";
    $notification_type = "success";
    
    // Ticket güncel bilgilerini al
    $stmt = $pdo->prepare("SELECT * FROM support_tickets WHERE id = ?");
    $stmt->execute([$ticket_id]);
    $ticket = array_merge($ticket, $stmt->fetch());
}

// Mesajları getir
$messages = getTicketMessages($pdo, $ticket_id);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Destek Sohbeti - #<?= $ticket['id'] ?></title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f6fa; height: 100vh; display: flex; flex-direction: column; }
        
        .chat-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header-content { max-width: 1200px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; }
        .ticket-info h1 { font-size: 24px; margin-bottom: 5px; display: flex; align-items: center; gap: 10px; }
        .ticket-details { color: rgba(255,255,255,0.9); font-size: 14px; }
        .header-buttons { display: flex; gap: 15px; }
        .btn { padding: 10px 20px; border: none; border-radius: 20px; text-decoration: none; font-weight: bold; transition: all 0.3s; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; }
        .btn-secondary { background: rgba(255,255,255,0.2); color: white; }
        .btn-secondary:hover { background: rgba(255,255,255,0.3); }
        .btn-primary { background: rgba(255,255,255,0.9); color: #667eea; }
        .btn-primary:hover { background: white; }
        
        .chat-container { flex: 1; display: flex; flex-direction: column; max-width: 1200px; margin: 0 auto; width: 100%; padding: 20px; }
        
        .ticket-status { background: white; padding: 15px; border-radius: 10px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .status-info { display: flex; gap: 15px; align-items: center; }
        .status-badge { padding: 6px 15px; border-radius: 20px; font-size: 14px; font-weight: 600; }
        .status-open { background: #c6f6d5; color: #22543d; }
        .status-in_progress { background: #bee3f8; color: #2a4365; }
        .status-closed { background: #e2e8f0; color: #4a5568; }
        .priority-badge { padding: 6px 15px; border-radius: 20px; font-size: 14px; font-weight: 600; }
        .priority-high-badge { background: #fed7d7; color: #742a2a; }
        .priority-medium-badge { background: #faf089; color: #744210; }
        .priority-low-badge { background: #c6f6d5; color: #22543d; }
        
        .status-controls select { padding: 8px 15px; border: 2px solid #e2e8f0; border-radius: 8px; background: white; }
        .update-btn { background: #4299e1; color: white; padding: 8px 15px; border: none; border-radius: 8px; cursor: pointer; margin-left: 10px; }
        .update-btn:hover { background: #3182ce; }
        
        .messages-container { flex: 1; background: white; border-radius: 15px; padding: 20px; margin-bottom: 20px; overflow-y: auto; min-height: 400px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        
        .message { margin-bottom: 20px; display: flex; gap: 15px; }
        .message.admin-message { flex-direction: row-reverse; }
        .message-avatar { width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 18px; flex-shrink: 0; }
        .student-avatar { background: #667eea; color: white; }
        .admin-avatar { background: #48bb78; color: white; }
        
        .message-content { max-width: 70%; }
        .message-header { display: flex; gap: 10px; align-items: center; margin-bottom: 5px; }
        .message-sender { font-weight: 600; color: #2d3748; }
        .message-time { color: #718096; font-size: 12px; }
        .message-bubble { padding: 15px; border-radius: 15px; line-height: 1.5; word-wrap: break-word; }
        .student-bubble { background: #f7fafc; border: 1px solid #e2e8f0; }
        .admin-bubble { background: #667eea; color: white; }
        
        .message-form { background: white; padding: 20px; border-radius: 15px; box-shadow: 0 -2px 10px rgba(0,0,0,0.05); }
        .form-container { display: flex; gap: 15px; align-items: flex-end; }
        .message-input { flex: 1; padding: 15px; border: 2px solid #e2e8f0; border-radius: 10px; resize: vertical; min-height: 60px; font-family: inherit; }
        .message-input:focus { outline: none; border-color: #667eea; }
        .send-btn { background: #667eea; color: white; padding: 15px 25px; border: none; border-radius: 10px; cursor: pointer; font-weight: 600; transition: all 0.3s; }
        .send-btn:hover { background: #5a67d8; transform: translateY(-2px); }
        .send-btn:disabled { background: #cbd5e0; cursor: not-allowed; transform: none; }
        
        .empty-messages { text-align: center; color: #718096; padding: 40px; }
        .empty-icon { font-size: 48px; margin-bottom: 15px; }
        
        .notification { position: fixed; bottom: 30px; right: 30px; padding: 15px 25px; border-radius: 10px; color: white; font-weight: 600; z-index: 1000; transform: translateX(400px); transition: transform 0.3s; }
        .notification.success { background: #48bb78; }
        .notification.error { background: #e53e3e; }
        .notification.show { transform: translateX(0); }
        
        @media (max-width: 768px) {
            .chat-container { padding: 10px; }
            .header-content { flex-direction: column; gap: 15px; text-align: center; }
            .ticket-status { flex-direction: column; gap: 15px; }
            .message-content { max-width: 90%; }
            .form-container { flex-direction: column; }
        }
    </style>
</head>
<body>
    <div class="chat-header">
        <div class="header-content">
            <div class="ticket-info">
                <h1>
                    💬 Destek Sohbeti #<?= $ticket['id'] ?>
                </h1>
                <div class="ticket-details">
                    📋 <?= htmlspecialchars($ticket['subject']) ?> | 
                    👤 <?= htmlspecialchars($ticket['student_name']) ?> - <?= htmlspecialchars($ticket['student_class']) ?> | 
                    🕒 <?= date('d.m.Y H:i', strtotime($ticket['created_at'])) ?>
                </div>
            </div>
            <div class="header-buttons">
                <?php if ($is_admin): ?>
                    <a href="support.php" class="btn btn-secondary">
                        ← Destek Listesi
                    </a>
                <?php else: ?>
                    <a href="student_support.php" class="btn btn-secondary">
                        ← Destek Taleplerim
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="chat-container">
        <div class="ticket-status">
            <div class="status-info">
                <span class="status-badge status-<?= $ticket['status'] ?>">
                    <?php
                    $status_labels = [
                        'open' => '🟢 Açık',
                        'in_progress' => '🔄 İşlemde',
                        'closed' => '✅ Kapalı'
                    ];
                    echo $status_labels[$ticket['status']] ?? $ticket['status'];
                    ?>
                </span>
                
                <span class="priority-badge priority-<?= $ticket['priority'] ?>-badge">
                    <?php
                    $priority_labels = [
                        'high' => '🔴 Yüksek Öncelik',
                        'medium' => '🟡 Orta Öncelik',
                        'low' => '🟢 Düşük Öncelik'
                    ];
                    echo $priority_labels[$ticket['priority']] ?? $ticket['priority'];
                    ?>
                </span>
            </div>
            
            <?php if ($is_admin && $ticket['status'] !== 'closed'): ?>
                <div class="status-controls">
                    <form method="POST" style="display: inline-flex; align-items: center; gap: 10px;">
                        <select name="status">
                            <option value="open" <?= $ticket['status'] === 'open' ? 'selected' : '' ?>>Açık</option>
                            <option value="in_progress" <?= $ticket['status'] === 'in_progress' ? 'selected' : '' ?>>İşlemde</option>
                            <option value="closed" <?= $ticket['status'] === 'closed' ? 'selected' : '' ?>>Kapalı</option>
                        </select>
                        <button type="submit" name="update_status" class="update-btn">
                            🔄 Güncelle
                        </button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="messages-container" id="messagesContainer">
            <?php if (count($messages) > 0): ?>
                <?php foreach ($messages as $message): ?>
                    <div class="message <?= $message['sender_type'] ?>-message">
                        <div class="message-avatar <?= $message['sender_type'] ?>-avatar">
                            <?= $message['sender_type'] === 'admin' ? '🎧' : '👤' ?>
                        </div>
                        <div class="message-content">
                            <div class="message-header">
                                <span class="message-sender"><?= htmlspecialchars($message['sender_name']) ?></span>
                                <span class="message-time"><?= date('d.m.Y H:i', strtotime($message['created_at'])) ?></span>
                            </div>
                            <div class="message-bubble <?= $message['sender_type'] ?>-bubble">
                                <?= nl2br(htmlspecialchars($message['message'])) ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-messages">
                    <div class="empty-icon">💬</div>
                    <h3>Henüz mesaj yok</h3>
                    <p>Sohbeti başlatmak için aşağıdan mesaj gönderin.</p>
                </div>
            <?php endif; ?>
        </div>
        
        <?php if ($ticket['status'] !== 'closed'): ?>
            <div class="message-form">
                <form method="POST" id="messageForm">
                    <div class="form-container">
                        <textarea name="message" class="message-input" placeholder="Mesajınızı yazın..." required rows="3"></textarea>
                        <button type="submit" name="send_message" class="send-btn">
                            📤 Gönder
                        </button>
                    </div>
                </form>
            </div>
        <?php else: ?>
            <div style="text-align: center; padding: 20px; color: #718096; background: white; border-radius: 15px;">
                ✅ Bu destek talebi kapatılmıştır. Yeni sorularınız için yeni talep oluşturabilirsiniz.
            </div>
        <?php endif; ?>
    </div>
    
    <?php if ($notification): ?>
        <div class="notification <?= $notification_type ?>" id="notification">
            <?= htmlspecialchars($notification) ?>
        </div>
        <script>
            const notification = document.getElementById('notification');
            setTimeout(() => notification.classList.add('show'), 100);
            setTimeout(() => notification.classList.remove('show'), 3000);
        </script>
    <?php endif; ?>
    
    <script>
        // Mesaj gönderme
        document.getElementById('messageForm').addEventListener('submit', function() {
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.textContent = '📤 Gönderiliyor...';
        });
        
        // Mesaj konteynırını en alta kaydır
        function scrollToBottom() {
            const container = document.getElementById('messagesContainer');
            container.scrollTop = container.scrollHeight;
        }
        
        // Sayfa yüklendiğinde en alta kaydır
        window.addEventListener('load', scrollToBottom);
        
        // Enter + Shift ile gönder
        document.querySelector('.message-input').addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && e.shiftKey) {
                e.preventDefault();
                document.getElementById('messageForm').submit();
            }
        });
        
        // Auto-refresh mesajlar (her 30 saniyede)
        setInterval(function() {
            if (document.hidden) return; // Sayfa görünür değilse refresh yapma
            
            fetch(window.location.href)
                .then(response => response.text())
                .then(html => {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    const newMessages = doc.getElementById('messagesContainer').innerHTML;
                    const currentMessages = document.getElementById('messagesContainer').innerHTML;
                    
                    if (newMessages !== currentMessages) {
                        document.getElementById('messagesContainer').innerHTML = newMessages;
                        scrollToBottom();
                    }
                })
                .catch(error => console.log('Auto-refresh error:', error));
        }, 30000);
    </script>
</body>
</html>
