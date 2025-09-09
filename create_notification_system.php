<?php
include 'config.php';

try {
    // Bildirimler tablosu
    $pdo->exec("CREATE TABLE notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_type ENUM('admin', 'student') NOT NULL,
        user_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        type ENUM('order', 'product', 'system', 'support') NOT NULL,
        read_status BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Destek talepleri tablosu
    $pdo->exec("CREATE TABLE support_tickets (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id INT NOT NULL,
        subject VARCHAR(255) NOT NULL,
        status ENUM('open', 'in_progress', 'closed') DEFAULT 'open',
        priority ENUM('low', 'medium', 'high') DEFAULT 'medium',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
    )");
    
    // Destek mesajları tablosu
    $pdo->exec("CREATE TABLE support_messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ticket_id INT NOT NULL,
        sender_type ENUM('admin', 'student') NOT NULL,
        sender_id INT NOT NULL,
        message TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (ticket_id) REFERENCES support_tickets(id) ON DELETE CASCADE
    )");
    
    echo "✅ Bildirim ve destek sistemi tabloları oluşturuldu!<br>";
    echo "✅ notifications tablosu eklendi<br>";
    echo "✅ support_tickets tablosu eklendi<br>";
    echo "✅ support_messages tablosu eklendi<br>";
    echo "<br><a href='admin_panel.php'>Admin Panel'e Dön</a>";
    
} catch (Exception $e) {
    echo "Hata: " . $e->getMessage();
}
?>
