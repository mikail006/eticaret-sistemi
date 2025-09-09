<?php
session_start();
include 'config.php';

if (!isset($_SESSION['student_id'])) {
    header('Location: student_login.php');
    exit;
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$product_id = $_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch();

if (!$product) {
    header('Location: index.php');
    exit;
}

$images = json_decode($product['images'], true) ?: [];
?>
<!DOCTYPE html>
<html>
<head>
    <title><?= htmlspecialchars($product['name']) ?> - Ürün Detayı</title>
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
        .btn-success { background: #48bb78; color: white; font-size: 18px; padding: 15px 30px; }
        .btn-success:hover { background: #38a169; transform: translateY(-2px); }
        .btn-disabled { background: #cbd5e0; color: #718096; cursor: not-allowed; font-size: 18px; padding: 15px 30px; }
        
        .product-detail { display: grid; grid-template-columns: 1fr 1fr; gap: 40px; background: white; border-radius: 15px; padding: 30px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
        
        .product-gallery { position: relative; }
        .main-image { width: 100%; height: 400px; border-radius: 15px; object-fit: cover; margin-bottom: 20px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
        .thumbnail-container { display: flex; gap: 10px; overflow-x: auto; padding: 10px 0; }
        .thumbnail { width: 80px; height: 80px; border-radius: 10px; object-fit: cover; cursor: pointer; border: 3px solid transparent; transition: all 0.3s; }
        .thumbnail:hover, .thumbnail.active { border-color: #667eea; transform: scale(1.05); }
        
        .product-info h1 { font-size: 32px; color: #2d3748; margin-bottom: 15px; }
        .product-price { font-size: 36px; font-weight: bold; color: #667eea; margin-bottom: 20px; }
        .product-stock { font-size: 18px; margin-bottom: 20px; padding: 15px; border-radius: 10px; }
        .stock-available { background: #f0fff4; color: #38a169; border: 2px solid #c6f6d5; }
        .stock-low { background: #fffaf0; color: #dd6b20; border: 2px solid #fbd38d; }
        .stock-out { background: #fed7d7; color: #e53e3e; border: 2px solid #feb2b2; }
        
        .product-description { background: #f7fafc; padding: 20px; border-radius: 10px; margin-bottom: 25px; }
        .product-description h3 { margin-bottom: 10px; color: #2d3748; }
        .product-description p { color: #718096; line-height: 1.6; }
        
        .purchase-section { display: flex; align-items: center; gap: 20px; margin-top: 30px; }
        .quantity-selector { display: flex; align-items: center; gap: 10px; }
        .quantity-btn { width: 40px; height: 40px; border: 2px solid #e2e8f0; background: white; border-radius: 8px; cursor: pointer; font-size: 18px; font-weight: bold; }
        .quantity-btn:hover { border-color: #667eea; }
        .quantity-input { width: 80px; height: 40px; text-align: center; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 16px; }
        
        @media (max-width: 768px) {
            .container { padding: 10px; }
            .header { flex-direction: column; gap: 15px; text-align: center; }
            .product-detail { grid-template-columns: 1fr; gap: 20px; }
            .purchase-section { flex-direction: column; align-items: stretch; }
            .main-image { height: 300px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📦 Ürün Detayı</h1>
            <div class="header-buttons">
                <a href="index.php" class="btn btn-secondary">Ana Sayfa</a>
                <a href="cart.php" class="btn btn-primary">🛒 Sepetim</a>
            </div>
        </div>
        
        <div class="product-detail">
            <div class="product-gallery">
                <?php if (!empty($images)): ?>
                    <img src="uploads/<?= htmlspecialchars($images[0]) ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="main-image" id="mainImage">
                    
                    <?php if (count($images) > 1): ?>
                        <div class="thumbnail-container">
                            <?php foreach ($images as $index => $image): ?>
                                <img src="uploads/<?= htmlspecialchars($image) ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="thumbnail <?= $index === 0 ? 'active' : '' ?>" onclick="setMainImage('<?= htmlspecialchars($image) ?>', this)">
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <img src="uploads/placeholder.jpg" alt="Resim Yok" class="main-image">
                <?php endif; ?>
            </div>
            
            <div class="product-info">
                <h1><?= htmlspecialchars($product['name']) ?></h1>
                <div class="product-price"><?= number_format($product['price'], 2) ?> ₺</div>
                
                <div class="product-stock <?= $product['stock'] > 10 ? 'stock-available' : ($product['stock'] > 0 ? 'stock-low' : 'stock-out') ?>">
                    <?php if ($product['stock'] > 10): ?>
                        ✅ Stokta var (<?= $product['stock'] ?> adet)
                    <?php elseif ($product['stock'] > 0): ?>
                        ⚠️ Son <?= $product['stock'] ?> adet kaldı!
                    <?php else: ?>
                        ❌ Bu ürün şu anda stokta yok
                    <?php endif; ?>
                </div>
                
                <?php if ($product['description']): ?>
                    <div class="product-description">
                        <h3>Ürün Açıklaması</h3>
                        <p><?= nl2br(htmlspecialchars($product['description'])) ?></p>
                    </div>
                <?php endif; ?>
                
                <?php if ($product['stock'] > 0): ?>
                    <form method="POST" action="add_to_cart.php">
                        <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                        <div class="purchase-section">
                            <div class="quantity-selector">
                                <span>Miktar:</span>
                                <button type="button" class="quantity-btn" onclick="changeQuantity(-1)">-</button>
                                <input type="number" name="quantity" id="quantity" value="1" min="1" max="<?= $product['stock'] ?>" class="quantity-input">
                                <button type="button" class="quantity-btn" onclick="changeQuantity(1)">+</button>
                            </div>
                            <button type="submit" class="btn btn-success">🛒 Sepete Ekle</button>
                        </div>
                    </form>
                <?php else: ?>
                    <div class="purchase-section">
                        <button class="btn btn-disabled" disabled>Stokta Yok</button>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
        function setMainImage(imageSrc, thumbnailElement) {
            document.getElementById('mainImage').src = 'uploads/' + imageSrc;
            
            document.querySelectorAll('.thumbnail').forEach(thumb => thumb.classList.remove('active'));
            thumbnailElement.classList.add('active');
        }
        
        function changeQuantity(change) {
            const quantityInput = document.getElementById('quantity');
            let newValue = parseInt(quantityInput.value) + change;
            const maxStock = parseInt(quantityInput.max);
            
            if (newValue < 1) newValue = 1;
            if (newValue > maxStock) newValue = maxStock;
            
            quantityInput.value = newValue;
        }
    </script>
</body>
</html>
