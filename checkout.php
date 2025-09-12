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
$subtotal = 0;
foreach ($cart_items as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}
$kdv_amount = $subtotal * 0.20;
$total_amount = $subtotal + $kdv_amount;

// Sepet sayısı
$cart_count_stmt = $pdo->prepare("SELECT COUNT(*) FROM cart WHERE student_id = ?");
$cart_count_stmt->execute([$student_id]);
$cart_count = $cart_count_stmt->fetchColumn();

$notification = '';
$notification_type = '';

// Sipariş işleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    try {
        $payment_method = $_POST['payment_method'];
        $order_number = 'ORD' . date('Ymd') . '_' . uniqid();
        
        // Sipariş oluştur
        $stmt = $pdo->prepare("
            INSERT INTO orders (student_id, order_number, total_amount, payment_method, status, student_name, student_class, student_phone, student_address, parent_name, parent_phone, parent_address) 
            VALUES (?, ?, ?, ?, 'beklemede', ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $student_id, 
            $order_number, 
            $total_amount, 
            $payment_method, 
            $student['full_name'], 
            $student['class'], 
            $student['phone'], 
            $student['address'],
            $student['parent_name'],
            $student['parent_phone'],
            $student['parent_address']
        ]);
        
        $order_id = $pdo->lastInsertId();
        
        // Sipariş detaylarını ekle ve stok güncelle
        foreach ($cart_items as $item) {
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
            
            $stmt = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
            $stmt->execute([$item['quantity'], $item['product_id']]);
        }
        
        // Bildirimleri gönder
        notifyNewOrder($pdo, $order_number, $student['full_name']);
        
        // Sepeti temizle
        $stmt = $pdo->prepare("DELETE FROM cart WHERE student_id = ?");
        $stmt->execute([$student_id]);
        
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
    <title>Ödeme - E-Ticaret</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: #f5f6fa; }
        .container { max-width: 1400px; margin: 0 auto; padding: 20px; }
        
        /* HEADER */
        .header { background: white; border: 2px solid #f0f0f0; border-radius: 15px; padding: 20px; margin-bottom: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .header-container { display: flex; justify-content: space-between; align-items: center; }
        
        .header-brand { display: flex; align-items: center; gap: 15px; color: #333; }
        .profile-image { width: 50px; height: 50px; border-radius: 50%; object-fit: cover; border: 2px solid #f0f0f0; }
        .profile-placeholder { width: 50px; height: 50px; border-radius: 50%; background: #e9ecef; }
        .student-name { font-size: 24px; font-weight: 700; margin-bottom: 5px; }
        .student-class { font-size: 16px; font-weight: 400; color: #666; }
        
        .header-nav { display: flex; gap: 20px; align-items: center; }
        .nav-link { 
            color: #333; 
            text-decoration: none; 
            font-weight: 500; 
            padding: 10px 16px; 
            border-radius: 8px;
        }
        .nav-link:hover { background: #f0f0f0; }
        .nav-link.active { background: #333; color: white; }
        .cart-count { font-weight: 700; color: #333; }
        
        /* CHECKOUT LAYOUT */
        .checkout-container { display: grid; grid-template-columns: 2fr 1fr; gap: 30px; }
        
        /* SOL TARAF - ÖDEME FORMU */
        .payment-section { background: white; border-radius: 15px; padding: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border: 1px solid #f0f0f0; }
        
        .section-title { font-size: 24px; font-weight: 700; color: #333; margin-bottom: 8px; }
        .section-subtitle { color: #666; margin-bottom: 32px; font-size: 16px; }
        
        /* BİLGİ SECTIONS */
        .info-section { background: #f8f9fa; border: 2px solid #f0f0f0; border-radius: 15px; padding: 25px; margin-bottom: 25px; }
        .info-title { font-size: 18px; font-weight: 600; color: #333; margin-bottom: 16px; }
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .info-item { }
        .info-label { font-size: 13px; font-weight: 600; color: #666; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px; }
        .info-value { font-size: 16px; color: #333; font-weight: 500; }
        .info-value-full { grid-column: 1 / -1; }
        
        /* ÖDEME YÖNTEMLERİ */
        .payment-methods { margin-bottom: 32px; }
        .method-title { font-size: 18px; font-weight: 600; color: #333; margin-bottom: 20px; }
        .method-options { display: grid; gap: 16px; }
        .method-option { 
            border: 2px solid #f0f0f0; 
            border-radius: 15px; 
            padding: 20px; 
            cursor: pointer; 
            position: relative;
        }
        .method-option:hover { border-color: #333; background: #f8f9fa; }
        .method-option.selected { border-color: #333; background: #f8f9fa; }
        .method-option input[type="radio"] { position: absolute; opacity: 0; }
        .method-header { display: flex; align-items: center; gap: 12px; margin-bottom: 8px; }
        .method-name { font-size: 16px; font-weight: 600; color: #333; }
        .method-desc { color: #666; font-size: 14px; }
        
        /* KVKK */
        .kvkk-section { margin-bottom: 20px; }
        .checkbox-group { display: flex; align-items: flex-start; gap: 10px; margin-bottom: 15px; }
        .checkbox-group input[type="checkbox"] { margin-top: 5px; }
        .checkbox-label { color: #333; font-size: 14px; line-height: 1.4; }
        .checkbox-label a { color: #333; text-decoration: underline; }
        
        /* SAĞ TARAF - SİPARİŞ ÖZETİ */
        .order-summary { background: white; border-radius: 15px; padding: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border: 1px solid #f0f0f0; position: sticky; top: 20px; }
        .summary-title { font-size: 20px; font-weight: 700; color: #333; margin-bottom: 24px; }
        
        .order-items { margin-bottom: 24px; }
        .order-item { display: flex; align-items: center; gap: 16px; padding: 16px 0; border-bottom: 1px solid #f0f0f0; }
        .order-item:last-child { border-bottom: none; }
        .item-image { width: 60px; height: 60px; border-radius: 50%; object-fit: cover; border: 2px solid #f0f0f0; }
        .item-details { flex: 1; }
        .item-name { font-size: 15px; font-weight: 600; color: #333; margin-bottom: 4px; }
        .item-quantity { font-size: 13px; color: #666; }
        .item-price { font-size: 16px; font-weight: 600; color: #333; }
        
        /* FIYAT ÖZETİ */
        .price-breakdown { border-top: 2px solid #f0f0f0; padding-top: 20px; }
        .price-row { display: flex; justify-content: space-between; margin-bottom: 12px; }
        .price-label { color: #666; font-size: 16px; }
        .price-value { font-weight: 600; color: #333; font-size: 16px; }
        .total-row { border-top: 2px solid #f0f0f0; padding-top: 16px; margin-top: 16px; }
        .total-label { font-size: 18px; font-weight: 700; color: #333; }
        .total-value { font-size: 24px; font-weight: 700; color: #28a745; }
        
        /* BUTONLAR */
        .action-buttons { display: flex; gap: 16px; margin-top: 32px; }
        .btn { 
            padding: 16px 32px; 
            border: none; 
            border-radius: 12px; 
            cursor: pointer; 
            text-decoration: none; 
            display: inline-block; 
            text-align: center; 
            font-size: 16px; 
            font-weight: 600;
            flex: 1;
        }
        .btn-secondary { background: #f8f9fa; color: #666; border: 2px solid #f0f0f0; }
        .btn-secondary:hover { background: #e9ecef; }
        .btn-primary { background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; }
        .btn-primary:disabled { background: #e9ecef; color: #666; cursor: not-allowed; }
        
        /* NOTIFICATION */
        .notification { 
            position: fixed; 
            bottom: 30px; 
            right: 30px; 
            padding: 16px 24px; 
            border-radius: 12px; 
            color: white; 
            font-weight: 600; 
            z-index: 1000; 
            transform: translateX(400px); 
            transition: transform 0.3s;
        }
        .notification.error { background: #dc3545; }
        .notification.show { transform: translateX(0); }
        
        /* RESPONSIVE */
        @media (max-width: 1024px) {
            .checkout-container { grid-template-columns: 1fr; }
            .order-summary { position: static; }
        }
        
        @media (max-width: 768px) {
            .container { padding: 15px; }
            .header-container { flex-direction: column; gap: 15px; }
            .header-nav { flex-wrap: wrap; justify-content: center; }
            
            .payment-section, .order-summary { padding: 20px; }
            .section-title { font-size: 22px; }
            .info-grid { grid-template-columns: 1fr; }
            .action-buttons { flex-direction: column; }
        }
        
        @media (max-width: 480px) {
            .container { padding: 10px; }
            .payment-section, .order-summary { padding: 20px 16px; }
            .section-title { font-size: 20px; }
            .info-section { padding: 20px; }
            
            .notification { 
                bottom: 20px; 
                right: 20px; 
                left: 20px;
                transform: translateY(100px);
            }
            .notification.show { transform: translateY(0); }
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
                    <a href="cart.php" class="nav-link">
                        Sepetim 
                        <?php if ($cart_count > 0): ?>
                            (<span class="cart-count"><?= $cart_count ?></span>)
                        <?php endif; ?>
                    </a>
                    <a href="my_orders.php" class="nav-link">Siparişlerim</a>
                    <a href="student_login.php" class="nav-link">Çıkış</a>
                </nav>
            </div>
        </div>
        
        <div class="checkout-container">
            <!-- SOL TARAF - ÖDEME FORMU -->
            <div class="payment-section">
                <h1 class="section-title">Ödeme Bilgileri</h1>
                <p class="section-subtitle">Siparişinizi tamamlamak için gerekli bilgileri kontrol edin</p>
                
                <!-- ÖĞRENCİ BİLGİLERİ -->
                <div class="info-section">
                    <h3 class="info-title">👤 Öğrenci Bilgileri</h3>
                    <div class="info-grid">
                        <div class="info-item">
                            <div class="info-label">Ad Soyad</div>
                            <div class="info-value"><?= htmlspecialchars($student['full_name']) ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Sınıf</div>
                            <div class="info-value"><?= htmlspecialchars($student['class']) ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Telefon</div>
                            <div class="info-value"><?= htmlspecialchars($student['phone'] ?: 'Belirtilmemiş') ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">E-posta</div>
                            <div class="info-value"><?= htmlspecialchars($student['email'] ?: 'Belirtilmemiş') ?></div>
                        </div>
                        <div class="info-item info-value-full">
                            <div class="info-label">Adres</div>
                            <div class="info-value"><?= htmlspecialchars($student['address'] ?: 'Belirtilmemiş') ?></div>
                        </div>
                    </div>
                </div>
                
                <!-- VELİ BİLGİLERİ -->
                <div class="info-section">
                    <h3 class="info-title">👨‍👩‍👧‍👦 Veli Bilgileri</h3>
                    <div class="info-grid">
                        <div class="info-item">
                            <div class="info-label">Veli Ad Soyad</div>
                            <div class="info-value"><?= htmlspecialchars($student['parent_name'] ?: 'Belirtilmemiş') ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Veli Telefon</div>
                            <div class="info-value"><?= htmlspecialchars($student['parent_phone'] ?: 'Belirtilmemiş') ?></div>
                        </div>
                        <div class="info-item info-value-full">
                            <div class="info-label">Veli Adres</div>
                            <div class="info-value"><?= htmlspecialchars($student['parent_address'] ?: 'Belirtilmemiş') ?></div>
                        </div>
                    </div>
                </div>
                
                <!-- ÖDEME YÖNTEMLERİ -->
                <form method="POST" id="checkoutForm">
                    <div class="payment-methods">
                        <h3 class="method-title">Ödeme Yöntemi Seçin</h3>
                        <div class="method-options">
                            <label class="method-option" onclick="selectPayment('eft')">
                                <input type="radio" name="payment_method" value="eft" required>
                                <div class="method-header">
                                    <div class="method-name">EFT / Havale</div>
                                </div>
                                <div class="method-desc">Banka hesabına para transferi ile ödeme</div>
                            </label>
                            
                            <label class="method-option" onclick="selectPayment('kredi_karti')">
                                <input type="radio" name="payment_method" value="kredi_karti" required>
                                <div class="method-header">
                                    <div class="method-name">Kredi Kartı</div>
                                </div>
                                <div class="method-desc">Visa, MasterCard ve diğer kartlar kabul edilir</div>
                            </label>
                        </div>
                    </div>
                    
                    <!-- KVKK VE KULLANIM ŞARTLARI -->
                    <div class="kvkk-section">
                        <div class="checkbox-group">
                            <input type="checkbox" id="kvkk" name="kvkk" required>
                            <label for="kvkk" class="checkbox-label">
                                <a href="#" onclick="showKVKK()">KVKK Aydınlatma Metni</a>ni okudum ve kabul ediyorum.
                            </label>
                        </div>
                        <div class="checkbox-group">
                            <input type="checkbox" id="terms" name="terms" required>
                            <label for="terms" class="checkbox-label">
                                <a href="#" onclick="showTerms()">Kullanım Şartları</a>nı okudum ve kabul ediyorum.
                            </label>
                        </div>
                    </div>
                    
                    <div class="action-buttons">
                        <a href="cart.php" class="btn btn-secondary">Sepete Dön</a>
                        <button type="submit" name="place_order" class="btn btn-primary" id="submitBtn" disabled>
                            Ödeme Yöntemi Seçin
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- SAĞ TARAF - SİPARİŞ ÖZETİ -->
            <div class="order-summary">
                <h2 class="summary-title">Sipariş Özeti</h2>
                
                <div class="order-items">
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
                            </div>
                            <div class="item-price"><?= number_format($item['price'] * $item['quantity'], 2) ?> TL</div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="price-breakdown">
                    <div class="price-row">
                        <span class="price-label">Ürün Sayısı:</span>
                        <span class="price-value"><?= count($cart_items) ?> çeşit</span>
                    </div>
                    <div class="price-row">
                        <span class="price-label">Toplam Adet:</span>
                        <span class="price-value"><?= array_sum(array_column($cart_items, 'quantity')) ?> adet</span>
                    </div>
                    <div class="price-row">
                        <span class="price-label">Ara Toplam:</span>
                        <span class="price-value"><?= number_format($subtotal, 2) ?> TL</span>
                    </div>
                    <div class="price-row">
                        <span class="price-label">KDV (%20):</span>
                        <span class="price-value"><?= number_format($kdv_amount, 2) ?> TL</span>
                    </div>
                    <div class="price-row total-row">
                        <span class="total-label">Toplam:</span>
                        <span class="total-value"><?= number_format($total_amount, 2) ?> TL</span>
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
            // Tüm seçeneklerin selected class'ını kaldır
            document.querySelectorAll('.method-option').forEach(option => {
                option.classList.remove('selected');
            });
            
            // Tıklanan seçeneği selected yap
            event.currentTarget.classList.add('selected');
            
            const submitBtn = document.getElementById('submitBtn');
            submitBtn.textContent = 'Siparişi Tamamla (<?= number_format($total_amount, 2) ?> TL)';
            checkFormValidity();
        }
        
        function checkFormValidity() {
            const paymentSelected = document.querySelector('input[name="payment_method"]:checked');
            const kvkkChecked = document.getElementById('kvkk').checked;
            const termsChecked = document.getElementById('terms').checked;
            const submitBtn = document.getElementById('submitBtn');
            
            if (paymentSelected && kvkkChecked && termsChecked) {
                submitBtn.disabled = false;
            } else {
                submitBtn.disabled = true;
            }
        }
        
        // Checkbox'ları dinle
        document.getElementById('kvkk').addEventListener('change', checkFormValidity);
        document.getElementById('terms').addEventListener('change', checkFormValidity);
        
        function showKVKK() {
            alert('KVKK Aydınlatma Metni:\n\n1. Kişisel verileriniz güvenli şekilde saklanmaktadır.\n2. Verileriniz sadece sipariş işlemleri için kullanılır.\n3. Verileriniz üçüncü taraflarla paylaşılmaz.\n4. İstediğiniz zaman verilerinizi silebilirsiniz.\n5. Detaylı bilgi için iletişime geçebilirsiniz.');
        }
        
        function showTerms() {
            alert('Kullanım Şartları:\n\n1. Siparişler onay sonrası iptal edilemez.\n2. Ürün fiyatları KDV dahildir.\n3. Teslimat 1-3 iş günü içinde yapılır.\n4. Ödeme onayı sonrası sipariş hazırlanır.\n5. Sorunlar için müşteri hizmetlerine başvurun.');
        }
    </script>
</body>
</html>
