<?php
session_start();
include 'config.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit;
}

// Öğrenci silme
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = $_GET['id'];
    
    // Öğrencinin profil resmini al ve sil
    $stmt = $pdo->prepare("SELECT profile_image FROM students WHERE id = ?");
    $stmt->execute([$id]);
    $student = $stmt->fetch();
    
    if ($student && $student['profile_image'] && file_exists('uploads/' . $student['profile_image'])) {
        unlink('uploads/' . $student['profile_image']);
    }
    
    // Öğrenciyi sil
    $stmt = $pdo->prepare("DELETE FROM students WHERE id = ?");
    $stmt->execute([$id]);
    
    header("Location: student_management.php?deleted=1");
    exit;
}

// Bildirim
$notification = '';
$notification_type = '';
if (isset($_GET['deleted'])) {
    $notification = "Öğrenci başarıyla silindi";
    $notification_type = "success";
}

// Öğrencileri getir
$stmt = $pdo->query("SELECT * FROM students ORDER BY class, full_name");
$students = $stmt->fetchAll();

// Sınıf listesi
$classes = ['Anaokulu - Kreş', '1.sınıf', '2.sınıf', '3.sınıf', '4.sınıf', '5.sınıf', '6.sınıf', '7.sınıf', '8.sınıf', '9.sınıf', '10.sınıf', '11.sınıf', '12.sınıf'];
?>
<!DOCTYPE html>
<html>
<head>
    <title>Öğrenci Yönetimi</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f6fa; }
        .container { max-width: 1400px; margin: 0 auto; padding: 20px; }
        
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 15px; margin-bottom: 30px; box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3); display: flex; justify-content: space-between; align-items: center; }
        .header h1 { font-size: 28px; }
        .header-buttons { display: flex; gap: 15px; }
        .btn { padding: 12px 24px; border: none; border-radius: 25px; text-decoration: none; font-weight: bold; transition: all 0.3s; cursor: pointer; }
        .btn-primary { background: rgba(255,255,255,0.9); color: #667eea; }
        .btn-primary:hover { background: white; transform: translateY(-2px); }
        .btn-secondary { background: rgba(255,255,255,0.2); color: white; }
        .btn-secondary:hover { background: rgba(255,255,255,0.3); transform: translateY(-2px); }
        
        .students-section { background: white; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); overflow: hidden; }
        .section-header { padding: 30px; border-bottom: 2px solid #f1f3f4; display: flex; justify-content: space-between; align-items: center; }
        .section-header h2 { color: #2d3748; font-size: 24px; }
        
        .table-container { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 15px; text-align: left; border-bottom: 1px solid #e2e8f0; }
        th { background: #f8f9fa; font-weight: 600; color: #4a5568; }
        tr:hover { background: #f8f9fa; }
        
        .student-image { width: 50px; height: 50px; object-fit: cover; border-radius: 50%; }
        .student-name { font-weight: 600; color: #2d3748; }
        .student-class { padding: 4px 8px; border-radius: 12px; font-size: 12px; font-weight: 600; background: #e6f3ff; color: #0066cc; }
        
        .action-buttons { display: flex; gap: 8px; }
        .btn-edit { background: #667eea; color: white; padding: 6px 12px; border-radius: 6px; text-decoration: none; font-size: 12px; }
        .btn-edit:hover { background: #5a67d8; }
        .btn-delete { background: #e53e3e; color: white; padding: 6px 12px; border-radius: 6px; border: none; font-size: 12px; cursor: pointer; }
        .btn-delete:hover { background: #c53030; }
        
        .notification { position: fixed; bottom: -100px; left: 50%; transform: translateX(-50%); padding: 20px 30px; border-radius: 10px; color: white; font-size: 16px; font-weight: bold; cursor: pointer; transition: all 0.5s ease; z-index: 1000; box-shadow: 0 10px 30px rgba(0,0,0,0.3); }
        .notification.success { background: linear-gradient(45deg, #48bb78, #38a169); }
        .notification.show { bottom: 100px; }
        
        .empty-state { text-align: center; padding: 60px 20px; color: #718096; }
        
        .custom-alert { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: none; justify-content: center; align-items: center; z-index: 2000; }
        .alert-box { background: white; padding: 30px; border-radius: 15px; text-align: center; max-width: 400px; box-shadow: 0 20px 40px rgba(0,0,0,0.3); }
        .alert-box h3 { margin-bottom: 15px; color: #2d3748; }
        .alert-box p { margin-bottom: 25px; color: #718096; }
        .alert-buttons { display: flex; gap: 10px; justify-content: center; }
        .alert-btn { padding: 10px 20px; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; }
        .alert-btn-yes { background: #e53e3e; color: white; }
        .alert-btn-no { background: #e2e8f0; color: #4a5568; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Öğrenci Yönetimi</h1>
            <div class="header-buttons">
                <a href="add_student.php" class="btn btn-primary">+ Yeni Öğrenci Ekle</a>
                <a href="admin_panel.php" class="btn btn-secondary">← Ürün Yönetimine Dön</a>
            </div>
        </div>
        
        <div class="students-section">
            <div class="section-header">
                <h2>Öğrenci Listesi (<?= count($students) ?> öğrenci)</h2>
            </div>
            
            <?php if (count($students) > 0): ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Profil</th>
                                <th>Ad Soyad</th>
                                <th>Kullanıcı Adı</th>
                                <th>Sınıf</th>
                                <th>Telefon</th>
                                <th>E-posta</th>
                                <th>Kayıt Tarihi</th>
                                <th>İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $student): ?>
                                <tr>
                                    <td>
                                        <?php if ($student['profile_image']): ?>
                                            <img src="uploads/<?= $student['profile_image'] ?>" class="student-image" alt="<?= htmlspecialchars($student['full_name']) ?>">
                                        <?php else: ?>
                                            <div style="width: 50px; height: 50px; background: #e2e8f0; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #a0aec0;">👤</div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="student-name"><?= htmlspecialchars($student['full_name']) ?></div>
                                    </td>
                                    <td><?= htmlspecialchars($student['username']) ?></td>
                                    <td>
                                        <span class="student-class"><?= htmlspecialchars($student['class'] ?: 'Belirtilmemiş') ?></span>
                                    </td>
                                    <td><?= htmlspecialchars($student['phone'] ?: '-') ?></td>
                                    <td><?= htmlspecialchars($student['email']) ?></td>
                                    <td><?= date('d.m.Y', strtotime($student['created_at'])) ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="edit_student.php?id=<?= $student['id'] ?>" class="btn-edit">Düzenle</a>
                                            <button class="btn-delete" onclick="confirmDelete(<?= $student['id'] ?>, '<?= htmlspecialchars($student['full_name']) ?>')">Sil</button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <h3>Henüz öğrenci eklenmemiş</h3>
                    <p>İlk öğrenciyi eklemek için yukarıdaki "Yeni Öğrenci Ekle" butonunu kullanın.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="custom-alert" id="deleteAlert">
        <div class="alert-box">
            <h3>Öğrenciyi Sil</h3>
            <p id="deleteMessage">Bu öğrenciyi silmek istediğinizden emin misiniz?</p>
            <div class="alert-buttons">
                <button class="alert-btn alert-btn-yes" id="confirmYes">Evet</button>
                <button class="alert-btn alert-btn-no" id="confirmNo">Hayır</button>
            </div>
        </div>
    </div>

    <script>
        <?php if ($notification): ?>
            showNotification('<?= $notification ?>', '<?= $notification_type ?>');
            
            setTimeout(() => {
                const url = new URL(window.location);
                url.searchParams.delete('deleted');
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
        
        let deleteId = null;
        
        function confirmDelete(id, studentName) {
            deleteId = id;
            document.getElementById('deleteMessage').textContent = `"${studentName}" öğrencisini silmek istediğinizden emin misiniz? Bu işlem geri alınamaz.`;
            document.getElementById('deleteAlert').style.display = 'flex';
        }
        
        document.getElementById('confirmYes').onclick = function() {
            if (deleteId) {
                window.location.href = `student_management.php?action=delete&id=${deleteId}`;
            }
        };
        
        document.getElementById('confirmNo').onclick = function() {
            document.getElementById('deleteAlert').style.display = 'none';
            deleteId = null;
        };
        
        document.getElementById('deleteAlert').onclick = function(e) {
            if (e.target === this) {
                this.style.display = 'none';
                deleteId = null;
            }
        };
    </script>
</body>
</html>
