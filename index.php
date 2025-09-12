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
        
        /* HEADER TASARIMI */
        .header { background: #f8f9fa; border: 2px solid #e9ecef; border-radius: 15px; padding: 20px; margin-bottom: 30px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
        .header-container { display: flex; justify-content: space-between; align-items: center; }
        
        .header-brand { display: flex; align-items: center; gap: 15px; color: #333; }
        .profile-image { width: 50px; height: 50px; border-radius: 50%; object-fit: cover; border: 2px solid #e9ecef; }
        .profile-placeholder { width: 50px; height: 50px; border-radius: 50%; background: #e9ecef; display: flex; align-items: center; justify-content: center; font-size: 24px; color: #666; }
        .student-info { }
        .student-name { font-size: 24px; font-weight: 700; margin-bottom: 5px; }
        .student-class { font-size: 16px; font-weight: 400; color: #666; }
        
        .header-nav { display: flex; gap: 20px; align-items: center; }
        .nav-link { color: #333; text-decoration: none; font-weight: 500; padding: 10px 16px; border-radius: 8px; }
        .nav-link:hover { background: #e9ecef; }
        .nav-link.active { background: #333; color: white; }
        
        /* ÜRÜN GRID */
        .products-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 25px; margin-top: 20px; }
        .product-card { background: white; border-radius: 15px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border: 1px solid #f0f0f0; }
        
        .product-image { height: 250px; overflow: hidden; }
        .product-image img { width: 100%; height: 100%; object-fit: cover; }
        
        .product-info { padding: 20px; text-align: center; }
        .product-info h3 { font-size: 18px; font-weight: 600; margin-bottom: 10px; color: #333; }
        .product-price { font-size: 20px; font-weight: 600; color: #000; margin-bottom: 20px; }
        
        .product-actions { display: flex; flex-direction: column; gap: 10px; align-items: center; }
        .btn { padding: 12px 30px; border: none; border-radius: 8px; cursor: pointer; text-decoration: none; display: inline-block; text-align: center; width: 180px; font-size: 14px; font-weight: 600; background: linear-gradient(135deg, #333 0%, #555 100%); color: white; }
        .btn-disabled { background: #e9ecef; color: #666; cursor: not-allowed; font-weight: 400; }
        
        .welcome-section { background: white; border-radius: 15px; padding: 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 30px; text-align: center; border: 1px solid #f0f0f0; }
        .welcome-section h2 { color: #333; margin-bottom: 10px; font-weight: 600; }
        .welcome-section p { color: #666; }
        
        @media (max-width: 768px) {
            .container { padding: 10px; }
            .header-container { flex-direction: column; gap: 15px; }
            .header-nav { flex-wrap: wrap; justify-content: center; }
            .products-grid { grid-template-columns: 1fr; }
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
                    <a href="index.php" class="nav-link active">Anasayfa</a>
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
        
        <div class="welcome-section">
            <h2>Sınıfına Özel Ürünler</h2>
            <p>Sınıf: <?= htmlspecialchars($student['class']) ?> için <?= count($products) ?> ürün bulundu</p>
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
                                <a href="product_detail.php?id=<?= $product['id'] ?>" class="btn">Ürün Detay</a>
                                <?php if ($product['stock'] > 0): ?>
                                    <form method="POST" action="add_to_cart.php">
                                        <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                        <input type="hidden" name="quantity" value="1">
                                        <button type="submit" class="btn">Sepete Ekle</button>
                                    </form>
                                <?php else: ?>
                                    <button class="btn btn-disabled" disabled>Stokta Yok</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <?php if (count($products) === 0): ?>
            <div class="welcome-section">
                <h2>Sınıfın İçin Ürün Yok</h2>
                <p>Henüz "<?= htmlspecialchars($student['class']) ?>" sınıfı için ürün eklenmemiş!</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
