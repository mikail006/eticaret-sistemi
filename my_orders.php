<?php
session_start();
include 'config.php';

if (!isset($_SESSION['student_id'])) {
    header('Location: student_login.php');
    exit;
}

$student_id = $_SESSION['student_id'];

$notification = '';
$notification_type = '';
if (isset($_GET['success']) && $_GET['success'] === 'order_placed') {
    $order_number = $_GET['order_number'] ?? '';
    $notification = "Sipariş başarıyla oluşturuldu! Sipariş No: " . $order_number;
    $notification_type = "success";
}

// Öğrencinin siparişlerini getir
$stmt = $pdo->prepare("
    SELECT o.*, 
           COUNT(oi.id) as item_count,
           GROUP_CONCAT(oi.product_name SEPARATOR ', ') as product_names
    FROM orders o 
    LEFT JOIN order_items oi ON o.id = oi.order_id 
    WHERE o.student_id = ? 
    GROUP BY o.id 
    ORDER BY o.order_date DESC
");
$stmt->execute([$student_id]);
$orders = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Siparişlerim</title>
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
        .btn-secondary { background: rgba(255,255,255,0.2); color: white; }
        .btn-secondary:hover { background: rgba(255,255,255,0.3); }
        .btn-primary { background: rgba(255,255,255,0.9); color: #667eea; }
        .btn-primary:hover { background: white; transform: translateY(-2px); }
        
        .orders-section { background: white; border-radius: 15px; padding: 25px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
        .section-header { margin-bottom: 25px; }
        .section-header h2 { color: #2d3748; }
        
        .order-card { border: 2px solid #e2e8f0; border-radius: 15px; padding: 25px; margin-bottom: 20px; transition: all 0.3s; }
        .order-card:hover { border-color: #667eea; transform: translateY(-2px); }
        
        .order-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .order-number { font-size: 18px; font-weight: bold; color: #2d3748; }
        .order-date { color: #718096; }
        
        .status-badge { padding: 6px 15px; border-radius: 20px; font-size: 12px; font-weight: 600; text-transform: uppercase; }
        .status-beklemede { background: #fef5e7; color: #d69e2e; }
        .status-onaylandi { background: #e6fffa; color: #2c7a7b; }
        .status-hazirlaniyor { background: #ebf8ff; color: #2b6cb0; }
        .status-kargoda { background: #f0f4ff; color: #5a67d8; }
        .status-teslim_edildi { background: #f0fff4; color: #22543d; }
        .status-iptal { background: #fed7d7; color: #c53030; }
        
        .order-details { display: grid; grid-template-columns: 2fr 1fr 1fr; gap: 20px; align-items: start; }
        .order-items { }
        .order-items h4 { color: #4a5568; margin-bottom: 10px; }
        .product-list { color: #718096; line-height: 1.6; }
        .order-summary { text-align: right; }
        .order-total { font-size: 24px; font-weight: bold; color: #667eea; }
        .order-method { color: #718096; margin-top: 5px; }
        
        .empty-state { text-align: center; padding: 60px; color: #718096; }
        .empty-state h3 { margin-bottom: 10px; }
        
        .notification { position: fixed; bottom: 100px; right: 30px; padding: 15px 25px; border-radius: 10px; color: white; font-weight: 600; z-index: 1000; transform: translateX(400px); transition: transform 0.3s; }
        .notification.success { background: #48bb78; }
        .notification.show { transform: translateX(0); }
        
        @media (max-width: 768px) {
            .container { padding: 10px; }
            .header { flex-direction: column; gap: 15px; text-align: center; }
            .order-header { flex-direction: column; gap: 10px; text-align: center; }
            .order-details { grid-template-columns: 1fr; gap: 15px; text-align: center; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Siparişlerim</h1>
            <div class="header-buttons">
                <a href="index.php" class="btn btn-secondary">Ana Sayfa</a>
                <a href="cart.php" class="btn btn-primary">Sepetim</a>
                <a href="student_login.php" class="btn btn-secondary">Çıkış</a>
            </div>
        </div>
        
        <div class="orders-section">
            <div class="section-header">
                <h2>Siparişlerim (<?= count($orders) ?> sipariş)</h2>
            </div>
            
            <?php if (count($orders) > 0): ?>
                <?php foreach ($orders as $order): ?>
                    <div class="order-card">
                        <div class="order-header">
                            <div>
                                <div class="order-number">Sipariş #<?= htmlspecialchars($order['order_number']) ?></div>
                                <div class="order-date"><?= date('d.m.Y H:i', strtotime($order['order_date'])) ?></div>
                            </div>
                            <span class="status-badge status-<?= $order['status'] ?>">
                                <?php
                                $status_labels = [
                                    'beklemede' => 'Beklemede',
                                    'onaylandi' => 'Onaylandı',
                                    'hazirlaniyor' => 'Hazırlanıyor',
                                    'kargoda' => 'Kargoda',
                                    'teslim_edildi' => 'Teslim Edildi',
                                    'iptal' => 'İptal'
                                ];
                                echo $status_labels[$order['status']] ?? $order['status'];
                                ?>
                            </span>
                        </div>
                        
                        <div class="order-details">
                            <div class="order-items">
                                <h4><?= $order['item_count'] ?> ürün</h4>
                                <div class="product-list">
                                    <?= htmlspecialchars($order['product_names']) ?>
                                </div>
                            </div>
                            
                            <div class="order-method">
                                <strong>Ödeme:</strong><br>
                                <?php
                                $payment_labels = [
                                    'eft' => 'EFT/Havale',
                                    'kredi_karti' => 'Kredi Kartı'
                                ];
                                echo $payment_labels[$order['payment_method']] ?? $order['payment_method'];
                                ?>
                            </div>
                            
                            <div class="order-summary">
                                <div class="order-total"><?= number_format($order['total_amount'], 2) ?> ₺</div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <h3>Henüz siparişiniz yok</h3>
                    <p>İlk siparişinizi vermek için alışverişe başlayın!</p>
                    <br>
                    <a href="index.php" class="btn btn-primary">Alışverişe Başla</a>
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
            setTimeout(() => notification.classList.remove('show'), 4000);
            
            if (window.history.replaceState) {
                window.history.replaceState(null, null, window.location.pathname);
            }
        </script>
    <?php endif; ?>
</body>
</html>
