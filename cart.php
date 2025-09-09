<?php
session_start();
include 'config.php';

// Öğrenci girişi kontrol
if (!isset($_SESSION['student_id'])) {
    header('Location: student_login.php');
    exit;
}

$student_id = $_SESSION['student_id'];
$notification = '';
$notification_type = '';

if (isset($_GET['success'])) {
    $notification = "Ürün sepete eklendi";
    $notification_type = "success";
} elseif (isset($_GET['updated'])) {
    $notification = "Sepet güncellendi";
    $notification_type = "success";
} elseif (isset($_GET['removed'])) {
    $notification = "Ürün sepetten çıkarıldı";
    $notification_type = "success";
}

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

// Toplam fiyat hesapla
$total_amount = 0;
foreach ($cart_items as $item) {
    $total_amount += $item['price'] * $item['quantity'];
}

// Sepet güncelleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_cart'])) {
    foreach ($_POST['quantities'] as $cart_id => $quantity) {
        if ($quantity > 0) {
            $stmt = $pdo->prepare("UPDATE cart SET quantity = ? WHERE id = ? AND student_id = ?");
            $stmt->execute([$quantity, $cart_id, $student_id]);
        } else {
            $stmt = $pdo->prepare("DELETE FROM cart WHERE id = ? AND student_id = ?");
            $stmt->execute([$cart_id, $student_id]);
        }
    }
    header('Location: cart.php?updated=1');
    exit;
}

// Ürün silme
if (isset($_GET['remove']) && is_numeric($_GET['remove'])) {
    $cart_id = $_GET['remove'];
    $stmt = $pdo->prepare("DELETE FROM cart WHERE id = ? AND student_id = ?");
    $stmt->execute([$cart_id, $student_id]);
    header('Location: cart.php?removed=1');
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Sepetim</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f6fa; }
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 15px; margin-bottom: 30px; box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3); display: flex; justify-content: space-between; align-items: center; }
        .header h1 { font-size: 28px; }
        .header-buttons { display: flex; gap: 15px; }
        .btn { padding: 12px 24px; border: none; border-radius: 25px; text-decoration: none; font-weight: bold; transition: all 0.3s; cursor: pointer; display: inline-block; text-align: center; }
        .btn-primary { background: rgba(255,255,255,0.9); color: #667eea; }
        .btn-primary:hover { background: white; transform: translateY(-2px); }
        .btn-secondary { background: rgba(255,255,255,0.2); color: white; }
        .btn-secondary:hover { background: rgba(255,255,255,0.3); }
        .btn-success { background: #48bb78; color: white; }
        .btn-success:hover { background: #38a169; }
        
        .cart-section { background: white; border-radius: 15px; padding: 25px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .cart-item { display: flex; align-items: center; padding: 20px; border-bottom: 1px solid #e2e8f0; }
        .cart-item:last-child { border-bottom: none; }
        .item-image { width: 80px; height: 80px; border-radius: 10px; object-fit: cover; margin-right: 20px; }
        .item-details { flex: 1; }
        .item-name { font-size: 18px; font-weight: 600; color: #2d3748; margin-bottom: 5px; }
        .item-price { font-size: 16px; color: #667eea; font-weight: 600; }
        .item-controls { display: flex; align-items: center; gap: 15px; }
        .quantity-input { width: 60px; padding: 8px; border: 2px solid #e2e8f0; border-radius: 8px; text-align: center; }
        .remove-btn { background: #e53e3e; color: white; padding: 8px 16px; border: none; border-radius: 8px; cursor: pointer; }
        .remove-btn:hover { background: #c53030; }
        
        .cart-summary { background: #f7fafc; padding: 20px; border-radius: 10px; margin-top: 20px; }
        .summary-row { display: flex; justify-content: space-between; margin-bottom: 10px; }
        .summary-total { font-size: 24px; font-weight: bold; color: #2d3748; border-top: 2px solid #e2e8f0; padding-top: 15px; margin-top: 15px; }
        
        .empty-cart { text-align: center; padding: 60px 20px; color: #718096; }
        .empty-cart h3 { margin-bottom: 10px; }
        
        .notification { position: fixed; bottom: 100px; right: 30px; padding: 15px 25px; border-radius: 10px; color: white; font-weight: 600; z-index: 1000; transform: translateX(400px); transition: transform 0.3s; }
        .notification.success { background: #48bb78; }
        .notification.show { transform: translateX(0); }
        
        @media (max-width: 768px) {
            .container { padding: 10px; }
            .header { flex-direction: column; gap: 15px; text-align: center; }
            .cart-item { flex-direction: column; align-items: flex-start; gap: 15px; }
            .item-controls { width: 100%; justify-content: space-between; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🛒 Sepetim</h1>
            <div class="header-buttons">
                <a href="index.php" class="btn btn-secondary">Ana Sayfa</a>
                <a href="my_orders.php" class="btn btn-secondary">Siparişlerim</a>
                <a href="student_login.php" class="btn btn-secondary">Çıkış</a>
            </div>
        </div>
        
        <?php if (count($cart_items) > 0): ?>
            <form method="POST">
                <div class="cart-section">
                    <?php foreach ($cart_items as $item): ?>
                        <?php 
                        $images = json_decode($item['images'], true) ?: [];
                        $first_image = !empty($images) ? $images[0] : 'placeholder.jpg';
                        ?>
                        <div class="cart-item">
                            <img src="uploads/<?= htmlspecialchars($first_image) ?>" alt="<?= htmlspecialchars($item['name']) ?>" class="item-image">
                            <div class="item-details">
                                <div class="item-name"><?= htmlspecialchars($item['name']) ?></div>
                                <div class="item-price"><?= number_format($item['price'], 2) ?> ₺</div>
                            </div>
                            <div class="item-controls">
                                <input type="number" name="quantities[<?= $item['id'] ?>]" value="<?= $item['quantity'] ?>" min="0" max="<?= $item['stock'] ?>" class="quantity-input">
                                <span>× <?= number_format($item['price'], 2) ?> ₺ = <?= number_format($item['price'] * $item['quantity'], 2) ?> ₺</span>
                                <a href="cart.php?remove=<?= $item['id'] ?>" class="remove-btn" onclick="return confirm('Bu ürünü sepetten çıkarmak istediğinize emin misiniz?')">Sil</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <div class="cart-summary">
                        <div class="summary-row">
                            <span>Toplam Ürün:</span>
                            <span><?= count($cart_items) ?> adet</span>
                        </div>
                        <div class="summary-row summary-total">
                            <span>Genel Toplam:</span>
                            <span><?= number_format($total_amount, 2) ?> ₺</span>
                        </div>
                    </div>
                </div>
                
                <div style="display: flex; gap: 15px; justify-content: center;">
                    <button type="submit" name="update_cart" class="btn btn-primary">Sepeti Güncelle</button>
                    <a href="checkout.php" class="btn btn-success">Sipariş Ver</a>
                </div>
            </form>
        <?php else: ?>
            <div class="cart-section">
                <div class="empty-cart">
                    <h3>Sepetiniz boş</h3>
                    <p>Alışverişe başlamak için ürünleri keşfedin!</p>
                    <br>
                    <a href="index.php" class="btn btn-primary">Alışverişe Başla</a>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <?php if ($notification): ?>
        <div class="notification <?= $notification_type ?>" id="notification">
            <?= htmlspecialchars($notification) ?>
        </div>
        <script>
            const notification = document.getElementById('notification');
            setTimeout(() => notification.classList.add('show'), 100);
            setTimeout(() => notification.classList.remove('show'), 3000);
            
            // URL'yi temizle
            if (window.history.replaceState) {
                window.history.replaceState(null, null, window.location.pathname);
            }
        </script>
    <?php endif; ?>
</body>
</html>
