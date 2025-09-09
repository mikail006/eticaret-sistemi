<?php
session_start();
include 'config.php';
include 'notification_functions.php';

if (!isset($_SESSION['student_id'])) {
    header('Location: student_login.php');
    exit;
}

$student_id = $_SESSION['student_id'];

$notification_msg = '';
$notification_type = '';

// Bildirim okuma işareti
if (isset($_GET['mark_read']) && is_numeric($_GET['mark_read'])) {
    markNotificationRead($pdo, $_GET['mark_read']);
    $notification_msg = "Bildirim okundu olarak işaretlendi";
    $notification_type = "success";
}

// Tüm bildirimleri okundu olarak işaretle
if (isset($_POST['mark_all_read'])) {
    $stmt = $pdo->prepare("UPDATE notifications SET read_status = TRUE WHERE user_type = 'student' AND user_id = ?");
    $stmt->execute([$student_id]);
    $notification_msg = "Tüm bildirimler okundu olarak işaretlendi";
    $notification_type = "success";
}

// Öğrenci bildirimlerini getir
$notifications = getAllNotifications($pdo, 'student', $student_id, 50);
$unread_count = count(getUnreadNotifications($pdo, 'student', $student_id));
?>
<!DOCTYPE html>
<html>
<head>
    <title>Bildirimlerim</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f6fa; }
        .container { max-width: 1000px; margin: 0 auto; padding: 20px; }
        
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 15px; margin-bottom: 30px; box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3); display: flex; justify-content: space-between; align-items: center; }
        .header h1 { font-size: 28px; }
        .header-buttons { display: flex; gap: 15px; }
        .btn { padding: 12px 24px; border: none; border-radius: 25px; text-decoration: none; font-weight: bold; transition: all 0.3s; cursor: pointer; display: inline-block; text-align: center; }
        .btn-primary { background: rgba(255,255,255,0.9); color: #667eea; }
        .btn-primary:hover { background: white; transform: translateY(-2px); }
        .btn-secondary { background: rgba(255,255,255,0.2); color: white; }
        .btn-secondary:hover { background: rgba(255,255,255,0.3); }
        
        .notifications-section { background: white; border-radius: 15px; padding: 25px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
        .section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
        .section-header h2 { color: #2d3748; }
        .unread-badge { background: #e53e3e; color: white; padding: 4px 12px; border-radius: 20px; font-size: 14px; margin-left: 10px; }
        
        .notification-item { border: 2px solid #e2e8f0; border-radius: 10px; padding: 20px; margin-bottom: 15px; transition: all 0.3s; position: relative; }
        .notification-item:hover { border-color: #667eea; }
        .notification-item.unread { background: #f0f4ff; border-color: #667eea; }
        .notification-item.unread::before { content: '●'; color: #667eea; position: absolute; left: 10px; top: 15px; font-size: 12px; }
        
        .notification-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
        .notification-title { font-weight: 600; color: #2d3748; font-size: 16px; }
        .notification-time { color: #718096; font-size: 14px; }
        .notification-message { color: #4a5568; line-height: 1.6; margin-bottom: 15px; }
        
        .notification-type { padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; text-transform: uppercase; }
        .type-order { background: #e6fffa; color: #2c7a7b; }
        .type-product { background: #f0f4ff; color: #5a67d8; }
        .type-system { background: #f0fff4; color: #22543d; }
        .type-support { background: #fef5e7; color: #d69e2e; }
        
        .notification-actions { display: flex; gap: 10px; align-items: center; }
        .mark-read-btn { background: #4299e1; color: white; padding: 6px 12px; border: none; border-radius: 6px; cursor: pointer; font-size: 12px; text-decoration: none; }
        .mark-read-btn:hover { background: #3182ce; }
        
        .mark-all-btn { background: #48bb78; color: white; padding: 12px 24px; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; }
        .mark-all-btn:hover { background: #38a169; }
        
        .empty-state { text-align: center; padding: 60px; color: #718096; }
        .empty-state h3 { margin-bottom: 10px; }
        
        .notification { position: fixed; bottom: 100px; right: 30px; padding: 15px 25px; border-radius: 10px; color: white; font-weight: 600; z-index: 1000; transform: translateX(400px); transition: transform 0.3s; }
        .notification.success { background: #48bb78; }
        .notification.show { transform: translateX(0); }
        
        @media (max-width: 768px) {
            .container { padding: 10px; }
            .header { flex-direction: column; gap: 15px; text-align: center; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Bildirimlerim</h1>
            <div class="header-buttons">
                <a href="index.php" class="btn btn-secondary">Ana Sayfa</a>
                <a href="my_orders.php" class="btn btn-primary">Siparişlerim</a>
            </div>
        </div>
        
        <div class="notifications-section">
            <div class="section-header">
                <h2>
                    Bildirimler (<?= count($notifications) ?>)
                    <?php if ($unread_count > 0): ?>
                        <span class="unread-badge"><?= $unread_count ?> Okunmamış</span>
                    <?php endif; ?>
                </h2>
                <?php if ($unread_count > 0): ?>
                    <form method="POST" style="display: inline;">
                        <button type="submit" name="mark_all_read" class="mark-all-btn">Tümünü Okundu İşaretle</button>
                    </form>
                <?php endif; ?>
            </div>
            
            <?php if (count($notifications) > 0): ?>
                <?php foreach ($notifications as $notif): ?>
                    <div class="notification-item <?= !$notif['read_status'] ? 'unread' : '' ?>">
                        <div class="notification-header">
                            <div class="notification-title"><?= htmlspecialchars($notif['title']) ?></div>
                            <div class="notification-time"><?= date('d.m.Y H:i', strtotime($notif['created_at'])) ?></div>
                        </div>
                        
                        <div class="notification-message">
                            <?= htmlspecialchars($notif['message']) ?>
                        </div>
                        
                        <div class="notification-actions">
                            <span class="notification-type type-<?= $notif['type'] ?>">
                                <?php
                                $type_labels = [
                                    'order' => 'Sipariş',
                                    'product' => 'Ürün',
                                    'system' => 'Sistem',
                                    'support' => 'Destek'
                                ];
                                echo $type_labels[$notif['type']] ?? $notif['type'];
                                ?>
                            </span>
                            
                            <?php if (!$notif['read_status']): ?>
                                <a href="student_notifications.php?mark_read=<?= $notif['id'] ?>" class="mark-read-btn">Okundu İşaretle</a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <h3>Henüz bildirim yok</h3>
                    <p>Sipariş durumu güncellemeleri burada görünecek.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <?php if ($notification_msg): ?>
        <div class="notification <?= $notification_type ?>" id="notification">
            <?= htmlspecialchars($notification_msg) ?>
        </div>
        <script>
            const notification = document.getElementById('notification');
            setTimeout(() => notification.classList.add('show'), 100);
            setTimeout(() => notification.classList.remove('show'), 3000);
            
            if (window.history.replaceState) {
                window.history.replaceState(null, null, window.location.pathname);
            }
        </script>
    <?php endif; ?>
</body>
</html>
