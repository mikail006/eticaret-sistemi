<?php
include 'config.php';

try {
    // SEPET TABLOSU
    $pdo->exec("CREATE TABLE cart (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id INT NOT NULL,
        product_id INT NOT NULL,
        quantity INT DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
        UNIQUE KEY unique_cart_item (student_id, product_id)
    )");
    
    // SİPARİŞLER TABLOSU
    $pdo->exec("CREATE TABLE orders (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id INT NOT NULL,
        order_number VARCHAR(50) UNIQUE NOT NULL,
        total_amount DECIMAL(10,2) NOT NULL,
        payment_method ENUM('eft', 'havale', 'kredi_karti') NOT NULL,
        status ENUM('beklemede', 'onaylandi', 'hazirlaniyor', 'kargoda', 'teslim_edildi', 'iptal') DEFAULT 'beklemede',
        student_name VARCHAR(100) NOT NULL,
        student_class VARCHAR(50) NOT NULL,
        student_phone VARCHAR(20),
        student_address TEXT,
        order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
    )");
    
    // SİPARİŞ DETAYLARI TABLOSU
    $pdo->exec("CREATE TABLE order_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_id INT NOT NULL,
        product_id INT NOT NULL,
        product_name VARCHAR(100) NOT NULL,
        product_price DECIMAL(10,2) NOT NULL,
        quantity INT NOT NULL,
        total_price DECIMAL(10,2) NOT NULL,
        FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
    )");
    
    echo "✅ Sipariş sistemi tabloları başarıyla oluşturuldu!";
    
} catch (Exception $e) {
    echo "Hata: " . $e->getMessage();
}
?>
