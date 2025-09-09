<?php
session_start();
include 'config.php';

// Öğrenci girişi kontrol
if (!isset($_SESSION['student_id'])) {
    header('Location: student_login.php');
    exit;
}

if ($_POST) {
    $student_id = $_SESSION['student_id'];
    $product_id = $_POST['product_id'];
    $quantity = $_POST['quantity'] ?? 1;
    
    try {
        // Ürün var mı kontrol et
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch();
        
        if (!$product) {
            header('Location: index.php?error=product_not_found');
            exit;
        }
        
        // Stok kontrolü
        if ($product['stock'] < $quantity) {
            header('Location: product_detail.php?id=' . $product_id . '&error=insufficient_stock');
            exit;
        }
        
        // Sepette zaten var mı kontrol et
        $stmt = $pdo->prepare("SELECT * FROM cart WHERE student_id = ? AND product_id = ?");
        $stmt->execute([$student_id, $product_id]);
        $existing = $stmt->fetch();
        
        if ($existing) {
            // Miktarı güncelle
            $new_quantity = $existing['quantity'] + $quantity;
            if ($new_quantity > $product['stock']) {
                $new_quantity = $product['stock'];
            }
            
            $stmt = $pdo->prepare("UPDATE cart SET quantity = ? WHERE student_id = ? AND product_id = ?");
            $stmt->execute([$new_quantity, $student_id, $product_id]);
        } else {
            // Yeni ürün ekle
            $stmt = $pdo->prepare("INSERT INTO cart (student_id, product_id, quantity) VALUES (?, ?, ?)");
            $stmt->execute([$student_id, $product_id, $quantity]);
        }
        
        header('Location: cart.php?success=added');
        exit;
        
    } catch (Exception $e) {
        header('Location: index.php?error=cart_error');
        exit;
    }
}

header('Location: index.php');
?>
