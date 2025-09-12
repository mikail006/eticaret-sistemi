<?php
session_start();
include 'config.php';

if (!isset($_SESSION['student_id'])) {
    header('Location: student_login.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
$stmt->execute([$_SESSION['student_id']]);
$student = $stmt->fetch();

// SADECE öğrenci sınıfına uygun ürünleri getir
$student_class = $student['class'];
$stmt = $pdo->prepare("SELECT * FROM products WHERE JSON_CONTAINS(classes, ?) ORDER BY created_at DESC");
$stmt->execute(['"' . $student_class . '"']);
$products = $stmt->fetchAll();

// Sepet sayısı
$cart_count_stmt = $pdo->prepare("SELECT COUNT(*) FROM cart WHERE student_id = ?");
$cart_count_stmt->execute([$_SESSION['student_id']]);
$cart_count = $cart_count_stmt->fetchColumn();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Ana Sayfa - E-Ticaret</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: #f5f6fa; }
        .container { max-width: 1400px; margin: 0 auto; padding: 20px; }
        
        /* HEADER */
        .header { background: white; border: 2px solid #f0f0f0; border-radius: 15px; padding: 20px; margin-bottom: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .header-container { display: flex; justify-content: space-between; align-items: center; }
        
        .header-brand { display: flex; align-items: center; gap: 15px; color: #333; }
        .profile-image { width: 50px; height: 50px; border-radius: 50%; object-fit: cover; border: 2px solid #f0f0f0; }
        .profile-placeholder { width: 50px; height: 50px; border-radius: 50%; background: #e9ecef; }
        .student-name { font-size: 24px; font-weight: 700; margin-bottom: 5px; }
        .student-class { font-size: 16px; font-weight: 400; color: #666; }
        
        .header-nav { display: flex; gap: 20px; align-items: center; }
        .nav-link { 
            color: #333; 
            text-decoration: none; 
            font-weight: 500; 
            padding: 10px 16px; 
            border-radius: 8px;
        }
        .nav-link:hover { background: #f0f0f0; }
        .nav-link.active { background: #333; color: white; }
        
        /* SEPET SAYISI */
        .cart-count { 
            font-weight: 700; 
            color: #333; 
        }
        
        /* SLIDER */
        .hero-slider { 
            background: #e9ecef; 
            border-radius: 15px; 
            height: 250px; 
            margin-bottom: 30px; 
            display: flex; 
            align-items: center; 
            padding-left: 60px;
        }
        .slider-title { 
            font-size: 36px; 
            font-weight: 700; 
            color: #333; 
            margin-bottom: 12px; 
        }
        .slider-subtitle { 
            font-size: 18px; 
            color: #666; 
            font-weight: 400; 
        }
        
        /* ÜRÜNLER */
        .products-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 25px; margin-top: 20px; }
        .product-card { background: white; border-radius: 15px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border: 1px solid #f0f0f0; }
        
        .product-image { height: 250px; overflow: hidden; }
        .product-image img { width: 100%; height: 100%; object-fit: cover; }
        
        .product-info { padding: 20px; text-align: center; }
        .product-info h3 { font-size: 18px; font-weight: 600; margin-bottom: 10px; color: #333; }
        .product-price { font-size: 20px; font-weight: 600; color: #333; margin-bottom: 20px; }
        
        .product-actions { display: flex; flex-direction: column; gap: 10px; align-items: center; }
        .btn { 
            padding: 12px 30px; 
            border: none; 
            border-radius: 8px; 
            cursor: pointer; 
            text-decoration: none; 
            display: inline-block; 
            text-align: center; 
            width: 200px; 
            font-size: 14px; 
            font-weight: 600;
        }
        .btn-primary { background: linear-gradient(135deg, #333 0%, #555 100%); color: white; }
        .btn-success { background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; }
        .btn-disabled { background: #e9ecef; color: #666; cursor: not-allowed; }
        
        /* BOŞ DURUM */
        .empty-products { background: white; border-radius: 15px; padding: 40px; text-align: center; border: 1px solid #f0f0f0; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .empty-products h2 { color: #333; margin-bottom: 10px; font-weight: 600; }
        .empty-products p { color: #666; }
        
        @media (max-width: 768px) {
            .container { padding: 10px; }
            .header-container { flex-direction: column; gap: 15px; }
            .header-nav { flex-wrap: wrap; justify-content: center; }
            .products-grid { grid-template-columns: 1fr; }
            .hero-slider { padding-left: 40px; height: 200px; }
            .slider-title { font-size: 28px; }
            .slider-subtitle { font-size: 16px; }
        }
        
        @media (max-width: 480px) {
            .hero-slider { padding-left: 30px; height: 180px; }
            .slider-title { font-size: 24px; }
            .slider-subtitle { font-size: 15px; }
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
                        <div class="profile-placeholder"></div>
                    <?php endif; ?>
                    
                    <div class="student-info">
                        <div class="student-name"><?= htmlspecialchars($student['full_name']) ?></div>
                        <div class="student-class"><?= htmlspecialchars($student['class']) ?></div>
                    </div>
                </div>
                
                <nav class="header-nav">
                    <a href="index.php" class="nav-link active">Anasayfa</a>
                    <a href="profile.php" class="nav-link">Profil</a>
                    <a href="cart.php" class="nav-link">
                        Sepetim 
                        <?php if ($cart_count > 0): ?>
                            (<span class="cart-count"><?= $cart_count ?></span>)
                        <?php endif; ?>
                    </a>
                    <a href="my_orders.php" class="nav-link">Siparişlerim</a>
                    <a href="student_login.php" class="nav-link">Çıkış</a>
                </nav>
            </div>
        </div>
        
        <!-- HERO SLIDER -->
        <div class="hero-slider">
            <div>
                <div class="slider-title">Büyük Başlık</div>
                <div class="slider-subtitle">Küçük alt başlık buraya gelecek</div>
            </div>
        </div>
        
        <div class="products-grid">
            <?php if (count($products) > 0): ?>
                <?php foreach ($products as $product): ?>
                    <?php 
                    $images = json_decode($product['images'], true) ?: [];
                    $first_image = !empty($images) ? $images[0] : 'placeholder.jpg';
                    ?>
                    <div class="product-card">
                        <div class="product-image">
                            <img src="uploads/<?= htmlspecialchars($first_image) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
                        </div>
                        <div class="product-info">
                            <h3><?= htmlspecialchars($product['name']) ?></h3>
                            <div class="product-price"><?= number_format($product['price'], 2) ?> TL</div>
                            
                            <div class="product-actions">
                                <a href="product_detail.php?id=<?= $product['id'] ?>" class="btn btn-primary">Ürün Detay</a>
                                <?php if ($product['stock'] > 0): ?>
                                    <form method="POST" action="add_to_cart.php">
                                        <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                        <input type="hidden" name="quantity" value="1">
                                        <button type="submit" class="btn btn-success">Sepete Ekle</button>
                                    </form>
                                <?php else: ?>
                                    <button class="btn btn-disabled" disabled>Stokta Yok</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-products">
                    <h2>Sınıfın İçin Ürün Yok</h2>
                    <p>Henüz "<?= htmlspecialchars($student['class']) ?>" sınıfı için ürün eklenmemiş!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
