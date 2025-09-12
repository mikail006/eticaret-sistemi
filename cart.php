<?php
session_start();
include 'config.php';

if (!isset($_SESSION['student_id'])) {
    header('Location: student_login.php');
    exit;
}

$student_id = $_SESSION['student_id'];

// Öğrenci bilgilerini al
$stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
$stmt->execute([$student_id]);
$student = $stmt->fetch();

// Sepetteki ürünleri getir
$stmt = $pdo->prepare("
    SELECT c.*, p.name, p.price, p.stock, p.images 
    FROM cart c 
    JOIN products p ON c.product_id = p.id 
    WHERE c.student_id = ? 
    ORDER BY c.created_at DESC
");
$stmt->execute([$student_id]);
$cart_items = $stmt->fetchAll();

// Toplam hesapla
$subtotal = 0;
foreach ($cart_items as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}
$kdv_amount = $subtotal * 0.20;
$total_amount = $subtotal + $kdv_amount;

// Sepet güncelleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_quantity'])) {
    $cart_id = $_POST['cart_id'];
    $new_quantity = $_POST['quantity'];
    
    if ($new_quantity > 0) {
        $stmt = $pdo->prepare("UPDATE cart SET quantity = ? WHERE id = ? AND student_id = ?");
        $stmt->execute([$new_quantity, $cart_id, $student_id]);
    } else {
        $stmt = $pdo->prepare("DELETE FROM cart WHERE id = ? AND student_id = ?");
        $stmt->execute([$cart_id, $student_id]);
    }
    
    header('Location: cart.php');
    exit;
}

// Ürün silme
if (isset($_GET['remove'])) {
    $cart_id = $_GET['remove'];
    $stmt = $pdo->prepare("DELETE FROM cart WHERE id = ? AND student_id = ?");
    $stmt->execute([$cart_id, $student_id]);
    header('Location: cart.php');
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Sepetim - E-Ticaret</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: #f5f6fa; }
        .container { max-width: 1400px; margin: 0 auto; padding: 20px; }
        
        /* HEADER */
        .header { background: #f8f9fa; border: 2px solid #e9ecef; border-radius: 15px; padding: 20px; margin-bottom: 30px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
        .header-container { display: flex; justify-content: space-between; align-items: center; }
        
        .header-brand { display: flex; align-items: center; gap: 15px; color: #333; }
        .profile-image { width: 50px; height: 50px; border-radius: 50%; object-fit: cover; border: 2px solid #e9ecef; }
        .profile-placeholder { width: 50px; height: 50px; border-radius: 50%; background: #e9ecef; }
        .student-name { font-size: 24px; font-weight: 700; margin-bottom: 5px; }
        .student-class { font-size: 16px; font-weight: 400; color: #666; }
        
        .header-nav { display: flex; gap: 20px; align-items: center; }
        .nav-link { color: #333; text-decoration: none; font-weight: 500; padding: 10px 16px; border-radius: 8px; }
        .nav-link:hover { background: #e9ecef; }
        .nav-link.active { background: #333; color: white; }
        
        /* CART LAYOUT */
        .cart-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 30px; }
        
        /* SOL TARAF - ÜRÜNLER */
        .products-section { background: white; border-radius: 15px; padding: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border: 1px solid #f0f0f0; }
        .products-title { font-size: 28px; font-weight: 700; color: #333; margin-bottom: 30px; }
        
        .product-item { display: flex; align-items: center; gap: 20px; padding: 20px 0; border-bottom: 1px solid #f0f0f0; }
        .product-item:last-child { border-bottom: none; }
        
        .product-image { width: 80px; height: 80px; border-radius: 50%; object-fit: cover; border: 2px solid #f0f0f0; }
        .product-details { flex: 1; }
        .product-name { font-size: 18px; font-weight: 600; color: #333; margin-bottom: 5px; }
        .product-price { font-size: 16px; color: #666; }
        
        .product-controls { display: flex; align-items: center; gap: 15px; }
        .quantity-controls { display: flex; align-items: center; gap: 10px; }
        .qty-btn { 
            width: 40px; height: 40px; 
            border: 2px solid #e9ecef; 
            background: white; 
            border-radius: 8px; 
            cursor: pointer; 
            font-size: 18px; 
            font-weight: bold; 
            color: #333;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .qty-btn:hover { border-color: #333; }
        .qty-display { 
            min-width: 50px; 
            text-align: center; 
            font-weight: 600; 
            font-size: 18px; 
            color: #333; 
        }
        
        .remove-btn { 
            background: #dc3545; 
            color: white; 
            padding: 8px 16px; 
            border: none; 
            border-radius: 8px; 
            cursor: pointer; 
            font-weight: 600;
        }
        .remove-btn:hover { background: #c82333; }
        
        /* SAĞ TARAF - ÖZET */
        .summary-section { background: white; border-radius: 15px; padding: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border: 1px solid #f0f0f0; position: sticky; top: 20px; }
        .summary-title { font-size: 24px; font-weight: 700; color: #333; margin-bottom: 25px; }
        
        .summary-row { display: flex; justify-content: space-between; margin-bottom: 15px; font-size: 16px; }
        .summary-label { color: #666; }
        .summary-value { font-weight: 600; color: #333; }
        
        .total-row { 
            border-top: 2px solid #f0f0f0; 
            padding-top: 20px; 
            margin-top: 20px; 
            font-size: 20px; 
            font-weight: 700; 
        }
        .total-amount { color: #28a745; font-size: 24px; }
        
        .checkout-buttons { display: flex; flex-direction: column; gap: 15px; margin-top: 25px; }
        .btn { 
            padding: 15px 25px; 
            border: none; 
            border-radius: 10px; 
            cursor: pointer; 
            text-decoration: none; 
            text-align: center; 
            font-size: 16px; 
            font-weight: 600;
        }
        .btn-primary { background: linear-gradient(135deg, #333 0%, #555 100%); color: white; }
        .btn-success { background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; }
        
        /* BOŞ SEPET */
        .empty-cart { text-align: center; padding: 80px 20px; color: #666; }
        .empty-cart h3 { font-size: 24px; margin-bottom: 15px; color: #333; }
        .empty-cart p { font-size: 18px; margin-bottom: 30px; }
        
        /* MOBİL */
        @media (max-width: 1024px) {
            .cart-grid { grid-template-columns: 1fr; }
            .summary-section { position: static; order: -1; }
        }
        
        @media (max-width: 768px) {
            .container { padding: 15px; }
            .header-container { flex-direction: column; gap: 15px; }
            .header-nav { flex-wrap: wrap; justify-content: center; }
            
            .products-section, .summary-section { padding: 20px; }
            .product-item { flex-direction: column; text-align: center; gap: 15px; }
            .product-controls { justify-content: center; }
        }
        
        @media (max-width: 480px) {
            .container { padding: 10px; }
            .products-section, .summary-section { padding: 15px; }
            .product-image { width: 60px; height: 60px; }
            .qty-btn { width: 35px; height: 35px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- HEADER -->
        <div class="header">
            <div class="header-container">
                <div class="header-brand">
                    <?php if ($student['profile_image']): ?>
                        <img src="uploads/<?= htmlspecialchars($student['profile_image']) ?>" class="profile-image" alt="Profil">
                    <?php else: ?>
                        <div class="profile-placeholder"></div>
                    <?php endif; ?>
                    
                    <div class="student-info">
                        <div class="student-name"><?= htmlspecialchars($student['full_name']) ?></div>
                        <div class="student-class"><?= htmlspecialchars($student['class']) ?></div>
                    </div>
                </div>
                
                <nav class="header-nav">
                    <a href="index.php" class="nav-link">Anasayfa</a>
                    <a href="profile.php" class="nav-link">Profil</a>
                    <a href="cart.php" class="nav-link active">Sepetim</a>
                    <a href="my_orders.php" class="nav-link">Siparişlerim</a>
                    <a href="student_login.php" class="nav-link">Çıkış</a>
                </nav>
            </div>
        </div>
        
        <?php if (count($cart_items) > 0): ?>
            <div class="cart-grid">
                <!-- SOL TARAF - ÜRÜNLER -->
                <div class="products-section">
                    <h1 class="products-title">Sepetim</h1>
                    
                    <?php foreach ($cart_items as $item): ?>
                        <?php 
                        $images = json_decode($item['images'], true) ?: [];
                        $first_image = !empty($images) ? $images[0] : 'placeholder.jpg';
                        ?>
                        <div class="product-item">
                            <img src="uploads/<?= htmlspecialchars($first_image) ?>" alt="<?= htmlspecialchars($item['name']) ?>" class="product-image">
                            
                            <div class="product-details">
                                <div class="product-name"><?= htmlspecialchars($item['name']) ?></div>
                                <div class="product-price"><?= number_format($item['price'], 2) ?> TL / adet</div>
                            </div>
                            
                            <div class="product-controls">
                                <div class="quantity-controls">
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="cart_id" value="<?= $item['id'] ?>">
                                        <input type="hidden" name="quantity" value="<?= $item['quantity'] - 1 ?>">
                                        <button type="submit" name="update_quantity" class="qty-btn" <?= $item['quantity'] <= 1 ? 'disabled' : '' ?>>-</button>
                                    </form>
                                    
                                    <div class="qty-display"><?= $item['quantity'] ?></div>
                                    
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="cart_id" value="<?= $item['id'] ?>">
                                        <input type="hidden" name="quantity" value="<?= $item['quantity'] + 1 ?>">
                                        <button type="submit" name="update_quantity" class="qty-btn" <?= $item['quantity'] >= $item['stock'] ? 'disabled' : '' ?>>+</button>
                                    </form>
                                </div>
                                
                                <a href="cart.php?remove=<?= $item['id'] ?>" class="remove-btn" onclick="return confirm('Bu ürünü sepetten çıkarmak istediğinize emin misiniz?')">Kaldır</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- SAĞ TARAF - ÖZET -->
                <div class="summary-section">
                    <h2 class="summary-title">Sipariş Özeti</h2>
                    
                    <div class="summary-row">
                        <span class="summary-label">Ürün Çeşidi:</span>
                        <span class="summary-value"><?= count($cart_items) ?> çeşit</span>
                    </div>
                    
                    <div class="summary-row">
                        <span class="summary-label">Toplam Adet:</span>
                        <span class="summary-value"><?= array_sum(array_column($cart_items, 'quantity')) ?> adet</span>
                    </div>
                    
                    <div class="summary-row">
                        <span class="summary-label">Ara Toplam:</span>
                        <span class="summary-value"><?= number_format($subtotal, 2) ?> TL</span>
                    </div>
                    
                    <div class="summary-row">
                        <span class="summary-label">KDV (%20):</span>
                        <span class="summary-value"><?= number_format($kdv_amount, 2) ?> TL</span>
                    </div>
                    
                    <div class="summary-row total-row">
                        <span class="summary-label">Genel Toplam:</span>
                        <span class="total-amount"><?= number_format($total_amount, 2) ?> TL</span>
                    </div>
                    
                    <div class="checkout-buttons">
                        <a href="index.php" class="btn btn-primary">Alışverişe Devam Et</a>
                        <a href="checkout.php" class="btn btn-success">Ödemeye Geç</a>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="products-section">
                <div class="empty-cart">
                    <h3>Sepetiniz Boş</h3>
                    <p>Henüz sepetinizde ürün bulunmuyor</p>
                    <a href="index.php" class="btn btn-primary">Alışverişe Başla</a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
