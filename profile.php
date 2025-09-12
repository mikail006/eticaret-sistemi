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

// Sepet sayısı
$cart_count_stmt = $pdo->prepare("SELECT COUNT(*) FROM cart WHERE student_id = ?");
$cart_count_stmt->execute([$student_id]);
$cart_count = $cart_count_stmt->fetchColumn();

// Profil fotoğrafı yükleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === 0) {
    $upload_dir = 'uploads/';
    $file_extension = strtolower(pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION));
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
    
    if (in_array($file_extension, $allowed_extensions)) {
        $new_filename = 'profile_' . $student_id . '_' . time() . '.' . $file_extension;
        $upload_path = $upload_dir . $new_filename;
        
        if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $upload_path)) {
            // Eski fotoğrafı sil
            if ($student['profile_image'] && file_exists($upload_dir . $student['profile_image'])) {
                unlink($upload_dir . $student['profile_image']);
            }
            
            $stmt = $pdo->prepare("UPDATE students SET profile_image = ? WHERE id = ?");
            $stmt->execute([$new_filename, $student_id]);
            
            header('Location: profile.php?photo_updated=1');
            exit;
        }
    }
}

// Profil fotoğrafı silme
if (isset($_GET['remove_photo'])) {
    if ($student['profile_image'] && file_exists('uploads/' . $student['profile_image'])) {
        unlink('uploads/' . $student['profile_image']);
    }
    
    $stmt = $pdo->prepare("UPDATE students SET profile_image = NULL WHERE id = ?");
    $stmt->execute([$student_id]);
    
    header('Location: profile.php?photo_removed=1');
    exit;
}

// Adres güncelleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_address'])) {
    $address = $_POST['address'];
    $parent_address = $_POST['parent_address'];
    
    $stmt = $pdo->prepare("UPDATE students SET address = ?, parent_address = ? WHERE id = ?");
    $stmt->execute([$address, $parent_address, $student_id]);
    
    header('Location: profile.php?updated=1');
    exit;
}

// Güncel bilgileri al
$stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
$stmt->execute([$student_id]);
$student = $stmt->fetch();

$notification = '';
$notification_type = '';

if (isset($_GET['updated'])) {
    $notification = "Adres bilgileri güncellendi!";
    $notification_type = "success";
} elseif (isset($_GET['photo_updated'])) {
    $notification = "Profil fotoğrafı güncellendi!";
    $notification_type = "success";
} elseif (isset($_GET['photo_removed'])) {
    $notification = "Profil fotoğrafı kaldırıldı!";
    $notification_type = "success";
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Profil - E-Ticaret</title>
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
        
        /* PROFILE SECTION */
        .profile-section { background: white; border-radius: 15px; padding: 40px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border: 1px solid #f0f0f0; }
        .profile-title { font-size: 28px; font-weight: 700; color: #333; margin-bottom: 30px; text-align: center; }
        
        /* PHOTO SECTION - SABİT LAYOUT */
        .photo-section { 
            text-align: center; 
            margin-bottom: 40px; 
            min-height: 200px; 
            display: flex; 
            flex-direction: column; 
            align-items: center; 
            justify-content: center; 
        }
        .photo-display { 
            width: 120px; 
            height: 120px; 
            border-radius: 50%; 
            border: 4px solid #f0f0f0; 
            object-fit: cover; 
            margin-bottom: 20px;
        }
        .photo-placeholder { 
            width: 120px; 
            height: 120px; 
            border-radius: 50%; 
            border: 4px solid #f0f0f0; 
            background: #e9ecef; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            font-size: 24px;
            color: #666;
            margin-bottom: 20px;
        }
        .photo-actions { 
            display: flex; 
            gap: 10px; 
            justify-content: center; 
        }
        .photo-btn { 
            padding: 8px 16px; 
            border: none; 
            border-radius: 6px; 
            cursor: pointer; 
            font-size: 14px; 
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
        }
        .photo-btn-upload { background: linear-gradient(135deg, #333 0%, #555 100%); color: white; }
        .photo-btn-remove { background: #dc3545; color: white; }
        .photo-input { display: none; }
        
        /* INFO SECTIONS */
        .info-section { margin-bottom: 30px; background: #f8f9fa; border: 2px solid #f0f0f0; border-radius: 15px; padding: 25px; }
        .section-title { font-size: 20px; font-weight: 600; color: #333; margin-bottom: 20px; }
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
        .info-item { }
        .info-label { font-size: 14px; font-weight: 600; color: #666; margin-bottom: 5px; }
        .info-value { font-size: 16px; color: #333; padding: 12px; background: white; border-radius: 8px; border: 2px solid #e9ecef; }
        
        /* FORM */
        .form-group { margin-bottom: 20px; }
        .form-label { display: block; margin-bottom: 8px; font-weight: 600; color: #333; }
        .form-input { 
            width: 100%; 
            padding: 15px; 
            border: 2px solid #f0f0f0; 
            border-radius: 10px; 
            font-size: 16px; 
            background: white;
        }
        .form-input:focus { outline: none; border-color: #333; }
        
        /* BUTTON */
        .btn { 
            padding: 15px 40px; 
            border: none; 
            border-radius: 10px; 
            cursor: pointer; 
            font-size: 16px; 
            font-weight: 600;
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%); 
            color: white;
            width: 100%;
            margin-top: 20px;
        }
        
        /* NOTIFICATION */
        .notification { 
            position: fixed; 
            bottom: 30px; 
            right: 30px; 
            padding: 15px 25px; 
            border-radius: 10px; 
            color: white; 
            font-weight: 600; 
            z-index: 1000; 
            transform: translateX(400px); 
            transition: transform 0.3s;
        }
        .notification.success { background: #28a745; }
        .notification.show { transform: translateX(0); }
        
        @media (max-width: 768px) {
            .container { padding: 10px; }
            .header-container { flex-direction: column; gap: 15px; }
            .header-nav { flex-wrap: wrap; justify-content: center; }
            .profile-section { padding: 20px; }
            .info-grid { grid-template-columns: 1fr; }
            
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
                    <a href="profile.php" class="nav-link active">Profil</a>
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
        
        <div class="profile-section">
            <h1 class="profile-title">Profil Bilgileri</h1>
            
            <!-- PROFIL FOTOĞRAFI - SABİT LAYOUT -->
            <div class="photo-section">
                <?php if ($student['profile_image']): ?>
                    <img src="uploads/<?= htmlspecialchars($student['profile_image']) ?>" class="photo-display" alt="Profil">
                <?php else: ?>
                    <div class="photo-placeholder">📷</div>
                <?php endif; ?>
                
                <div class="photo-actions">
                    <form method="POST" enctype="multipart/form-data" style="display: inline;">
                        <label for="profile_image" class="photo-btn photo-btn-upload">
                            Fotoğraf Yükle
                        </label>
                        <input type="file" id="profile_image" name="profile_image" class="photo-input" accept="image/*" onchange="this.form.submit()">
                    </form>
                    
                    <?php if ($student['profile_image']): ?>
                        <a href="profile.php?remove_photo=1" class="photo-btn photo-btn-remove" onclick="return confirm('Profil fotoğrafını kaldırmak istediğinize emin misiniz?')">Fotoğrafı Kaldır</a>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- ÖĞRENCİ BİLGİLERİ -->
            <div class="info-section">
                <div class="section-title">Öğrenci Bilgileri</div>
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
                </div>
                
                <form method="POST">
                    <div class="form-group">
                        <label class="form-label">Öğrenci Adres</label>
                        <textarea name="address" class="form-input" rows="3" placeholder="Öğrenci adresi"><?= htmlspecialchars($student['address']) ?></textarea>
                    </div>
            </div>
            
            <!-- VELİ BİLGİLERİ -->
            <div class="info-section">
                <div class="section-title">Veli Bilgileri</div>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Veli Ad Soyad</div>
                        <div class="info-value"><?= htmlspecialchars($student['parent_name'] ?: 'Belirtilmemiş') ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Veli Telefon</div>
                        <div class="info-value"><?= htmlspecialchars($student['parent_phone'] ?: 'Belirtilmemiş') ?></div>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Veli Adres</label>
                    <textarea name="parent_address" class="form-input" rows="3" placeholder="Veli adresi"><?= htmlspecialchars($student['parent_address']) ?></textarea>
                </div>
            </div>
            
            <button type="submit" name="update_address" class="btn">Adres Bilgilerini Güncelle</button>
            </form>
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
