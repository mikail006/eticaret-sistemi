<?php
// Destek talebi oluşturma
function createSupportTicket($pdo, $student_id, $subject, $priority = 'medium') {
    try {
        $stmt = $pdo->prepare("INSERT INTO support_tickets (student_id, subject, priority) VALUES (?, ?, ?)");
        $stmt->execute([$student_id, $subject, $priority]);
        return $pdo->lastInsertId();
    } catch (Exception $e) {
        return false;
    }
}

// Destek mesajı gönderme
function sendSupportMessage($pdo, $ticket_id, $sender_type, $sender_id, $message) {
    try {
        $stmt = $pdo->prepare("INSERT INTO support_messages (ticket_id, sender_type, sender_id, message) VALUES (?, ?, ?, ?)");
        $stmt->execute([$ticket_id, $sender_type, $sender_id, $message]);
        return true;
    } catch (Exception $e) {
        return false;
    }
}

// Destek talebini kapatma
function closeSupportTicket($pdo, $ticket_id) {
    try {
        $stmt = $pdo->prepare("UPDATE support_tickets SET status = 'closed', updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->execute([$ticket_id]);
        return true;
    } catch (Exception $e) {
        return false;
    }
}

// Öğrencinin destek taleplerini getir
function getStudentTickets($pdo, $student_id) {
    $stmt = $pdo->prepare("
        SELECT st.*, 
               COUNT(sm.id) as message_count,
               MAX(sm.created_at) as last_message_time
        FROM support_tickets st 
        LEFT JOIN support_messages sm ON st.id = sm.ticket_id 
        WHERE st.student_id = ? 
        GROUP BY st.id 
        ORDER BY st.updated_at DESC
    ");
    $stmt->execute([$student_id]);
    return $stmt->fetchAll();
}

// Tüm destek taleplerini getir (admin için)
function getAllTickets($pdo) {
    $stmt = $pdo->query("
        SELECT st.*, s.full_name as student_name, s.class as student_class,
               COUNT(sm.id) as message_count,
               MAX(sm.created_at) as last_message_time
        FROM support_tickets st 
        JOIN students s ON st.student_id = s.id
        LEFT JOIN support_messages sm ON st.id = sm.ticket_id 
        GROUP BY st.id 
        ORDER BY st.updated_at DESC
    ");
    return $stmt->fetchAll();
}

// Destek talebinin mesajlarını getir
function getTicketMessages($pdo, $ticket_id) {
    $stmt = $pdo->prepare("
        SELECT sm.*, 
               CASE 
                   WHEN sm.sender_type = 'student' THEN s.full_name
                   WHEN sm.sender_type = 'admin' THEN 'Admin'
               END as sender_name
        FROM support_messages sm
        LEFT JOIN students s ON sm.sender_type = 'student' AND sm.sender_id = s.id
        WHERE sm.ticket_id = ? 
        ORDER BY sm.created_at ASC
    ");
    $stmt->execute([$ticket_id]);
    return $stmt->fetchAll();
}

// Yeni destek talebi bildirimi
function notifyNewSupportTicket($pdo, $ticket_id, $student_name, $subject) {
    $title = "Yeni destek talebi";
    $message = "{$student_name} tarafından yeni destek talebi: {$subject}";
    createNotification($pdo, 'admin', 1, $title, $message, 'support');
}

// Destek yanıtı bildirimi
function notifySuppo

// Destek yanıtı bildirimi
function notifySupportReply($pdo, $ticket_id, $student_id, $subject) {
    $title = "Destek yanıtı aldınız";
    $message = "#{$ticket_id} numaralı destek talebinize yanıt verildi: {$subject}";
    createNotification($pdo, 'student', $student_id, $title, $message, 'support');
}
?>
