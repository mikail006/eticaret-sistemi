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
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); margin: 0; padding: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .login-container { background: white; padding: 40px; border-radius: 20px; box-shadow: 0 20px 40px rgba(0,0,0,0.1); max-width: 400px; width: 90%; }
        h2 { color: #333; text-align: center; margin-bottom: 30px; font-size: 28px; }
        input { width: 100%; padding: 15px; margin: 15px 0; border: 2px solid #e1e8ed; border-radius: 10px; font-size: 16px; transition: all 0.3s; }
        input:focus { outline: none; border-color: #667eea; box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1); }
        button { background: linear-gradient(45deg, #667eea, #764ba2); color: white; padding: 15px; border: none; border-radius: 10px; cursor: pointer; width: 100%; font-size: 18px; font-weight: bold; transition: all 0.3s; }
        button:hover { transform: translateY(-2px); box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4); }
        .error { color: #e74c3c; margin: 15px 0; text-align: center; background: #ffeaea; padding: 10px; border-radius: 8px; }
        .admin-link { text-align: center; margin-top: 25px; }
        .admin-link a { color: #667eea; text-decoration: none; font-weight: 600; }
        .admin-link a:hover { text-decoration: underline; }
        .info-box { background: #f8f9fa; padding: 15px; border-radius: 10px; margin-bottom: 20px; text-align: center; color: #666; }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>Öğrenci Girişi</h2>
        <div class="info-box">
            Kullanıcı adı ve şifrenizi öğretmeninizden alabilirsiniz.
        </div>
        
        <?php if (isset($error)) echo "<div class='error'>$error</div>"; ?>
        
        <form method="POST">
            <input type="text" name="username" placeholder="Kullanıcı Adı" required>
            <input type="password" name="password" placeholder="Şifre" required>
            <button type="submit" name="login">Giriş Yap</button>
        </form>
        
        <div class="admin-link">
            <a href="admin_login.php">Öğretmen Girişi</a>
        </div>
    </div>
</body>
</html>
