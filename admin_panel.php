<?php
session_start();
include 'config.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit;
}

$notification = '';
$notification_type = '';
if (isset($_GET['success'])) {
    $notification = "İşlem başarılı";
    $notification_type = "success";
} elseif (isset($_GET['deleted'])) {
    $notification = "Ürün silindi";
    $notification_type = "success";
}

// Ürün silme
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = $_GET['delete'];
    
    $stmt = $pdo->prepare("SELECT images FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $product = $stmt->fetch();
    
    if ($product) {
        $images = json_decode($product['images'], true) ?: [];
        foreach ($images as $image) {
            if (file_exists('uploads/' . $image)) {
                unlink('uploads/' . $image);
            }
        }
    }
    
    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
    $stmt->execute([$id]);
    
    header("Location: admin_panel.php?deleted=1");
    exit;
}

$stmt = $pdo->query("SELECT * FROM products ORDER BY created_at DESC");
$products = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Panel</title>
    <meta charset="UTF-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f6fa; }
        .container { max-width: 1400px; margin: 0 auto; padding: 20px; }
        
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 15px; margin-bottom: 30px; box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3); display: flex; justify-content: space-between; align-items: center; }
        .header h1 { font-size: 28px; }
        .header-buttons { display: flex; gap: 15px; }
        .btn { padding: 12px 24px; border: none; border-radius: 25px; text-decoration: none; font-weight: bold; transition: all 0.3s; cursor: pointer; display: inline-block; text-align: center; }
        .btn-primary { background: rgba(255,255,255,0.9); color: #667eea; }
        .btn-primary:hover { background: white; transform: translateY(-2px); }
        .btn-secondary { background: rgba(255,255,255,0.2); color: white; }
        .btn-secondary:hover { background: rgba(255,255,255,0.3); }
        
        .products-section { background: white; border-radius: 15px; padding: 25px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
        .section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
        .section-header h2 { color: #2d3748; }
        
        .table-container { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 15px; text-align: left; border-bottom: 1px solid #e2e8f0; }
        th { background: #f7fafc; color: #4a5568; font-weight: 600; }
        tr:hover { background: #f9f9f9; }
        
        .product-thumb { width: 60px; height: 60px; object-fit: cover; border-radius: 8px; }
        .stock-badge { padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
        .stock-high { background: #c6f6d5; color: #22543d; }
        .stock-low { background: #fed7d7; color: #742a2a; }
        .stock-medium { background: #faf089; color: #744210; }
        
        .action-buttons { display: flex; gap: 8px; }
        .btn-edit { background: #4299e1; color: white; padding: 8px 16px; border: none; border-radius: 6px; cursor: pointer; text-decoration: none; }
        .btn-delete { background: #e53e3e; color: white; padding: 8px 16px; border: none; border-radius: 6px; cursor: pointer; }
        .btn-edit:hover { background: #3182ce; }
        .btn-delete:hover { background: #c53030; }
        
        .notification { position: fixed; bottom: 100px; right: 30px; padding: 15px 25px; border-radius: 10px; color: white; font-weight: 600; z-index: 1000; transform: translateX(400px); transition: transform 0.3s; }
        .notification.success { background: #48bb78; }
        .notification.show { transform: translateX(0); }
        
        @media (max-width: 768px) {
            .container { padding: 10px; }
            .header { flex-direction: column; gap: 15px; text-align: center; }
            .header-buttons { flex-direction: column; width: 100%; }
            th, td { padding: 8px; font-size: 14px; }
            .action-buttons { flex-direction: column; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Ürün Yönetimi</h1>
            <div class="header-buttons">
                <a href="add_product.php" class="btn btn-primary">+ Yeni Ürün Ekle</a>
                <a href="student_management.php" class="btn btn-primary">Öğrenci Yönetimi</a>
                <a href="student_login.php" class="btn btn-secondary">Çıkış Yap</a>
            </div>
        </div>
        
        <div class="products-section">
            <div class="section-header">
                <h2>Ürün Listesi (<?= count($products) ?> ürün)</h2>
            </div>
            
            <?php if (count($products) > 0): ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Resim</th>
                                <th>Ürün Adı</th>
                                <th>Fiyat</th>
                                <th>Stok</th>
                                <th>Hedef Sınıflar</th>
                                <th>Eklenme Tarihi</th>
                                <th>İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $product): ?>
                                <?php 
                                $images = json_decode($product['images'], true) ?: [];
                                $first_image = !empty($images) ? $images[0] : 'placeholder.jpg';
                                
                                $product_classes = json_decode($product['classes'], true) ?: [];
                                $class_display = !empty($product_classes) ? implode(', ', $product_classes) : 'Tüm Sınıflar';
                                ?>
                                <tr>
                                    <td>
                                        <img src="uploads/<?= htmlspecialchars($first_image) ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="product-thumb">
                                    </td>
                                    <td>
                                        <strong><?= htmlspecialchars($product['name']) ?></strong><br>
                                        <small><?= htmlspecialchars(substr($product['description'], 0, 50)) ?>...</small>
                                    </td>
                                    <td>
                                        <strong><?= number_format($product['price'], 2) ?> ₺</strong>
                                    </td>
                                    <td>
                                        <?php if ($product['stock'] > 10): ?>
                                            <span class="stock-badge stock-high"><?= $product['stock'] ?> adet</span>
                                        <?php elseif ($product['stock'] > 0): ?>
                                            <span class="stock-badge stock-medium"><?= $product['stock'] ?> adet</span>
                                        <?php else: ?>
                                            <span class="stock-badge stock-low">Stokta yok</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <small><?= htmlspecialchars($class_display) ?></small>
                                    </td>
                                    <td>
                                        <small><?= date('d.m.Y', strtotime($product['created_at'])) ?></small>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="edit_product.php?id=<?= $product['id'] ?>" class="btn-edit">Düzenle</a>
                                            <button onclick="deleteProduct(<?= $product['id'] ?>)" class="btn-delete">Sil</button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div style="text-align: center; padding: 60px; color: #718096;">
                    <h3>Henüz ürün yok</h3>
                    <p>İlk ürününüzü eklemek için yukarıdaki butonu kullanın.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <?php if ($notification): ?>
        <div class="notification <?= $notification_type ?>" id="notification">
            <?= htmlspecialchars($notification) ?>
        </div>
        <script>
            const notification = document.getElementById('notification');
            setTimeout(() => notification.classList.add('show'), 100);
            setTimeout(() => notification.classList.remove('show'), 3000);
            
            if (window.history.replaceState) {
                window.history.replaceState(null, null, window.location.pathname);
            }
        </script>
    <?php endif; ?>
    
    <script>
        function deleteProduct(id) {
            if (confirm('Bu ürünü silmek istediğinizden emin misiniz?')) {
                window.location.href = 'admin_panel.php?delete=' + id;
            }
        }
    </script>
</body>
</html>
