<?php
session_start();

if (!isset($_SESSION['student_id']) || !isset($_GET['order_number'])) {
    header('Location: index.php');
    exit;
}

$order_number = $_GET['order_number'];
?>
<!DOCTYPE html>
<html>
<head>
    <title>Sipariş Başarılı</title>
    <meta charset="UTF-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f6fa; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
        .success-container { background: white; border-radius: 20px; padding: 60px; text-align: center; box-shadow: 0 20px 40px rgba(0,0,0,0.1); max-width: 500px; }
        .success-icon { font-size: 80px; margin-bottom: 30px; }
        .success-title { font-size: 32px; color: #48bb78; margin-bottom: 20px; font-weight: bold; }
        .success-message { color: #718096; margin-bottom: 30px; line-height: 1.6; }
        .order-number { background: #f7fafc; padding: 20px; border-radius: 10px; margin-bottom: 30px; }
        .order-number strong { color: #2d3748; font-size: 18px; }
        .action-buttons { display: flex; gap: 15px; justify-content: center; }
        .btn { padding: 15px 30px; border: none; border-radius: 25px; text-decoration: none; font-weight: bold; transition: all 0.3s; }
        .btn-primary { background: #667eea; color: white; }
        .btn-primary:hover { background: #5a67d8; transform: translateY(-2px); }
        .btn-secondary { background: #e2e8f0; color: #4a5568; }
        .btn-secondary:hover { background: #cbd5e0; }
    </style>
</head>
<body>
    <div class="success-container">
        <div class="success-icon">🎉</div>
        <h1 class="success-title">Sipariş Oluşturuldu!</h1>
        <p class="success-message">
            Siparişiniz başarıyla oluşturuldu. Sipariş durumunuzu siparişlerim sayfasından takip edebilirsiniz.
        </p>
        
        <div class="order-number">
            <strong>Sipariş Numaranız: <?= htmlspecialchars($order_number) ?></strong>
        </div>
        
        <div class="action-buttons">
            <a href="my_orders.php" class="btn btn-primary">Siparişlerim</a>
            <a href="index.php" class="btn btn-secondary">Ana Sayfa</a>
        </div>
    </div>
</body>
</html>
