<?php
session_start();
include 'config.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit;
}

$product_id = $_GET['id'] ?? 0;

// Ürün bilgilerini getir
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch();

if (!$product) {
    header('Location: admin_panel.php');
    exit;
}

$notification = '';
$notification_type = '';
if (isset($_GET['success'])) {
    $notification = "Ürün başarılı bir şekilde güncellendi";
    $notification_type = "success";
} elseif (isset($_GET['error'])) {
    $notification = "Ürün güncelleme başarısız";
    $notification_type = "error";
}

// Sınıf listesi
$classes = ['Anaokulu - Kreş', '1.sınıf', '2.sınıf', '3.sınıf', '4.sınıf', '5.sınıf', '6.sınıf', '7.sınıf', '8.sınıf', '9.sınıf', '10.sınıf', '11.sınıf', '12.sınıf'];

// Mevcut sınıfları al
$existing_classes = json_decode($product['classes'], true) ?: [];

// Ürün güncelleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_product'])) {
    try {
        $name = $_POST['name'];
        $description = $_POST['description'];
        $price = $_POST['price'];
        $stock = $_POST['stock'];
        $target_classes = isset($_POST['target_classes']) ? $_POST['target_classes'] : [];

        $images = json_decode($product['images'], true) ?: [];

        // Yeni resimler varsa ekle
        if (isset($_FILES['images']) && $_FILES['images']['error'][0] !== UPLOAD_ERR_NO_FILE) {
            foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
                if (!empty($tmp_name) && $_FILES['images']['error'][$key] === 0) {
                    $file_name = time() . '_' . uniqid() . '_' . $_FILES['images']['name'][$key];
                    $upload_path = 'uploads/' . $file_name;

                    if (!file_exists('uploads')) {
                        mkdir('uploads', 0777, true);
                    }

                    if (move_uploaded_file($tmp_name, $upload_path)) {
                        $images[] = $file_name;
                    }
                }
            }
        }

        $images_json = json_encode($images);
        $target_classes_json = json_encode($target_classes);

        $stmt = $pdo->prepare("UPDATE products SET name = ?, description = ?, price = ?, stock = ?, images = ?, classes = ? WHERE id = ?");
        $stmt->execute([$name, $description, $price, $stock, $images_json, $target_classes_json, $product_id]);

        header("Location: edit_product.php?id=$product_id&success=1");
        exit;

    } catch (Exception $e) {
        header("Location: edit_product.php?id=$product_id&error=1");
        exit;
    }
}

$existing_images = json_decode($product['images'], true) ?: [];
?>
<!DOCTYPE html>
<html>
<head>
    <title>Ürün Düzenle</title>
    <meta charset="UTF-8">
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

        input, textarea { width: 100%; padding: 15px; border: 2px solid #e1e8ed; border-radius: 10px; font-size: 16px; transition: all 0.3s; }
        input:focus, textarea:focus { outline: none; border-color: #667eea; box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1); }

        .class-selection { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 10px; padding: 20px; background: #f8f9fa; border-radius: 10px; margin: 20px 0; }
        .class-checkbox { display: flex; align-items: center; gap: 8px; padding: 12px; border: 2px solid #e2e8f0; border-radius: 8px; cursor: pointer; transition: all 0.3s; background: white; }
        .class-checkbox:hover { border-color: #667eea; background: #f0f4ff; }
        .class-checkbox input[type="checkbox"]:checked { accent-color: #667eea; }
        .class-checkbox input[type="checkbox"]:checked + span { color: #667eea; font-weight: 600; }

        .existing-images { margin: 20px 0; }
        .existing-images h3 { margin-bottom: 15px; color: #4a5568; }
        .existing-grid { display: grid; grid-template-columns: repeat(auto-fill, 100px); gap: 10px; }
        .existing-item { position: relative; width: 100px; height: 100px; border-radius: 8px; overflow: hidden; }
        .existing-image { width: 100%; height: 100%; object-fit: cover; }
        .remove-existing { position: absolute; top: 5px; right: 5px; background: #e53e3e; color: white; border: none; border-radius: 50%; width: 25px; height: 25px; cursor: pointer; font-size: 12px; }

        .upload-container { margin: 30px 0; }
        .drop-zone { border: 3px dashed #cbd5e0; background: #f7fafc; padding: 60px 20px; text-align: center; border-radius: 15px; transition: all 0.3s; cursor: pointer; }
        .drop-zone:hover, .drop-zone.dragover { border-color: #667eea; background: #edf2f7; transform: scale(1.02); }

        .upload-icon { font-size: 48px; color: #a0aec0; margin-bottom: 20px; }
        .upload-text { font-size: 18px; color: #4a5568; margin-bottom: 10px; }
        .upload-hint { color: #718096; font-size: 14px; }

        .file-input { display: none; }

        .submit-btn { background: linear-gradient(45deg, #667eea, #764ba2); color: white; padding: 18px 40px; border: none; border-radius: 25px; font-size: 18px; cursor: pointer; transition: all 0.3s; margin-top: 30px; }
        .submit-btn:hover { transform: translateY(-3px); box-shadow: 0 15px 35px rgba(102, 126, 234, 0.4); }

        .notification { position: fixed; bottom: -100px; left: 50%; transform: translateX(-50%); padding: 20px 30px; border-radius: 10px; color: white; font-size: 16px; font-weight: bold; cursor: pointer; transition: all 0.5s ease; z-index: 1000; box-shadow: 0 10px 30px rgba(0,0,0,0.3); }
        .notification.success { background: linear-gradient(45deg, #48bb78, #38a169); }
        .notification.error { background: linear-gradient(45deg, #e53e3e, #c53030); }
        .notification.show { bottom: 100px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Ürün Düzenle</h1>
            <a href="admin_panel.php" class="btn-back">← Geri Dön</a>
        </div>

        <div class="form-section">
            <form method="POST" enctype="multipart/form-data">
                <div class="form-row">
                    <input type="text" name="name" placeholder="Ürün Adı" value="<?= htmlspecialchars($product['name']) ?>" required>
                    <input type="number" step="0.01" name="price" placeholder="Fiyat (TL)" value="<?= $product['price'] ?>" required>
                </div>

                <div class="form-row">
                    <input type="number" name="stock" placeholder="Stok Adedi" value="<?= $product['stock'] ?>" required>
                    <div></div>
                </div>

                <div class="form-full">
                    <textarea name="description" placeholder="Ürün Açıklaması" rows="4" required><?= htmlspecialchars($product['description']) ?></textarea>
                </div>

                <!-- Sınıf seçimi -->
                <div class="form-full">
                    <h3 style="margin-bottom: 15px; color: #4a5568;">Bu ürün hangi sınıflara gösterilsin?</h3>
                    <div class="class-selection">
                        <?php foreach ($classes as $class): ?>
                            <label class="class-checkbox">
                                <input type="checkbox" name="target_classes[]" value="<?= $class ?>" <?= in_array($class, $existing_classes) ? 'checked' : '' ?>>
                                <span><?= $class ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <?php if (count($existing_images) > 0): ?>
                    <div class="existing-images">
                        <h3>Mevcut Resimler</h3>
                        <div class="existing-grid">
                            <?php foreach ($existing_images as $image): ?>
                                <div class="existing-item">
                                    <img src="uploads/<?= $image ?>" class="existing-image" alt="Mevcut resim">
                                    <button type="button" class="remove-existing" onclick="removeExistingImage('<?= $image ?>')" title="Sil">×</button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="upload-container">
                    <div class="drop-zone" id="dropZone">
                        <div class="upload-icon">📁</div>
                        <div class="upload-text">Yeni resimler eklemek için buraya sürükleyin</div>
                        <div class="upload-hint">PNG, JPG, GIF formatları desteklenir</div>
                        <input type="file" id="fileInput" name="images[]" multiple accept="image/*" class="file-input">
                    </div>
                </div>

                <button type="submit" name="update_product" class="submit-btn">
                    Ürünü Güncelle
                </button>
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
                window.history.replaceState(null, null, window.location.pathname + window.location.search);
            }
        </script>
    <?php endif; ?>

    <script>
        function removeExistingImage(imageName) {
            if (confirm('Bu resmi silmek istediğinizden emin misiniz?')) {
                fetch('delete_image.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        product_id: <?= $product_id ?>,
                        image_name: imageName
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Resim silinirken hata oluştu.');
                    }
                });
            }
        }

        const dropZone = document.getElementById('dropZone');
        const fileInput = document.getElementById('fileInput');

        dropZone.addEventListener('click', () => {
            fileInput.click();
        });

        dropZone.addEventListener('dragover', (e) => {
            e.preventDefault();
            dropZone.classList.add('dragover');
        });

        dropZone.addEventListener('dragleave', () => {
            dropZone.classList.remove('dragover');
        });

        dropZone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropZone.classList.remove('dragover');
            fileInput.files = e.dataTransfer.files;
        });
    </script>
</body>
</html>
