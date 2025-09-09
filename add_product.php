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
    $notification = "Ürün başarılı bir şekilde eklendi";
    $notification_type = "success";
} elseif (isset($_GET['error'])) {
    $notification = "Ürün yükleme başarısız";
    $notification_type = "error";
}

// Sınıf listesi
$classes = ['Anaokulu - Kreş', '1.sınıf', '2.sınıf', '3.sınıf', '4.sınıf', '5.sınıf', '6.sınıf', '7.sınıf', '8.sınıf', '9.sınıf', '10.sınıf', '11.sınıf', '12.sınıf'];

// Ürün ekleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
    try {
        $name = $_POST['name'];
        $description = $_POST['description'];
        $price = $_POST['price'];
        $stock = $_POST['stock'];
        $target_classes = isset($_POST['target_classes']) ? $_POST['target_classes'] : [];

        $images = [];
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

        $stmt = $pdo->prepare("INSERT INTO products (name, description, price, stock, images, classes) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$name, $description, $price, $stock, $images_json, $target_classes_json]);

        header("Location: add_product.php?success=1");
        exit;

    } catch (Exception $e) {
        header("Location: add_product.php?error=1");
        exit;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Yeni Ürün Ekle</title>
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

        input, textarea, select { width: 100%; padding: 15px; border: 2px solid #e1e8ed; border-radius: 10px; font-size: 16px; transition: all 0.3s; }
        input:focus, textarea:focus, select:focus { outline: none; border-color: #667eea; box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1); }

        .class-selection { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 10px; padding: 20px; background: #f8f9fa; border-radius: 10px; }
        .class-checkbox { display: flex; align-items: center; gap: 8px; padding: 12px; border: 2px solid #e2e8f0; border-radius: 8px; cursor: pointer; transition: all 0.3s; background: white; }
        .class-checkbox:hover { border-color: #667eea; background: #f0f4ff; }
        .class-checkbox input[type="checkbox"]:checked { accent-color: #667eea; }
        .class-checkbox input[type="checkbox"]:checked + span { color: #667eea; font-weight: 600; }

        .upload-container { margin: 30px 0; }
        .drop-zone { border: 3px dashed #cbd5e0; background: #f7fafc; padding: 60px 20px; text-align: center; border-radius: 15px; transition: all 0.3s; cursor: pointer; }
        .drop-zone:hover, .drop-zone.dragover { border-color: #667eea; background: #edf2f7; transform: scale(1.02); }
        .drop-zone.dragover { border-color: #4299e1; background: #ebf8ff; }

        .upload-icon { font-size: 48px; color: #a0aec0; margin-bottom: 20px; }
        .upload-text { font-size: 18px; color: #4a5568; margin-bottom: 10px; }
        .upload-hint { color: #718096; font-size: 14px; }

        .file-input { display: none; }

        .preview-container { margin-top: 30px; }
        .preview-grid { display: grid; grid-template-columns: repeat(auto-fill, 100px); gap: 10px; justify-content: start; }
        .preview-item { position: relative; width: 100px; height: 100px; border-radius: 8px; overflow: hidden; cursor: move; transition: opacity 0.3s ease; }
        .preview-item:hover { transform: scale(1.05); }
        .preview-item.removing { opacity: 0; transform: scale(0.8); transition: all 0.3s ease; }

        .highlight { border: 3px solid #667eea; background: rgba(102, 126, 234, 0.1); transform: scale(1.05); }

        .preview-image { width: 100%; height: 100%; object-fit: cover; }
        .preview-overlay { position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.7); opacity: 0; transition: opacity 0.2s; display: flex; align-items: center; justify-content: center; gap: 5px; }
        .preview-item:hover .preview-overlay { opacity: 1; }

        .preview-btn { background: white; border: none; padding: 6px; border-radius: 50%; cursor: pointer; font-size: 12px; }
        .preview-btn:hover { transform: scale(1.1); }
        .delete-btn { color: #e53e3e; }
        .move-btn { color: #4299e1; cursor: grab; }
        .move-btn:active { cursor: grabbing; }

        .submit-btn { background: linear-gradient(45deg, #667eea, #764ba2); color: white; padding: 18px 40px; border: none; border-radius: 25px; font-size: 18px; cursor: pointer; transition: all 0.3s; margin-top: 30px; }
        .submit-btn:hover { transform: translateY(-3px); box-shadow: 0 15px 35px rgba(102, 126, 234, 0.4); }

        .notification { position: fixed; bottom: -100px; left: 50%; transform: translateX(-50%); padding: 20px 30px; border-radius: 10px; color: white; font-size: 16px; font-weight: bold; cursor: pointer; transition: all 0.5s ease; z-index: 1000; box-shadow: 0 10px 30px rgba(0,0,0,0.3); }
        .notification.success { background: linear-gradient(45deg, #48bb78, #38a169); }
        .notification.error { background: linear-gradient(45deg, #e53e3e, #c53030); }
        .notification.show { bottom: 100px; }

        @media (max-width: 768px) {
            .form-row { grid-template-columns: 1fr; }
            .preview-grid { grid-template-columns: repeat(auto-fill, 80px); }
            .container { padding: 10px; }
            .header { flex-direction: column; gap: 15px; text-align: center; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Yeni Ürün Ekle</h1>
            <a href="admin_panel.php" class="btn-back">← Geri Dön</a>
        </div>

        <div class="form-section">
            <form method="POST" enctype="multipart/form-data" id="productForm">
                <div class="form-row">
                    <input type="text" name="name" placeholder="Ürün Adı" required>
                    <input type="number" step="0.01" name="price" placeholder="Fiyat (TL)" required>
                </div>

                <div class="form-row">
                    <input type="number" name="stock" placeholder="Stok Adedi" required>
                    <div></div>
                </div>

                <div class="form-full">
                    <textarea name="description" placeholder="Ürün Açıklaması" rows="4" required></textarea>
                </div>

                <!-- Sınıf seçimi -->
                <div class="form-full">
                    <h3 style="margin-bottom: 15px; color: #4a5568;">Bu ürün hangi sınıflara gösterilsin?</h3>
                    <div class="class-selection">
                        <?php foreach ($classes as $class): ?>
                            <label class="class-checkbox">
                                <input type="checkbox" name="target_classes[]" value="<?= $class ?>">
                                <span><?= $class ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    <small style="color: #718096; margin-top: 10px; display: block;">En az bir sınıf seçmelisiniz</small>
                </div>

                <div class="upload-container">
                    <div class="drop-zone" id="dropZone">
                        <div class="upload-icon">📁</div>
                        <div class="upload-text">Resimleri buraya sürükleyin veya tıklayın</div>
                        <div class="upload-hint">PNG, JPG, GIF formatları desteklenir • İlk resim ana resim olacak</div>
                        <input type="file" id="fileInput" name="images[]" multiple accept="image/*" class="file-input">
                    </div>

                    <div class="preview-container" id="previewContainer" style="display: none;">
                        <h3 style="margin-bottom: 20px; color: #4a5568;">Yüklenen Resimler (Bire bir yer değiştirme)</h3>
                        <div class="preview-grid" id="previewGrid"></div>
                    </div>
                </div>

                <button type="submit" name="add_product" class="submit-btn" id="submitBtn">
                    Ürün Ekle
                </button>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/plugins/Swap/Sortable.swap.min.js"></script>

    <script>
        let selectedFiles = [];

        const dropZone = document.getElementById('dropZone');
        const fileInput = document.getElementById('fileInput');
        const previewContainer = document.getElementById('previewContainer');
        const previewGrid = document.getElementById('previewGrid');
        const submitBtn = document.getElementById('submitBtn');

        // Bildirim göster ve URL temizle
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

        let sortable = Sortable.create(previewGrid, {
            animation: 150,
            swap: true,
            swapClass: 'highlight',
            onEnd: function(evt) {
                if (evt.oldIndex !== evt.newIndex) {
                    [selectedFiles[evt.oldIndex], selectedFiles[evt.newIndex]] =
                    [selectedFiles[evt.newIndex], selectedFiles[evt.oldIndex]];
                    updateFileInput();
                }
            }
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
            const files = Array.from(e.dataTransfer.files);
            handleFiles(files);
        });

        dropZone.addEventListener('click', () => {
            fileInput.click();
        });

        fileInput.addEventListener('change', (e) => {
            const files = Array.from(e.target.files);
            handleFiles(files);
        });

        function handleFiles(files) {
            files.forEach(file => {
                if (file.type.startsWith('image/')) {
                    selectedFiles.push(file);
                    createPreview(file);
                }
            });

            if (selectedFiles.length > 0) {
                previewContainer.style.display = 'block';
                updateSubmitButton();
            }
        }

        function createPreview(file) {
            const reader = new FileReader();

            reader.onload = function(e) {
                const previewItem = document.createElement('div');
                previewItem.className = 'preview-item';

                previewItem.innerHTML = `
                    <img src="${e.target.result}" class="preview-image" alt="Preview">
                    <div class="preview-overlay">
                        <button type="button" class="preview-btn move-btn" title="Takas">⇄</button>
                        <button type="button" class="preview-btn delete-btn" onclick="removeFile(this)" title="Sil">🗑️</button>
                    </div>
                `;

                previewGrid.appendChild(previewItem);
            };

            reader.readAsDataURL(file);
        }

        function removeFile(button) {
            const previewItem = button.closest('.preview-item');
            const index = Array.from(previewGrid.children).indexOf(previewItem);

            previewItem.classList.add('removing');

            setTimeout(() => {
                selectedFiles.splice(index, 1);
                previewItem.remove();

                if (selectedFiles.length === 0) {
                    previewContainer.style.display = 'none';
                }

                updateFileInput();
                updateSubmitButton();
            }, 300);
        }

        function updateFileInput() {
            const dataTransfer = new DataTransfer();
            selectedFiles.forEach(file => {
                dataTransfer.items.add(file);
            });
            fileInput.files = dataTransfer.files;
        }

        function updateSubmitButton() {
            if (selectedFiles.length > 0) {
                submitBtn.innerHTML = `Ürün Ekle (${selectedFiles.length} resim)`;
            } else {
                submitBtn.innerHTML = 'Ürün Ekle';
            }
        }
    </script>
</body>
</html>
