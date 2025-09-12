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

// Öğrenci bilgilerini al
$stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
$stmt->execute([$_SESSION['student_id']]);
$student = $stmt->fetch();

// Sepet sayısı
$cart_count_stmt = $pdo->prepare("SELECT COUNT(*) FROM cart WHERE student_id = ?");
$cart_count_stmt->execute([$_SESSION['student_id']]);
$cart_count = $cart_count_stmt->fetchColumn();

$images = json_decode($product['images'], true) ?: [];
?>
<!DOCTYPE html>
<html>
<head>
    <title><?= htmlspecialchars($product['name']) ?> - Ürün Detayı</title>
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
        .profile-placeholder { width: 50px; height: 50px; border-radius: 50%; background: #e9ecef; display: flex; align-items: center; justify-content: center; font-size: 24px; color: #666; }
        .student-name { font-size: 24px; font-weight: 700; margin-bottom: 5px; }
        .student-class { font-size: 16px; font-weight: 400; color: #666; }
        
        .header-nav { display: flex; gap: 20px; align-items: center; }
        .nav-link { color: #333; text-decoration: none; font-weight: 500; padding: 10px 16px; border-radius: 8px; }
        .nav-link:hover { background: #e9ecef; }
        .nav-link.active { background: #333; color: white; }
        
        /* ÜRÜN DETAY */
        .product-detail { background: white; border-radius: 15px; padding: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border: 1px solid #f0f0f0; display: grid; grid-template-columns: 1fr 1fr; gap: 40px; }
        
        .product-gallery { }
        .main-image { width: 100%; height: 400px; border-radius: 15px; object-fit: cover; margin-bottom: 20px; border: 2px solid #f0f0f0; }
        .thumbnail-container { display: flex; gap: 10px; overflow-x: auto; padding: 10px 0; }
        .thumbnail { width: 80px; height: 80px; border-radius: 10px; object-fit: cover; cursor: pointer; border: 2px solid #f0f0f0; }
        .thumbnail:hover, .thumbnail.active { border-color: #333; }
        
        .product-info { }
        .product-title { font-size: 32px; font-weight: 700; color: #333; margin-bottom: 15px; }
        .product-price { font-size: 36px; font-weight: 700; color: #000; margin-bottom: 20px; }
        .product-stock { font-size: 18px; margin-bottom: 20px; padding: 15px; border-radius: 10px; border: 2px solid #e9ecef; background: #f8f9fa; font-weight: 600; }
        .stock-available { color: #22c55e; }
        .stock-low { color: #f59e0b; }
        .stock-out { color: #ef4444; }
        
        .product-description { background: #f8f9fa; border: 2px solid #e9ecef; padding: 20px; border-radius: 10px; margin-bottom: 25px; }
        .product-description h3 { margin-bottom: 10px; color: #333; font-weight: 700; }
        .product-description p { color: #666; line-height: 1.6; font-weight: 500; }
        
        .purchase-section { display: flex; align-items: center; gap: 20px; margin-top: 30px; }
        .quantity-selector { display: flex; align-items: center; gap: 10px; }
        .quantity-label { font-weight: 600; color: #333; }
        .quantity-btn { width: 40px; height: 40px; border: 2px solid #e9ecef; background: white; border-radius: 8px; cursor: pointer; font-size: 18px; font-weight: bold; color: #333; }
        .quantity-btn:hover { border-color: #333; }
        .quantity-input { width: 80px; height: 40px; text-align: center; border: 2px solid #e9ecef; border-radius: 8px; font-size: 16px; font-weight: 600; }
        
        .btn { padding: 15px 30px; border: none; border-radius: 8px; cursor: pointer; text-decoration: none; display: inline-block; text-align: center; font-size: 16px; font-weight: 600; background: linear-gradient(135deg, #333 0%, #555 100%); color: white; }
        .btn-disabled { background: #e9ecef; color: #666; cursor: not-allowed; }
        
        @media (max-width: 768px) {
            .container { padding: 10px; }
            .header-container { flex-direction: column; gap: 15px; }
            .header-nav { flex-wrap: wrap; justify-content: center; }
            .product-detail { grid-template-columns: 1fr; gap: 20px; }
            .purchase-section { flex-direction: column; align-items: stretch; }
            .main-image { height: 300px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="header-container">
                <div class="header-brand">
                    <?php if ($student['profile_image']): ?>
                        <img src="uploads/<?= htmlspecialchars($student['profile_image']) ?>" class="profile-image" alt="Profil">
                    <?php else: ?>
                        <div class="profile-placeholder">👤</div>
                    <?php endif; ?>
                    
                    <div class="student-info">
                        <div class="student-name"><?= htmlspecialchars($student['full_name']) ?></div>
                        <div class="student-class"><?= htmlspecialchars($student['class']) ?></div>
                    </div>
                </div>
                
                <nav class="header-nav">
                    <a href="index.php" class="nav-link">Anasayfa</a>
                    <a href="profile.php" class="nav-link">Profil</a>
                    <a href="cart.php" class="nav-link">
                        Sepetim
                        <?php if ($cart_count > 0): ?>
                            (<?= $cart_count ?>)
                        <?php endif; ?>
                    </a>
                    <a href="student_login.php" class="nav-link">Çıkış</a>
                </nav>
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
                <h1 class="product-title"><?= htmlspecialchars($product['name']) ?></h1>
                <div class="product-price"><?= number_format($product['price'], 2) ?> TL</div>
                
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
                                <span class="quantity-label">Miktar:</span>
                                <button type="button" class="quantity-btn" onclick="changeQuantity(-1)">-</button>
                                <input type="number" name="quantity" id="quantity" value="1" min="1" max="<?= $product['stock'] ?>" class="quantity-input">
                                <button type="button" class="quantity-btn" onclick="changeQuantity(1)">+</button>
                            </div>
                            <button type="submit" class="btn">Sepete Ekle</button>
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
