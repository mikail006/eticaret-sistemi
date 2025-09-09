<?php
session_start();
include 'config.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit;
}

$notification = '';
$notification_type = '';
if (isset($_GET['success'])) {
    $notification = "Öğrenci başarılı bir şekilde eklendi";
    $notification_type = "success";
} elseif (isset($_GET['error'])) {
    $notification = "Öğrenci ekleme başarısız";
    $notification_type = "error";
}

// Sınıf listesi
$classes = ['Anaokulu - Kreş', '1.sınıf', '2.sınıf', '3.sınıf', '4.sınıf', '5.sınıf', '6.sınıf', '7.sınıf', '8.sınıf', '9.sınıf', '10.sınıf', '11.sınıf', '12.sınıf'];

// Öğrenci ekleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_student'])) {
    try {
        $username = $_POST['username'];
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $email = $_POST['email'];
        $full_name = $_POST['full_name'];
        $class = $_POST['class'];
        $phone = $_POST['phone'];
        $address = $_POST['address'];
        
        $profile_image = null;
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === 0) {
            $file_name = time() . '_' . uniqid() . '_' . $_FILES['profile_image']['name'];
            $upload_path = 'uploads/' . $file_name;
            
            if (!file_exists('uploads')) {
                mkdir('uploads', 0777, true);
            }
            
            if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $upload_path)) {
                $profile_image = $file_name;
            }
        }
        
        $stmt = $pdo->prepare("INSERT INTO students (username, password, email, full_name, class, phone, address, profile_image) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$username, $password, $email, $full_name, $class, $phone, $address, $profile_image]);
        
        header("Location: add_student.php?success=1");
        exit;
        
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
            $error = "Bu kullanıcı adı zaten kullanılıyor!";
        } else {
            header("Location: add_student.php?error=1");
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Yeni Öğrenci Ekle</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f6fa; }
        .container { max-width: 1000px; margin: 0 auto; padding: 20px; }
        
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 15px; margin-bottom: 30px; box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3); display: flex; justify-content: space-between; align-items: center; }
        .header h1 { font-size: 28px; }
        .btn-back { background: rgba(255,255,255,0.2); color: white; padding: 10px 20px; border: none; border-radius: 25px; text-decoration: none; transition: all 0.3s; }
        .btn-back:hover { background: rgba(255,255,255,0.3); transform: translateY(-2px); }
        
        .form-section { background: white; padding: 40px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
        .form-full { grid-column: 1 / -1; }
        
        input, textarea, select { width: 100%; padding: 15px; border: 2px solid #e1e8ed; border-radius: 10px; font-size: 16px; transition: all 0.3s; }
        input:focus, textarea:focus, select:focus { outline: none; border-color: #667eea; box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1); }
        
        .profile-upload { margin: 20px 0; text-align: center; }
        .profile-preview { width: 150px; height: 150px; border-radius: 50%; object-fit: cover; border: 5px solid #e2e8f0; margin: 20px auto; display: block; }
        .profile-placeholder { width: 150px; height: 150px; border-radius: 50%; background: #f7fafc; border: 3px dashed #cbd5e0; margin: 20px auto; display: flex; align-items: center; justify-content: center; color: #a0aec0; font-size: 48px; }
        
        .file-input { display: none; }
        .upload-btn { background: #667eea; color: white; padding: 10px 20px; border: none; border-radius: 8px; cursor: pointer; margin: 10px; }
        .upload-btn:hover { background: #5a67d8; }
        
        .submit-btn { background: linear-gradient(45deg, #667eea, #764ba2); color: white; padding: 18px 40px; border: none; border-radius: 25px; font-size: 18px; cursor: pointer; transition: all 0.3s; margin-top: 30px; }
        .submit-btn:hover { transform: translateY(-3px); box-shadow: 0 15px 35px rgba(102, 126, 234, 0.4); }
        
        .notification { position: fixed; bottom: -100px; left: 50%; transform: translateX(-50%); padding: 20px 30px; border-radius: 10px; color: white; font-size: 16px; font-weight: bold; cursor: pointer; transition: all 0.5s ease; z-index: 1000; box-shadow: 0 10px 30px rgba(0,0,0,0.3); }
        .notification.success { background: linear-gradient(45deg, #48bb78, #38a169); }
        .notification.error { background: linear-gradient(45deg, #e53e3e, #c53030); }
        .notification.show { bottom: 100px; }
        
        .error { color: #e53e3e; background: #ffeaea; padding: 15px; border-radius: 8px; margin: 15px 0; }
        
        @media (max-width: 768px) {
            .form-row { grid-template-columns: 1fr; }
            .container { padding: 10px; }
            .header { flex-direction: column; gap: 15px; text-align: center; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Yeni Öğrenci Ekle</h1>
            <a href="student_management.php" class="btn-back">← Geri Dön</a>
        </div>
        
        <div class="form-section">
            <?php if (isset($error)) echo "<div class='error'>$error</div>"; ?>
            
            <form method="POST" enctype="multipart/form-data" id="studentForm">
                <div class="profile-upload">
                    <div id="profilePreview" class="profile-placeholder">📷</div>
                    <input type="file" id="profileInput" name="profile_image" accept="image/*" class="file-input">
                    <button type="button" class="upload-btn" onclick="document.getElementById('profileInput').click()">Profil Fotoğrafı Seç</button>
                </div>
                
                <div class="form-row">
                    <input type="text" name="full_name" placeholder="Ad Soyad" required>
                    <select name="class" required>
                        <option value="">Sınıf Seçin</option>
                        <?php foreach ($classes as $class): ?>
                            <option value="<?= $class ?>"><?= $class ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-row">
                    <input type="text" name="username" placeholder="Kullanıcı Adı" required>
                    <input type="password" name="password" placeholder="Şifre" required>
                </div>
                
                <div class="form-row">
                    <input type="email" name="email" placeholder="E-posta" required>
                    <input type="tel" name="phone" placeholder="Telefon Numarası">
                </div>
                
                <div class="form-full">
                    <textarea name="address" placeholder="Adres" rows="3"></textarea>
                </div>
                
                <button type="submit" name="add_student" class="submit-btn">
                    Öğrenci Ekle
                </button>
            </form>
        </div>
    </div>

    <script>
        // Bildirim göster
        <?php if ($notification): ?>
            showNotification('<?= $notification ?>', '<?= $notification_type ?>');
            
            setTimeout(() => {
                const url = new URL(window.location);
                url.searchParams.delete('success');
                url.searchParams.delete('error');
                window.history.replaceState({}, document.title, url.pathname);
            }, 1000);
        <?php endif; ?>
        
        function showNotification(message, type) {
            const notification = document.createElement('div');
            notification.className = `notification ${type}`;
            notification.textContent = message;
            notification.onclick = hideNotification;
            notification.id = 'notification';
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.classList.add('show');
            }, 100);
        }
        
        function hideNotification() {
            const notification = document.getElementById('notification');
            if (notification) {
                notification.classList.remove('show');
                setTimeout(() => {
                    notification.remove();
                }, 500);
            }
        }
        
        // Profil fotoğrafı önizleme
        document.getElementById('profileInput').addEventListener('change', function(e) {
            const file = e.target.files[0];
            const preview = document.getElementById('profilePreview');
            
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.innerHTML = `<img src="${e.target.result}" class="profile-preview" alt="Profil Önizleme">`;
                };
                reader.readAsDataURL(file);
            } else {
                preview.innerHTML = '📷';
                preview.className = 'profile-placeholder';
            }
        });
    </script>
</body>
</html>
