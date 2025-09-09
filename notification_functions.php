<?php
// Bildirim oluşturma fonksiyonu
function createNotification($pdo, $user_type, $user_id, $title, $message, $type) {
    try {
        $stmt = $pdo->prepare("INSERT INTO notifications (user_type, user_id, title, message, type) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$user_type, $user_id, $title, $message, $type]);
        return true;
    } catch (Exception $e) {
        return false;
    }
}

// Bildirim okuma işareti
function markNotificationRead($pdo, $notification_id) {
    try {
        $stmt = $pdo->prepare("UPDATE notifications SET read_status = TRUE WHERE id = ?");
        $stmt->execute([$notification_id]);
        return true;
    } catch (Exception $e) {
        return false;
    }
}

// Kullanıcının okunmamış bildirimlerini getir
function getUnreadNotifications($pdo, $user_type, $user_id) {
    $stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_type = ? AND user_id = ? AND read_status = FALSE ORDER BY created_at DESC");
    $stmt->execute([$user_type, $user_id]);
    return $stmt->fetchAll();
}

// Kullanıcının tüm bildirimlerini getir
function getAllNotifications($pdo, $user_type, $user_id, $limit = 20) {
    $stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_type = ? AND user_id = ? ORDER BY created_at DESC LIMIT ?");
    $stmt->execute([$user_type, $user_id, $limit]);
    return $stmt->fetchAll();
}

// Sipariş durum değişikliği bildirimi
function notifyOrderStatusChange($pdo, $order_id, $new_status) {
    $stmt = $pdo->prepare("SELECT student_id, order_number, student_name FROM orders WHERE id = ?");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch();
    
    if ($order) {
        $status_messages = [
            'onaylandi' => 'Siparişiniz onaylandı',
            'hazirlaniyor' => 'Siparişiniz hazırlanıyor',
            'kargoda' => 'Siparişiniz kargoya verildi',
            'teslim_edildi' => 'Siparişiniz teslim edildi',
            'iptal' => 'Siparişiniz iptal edildi'
        ];
        
        $title = $status_messages[$new_status] ?? 'Sipariş durumu güncellendi';
        $message = "#{$order['order_number']} numaralı siparişiniz: " . $title;
        
        createNotification($pdo, 'student', $order['student_id'], $title, $message, 'order');
    }
}

// Admin'e yeni sipariş bildirimi
function notifyNewOrder($pdo, $order_number, $customer_name) {
    $title = "Yeni sipariş alındı";
    $message = "{$customer_name} tarafından #{$order_number} numaralı yeni sipariş oluşturuldu.";
    
    // Tüm adminlere bildirim gönder (şimdilik admin_id = 1)
    createNotification($pdo, 'admin', 1, $title, $message, 'order');
}
?>
