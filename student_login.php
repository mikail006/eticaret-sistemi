<?php
session_start();
include 'config.php';

if ($_POST && isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    $stmt = $pdo->prepare("SELECT * FROM students WHERE username = ?");
    $stmt->execute([$username]);
    $student = $stmt->fetch();
    
    if ($student && password_verify($password, $student['password'])) {
        $_SESSION['student_id'] = $student['id'];
        header('Location: index.php');
        exit;
    } else {
        $error = "Kullanıcı adı veya şifre hatalı!";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Öğrenci Giriş</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: #f5f6fa; min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        
        .login-container { background: #f8f9fa; border: 2px solid #e9ecef; border-radius: 15px; padding: 40px; max-width: 450px; width: 90%; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
        
        .login-header { text-align: center; margin-bottom: 30px; }
        .login-header h1 { font-size: 28px; font-weight: 700; color: #333; margin-bottom: 8px; }
        .login-header p { color: #666; font-weight: 400; }
        
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #333; }
        .form-group input { width: 100%; padding: 15px; border: 2px solid #e9ecef; border-radius: 8px; font-size: 16px; background: white; }
        .form-group input:focus { outline: none; border-color: #333; }
        
        .error { background: #fef2f2; border: 2px solid #fecaca; color: #dc2626; padding: 12px; border-radius: 8px; margin-bottom: 20px; font-weight: 500; }
        
        .login-btn { width: 100%; padding: 15px; border: none; border-radius: 8px; background: linear-gradient(135deg, #333 0%, #555 100%); color: white; font-size: 16px; font-weight: 600; cursor: pointer; margin-bottom: 20px; }
        .login-btn:hover { background: linear-gradient(135deg, #555 0%, #777 100%); }
        
        .admin-link { text-align: center; padding-top: 20px; border-top: 2px solid #e9ecef; }
        .admin-link a { color: #333; text-decoration: none; font-weight: 500; }
        .admin-link a:hover { text-decoration: underline; }
        
        @media (max-width: 768px) {
            .login-container { padding: 30px 20px; margin: 20px; }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>Öğrenci Girişi</h1>
            <p>Kullanıcı adı ve şifrenizi girin</p>
        </div>
        
        <?php if (isset($error)): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label for="username">Kullanıcı Adı</label>
                <input type="text" id="username" name="username" required>
            </div>
            
            <div class="form-group">
                <label for="password">Şifre</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <button type="submit" name="login" class="login-btn">Giriş Yap</button>
        </form>
        
        <div class="admin-link">
            <a href="admin_login.php">Öğretmen Girişi</a>
        </div>
    </div>
</body>
</html>
