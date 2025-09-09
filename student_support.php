<?php
session_start();
include 'config.php';
include 'notification_functions.php';
include 'support_functions.php';

if (!isset($_SESSION['student_id'])) {
    header('Location: student_login.php');
    exit;
}

$student_id = $_SESSION['student_id'];

$notification = '';
$notification_type = '';

// Yeni destek talebi oluşturma
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_ticket'])) {
    $subject = $_POST['subject'];
    $message = $_POST['message'];
    $priority = $_POST['priority'];
    
    $ticket_id = createSupportTicket($pdo, $student_id, $subject, $priority);
    
    if ($ticket_id) {
        sendSupportMessage($pdo, $ticket_id, 'student', $student_id, $message);
        
        // Admin'e bildirim gönder
        $stmt = $pdo->prepare("SELECT full_name FROM students WHERE id = ?");
        $stmt->execute([$student_id]);
        $student = $stmt->fetch();
        notifyNewSupportTicket($pdo, $ticket_id, $student['full_name'], $subject);
        
        $notification = "Destek talebiniz oluşturuldu";
        $notification_type = "success";
    } else {
        $notification = "Destek talebi oluşturulamadı";
        $notification_type = "error";
    }
}

// Öğrencinin destek taleplerini getir
$tickets = getStudentTickets($pdo, $student_id);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Destek</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f6fa; }
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 15px; margin-bottom: 30px; box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3); display: flex; justify-content: space-between; align-items: center; }
        .header h1 { font-size: 28px; display: flex; align-items: center; gap: 15px; }
        .header-icon { font-size: 32px; }
        .header-buttons { display: flex; gap: 15px; }
        .btn { padding: 12px 24px; border: none; border-radius: 25px; text-decoration: none; font-weight: bold; transition: all 0.3s; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; }
        .btn-primary { background: rgba(255,255,255,0.9); color: #667eea; }
        .btn-primary:hover { background: white; transform: translateY(-2px); }
        .btn-secondary { background: rgba(255,255,255,0.2); color: white; }
        .btn-secondary:hover { background: rgba(255,255,255,0.3); }
        .btn-success { background: #48bb78; color: white; }
        .btn-success:hover { background: #38a169; }
        
        .support-grid { display: grid; grid-template-columns: 1fr 2fr; gap: 30px; }
        .support-section { background: white; border-radius: 15px; padding: 25px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
        
        .form-section h3 { color: #2d3748; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
        .section-icon { font-size: 24px; }
        
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #2d3748; }
        .form-group input, .form-group textarea, .form-group select { width: 100%; padding: 12px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 16px; }
        .form-group input:focus, .form-group textarea:focus, .form-group select:focus { outline: none; border-color: #667eea; }
        
        .priority-select { background: white; cursor: pointer; }
        .priority-high { color: #e53e3e; }
        .priority-medium { color: #d69e2e; }
        .priority-low { color: #38a169; }
        
        .tickets-section h3 { color: #2d3748; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
        
        .ticket-item { border: 2px solid #e2e8f0; border-radius: 10px; padding: 20px; margin-bottom: 15px; transition: all 0.3s; cursor: pointer; }
        .ticket-item:hover { border-color: #667eea; transform: translateY(-2px); }
        .ticket-item.open { border-left: 5px solid #48bb78; }
        .ticket-item.in_progress { border-left: 5px solid #4299e1; }
        .ticket-item.closed { border-left: 5px solid #a0aec0; }
        
        .ticket-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
        .ticket-title { font-weight: 600; color: #2d3748; }
        .ticket-time { color: #718096; font-size: 14px; }
        
        .ticket-meta { display: flex; gap: 15px; align-items: center; }
        .status-badge { padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; text-transform: uppercase; }
        .status-open { background: #c6f6d5; color: #22543d; }
        .status-in_progress { background: #bee3f8; color: #2a4365; }
        .status-closed { background: #e2e8f0; color: #4a5568; }
        
        .priority-badge { padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
        .priority-high-badge { background: #fed7d7; color: #742a2a; }
        .priority-medium-badge { background: #faf089; color: #744210; }
        .priority-low-badge { background: #c6f6d5; color: #22543d; }
        
        .message-count { color: #667eea; font-weight: 600; display: flex; align-items: center; gap: 5px; }
        
        .empty-state { text-align: center; padding: 40px; color: #718096; }
        .empty-state-icon { font-size: 48px; margin-bottom: 15px; }
        
        .notification { position: fixed; bottom: 100px; right: 30px; padding: 15px 25px; border-radius: 10px; color: white; font-weight: 600; z-index: 1000; transform: translateX(400px); transition: transform 0.3s; }
        .notification.success { background: #48bb78; }
        .notification.error { background: #e53e3e; }
        .notification.show { transform: translateX(0); }
        
        @media (max-width: 768px) {
            .container { padding: 10px; }
            .header { flex-direction: column; gap: 15px; text-align: center; }
            .support-grid { grid-template-columns: 1fr; }
            .ticket-header { flex-direction: column; gap: 10px; text-align: center; }
            .ticket-meta { justify-content: center; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>
                <span class="header-icon">💬</span>
                Destek
            </h1>
            <div class="header-buttons">
                <a href="student_notifications.php" class="btn btn-primary">
                    🔔 Bildirimler
                </a>
                <a href="index.php" class="btn btn-secondary">
                    🏠 Ana Sayfa
                </a>
            </div>
        </div>
        
        <div class="support-grid">
            <div class="support-section form-section">
                <h3>
                    <span class="section-icon">✉️</span>
                    Yeni Destek Talebi
                </h3>
                
                <form method="POST">
                    <div class="form-group">
                        <label>Konu</label>
                        <input type="text" name="subject" placeholder="Sorununuzu kısaca özetleyin" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Öncelik</label>
                        <select name="priority" class="priority-select" required>
                            <option value="low" class="priority-low">🟢 Düşük - Genel sorular</option>
                            <option value="medium" class="priority-medium" selected>🟡 Orta - Sipariş sorunları</option>
                            <option value="high" class="priority-high">🔴 Yüksek - Acil durumlar</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Mesajınız</label>
                        <textarea name="message" rows="6" placeholder="Sorununuzu detaylı olarak açıklayın..." required></textarea>
                    </div>
                    
                    <button type="submit" name="create_ticket" class="btn btn-success" style="width: 100%;">
                        📤 Destek Talebi Gönder
                    </button>
                </form>
            </div>
            
            <div class="support-section tickets-section">
                <h3>
                    <span class="section-icon">📋</span>
                    Destek Taleplerim (<?= count($tickets) ?>)
                </h3>
                
                <?php if (count($tickets) > 0): ?>
                    <?php foreach ($tickets as $ticket): ?>
                        <div class="ticket-item <?= $ticket['status'] ?>" onclick="openTicket(<?= $ticket['id'] ?>)">
                            <div class="ticket-header">
                                <div class="ticket-title"><?= htmlspecialchars($ticket['subject']) ?></div>
                                <div class="ticket-time"><?= date('d.m.Y H:i', strtotime($ticket['created_at'])) ?></div>
                            </div>
                            
                            <div class="ticket-meta">
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
                                        'high' => '🔴 Yüksek',
                                        'medium' => '🟡 Orta',
                                        'low' => '🟢 Düşük'
                                    ];
                                    echo $priority_labels[$ticket['priority']] ?? $ticket['priority'];
                                    ?>
                                </span>
                                
                                <div class="message-count">
                                    💬 <?= $ticket['message_count'] ?> mesaj
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">📭</div>
                        <h4>Henüz destek talebiniz yok</h4>
                        <p>Sorularınız için yukarıdaki formu kullanabilirsiniz.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
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
        function openTicket(ticketId) {
            window.location.href = 'support_chat.php?ticket=' + ticketId;
        }
    </script>
</body>
</html>
