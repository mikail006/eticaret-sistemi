<?php
include 'config.php';

try {
    // Students tablosunu güncelle
    $pdo->exec("ALTER TABLE students ADD COLUMN class VARCHAR(20) AFTER full_name");
    $pdo->exec("ALTER TABLE students ADD COLUMN phone VARCHAR(20) AFTER class");
    $pdo->exec("ALTER TABLE students ADD COLUMN address TEXT AFTER phone");
    $pdo->exec("ALTER TABLE students ADD COLUMN profile_image VARCHAR(255) AFTER address");
    
    // Products tablosuna sınıf alanı ekle
    $pdo->exec("ALTER TABLE products ADD COLUMN target_classes TEXT AFTER images");
    
    echo "Veritabanı başarıyla güncellendi!";
    
} catch (Exception $e) {
    echo "Hata: " . $e->getMessage();
}
?>
