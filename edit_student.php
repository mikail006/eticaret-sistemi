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
?>
<!DOCTYPE html>
<html>
<head>
    <title>Profilim</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Arial', sans-serif; background: #f8f9fa; }
        
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px 0; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header-content { max-width: 1200px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; padding: 0 20px; }
        .logo { font-size: 24px; font-weight: bold; }
        .nav-links { display: flex; gap: 20px; }
        .nav-links a { color: white; text-decoration: none; padding: 8px 16px; border-radius: 20px; transition: all 0.3s; }
        .nav-links a:hover { background: rgba(255,255,255,0.2); }
        
        .container { max-width: 800px; margin: 40px auto; padding: 0 20px; }
        .profile-card { background: white; border-radius: 20px; padding: 40px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); text-align: center; }
        
        .profile-image { width: 150px; height: 150px; border-radius: 50%; object-fit: cover; border: 5px solid #e2e8f0; margin: 0 auto 30px; display: block; }
        .profile-placeholder { width: 150px; height: 150px; border-radius: 50%; background: #f7fafc; border: 3px solid #e2e8f0; margin: 0 auto 30px; display: flex; align-items: center; justify-content: center; color: #a0aec0; font-size: 72px; }
        
        .student-name { font-size: 28px; color: #2d3748; margin-bottom: 10px; font-weight: bold; }
        .student-class { display: inline-block; background: linear-gradient(45deg, #667eea, #764ba2); color: white; padding: 8px 20px; border-radius: 20px; font-size: 16px; font-weight: 600; margin-bottom: 30px; }
        
        .info-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; text-align: left; }
        .info-item { background: #f8f9fa; padding: 20px; border-radius: 15px; border-left: 4px solid #667eea; }
        .info-label { font-weight: 600; color: #4a5568; margin-bottom: 8px; font-size: 14px; text-transform: uppercase; letter-spacing: 0.5px; }
        .info-value { color: #2d3748; font-size: 16px; }
        
        .back-btn { background: linear-gradient(45deg, #667eea, #764ba2); color: white; padding: 12px 24px; border: none; border-radius: 25px; text-decoration: none; font-weight: 600; transition: all 0.3s; margin-top: 30px; display: inline-block; }
        .back-btn:hover { transform: translateY(-2px); box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4); }
        
        @media (max-width: 768px) {
            .container { padding: 20px; }
            .profile-card { padding: 20px; }
            .info-grid { grid-template-columns: 1fr; }
            .header-content { flex-direction: column; gap: 15px; text-align: center; }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <div class="logo">🛒 E-Ticaret</div>
            <div class="nav-links">
                <a href="index.php">Ana Sayfa</a>
                <a href="profile.php">Profilim</a>
                <a href="student_login.php">Çıkış</a>
            </div>
        </div>
    </div>
    
    <div class="container">
        <div class="profile-card">
            <?php if ($student['profile_image']): ?>
                <img src="uploads/<?= $student['profile_image'] ?>" class="profile-image" alt="<?= htmlspecialchars($student['full_name']) ?>">
            <?php else: ?>
                <div class="profile-placeholder">👤</div>
            <?php endif; ?>
            
            <div class="student-name"><?= htmlspecialchars($student['full_name']) ?></div>
            <div class="student-class"><?= htmlspecialchars($student['class'] ?: 'Sınıf Belirtilmemiş') ?></div>
            
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">Kullanıcı Adı</div>
                    <div class="info-value"><?= htmlspecialchars($student['username']) ?></div>
                </div>
                
                <div class="info-item">
                    <div class="info-label">E-posta</div>
                    <div class="info-value"><?= htmlspecialchars($student['email']) ?></div>
                </div>
                
                <?php if ($student['phone']): ?>
                <div class="info-item">
                    <div class="info-label">Telefon</div>
                    <div class="info-value"><?= htmlspecialchars($student['phone']) ?></div>
                </div>
                <?php endif; ?>
                
                <?php if ($student['address']): ?>
                <div class="info-item">
                    <div class="info-label">Adres</div>
                    <div class="info-value"><?= htmlspecialchars($student['address']) ?></div>
                </div>
                <?php endif; ?>
                
                <div class="info-item">
                    <div class="info-label">Kayıt Tarihi</div>
                    <div class="info-value"><?= date('d.m.Y', strtotime($student['created_at'])) ?></div>
                </div>
            </div>
            
            <a href="index.php" class="back-btn">← Ana Sayfaya Dön</a>
        </div>
    </div>
</body>
</html>
