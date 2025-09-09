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
?>
<!DOCTYPE html>
<html>
<head>
    <title>Ana Sayfa - E-Ticaret</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f6fa; }
        .container { max-width: 1400px; margin: 0 auto; padding: 20px; }
        
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 15px; margin-bottom: 30px; box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3); display: flex; justify-content: space-between; align-items: center; }
        .header h1 { font-size: 28px; }
        .header-info { text-align: right; }
        .header-info p { margin-bottom: 5px; opacity: 0.9; }
        .header-buttons { display: flex; gap: 15px; margin-top: 10px; }
        .btn { padding: 12px 24px; border: none; border-radius: 25px; text-decoration: none; font-weight: bold; transition: all 0.3s; cursor: pointer; display: inline-block; text-align: center; }
        .btn-primary { background: rgba(255,255,255,0.9); color: #667eea; }
        .btn-primary:hover { background: white; transform: translateY(-2px); }
        .btn-secondary { background: rgba(255,255,255,0.2); color: white; }
        .btn-secondary:hover { background: rgba(255,255,255,0.3); }
        .btn-success { background: #48bb78; color: white; }
        .btn-success:hover { background: #38a169; transform: translateY(-2px); }
        
        .products-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 25px; margin-top: 20px; }
        .product-card { background: white; border-radius: 15px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.1); transition: all 0.3s; }
        .product-card:hover { transform: translateY(-5px); box-shadow: 0 20px 40px rgba(0,0,0,0.15); }
        .product-image { height: 250px; overflow: hidden; }
        .product-image img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.3s; }
        .product-card:hover .product-image img { transform: scale(1.05); }
        .product-info { padding: 20px; }
        .product-info h3 { font-size: 18px; margin-bottom: 10px; color: #2d3748; }
        .product-price { font-size: 20px; font-weight: bold; color: #667eea; margin-bottom: 10px; }
        .product-stock { font-size: 14px; color: #718096; margin-bottom: 15px; }
        .product-actions { display: flex; gap: 10px; }
        .product-actions .btn { flex: 1; padding: 10px; font-size: 14px; }
        .product-actions form { flex: 1; }
        .product-actions button { width: 100%; }
        
        .welcome-section { background: white; border-radius: 15px; padding: 25px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); margin-bottom: 30px; text-align: center; }
        .welcome-section h2 { color: #2d3748; margin-bottom: 10px; }
        .welcome-section p { color: #718096; }
        
        @media (max-width: 768px) {
            .container { padding: 10px; }
            .header { flex-direction: column; gap: 15px; text-align: center; }
            .products-grid { grid-template-columns: 1fr; }
            .product-actions { flex-direction: column; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🛍️ E-Ticaret Mağazası</h1>
            <div class="header-info">
                <p>Hoş geldin, <?= htmlspecialchars($student['full_name']) ?>!</p>
                <p>Sınıf: <?= htmlspecialchars($student['class']) ?></p>
                <div class="header-buttons">
                    <a href="cart.php" class="btn btn-primary">🛒 Sepetim</a>
                    <a href="student_login.php" class="btn btn-secondary">Çıkış</a>
                </div>
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
                            <div class="product-price"><?= number_format($product['price'], 2) ?> ₺</div>
                            <div class="product-stock">
                                <?php if ($product['stock'] > 10): ?>
                                    <span style="color: #48bb78;">✅ Stokta var (<?= $product['stock'] ?>)</span>
                                <?php elseif ($product['stock'] > 0): ?>
                                    <span style="color: #ed8936;">⚠️ Son <?= $product['stock'] ?> adet</span>
                                <?php else: ?>
                                    <span style="color: #e53e3e;">❌ Stokta yok</span>
                                <?php endif; ?>
                            </div>
                            <div class="product-actions">
                                <a href="product_detail.php?id=<?= $product['id'] ?>" class="btn btn-primary">👁️ Detay</a>
                                <?php if ($product['stock'] > 0): ?>
                                    <form method="POST" action="add_to_cart.php">
                                        <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                        <input type="hidden" name="quantity" value="1">
                                        <button type="submit" class="btn btn-success">🛒 Sepete Ekle</button>
                                    </form>
                                <?php else: ?>
                                    <button class="btn" style="background: #cbd5e0; color: #718096;" disabled>Stokta Yok</button>
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
