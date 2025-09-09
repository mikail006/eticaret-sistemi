<?php
session_start();
include 'config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) {
    http_response_code(403);
    exit(json_encode(['success' => false, 'message' => 'Yetkisiz erişim']));
}

$input = json_decode(file_get_contents('php://input'), true);
$product_id = $input['product_id'] ?? 0;
$image_name = $input['image_name'] ?? '';

if (!$product_id || !$image_name) {
    exit(json_encode(['success' => false, 'message' => 'Geçersiz parametre']));
}

try {
    // Ürünün resimlerini al
    $stmt = $pdo->prepare("SELECT images FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();
    
    if (!$product) {
        exit(json_encode(['success' => false, 'message' => 'Ürün bulunamadı']));
    }
    
    $images = json_decode($product['images'], true) ?: [];
    
    // Resmi listeden çıkar
    $images = array_filter($images, function($img) use ($image_name) {
        return $img !== $image_name;
    });
    
    // Veritabanını güncelle
    $stmt = $pdo->prepare("UPDATE products SET images = ? WHERE id = ?");
    $stmt->execute([json_encode(array_values($images)), $product_id]);
    
    // Dosyayı sil
    $file_path = __DIR__ . '/uploads/' . $image_name;
    if (file_exists($file_path)) {
        unlink($file_path);
    }
    
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Hata: ' . $e->getMessage()]);
}
?>
