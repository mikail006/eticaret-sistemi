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
    <title>Siparişlerim - E-Ticaret</title>
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
        
        /* ORDERS */
        .orders-section { background: white; border-radius: 15px; padding: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border: 1px solid #f0f0f0; }
        .orders-title { font-size: 28px; font-weight: 700; color: #333; margin-bottom: 30px; }
        
        .order-card { border: 2px solid #f0f0f0; border-radius: 15px; padding: 25px; margin-bottom: 20px; transition: all 0.2s; }
        .order-card:hover { border-color: #e9ecef; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        
        .order-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .order-number { font-size: 18px; font-weight: 700; color: #333; }
        .order-date { color: #666; font-size: 14px; }
        
        .status-badge { padding: 8px 16px; border-radius: 20px; font-size: 12px; font-weight: 600; text-transform: uppercase; }
        .status-beklemede { background: #fff3cd; color: #856404; }
        .status-onaylandi { background: #d1ecf1; color: #0c5460; }
        .status-hazirlaniyor { background: #cce5ff; color: #004085; }
        .status-kargoda { background: #e2e3ff; color: #383d41; }
        .status-teslim_edildi { background: #d4edda; color: #155724; }
        .status-iptal { background: #f8d7da; color: #721c24; }
        
        .order-details { display: grid; grid-template-columns: 2fr 1fr 1fr; gap: 20px; align-items: center; }
        .order-items h4 { color: #333; margin-bottom: 8px; font-size: 16px; }
        .product-list { color: #666; line-height: 1.5; font-size: 14px; }
        .order-payment { text-align: center; }
        .payment-method { font-weight: 600; color: #333; margin-bottom: 5px; }
        .order-summary { text-align: right; }
        .order-total { font-size: 20px; font-weight: 700; color: #28a745; }
        
        /* BOŞ DURUM */
        .empty-orders { text-align: center; padding: 80px 20px; color: #666; }
        .empty-orders h3 { font-size: 24px; margin-bottom: 15px; color: #333; }
        .empty-orders p { font-size: 18px; margin-bottom: 30px; }
        .btn { 
            padding: 15px 30px; 
            border: none; 
            border-radius: 10px; 
            cursor: pointer; 
            text-decoration: none; 
            text-align: center; 
            font-size: 16px; 
            font-weight: 600;
        }
        .btn-primary { background: linear-gradient(135deg, #333 0%, #555 100%); color: white; }
        
        /* MOBİL */
        @media (max-width: 1024px) {
            .order-details { grid-template-columns: 1fr; gap: 15px; text-align: center; }
        }
        
        @media (max-width: 768px) {
            .container { padding: 15px; }
            .header-container { flex-direction: column; gap: 15px; }
            .header-nav { flex-wrap: wrap; justify-content: center; }
            
            .orders-section { padding: 20px; }
            .order-card { padding: 20px; }
            .order-header { flex-direction: column; gap: 10px; text-align: center; }
            .orders-title { font-size: 24px; }
        }
        
        @media (max-width: 480px) {
            .container { padding: 10px; }
            .orders-section { padding: 15px; }
            .order-card { padding: 15px; }
            .orders-title { font-size: 22px; }
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
                    <a href="cart.php" class="nav-link">Sepetim</a>
                    <a href="my_orders.php" class="nav-link active">Siparişlerim</a>
                    <a href="student_login.php" class="nav-link">Çıkış</a>
                </nav>
            </div>
        </div>
        
        <!-- SİPARİŞLER -->
        <div class="orders-section">
            <h1 class="orders-title">Siparişlerim (<?= count($orders) ?> sipariş)</h1>
            
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
                            
                            <div class="order-payment">
                                <div class="payment-method">
                                    <?php
                                    $payment_labels = [
                                        'eft' => 'EFT/Havale',
                                        'kredi_karti' => 'Kredi Kartı'
                                    ];
                                    echo $payment_labels[$order['payment_method']] ?? $order['payment_method'];
                                    ?>
                                </div>
                            </div>
                            
                            <div class="order-summary">
                                <div class="order-total"><?= number_format($order['total_amount'], 2) ?> TL</div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-orders">
                    <h3>Henüz siparişiniz yok</h3>
                    <p>İlk siparişinizi vermek için alışverişe başlayın!</p>
                    <a href="index.php" class="btn btn-primary">Alışverişe Başla</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
