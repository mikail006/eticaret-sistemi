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
    <title>Sipariş Başarılı - E-Ticaret</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: #f5f6fa; min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        
        .success-container { background: white; border-radius: 20px; padding: 60px 40px; text-align: center; box-shadow: 0 10px 40px rgba(0,0,0,0.1); border: 1px solid #f0f0f0; max-width: 600px; margin: 20px; }
        .success-icon { font-size: 80px; margin-bottom: 30px; color: #28a745; }
        .success-title { font-size: 32px; color: #28a745; margin-bottom: 20px; font-weight: 700; }
        .success-message { color: #666; margin-bottom: 30px; line-height: 1.6; font-size: 18px; }
        
        .order-info { background: #f8f9fa; border: 2px solid #e9ecef; padding: 25px; border-radius: 15px; margin-bottom: 30px; }
        .order-number { font-size: 20px; font-weight: 700; color: #333; margin-bottom: 10px; }
        .order-detail { color: #666; font-size: 16px; }
        
        .action-buttons { display: flex; gap: 15px; justify-content: center; flex-wrap: wrap; }
        .btn { 
            padding: 15px 30px; 
            border: none; 
            border-radius: 12px; 
            text-decoration: none; 
            font-weight: 600; 
            transition: all 0.2s;
            font-size: 16px;
        }
        .btn:hover { transform: translateY(-2px); }
        .btn-primary { background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; }
        .btn-secondary { background: #f8f9fa; color: #666; border: 2px solid #e9ecef; }
        .btn-secondary:hover { background: #e9ecef; }
        
        @media (max-width: 768px) {
            .success-container { padding: 40px 30px; margin: 10px; }
            .success-icon { font-size: 60px; }
            .success-title { font-size: 28px; }
            .success-message { font-size: 16px; }
            .action-buttons { flex-direction: column; }
        }
        
        @media (max-width: 480px) {
            .success-container { padding: 30px 20px; }
            .success-icon { font-size: 50px; }
            .success-title { font-size: 24px; }
            .order-info { padding: 20px; }
        }
    </style>
</head>
<body>
    <div class="success-container">
        <div class="success-icon">✅</div>
        <h1 class="success-title">Sipariş Oluşturuldu!</h1>
        <p class="success-message">
            Siparişiniz başarıyla oluşturuldu. Sipariş durumunuzu siparişlerim sayfasından takip edebilirsiniz.
        </p>
        
        <div class="order-info">
            <div class="order-number">Sipariş Numaranız</div>
            <div class="order-detail"><?= htmlspecialchars($order_number) ?></div>
        </div>
        
        <div class="action-buttons">
            <a href="my_orders.php" class="btn btn-primary">Siparişlerim</a>
            <a href="index.php" class="btn btn-secondary">Ana Sayfa</a>
        </div>
    </div>
</body>
</html>
