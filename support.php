<?php
session_start();
include 'config.php';
include 'notification_functions.php';
include 'support_functions.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit;
}

$notification = '';
$notification_type = '';

// Destek talebini kapatma
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['close_ticket'])) {
    $ticket_id = $_POST['ticket_id'];
    
    if (closeSupportTicket($pdo, $ticket_id)) {
        $notification = "Destek talebi kapatıldı";
        $notification_type = "success";
    } else {
        $notification = "Destek talebi kapatılamadı";
        $notification_type = "error";
    }
}

// Tüm destek taleplerini getir
$tickets = getAllTickets($pdo);

// İstatistikler
$stats = [
    'total_tickets' => count($tickets),
    'open_tickets' => count(array_filter($tickets, fn($t) => $t['status'] === 'open')),
    'in_progress_tickets' => count(array_filter($tickets, fn($t) => $t['status'] === 'in_progress')),
    'closed_tickets' => count(array_filter($tickets, fn($t) => $t['status'] === 'closed'))
];
?>
<!DOCTYPE html>
<html>
<head>
    <title>Destek Yönetimi</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f6fa; }
        .container { max-width: 1400px; margin: 0 auto; padding: 20px; }
        
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 15px; margin-bottom: 30px; box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3); display: flex; justify-content: space-between; align-items: center; }
        .header h1 { font-size: 28px; display: flex; align-items: center; gap: 15px; }
        .header-icon { font-size: 32px; }
        .header-buttons { display: flex; gap: 15px; }
        .btn { padding: 12px 24px; border: none; border-radius: 25px; text-decoration: none; font-weight: bold; transition: all 0.3s; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; }
        .btn-primary { background: rgba(255,255,255,0.9); color: #667eea; }
        .btn-primary:hover { background: white; transform: translateY(-2px); }
        .btn-secondary { background: rgba(255,255,255,0.2); color: white; }
        .btn-secondary:hover { background: rgba(255,255,255,0.3); }
        .btn-danger { background: #e53e3e; color: white; padding: 6px 12px; font-size: 12px; }
        .btn-danger:hover { background: #c53030; }
        
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: white; padding: 25px; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); text-align: center; }
        .stat-number { font-size: 36px; font-weight: bold; margin-bottom: 10px; }
        .stat-label { color: #718096; font-weight: 600; }
        .stat-total { color: #667eea; }
        .stat-open { color: #48bb78; }
        .stat-progress { color: #4299e1; }
        .stat-closed { color: #a0aec0; }
        
        .tickets-section { background: white; border-radius: 15px; padding: 25px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
        .section-header { margin-bottom: 25px; }
        .section-header h2 { color: #2d3748; display: flex; align-items: center; gap: 10px; }
        
        .filter-tabs { display: flex; gap: 10px; margin-bottom: 20px; }
        .filter-tab { padding: 10px 20px; border: 2px solid #e2e8f0; border-radius: 25px; cursor: pointer; transition: all 0.3s; background: white; }
        .filter-tab:hover { border-color: #667eea; }
        .filter-tab.active { background: #667eea; color: white; border-color: #667eea; }
        
        .ticket-item { border: 2px solid #e2e8f0; border-radius: 10px; padding: 20px; margin-bottom: 15px; transition: all 0.3s; cursor: pointer; }
        .ticket-item:hover { border-color: #667eea; transform: translateY(-2px); }
        .ticket-item.open { border-left: 5px solid #48bb78; }
        .ticket-item.in_progress { border-left: 5px solid #4299e1; }
        .ticket-item.closed { border-left: 5px solid #a0aec0; }
        
        .ticket-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 15px; }
        .ticket-info { flex: 1; }
        .ticket-title { font-weight: 600; color: #2d3748; font-size: 18px; margin-bottom: 8px; }
        .ticket-student { color: #667eea; font-weight: 600; margin-bottom: 5px; }
        .ticket-time { color: #718096; font-size: 14px; }
        
        .ticket-actions { display: flex; gap: 10px; align-items: center; }
        
        .ticket-meta { display: flex; gap: 15px; align-items: center; margin-bottom: 10px; }
        .status-badge { padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; text-transform: uppercase; }
        .status-open { background: #c6f6d5; color: #22543d; }
        .status-in_progress { background: #bee3f8; color: #2a4365; }
        .status-closed { background: #e2e8f0; color: #4a5568; }
        
        .priority-badge { padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
        .priority-high-badge { background: #fed7d7; color: #742a2a; }
        .priority-medium-badge { background: #faf089; color: #744210; }
        .priority-low-badge { background: #c6f6d5; color: #22543d; }
        
        .message-count { color: #667eea; font-weight: 600; display: flex; align-items: center; gap: 5px; }
        
        .empty-state { text-align: center; padding: 60px; color: #718096; }
        .empty-state-icon { font-size: 48px; margin-bottom: 15px; }
        
        .notification { position: fixed; bottom: 100px; right: 30px; padding: 15px 25px; border-radius: 10px; color: white; font-weight: 600; z-index: 1000; transform: translateX(400px); transition: transform 0.3s; }
        .notification.success { background: #48bb78; }
        .notification.error { background: #e53e3e; }
        .notification.show { transform: translateX(0); }
        
        @media (max-width: 768px) {
            .container { padding: 10px; }
            .header { flex-direction: column; gap: 15px; text-align: center; }
            .stats-grid { grid-template-columns: 1fr; }
            .filter-tabs { flex-wrap: wrap; }
            .ticket-header { flex-direction: column; gap: 15px; text-align: center; }
            .ticket-meta { justify-content: center; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>
                <span class="header-icon">🎧</span>
                Destek Yönetimi
            </h1>
            <div class="header-buttons">
                <a href="notifications.php" class="btn btn-primary">
                    🔔 Bildirimler
                </a>
                <a href="admin_orders.php" class="btn btn-primary">
                    📦 Siparişler
                </a>
                <a href="admin_panel.php" class="btn btn-secondary">
                    🏠 Ana Panel
                </a>
            </div>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number stat-total"><?= $stats['total_tickets'] ?></div>
                <div class="stat-label">📊 Toplam Talep</div>
            </div>
            <div class="stat-card">
                <div class="stat-number stat-open"><?= $stats['open_tickets'] ?></div>
                <div class="stat-label">🟢 Açık Talepler</div>
            </div>
            <div class="stat-card">
                <div class="stat-number stat-progress"><?= $stats['in_progress_tickets'] ?></div>
                <div class="stat-label">🔄 İşlemde</div>
            </div>
            <div class="stat-card">
                <div class="stat-number stat-closed"><?= $stats['closed_tickets'] ?></div>
                <div class="stat-label">✅ Kapatılan</div>
            </div>
        </div>
        
        <div class="tickets-section">
            <div class="section-header">
                <h2>
                    <span>💬</span>
                    Destek Talepleri (<?= count($tickets) ?>)
                </h2>
            </div>
            
            <div class="filter-tabs">
                <div class="filter-tab active" onclick="filterTickets('all')">🔍 Tümü</div>
                <div class="filter-tab" onclick="filterTickets('open')">🟢 Açık</div>
                <div class="filter-tab" onclick="filterTickets('in_progress')">🔄 İşlemde</div>
                <div class="filter-tab" onclick="filterTickets('closed')">✅ Kapalı</div>
            </div>
            
            <?php if (count($tickets) > 0): ?>
                <?php foreach ($tickets as $ticket): ?>
                    <div class="ticket-item <?= $ticket['status'] ?>" data-status="<?= $ticket['status'] ?>">
                        <div class="ticket-header">
                            <div class="ticket-info">
                                <div class="ticket-title" onclick="openTicket(<?= $ticket['id'] ?>)">
                                    <?= htmlspecialchars($ticket['subject']) ?>
                                </div>
                                <div class="ticket-student">
                                    👤 <?= htmlspecialchars($ticket['student_name']) ?> - <?= htmlspecialchars($ticket['student_class']) ?>
                                </div>
                                <div class="ticket-time">
                                    🕒 <?= date('d.m.Y H:i', strtotime($ticket['created_at'])) ?>
                                    <?php if ($ticket['last_message_time']): ?>
                                        | Son mesaj: <?= date('d.m.Y H:i', strtotime($ticket['last_message_time'])) ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="ticket-actions">
                                <?php if ($ticket['status'] !== 'closed'): ?>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="ticket_id" value="<?= $ticket['id'] ?>">
                                        <button type="submit" name="close_ticket" class="btn btn-danger">
                                            ❌ Kapat
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
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
                            
                            <div class="message-count" onclick="openTicket(<?= $ticket['id'] ?>)">
                                💬 <?= $ticket['message_count'] ?> mesaj
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-state-icon">📭</div>
                    <h3>Henüz destek talebi yok</h3>
                    <p>Öğrenciler destek talebi oluşturduğunda burada görünecek.</p>
                </div>
            <?php endif; ?>
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
        
        function filterTickets(status) {
            // Tab aktifliği
            document.querySelectorAll('.filter-tab').forEach(tab => {
                tab.classList.remove('active');
            });
            event.target.classList.add('active');
            
            // Ticket filtreleme
            document.querySelectorAll('.ticket-item').forEach(ticket => {
                if (status === 'all' || ticket.dataset.status === status) {
                    ticket.style.display = 'block';
                } else {
                    ticket.style.display = 'none';
                }
            });
        }
    </script>
</body>
</html>
