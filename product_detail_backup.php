<?php
session_start();
include 'config.php';

if (!isset($_SESSION['student_id'])) {
    header('Location: student_login.php');
    exit;
}

$product_id = $_GET['id'] ?? 0;

// Ürün bilgilerini getir
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
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Arial', sans-serif; background: #f8f9fa; }
        
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px 0; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header-content { max-width: 1200px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; padding: 0 20px; }
        .logo { font-size: 24px; font-weight: bold; }
        .back-btn { background: rgba(255,255,255,0.2); color: white; padding: 8px 16px; border: none; border-radius: 20px; cursor: pointer; text-decoration: none; }
        .back-btn:hover { background: rgba(255,255,255,0.3); }
        
        .container { max-width: 1200px; margin: 40px auto; padding: 0 20px; }
        .product-detail { background: white; border-radius: 15px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
        .product-content { display: grid; grid-template-columns: 1fr 1fr; gap: 0; }
        
        .image-section { position: relative; }
        .image-slider { position: relative; height: 500px; overflow: hidden; }
        .slide { display: none; width: 100%; height: 100%; }
        .slide.active { display: block; }
        .slide img { width: 100%; height: 100%; object-fit: cover; }
        
        .slider-nav { position: absolute; bottom: 20px; left: 50%; transform: translateX(-50%); display: flex; gap: 10px; }
        .nav-dot { width: 12px; height: 12px; border-radius: 50%; background: rgba(255,255,255,0.5); cursor: pointer; transition: all 0.3s ease; }
        .nav-dot.active { background: white; transform: scale(1.2); }
        
        .prev, .next { position: absolute; top: 50%; transform: translateY(-50%); background: rgba(0,0,0,0.5); color: white; border: none; padding: 15px 20px; cursor: pointer; font-size: 18px; border-radius: 5px; transition: all 0.3s ease; }
        .prev { left: 20px; }
        .next { right: 20px; }
        .prev:hover, .next:hover { background: rgba(0,0,0,0.8); }
        
        /* Yeni thumbnail özelliği */
        .thumbnails { padding: 20px; background: #f8f9fa; display: flex; gap: 10px; justify-content: center; flex-wrap: wrap; max-height: 120px; overflow-y: auto; }
        .thumbnail { width: 80px; height: 80px; border-radius: 8px; cursor: pointer; transition: all 0.3s ease; border: 3px solid transparent; }
        .thumbnail:hover { border-color: #667eea; transform: scale(1.05); }
        .thumbnail.active { border-color: #764ba2; transform: scale(1.1); }
        .thumbnail img { width: 100%; height: 100%; object-fit: cover; border-radius: 5px; }
        
        .info-section { padding: 40px; }
        .product-title { font-size: 32px; color: #333; margin-bottom: 20px; font-weight: bold; }
        .product-price { font-size: 36px; color: #667eea; margin-bottom: 20px; font-weight: bold; }
        .product-stock { color: #27ae60; font-size: 16px; margin-bottom: 30px; padding: 10px; background: #f8f9fa; border-radius: 8px; }
        .product-description { color: #666; line-height: 1.8; font-size: 16px; margin-bottom: 30px; }
        
        .action-buttons { display: flex; gap: 15px; }
        .btn { padding: 15px 30px; border: none; border-radius: 8px; cursor: pointer; font-size: 16px; text-decoration: none; display: inline-block; text-align: center; transition: all 0.3s ease; }
        .btn-primary { background: linear-gradient(45deg, #667eea, #764ba2); color: white; }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4); }
        .btn-secondary { background: #6c757d; color: white; }
        .btn-secondary:hover { background: #545b62; transform: translateY(-2px); }
        
        .product-specs { margin-top: 30px; padding: 20px; background: #f8f9fa; border-radius: 8px; }
        .spec-title { font-weight: bold; color: #333; margin-bottom: 15px; }
        .spec-item { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #dee2e6; }
        .spec-item:last-child { border-bottom: none; }
        
        @media (max-width: 768px) {
            .product-content { grid-template-columns: 1fr; }
            .image-slider { height: 400px; }
            .info-section { padding: 20px; }
            .action-buttons { flex-direction: column; }
            .thumbnails { padding: 10px; }
            .thumbnail { width: 60px; height: 60px; }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <div class="logo">🛒 E-Ticaret</div>
            <a href="index.php" class="back-btn">← Ana Sayfaya Dön</a>
        </div>
    </div>
    
    <div class="container">
        <div class="product-detail">
            <div class="product-content">
                <div class="image-section">
                    <div class="image-slider">
                        <?php if (count($images) > 0): ?>
                            <?php foreach ($images as $index => $image): ?>
                                <div class="slide <?= $index === 0 ? 'active' : '' ?>">
                                    <img src="uploads/<?= $image ?>" alt="<?= htmlspecialchars($product['name']) ?>">
                                </div>
                            <?php endforeach; ?>
                            
                            <?php if (count($images) > 1): ?>
                                <button class="prev" onclick="changeSlide(-1)">❮</button>
                                <button class="next" onclick="changeSlide(1)">❯</button>
                                
                                <div class="slider-nav">
                                    <?php foreach ($images as $index => $image): ?>
                                        <div class="nav-dot <?= $index === 0 ? 'active' : '' ?>" onclick="currentSlide(<?= $index + 1 ?>)"></div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="slide active">
                                <img src="https://via.placeholder.com/500x500?text=Ürün+Resmi" alt="Varsayılan Resim">
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Yeni thumbnail bölümü -->
                    <?php if (count($images) > 1): ?>
                        <div class="thumbnails">
                            <?php foreach ($images as $index => $image): ?>
                                <div class="thumbnail <?= $index === 0 ? 'active' : '' ?>" onclick="currentSlide(<?= $index + 1 ?>)">
                                    <img src="uploads/<?= $image ?>" alt="Thumbnail <?= $index + 1 ?>">
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="info-section">
                    <h1 class="product-title"><?= htmlspecialchars($product['name']) ?></h1>
                    <div class="product-price"><?= number_format($product['price'], 2) ?> TL</div>
                    <div class="product-stock">
                        <?php if ($product['stock'] > 0): ?>
                            ✅ Stokta var (<?= $product['stock'] ?> adet)
                        <?php else: ?>
                            ❌ Stokta yok
                        <?php endif; ?>
                    </div>
                    
                    <div class="product-description">
                        <?= nl2br(htmlspecialchars($product['description'])) ?>
                    </div>
                    
                    <div class="action-buttons">
                        <?php if ($product['stock'] > 0): ?>
                            <button class="btn btn-primary" onclick="alert('Sepete eklendi! (Demo)')">🛒 Sepete Ekle</button>
                        <?php else: ?>
                            <button class="btn btn-secondary" disabled>Stokta Yok</button>
                        <?php endif; ?>
                        <a href="index.php" class="btn btn-secondary">← Alışverişe Devam</a>
                    </div>
                    
                    <div class="product-specs">
                        <div class="spec-title">Ürün Bilgileri</div>
                        <div class="spec-item">
                            <span>Ürün Kodu:</span>
                            <span>#<?= str_pad($product['id'], 6, '0', STR_PAD_LEFT) ?></span>
                        </div>
                        <div class="spec-item">
                            <span>Kategori:</span>
                            <span>Genel</span>
                        </div>
                        <div class="spec-item">
                            <span>Resim Sayısı:</span>
                            <span><?= count($images) ?> resim</span>
                        </div>
                        <div class="spec-item">
                            <span>Stok Durumu:</span>
                            <span><?= $product['stock'] > 0 ? 'Mevcut' : 'Tükendi' ?></span>
                        </div>
                        <div class="spec-item">
                            <span>Eklenme Tarihi:</span>
                            <span><?= date('d.m.Y', strtotime($product['created_at'])) ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        let slideIndex = 1;
        const slides = document.querySelectorAll('.slide');
        const dots = document.querySelectorAll('.nav-dot');
        const thumbnails = document.querySelectorAll('.thumbnail');
        
        function showSlide(n) {
            if (n > slides.length) slideIndex = 1;
            if (n < 1) slideIndex = slides.length;
            
            slides.forEach(slide => slide.classList.remove('active'));
            dots.forEach(dot => dot.classList.remove('active'));
            thumbnails.forEach(thumb => thumb.classList.remove('active'));
            
            if (slides[slideIndex - 1]) {
                slides[slideIndex - 1].classList.add('active');
            }
            if (dots[slideIndex - 1]) {
                dots[slideIndex - 1].classList.add('active');
            }
            if (thumbnails[slideIndex - 1]) {
                thumbnails[slideIndex - 1].classList.add('active');
            }
        }
        
        function changeSlide(n) {
            slideIndex += n;
            showSlide(slideIndex);
        }
        
        function currentSlide(n) {
            slideIndex = n;
            showSlide(slideIndex);
        }
        
        // Otomatik slider (isteğe bağlı)
        setInterval(() => {
            if (slides.length > 1) {
                changeSlide(1);
            }
        }, 5000);
    </script>
</body>
</html>
