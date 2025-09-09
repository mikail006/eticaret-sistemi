<?php
session_start();
include 'config.php';
include 'notification_functions.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit;
}

$notification = '';
$notification_type = '';

// Sipariş durumu güncelleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $order_id = $_POST['order_id'];
    $new_status = $_POST['status'];
    
    $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->execute([$new_status, $order_id]);
    
    // Sipariş durum değişikliği bildirimi gönder
    notifyOrderStatusChange($pdo, $order_id, $new_status);
    
    $notification = "Sipariş durumu güncellendi ve müşteriye bildirim gönderildi";
    $notification_type = "success";
}

// Siparişleri getir
$stmt = $pdo->query("
    SELECT o.*, 
           COUNT(oi.id) as item_count,
           SUM(oi.quantity) as total_items,
           GROUP_CONCAT(CONCAT(oi.product_name, ' (', oi.quantity, 'x)') SEPARATOR ', ') as product_details
    FROM orders o 
    LEFT JOIN order_items oi ON o.id = oi.order_id 
    GROUP BY o.id 
    ORDER BY o.order_date DESC
");
$orders = $stmt->fetchAll();

// İstatistikler
$stats = [
    'total_orders' => count($orders),
    'pending_orders' => count(array_filter($orders, fn($o) => $o['status'] === 'beklemede')),
    'completed_orders' => count(array_filter($orders, fn($o) => $o['status'] === 'teslim_edildi')),
    'total_revenue' => array_sum(array_column($orders, 'total_amount'))
];
?>
<!DOCTYPE html>
<html>
<head>
    <title>Sipariş Yönetimi</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
        
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: white; padding: 25px; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); text-align: center; }
        .stat-number { font-size: 36px; font-weight: bold; color: #667eea; margin-bottom: 10px; }
        .stat-label { color: #718096; font-weight: 600; }
        
        .orders-section { background: white; border-radius: 15px; padding: 25px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
        .section-header { margin-bottom: 25px; }
        .section-header h2 { color: #2d3748; }
        
        .table-container { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 15px; text-align: left; border-bottom: 1px solid #e2e8f0; }
        th { background: #f7fafc; color: #4a5568; font-weight: 600; }
        tr:hover { background: #f9f9f9; }
        
        .order-number { font-weight: 600; color: #2d3748; }
        .customer-info { }
        .customer-name { font-weight: 600; color: #2d3748; }
        .customer-details { color: #718096; font-size: 14px; }
        
        .status-badge { padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; text-transform: uppercase; }
        .status-beklemede { background: #fef5e7; color: #d69e2e; }
        .status-onaylandi { background: #e6fffa; color: #2c7a7b; }
        .status-hazirlaniyor { background: #ebf8ff; color: #2b6cb0; }
        .status-kargoda { background: #f0f4ff; color: #5a67d8; }
        .status-teslim_edildi { background: #f0fff4; color: #22543d; }
        .status-iptal { background: #fed7d7; color: #c53030; }
        
        .status-select { padding: 8px 12px; border: 2px solid #e2e8f0; border-radius: 8px; background: white; cursor: pointer; }
        .status-select:focus { outline: none; border-color: #667eea; }
        
        .update-btn { background: #4299e1; color: white; padding: 6px 12px; border: none; border-radius: 6px; cursor: pointer; font-size: 12px; }
        .update-btn:hover { background: #3182ce; }
        
        .order-total { font-weight: bold; color: #667eea; font-size: 18px; }
        
        .notification { position: fixed; bottom: 100px; right: 30px; padding: 15px 25px; border-radius: 10px; color: white; font-weight: 600; z-index: 1000; transform: translateX(400px); transition: transform 0.3s; }
        .notification.success { background: #48bb78; }
        .notification.show { transform: translateX(0); }
        
        @media (max-width: 768px) {
            .container { padding: 10px; }
            .header { flex-direction: column; gap: 15px; text-align: center; }
            .stats-grid { grid-template-columns: 1fr; }
            th, td { padding: 8px; font-size: 14px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Sipariş Yönetimi</h1>
            <div class="header-buttons">
                <a href="admin_panel.php" class="btn btn-primary">Ürün Yönetimi</a>
                <a href="notifications.php" class="btn btn-primary">Bildirimler</a>
                <a href="support.php" class="btn btn-primary">Destek</a>
                <a href="student_login.php" class="btn btn-secondary">Çıkış</a>
            </div>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?= $stats['total_orders'] ?></div>
                <div class="stat-label">Toplam Sipariş</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $stats['pending_orders'] ?></div>
                <div class="stat-label">Bekleyen Sipariş</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $stats['completed_orders'] ?></div>
                <div class="stat-label">Tamamlanan</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= number_format($stats['total_revenue'], 0) ?> ₺</div>
                <div class="stat-label">Toplam Gelir</div>
            </div>
        </div>
        
        <div class="orders-section">
            <div class="section-header">
                <h2>Tüm Siparişler (<?= count($orders) ?> sipariş)</h2>
            </div>
            
            <?php if (count($orders) > 0): ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Sipariş No</th>
                                <th>Müşteri</th>
                                <th>Ürünler</th>
                                <th>Tutar</th>
                                <th>Ödeme</th>
                                <th>Durum</th>
                                <th>Tarih</th>
                                <th>İşlem</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td>
                                        <div class="order-number">#<?= htmlspecialchars($order['order_number']) ?></div>
                                    </td>
                                    <td>
                                        <div class="customer-info">
                                            <div class="customer-name"><?= htmlspecialchars($order['student_name']) ?></div>
                                            <div class="customer-details">
                                                <?= htmlspecialchars($order['student_class']) ?><br>
                                                <?= htmlspecialchars($order['student_phone'] ?: 'Tel: -') ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <small><?= $order['item_count'] ?> çeşit, <?= $order['total_items'] ?> adet</small><br>
                                        <small style="color: #718096;"><?= htmlspecialchars(substr($order['product_details'], 0, 50)) ?>...</small>
                                    </td>
                                    <td>
                                        <div class="order-total"><?= number_format($order['total_amount'], 2) ?> ₺</div>
                                    </td>
                                    <td>
                                        <small>
                                            <?php
                                            $payment_labels = [
                                                'eft' => 'EFT/Havale',
                                                'kredi_karti' => 'Kredi Kartı'
                                            ];
                                            echo $payment_labels[$order['payment_method']] ?? $order['payment_method'];
                                            ?>
                                        </small>
                                    </td>
                                    <td>
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
                                    </td>
                                    <td>
                                        <small><?= date('d.m.Y H:i', strtotime($order['order_date'])) ?></small>
                                    </td>
                                    <td>
                                        <form method="POST" style="display: flex; gap: 5px; align-items: center;">
                                            <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                            <select name="status" class="status-select">
                                                <option value="beklemede" <?= $order['status'] === 'beklemede' ? 'selected' : '' ?>>Beklemede</option>
                                                <option value="onaylandi" <?= $order['status'] === 'onaylandi' ? 'selected' : '' ?>>Onaylandı</option>
                                                <option value="hazirlaniyor" <?= $order['status'] === 'hazirlaniyor' ? 'selected' : '' ?>>Hazırlanıyor</option>
                                                <option value="kargoda" <?= $order['status'] === 'kargoda' ? 'selected' : '' ?>>Kargoda</option>
                                                <option value="teslim_edildi" <?= $order['status'] === 'teslim_edildi' ? 'selected' : '' ?>>Teslim Edildi</option>
                                                <option value="iptal" <?= $order['status'] === 'iptal' ? 'selected' : '' ?>>İptal</option>
                                            </select>
                                            <button type="submit" name="update_status" class="update-btn">Güncelle</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div style="text-align: center; padding: 60px; color: #718096;">
                    <h3>Henüz sipariş yok</h3>
                    <p>Müşteriler sipariş vermeye başladığında burada görünecek.</p>
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
</body>
</html>
