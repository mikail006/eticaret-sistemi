<?php
include 'config.php';

try {
    // Products tablosuna classes alanını ekle
    $pdo->exec("ALTER TABLE products ADD COLUMN classes TEXT");
    echo "✅ Classes alanı başarıyla eklendi!";
} catch (Exception $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "✅ Classes alanı zaten mevcut!";
    } else {
        echo "Hata: " . $e->getMessage();
    }
}
?>
