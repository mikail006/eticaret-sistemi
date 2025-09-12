<?php
session_start();
include 'config.php';

if (!isset($_SESSION['student_id'])) {
    header('Location: student_login.php');
    exit;
}

// Öğrenci bilgilerini getir
$stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
$stmt->execute([$_SESSION['student_id']]);
$student = $stmt->fetch();

if (!$student) {
    header('Location: student_login.php');
    exit;
}

// Sepet sayısı
$cart_count_stmt = $pdo->prepare("SELECT COUNT(*) FROM cart WHERE student_id = ?");
$cart_count_stmt->execute([$_SESSION['student_id']]);
$cart_count = $cart_count_stmt->fetchColumn();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Profilim - E-Ticaret</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: #f5f6fa; }
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        
        /* HEADER */
        .header { background: #f8f9fa; border: 2px solid #e9ecef; border-radius: 15px; padding: 20px; margin-bottom: 30px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
        .header-container { display: flex; justify-content: space-between; align-items: center; }
        
        .header-brand { display: flex; align-items: center; gap: 15px; color: #333; }
        .profile-image { width: 50px; height: 50px; border-radius: 50%; object-fit: cover; border: 2px solid #e9ecef; }
        .profile-placeholder { width: 50px; height: 50px; border-radius: 50%; background: #e9ecef; display: flex; align-items: center; justify-content: center; font-size: 24px; color: #666; }
        .student-name { font-size: 24px; font-weight: 700; margin-bottom: 5px; }
        .student-class { font-size: 16px; font-weight: 400; color: #666; }
        
        .header-nav { display: flex; gap: 20px; align-items: center; }
        .nav-link { color: #333; text-decoration: none; font-weight: 500; padding: 10px 16px; border-radius: 8px; }
        .nav-link:hover { background: #e9ecef; }
        .nav-link.active { background: #333; color: white; }
        
        /* PROFIL KARTI */
        .profile-card { background: white; border-radius: 15px; padding: 40px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border: 1px solid #f0f0f0; margin-bottom: 30px; text-align: center; }
        
        .profile-main-image { width: 150px; height: 150px; border-radius: 50%; object-fit: cover; border: 4px solid #e9ecef; margin: 0 auto 30px; }
        .profile-main-placeholder { width: 150px; height: 150px; border-radius: 50%; background: #e9ecef; display: flex; align-items: center; justify-content: center; font-size: 72px; color: #666; margin: 0 auto 30px; }
        
        .profile-name { font-size: 32px; font-weight: 700; color: #333; margin-bottom: 10px; }
        .profile-class { display: inline-block; background: #f8f9fa; border: 2px solid #e9ecef; color: #333; padding: 8px 20px; border-radius: 20px; font-size: 16px; font-weight: 600; margin-bottom: 30px; }
        
        /* BİLGİ GRİDİ */
        .info-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; text-align: left; margin-bottom: 30px; }
        .info-item { background: #f8f9fa; border: 2px solid #e9ecef; padding: 20px; border-radius: 15px; }
        .info-label { font-weight: 700; color: #333; margin-bottom: 8px; font-size: 14px; }
        .info-value { color: #666; font-size: 16px; font-weight: 500; }
        
        /* BUTON */
        .btn { padding: 12px 30px; border: none; border-radius: 8px; cursor: pointer; text-decoration: none; display: inline-block; text-align: center; font-size: 14px; font-weight: 600; background: linear-gradient(135deg, #333 0%, #555 100%); color: white; }
        
        @media (max-width: 768px) {
            .container { padding: 10px; }
            .header-container { flex-direction: column; gap: 15px; }
            .header-nav { flex-wrap: wrap; justify-content: center; }
            .profile-card { padding: 20px; }
            .info-grid { grid-template-columns: 1fr; }
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
                        <div class="profile-placeholder">👤</div>
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
                            (<?= $cart_count ?>)
                        <?php endif; ?>
                    </a>
                    <a href="student_login.php" class="nav-link">Çıkış</a>
                </nav>
            </div>
        </div>
        
        <div class="profile-card">
            <?php if ($student['profile_image']): ?>
                <img src="uploads/<?= htmlspecialchars($student['profile_image']) ?>" class="profile-main-image" alt="Profil">
            <?php else: ?>
                <div class="profile-main-placeholder">👤</div>
            <?php endif; ?>
            
            <div class="profile-name"><?= htmlspecialchars($student['full_name']) ?></div>
            <div class="profile-class"><?= htmlspecialchars($student['class'] ?: 'Sınıf Belirtilmemiş') ?></div>
            
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">KULLANICI ADI</div>
                    <div class="info-value"><?= htmlspecialchars($student['username']) ?></div>
                </div>
                
                <div class="info-item">
                    <div class="info-label">E-POSTA</div>
                    <div class="info-value"><?= htmlspecialchars($student['email']) ?></div>
                </div>
                
                <?php if ($student['phone']): ?>
                <div class="info-item">
                    <div class="info-label">TELEFON</div>
                    <div class="info-value"><?= htmlspecialchars($student['phone']) ?></div>
                </div>
                <?php endif; ?>
                
                <?php if ($student['address']): ?>
                <div class="info-item">
                    <div class="info-label">ADRES</div>
                    <div class="info-value"><?= htmlspecialchars($student['address']) ?></div>
                </div>
                <?php endif; ?>
                
                <div class="info-item">
                    <div class="info-label">KAYIT TARİHİ</div>
                    <div class="info-value"><?= date('d.m.Y', strtotime($student['created_at'])) ?></div>
                </div>
            </div>
            
            <a href="index.php" class="btn">Ana Sayfaya Dön</a>
        </div>
    </div>
</body>
</html>
