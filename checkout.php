<?php
session_start();
include 'config.php';
include 'notification_functions.php';

if (!isset($_SESSION['student_id'])) {
    header('Location: student_login.php');
    exit;
}

$student_id = $_SESSION['student_id'];

// Öğrenci bilgilerini al
$stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
$stmt->execute([$student_id]);
$student = $stmt->fetch();

// Sepetteki ürünleri getir
$stmt = $pdo->prepare("
    SELECT c.*, p.name, p.price, p.stock, p.images 
    FROM cart c 
    JOIN products p ON c.product_id = p.id 
    WHERE c.student_id = ?
");
$stmt->execute([$student_id]);
$cart_items = $stmt->fetchAll();

if (empty($cart_items)) {
    header('Location: cart.php');
    exit;
}

// Toplam fiyat hesapla
$total_amount = 0;
foreach ($cart_items as $item) {
    $total_amount += $item['price'] * $item['quantity'];
}

$notification = '';
$notification_type = '';

// Sipariş işleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    try {
        $payment_method = $_POST['payment_method'];
        $order_number = 'ORD' . date('Ymd') . '_' . uniqid();
        
        // Sipariş oluştur
        $stmt = $pdo->prepare("
            INSERT INTO orders (student_id, order_number, total_amount, payment_method, status, student_name, student_class, student_phone, student_address) 
            VALUES (?, ?, ?, ?, 'beklemede', ?, ?, ?, ?)
        ");
        $stmt->execute([
            $student_id, 
            $order_number, 
            $total_amount, 
            $payment_method, 
            $student['full_name'], 
            $student['class'], 
            $student['phone'], 
            $student['address']
        ]);
        
        $order_id = $pdo->lastInsertId();
        
        // Sipariş detaylarını ekle ve stok güncelle
        foreach ($cart_items as $item) {
            // Sipariş detayı ekle
            $stmt = $pdo->prepare("
                INSERT INTO order_items (order_id, product_id, product_name, product_price, quantity, total_price) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $order_id, 
                $item['product_id'], 
                $item['name'], 
                $item['price'], 
                $item['quantity'], 
                $item['price'] * $item['quantity']
            ]);
            
            // Stok güncelle
            $stmt = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
            $stmt->execute([$item['quantity'], $item['product_id']]);
        }
        
        // Bildirimleri gönder
        notifyNewOrder($pdo, $order_number, $student['full_name']);
        
        // Sepeti temizle
        $stmt = $pdo->prepare("DELETE FROM cart WHERE student_id = ?");
        $stmt->execute([$student_id]);
        
        // Başarı sayfasına yönlendir
        header("Location: order_success.php?order_number=" . $order_number);
        exit;
        
    } catch (Exception $e) {
        $notification = "Sipariş oluşturulurken hata oluştu";
        $notification_type = "error";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Sipariş Tamamla</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f6fa; }
        .container { max-width: 1000px; margin: 0 auto; padding: 20px; }
        
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 15px; margin-bottom: 30px; box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3); display: flex; justify-content: space-between; align-items: center; }
        .header h1 { font-size: 28px; }
        .header-buttons { display: flex; gap: 15px; }
        .btn { padding: 12px 24px; border: none; border-radius: 25px; text-decoration: none; font-weight: bold; transition: all 0.3s; cursor: pointer; display: inline-block; text-align: center; }
        .btn-secondary { background: rgba(255,255,255,0.2); color: white; }
        .btn-secondary:hover { background: rgba(255,255,255,0.3); }
        .btn-success { background: #48bb78; color: white; font-size: 18px; padding: 15px 30px; }
        .btn-success:hover { background: #38a169; transform: translateY(-2px); }
        .btn-success:disabled { background: #cbd5e0; cursor: not-allowed; transform: none; }
        
        .checkout-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 30px; }
        .checkout-section { background: white; border-radius: 15px; padding: 25px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
        
        .order-summary h3 { color: #2d3748; margin-bottom: 20px; }
        .order-item { display: flex; align-items: center; gap: 15px; padding: 15px 0; border-bottom: 1px solid #e2e8f0; }
        .order-item:last-child { border-bottom: none; }
        .item-image { width: 60px; height: 60px; border-radius: 8px; object-fit: cover; }
        .item-details { flex: 1; }
        .item-name { font-weight: 600; color: #2d3748; }
        .item-price { color: #667eea; font-weight: 600; }
        .item-quantity { color: #718096; font-size: 14px; }
        
        .summary-total { background: #f7fafc; padding: 20px; border-radius: 10px; margin-top: 20px; }
        .summary-row { display: flex; justify-content: space-between; margin-bottom: 10px; }
        .summary-final { font-size: 24px; font-weight: bold; color: #2d3748; border-top: 2px solid #e2e8f0; padding-top: 15px; margin-top: 15px; }
        
        .payment-section h3 { color: #2d3748; margin-bottom: 20px; }
        .payment-methods { display: grid; gap: 15px; margin-bottom: 30px; }
        .payment-option { border: 2px solid #e2e8f0; border-radius: 10px; padding: 20px; cursor: pointer; transition: all 0.3s; }
        .payment-option:hover { border-color: #667eea; background: #f0f4ff; }
        .payment-option.selected { border-color: #667eea; background: #f0f4ff; }
        .payment-option input[type="radio"] { margin-right: 12px; }
        .payment-title { font-weight: 600; color: #2d3748; margin-bottom: 5px; }
        .payment-desc { color: #718096; font-size: 14px; }
        
        .credit-card-form { display: none; margin-top: 20px; padding: 20px; background: #f7fafc; border-radius: 10px; }
        .credit-card-form.show { display: block; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px; }
        .form-full { grid-column: 1 / -1; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 600; color: #2d3748; }
        .form-group input { width: 100%; padding: 12px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 16px; }
        .form-group input:focus { outline: none; border-color: #667eea; }
        
        .student-info { background: #f7fafc; padding: 20px; border-radius: 10px; margin-bottom: 30px; }
        .student-info h4 { color: #2d3748; margin-bottom: 15px; }
        .info-row { display: flex; justify-content: space-between; margin-bottom: 8px; }
        
        .notification { position: fixed; bottom: 100px; right: 30px; padding: 15px 25px; border-radius: 10px; color: white; font-weight: 600; z-index: 1000; transform: translateX(400px); transition: transform 0.3s; }
        .notification.error { background: #e53e3e; }
        .notification.show { transform: translateX(0); }
        
        @media (max-width: 768px) {
            .container { padding: 10px; }
            .header { flex-direction: column; gap: 15px; text-align: center; }
            .checkout-grid { grid-template-columns: 1fr; }
            .order-item { flex-direction: column; text-align: center; }
            .form-row { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Sipariş Tamamla</h1>
            <div class="header-buttons">
                <a href="cart.php" class="btn btn-secondary">Sepete Dön</a>
                <a href="index.php" class="btn btn-secondary">Ana Sayfa</a>
            </div>
        </div>
        
        <div class="checkout-grid">
            <div class="checkout-section payment-section">
                <h3>Ödeme Bilgileri</h3>
                
                <div class="student-info">
                    <h4>Teslimat Bilgileri</h4>
                    <div class="info-row">
                        <span>Ad Soyad:</span>
                        <strong><?= htmlspecialchars($student['full_name']) ?></strong>
                    </div>
                    <div class="info-row">
                        <span>Sınıf:</span>
                        <strong><?= htmlspecialchars($student['class']) ?></strong>
                    </div>
                    <div class="info-row">
                        <span>Telefon:</span>
                        <strong><?= htmlspecialchars($student['phone'] ?: 'Belirtilmemiş') ?></strong>
                    </div>
                    <div class="info-row">
                        <span>Adres:</span>
                        <strong><?= htmlspecialchars($student['address'] ?: 'Belirtilmemiş') ?></strong>
                    </div>
                </div>
                
                <form method="POST" id="checkoutForm">
                    <div class="payment-methods">
                        <label class="payment-option" onclick="selectPayment('eft')">
                            <input type="radio" name="payment_method" value="eft" required>
                            <div class="payment-title">EFT / Havale</div>
                            <div class="payment-desc">Banka hesabına para transferi</div>
                        </label>
                        
                        <label class="payment-option" onclick="selectPayment('kredi_karti')">
                            <input type="radio" name="payment_method" value="kredi_karti" required>
                            <div class="payment-title">Kredi Kartı</div>
                            <div class="payment-desc">Visa, MasterCard kabul edilir</div>
                        </label>
                    </div>
                    
                    <div class="credit-card-form" id="creditCardForm">
                        <h4 style="margin-bottom: 20px; color: #2d3748;">Kredi Kartı Bilgileri</h4>
                        <div class="form-group">
                            <label>Kart Üzerindeki İsim</label>
                            <input type="text" id="cardName" placeholder="Ad Soyad">
                        </div>
                        <div class="form-group">
                            <label>Kart Numarası</label>
                            <input type="text" id="cardNumber" placeholder="1234 5678 9012 3456" maxlength="19">
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Son Kullanma Tarihi</label>
                                <input type="text" id="cardExpiry" placeholder="MM/YY" maxlength="5">
                            </div>
                            <div class="form-group">
                                <label>CVV</label>
                                <input type="text" id="cardCvv" placeholder="123" maxlength="3">
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" name="place_order" class="btn btn-success" id="submitBtn" style="width: 100%;" disabled>
                        Ödeme Yöntemi Seçin
                    </button>
                </form>
            </div>
            
            <div class="checkout-section order-summary">
                <h3>Sipariş Özeti</h3>
                
                <?php foreach ($cart_items as $item): ?>
                    <?php 
                    $images = json_decode($item['images'], true) ?: [];
                    $first_image = !empty($images) ? $images[0] : 'placeholder.jpg';
                    ?>
                    <div class="order-item">
                        <img src="uploads/<?= htmlspecialchars($first_image) ?>" alt="<?= htmlspecialchars($item['name']) ?>" class="item-image">
                        <div class="item-details">
                            <div class="item-name"><?= htmlspecialchars($item['name']) ?></div>
                            <div class="item-quantity"><?= $item['quantity'] ?> adet</div>
                            <div class="item-price"><?= number_format($item['price'] * $item['quantity'], 2) ?> ₺</div>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <div class="summary-total">
                    <div class="summary-row">
                        <span>Ürün Sayısı:</span>
                        <span><?= count($cart_items) ?> çeşit</span>
                    </div>
                    <div class="summary-row">
                        <span>Toplam Miktar:</span>
                        <span><?= array_sum(array_column($cart_items, 'quantity')) ?> adet</span>
                    </div>
                    <div class="summary-row summary-final">
                        <span>Genel Toplam:</span>
                        <span><?= number_format($total_amount, 2) ?> ₺</span>
                    </div>
                </div>
            </div>
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
        </script>
    <?php endif; ?>
    
    <script>
        function selectPayment(method) {
            document.querySelectorAll('.payment-option').forEach(option => {
                option.classList.remove('selected');
            });
            event.currentTarget.classList.add('selected');
            
            const creditCardForm = document.getElementById('creditCardForm');
            const submitBtn = document.getElementById('submitBtn');
            
            if (method === 'kredi_karti') {
                creditCardForm.classList.add('show');
                submitBtn.textContent = 'Ödeme Yap (<?= number_format($total_amount, 2) ?> ₺)';
                checkCreditCardForm();
            } else {
                creditCardForm.classList.remove('show');
                submitBtn.textContent = 'Siparişi Tamamla (<?= number_format($total_amount, 2) ?> ₺)';
                submitBtn.disabled = false;
            }
        }
        
        function checkCreditCardForm() {
            const cardName = document.getElementById('cardName').value;
            const cardNumber = document.getElementById('cardNumber').value;
            const cardExpiry = document.getElementById('cardExpiry').value;
            const cardCvv = document.getElementById('cardCvv').value;
            const submitBtn = document.getElementById('submitBtn');
            
            if (cardName && cardNumber.length >= 16 && cardExpiry.length === 5 && cardCvv.length === 3) {
                submitBtn.disabled = false;
            } else {
                submitBtn.disabled = true;
            }
        }
        
        // Kart numarası formatla
        document.getElementById('cardNumber').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\s/g, '').replace(/[^0-9]/gi, '');
            let formattedValue = value.match(/.{1,4}/g)?.join(' ');
            if (formattedValue) {
                e.target.value = formattedValue;
            }
            checkCreditCardForm();
        });
        
        // Expiry formatla
        document.getElementById('cardExpiry').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length >= 2) {
                value = value.substring(0, 2) + '/' + value.substring(2, 4);
            }
            e.target.value = value;
            checkCreditCardForm();
        });
        
        // CVV sadece rakam
        document.getElementById('cardCvv').addEventListener('input', function(e) {
            e.target.value = e.target.value.replace(/[^0-9]/g, '');
            checkCreditCardForm();
        });
        
        // İsim alanı kontrolü
        document.getElementById('cardName').addEventListener('input', checkCreditCardForm);
    </script>
</body>
</html>
