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
    $notification = "Ürün başarıyla sisteme kaydedildi ve satışa hazır hale getirildi";
    $notification_type = "success";
} elseif (isset($_GET['error'])) {
    $notification = "Ürün kaydetme işlemi başarısız oldu, lütfen tekrar deneyiniz";
    $notification_type = "error";
}

// Sınıf listesi
$classes = ['Anaokulu - Kreş', '1.sınıf', '2.sınıf', '3.sınıf', '4.sınıf', '5.sınıf', '6.sınıf', '7.sınıf', '8.sınıf', '9.sınıf', '10.sınıf', '11.sınıf', '12.sınıf'];

// Kategoriler
$categories = ['Giyim', 'Kırtasiye', 'Spor', 'Aksesuar'];

// Hazır varyasyonlar
$preset_variations = [
    'renk' => ['Kırmızı', 'Mavi', 'Yeşil', 'Sarı', 'Siyah', 'Beyaz'],
    'beden' => ['XS', 'S', 'M', 'L', 'XL', 'XXL'],
    'numara' => ['36', '37', '38', '39', '40', '41', '42', '43', '44', '45']
];

// Ürün ekleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
    try {
        $name = $_POST['name'];
        $description = $_POST['description'];
        $price = $_POST['price'];
        $product_code = $_POST['product_code'];
        $barcode = $_POST['barcode'];
        $category = $_POST['category'];
        $target_classes = isset($_POST['target_classes']) ? $_POST['target_classes'] : [];
        
        // Validation
        if (empty($name) || empty($description) || empty($price)) {
            $notification = "Zorunlu alanlar boş bırakılamaz, lütfen tüm alanları doldurunuz";
            $notification_type = "warning";
        } else {
            // Varyasyonları JSON olarak kaydet
            $variations = [];
            if (isset($_POST['variations'])) {
                $variations = $_POST['variations'];
            }

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
            $variations_json = json_encode($variations);

            // Stok yerine varyasyon sistemi kullandığımız için stock=1 default
            $stmt = $pdo->prepare("INSERT INTO products (name, description, price, stock, images, classes, product_code, barcode, category) VALUES (?, ?, ?, 1, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $description, $price, $images_json, $target_classes_json, $product_code, $barcode, $category]);

            header("Location: add_product.php?success=1");
            exit;
        }

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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            background: #F3F3F5; 
            color: #050E1A;
            display: flex;
        }
        
        .sidebar {
            width: 300px;
            background: white;
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            padding: 24px 0;
            box-shadow: 2px 0 10px rgba(0,0,0,0.05);
            overflow-y: auto;
        }
        
        .logo {
            padding: 0 24px;
            margin-bottom: 20px;
        }
        
        .logo h1 {
            font-size: 22px;
            font-weight: 700;
            color: #050E1A;
            margin-bottom: 16px;
        }
        
        .search-container {
            padding: 0 24px;
            margin-bottom: 32px;
            position: relative;
        }
        
        .search-box {
            position: relative;
            display: flex;
            align-items: center;
        }
        
        .search-input {
            width: 100%;
            padding: 12px 16px 12px 38px;
            background: #FAFAFB;
            border: 2px solid #ECEDEE;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 500;
            color: #050E1A;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
        
        .search-input:focus {
            outline: none;
            border-color: #050E1A;
        }
        
        .search-icon {
            position: absolute;
            left: 12px;
            width: 18px;
            height: 18px;
            color: #666;
        }
        
        .search-hotkey {
            position: absolute;
            right: 16px;
            background: #ECEDEE;
            color: #666;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;
        }
        
        .search-popup {
            position: absolute;
            top: calc(100% + 10px);
            left: 24px;
            right: 24px;
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.15);
            z-index: 1000;
            max-height: 500px;
            overflow-y: auto;
            display: none;
        }
        
        .nav-section {
            margin-bottom: 28px;
        }
        
        .nav-title {
            padding: 0 24px;
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
            font-weight: 600;
            letter-spacing: 0.5px;
            margin-bottom: 12px;
        }
        
        .nav-menu {
            list-style: none;
        }
        
        .nav-item {
            margin-bottom: 4px;
            position: relative;
        }
        
        .nav-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 24px;
            color: #050E1A;
            text-decoration: none;
            font-weight: 500;
            font-size: 16px;
            transition: none;
        }
        
        .nav-link.active {
            font-weight: 700;
        }
        
        .nav-link svg {
            width: 20px;
            height: 20px;
        }
        
        .nav-item.has-submenu > .nav-link {
            cursor: pointer;
        }
        
        .submenu {
            display: none;
            position: relative;
        }
        
        .nav-item.has-submenu.open .submenu {
            display: block;
        }
        
        .nav-item.has-submenu.open::before {
            content: '';
            position: absolute;
            left: 34px;
            top: 48px;
            bottom: 12px;
            width: 1px;
            background: #ECEDEE;
            z-index: 1;
        }
        
        .submenu-item {
            margin-bottom: 2px;
            position: relative;
        }
        
        .submenu-link {
            display: block;
            padding: 10px 16px;
            color: #666;
            text-decoration: none;
            font-size: 16px;
            transition: none;
            margin-left: 46px;
        }
        
        .submenu-link.active {
            font-weight: 700;
            color: #050E1A;
        }
        
        .submenu-item.active::before {
            content: '';
            position: absolute;
            left: 34px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #050E1A;
            z-index: 2;
        }
        
        .user-profile {
            position: absolute;
            bottom: 24px;
            left: 24px;
            right: 24px;
            border-top: 1px solid #ECEDEE;
            padding-top: 20px;
        }
        
        .user-card {
            background: #FAFAFB;
            border: 1px solid #ECEDEE;
            border-radius: 12px;
            padding: 16px;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .user-card:hover {
            background: #F0F0F0;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #CCCCCC;
            color: #050E1A;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 16px;
        }
        
        .user-details h3 {
            font-size: 15px;
            font-weight: 700;
            color: #050E1A;
            margin-bottom: 1px;
        }
        
        .user-details span {
            font-size: 13px;
            color: #666;
        }
        
        .main-content {
            margin-left: 315px;
            flex: 1;
            padding: 40px;
            max-width: calc(100vw - 315px);
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
        }
        
        .header-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            width: 100%;
            max-width: 1182px;
        }
        
        .page-title {
            font-size: 26px;
            font-weight: 700;
            color: #050E1A;
        }
        
        .header-buttons {
            display: flex;
            gap: 12px;
            align-items: center;
        }
        
        .test-button {
            background: #22c55e;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
        
        .test-button.error {
            background: #ef4444;
        }
        
        .test-button.warning {
            background: #f59e0b;
        }
        
        .nav-button {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 18px 32px;
            border: none;
            border-radius: 16px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            font-family: 'Plus Jakarta Sans', sans-serif;
            box-shadow: 0 8px 32px rgba(102, 126, 234, 0.3);
            transition: all 0.3s;
            margin-left: 16px;
        }
        
        .nav-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 40px rgba(102, 126, 234, 0.4);
        }
        
        .nav-popup {
            position: fixed;
            bottom: 170px;
            right: 50px;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.15);
            z-index: 1001;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s;
            min-width: 320px;
        }
        
        .nav-popup.show {
            opacity: 1;
            visibility: visible;
        }
        
        .nav-popup-content {
            padding: 24px;
        }
        
        .nav-popup-title {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 16px;
            text-align: center;
        }
        
        .nav-option {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px;
            border-radius: 12px;
            cursor: pointer;
            transition: background 0.2s;
            margin-bottom: 8px;
        }
        
        .nav-option:hover {
            background: #F8F9FA;
        }
        
        .nav-option-icon {
            width: 40px;
            height: 40px;
            background: #F0F0F0;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .nav-option-text {
            font-weight: 600;
        }
        
        .form-container {
            max-width: 1182px;
            width: 100%;
        }
        
        .product-form {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 24px;
            margin-bottom: 24px;
        }
        
        .form-full {
            grid-column: 1 / -1;
            margin-bottom: 24px;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
        }
        
        .form-label {
            margin-bottom: 12px;
            font-weight: 600;
            color: #050E1A;
            font-size: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .required {
            color: #ff6b6b;
            font-size: 16px;
        }
        
        .add-link {
            color: #050E1A;
            text-decoration: underline;
            font-weight: 700;
            cursor: pointer;
            font-size: 14px;
            margin-left: auto;
        }
        
        .add-link:hover {
            color: #1a2332;
        }
        
        .form-input {
            padding: 16px;
            background: #FAFAFB;
            border: 2px solid #ECEDEE;
            border-radius: 12px;
            font-size: 16px;
            color: #050E1A;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
        
        .form-input:focus {
            outline: none;
            border-color: #050E1A;
        }
        
        .barcode-container {
            position: relative;
        }
        
        .scan-button {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: #F0F0F0;
            border: 1px solid #ECEDEE;
            border-radius: 8px;
            padding: 8px 12px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            font-weight: 600;
            color: #666;
            transition: all 0.2s;
        }
        
        .scan-button:hover {
            background: #E0E0E0;
            border-color: #050E1A;
        }
        
        .scan-button svg {
            width: 16px;
            height: 16px;
        }
        
        .barcode-popup {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.8);
            z-index: 10000;
            justify-content: center;
            align-items: center;
        }
        
        .barcode-popup-content {
            background: white;
            border-radius: 24px;
            padding: 40px;
            width: 500px;
            height: 500px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            position: relative;
        }
        
        .barcode-scanner {
            width: 250px;
            height: 250px;
            border: 3px solid #ECEDEE;
            border-radius: 16px;
            position: relative;
            overflow: hidden;
            margin-bottom: 24px;
        }
        
        .scan-line {
            position: absolute;
            top: -3px;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, transparent, #22c55e, transparent);
            transform: translateY(0);
            opacity: 0;
            animation: none;
        }
        
        .scan-line.scanning {
            opacity: 1;
            animation: scanDown 2s ease-in-out infinite;
        }
        
        @keyframes scanDown {
            0% {
                transform: translateY(0);
                box-shadow: 0 0 20px rgba(34, 197, 94, 0.6);
            }
            50% {
                transform: translateY(250px);
                box-shadow: 0 0 30px rgba(34, 197, 94, 0.8);
            }
            100% {
                transform: translateY(0);
                box-shadow: 0 0 20px rgba(34, 197, 94, 0.6);
            }
        }
        
        .scanner-frame {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            border: 2px solid transparent;
            border-radius: 16px;
            transition: border-color 0.3s;
        }
        
        .scanner-frame.scanning {
            border-color: #22c55e;
            box-shadow: inset 0 0 0 2px rgba(34, 197, 94, 0.3);
        }
        
        .scan-status {
            font-size: 18px;
            font-weight: 600;
            color: #050E1A;
            margin-bottom: 16px;
            text-align: center;
        }
        
        .scan-status.failed {
            color: #ef4444;
        }
        
        .stars-container {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            pointer-events: none;
            overflow: hidden;
        }
        
        .star {
            position: absolute;
            color: #22c55e;
            font-size: 16px;
            opacity: 0;
            animation: starFall 3s ease-in-out infinite;
        }
        
        @keyframes starFall {
            0% {
                opacity: 1;
                transform: translateY(-20px) rotate(0deg);
            }
            100% {
                opacity: 0;
                transform: translateY(520px) rotate(360deg);
            }
        }
        
        .close-barcode-popup {
            position: absolute;
            top: 20px;
            right: 20px;
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #999;
        }
        
        .category-container {
            position: relative;
        }
        
        .category-dropdown {
            width: 100%;
            padding: 16px;
            background: #FAFAFB;
            border: 2px solid #ECEDEE;
            border-radius: 12px;
            font-size: 16px;
            color: #050E1A;
            font-family: 'Plus Jakarta Sans', sans-serif;
            appearance: none;
            cursor: pointer;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e");
            background-position: right 12px center;
            background-repeat: no-repeat;
            background-size: 16px;
        }
        
        .category-dropdown:focus {
            outline: none;
            border-color: #050E1A;
        }
        
        .textarea-container {
            position: relative;
        }
        
        .form-input[name="description"] {
            resize: vertical;
            min-height: 120px;
            padding-right: 80px;
        }
        
        .char-counter {
            position: absolute;
            bottom: 12px;
            right: 16px;
            font-size: 12px;
            color: #666;
            background: #FAFAFB;
            padding: 4px 8px;
            border-radius: 6px;
            border: 1px solid #ECEDEE;
        }
        
        .price-container {
            position: relative;
        }
        
        .currency-flag {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            display: flex;
            align-items: center;
            gap: 6px;
            background: #FAFAFB;
            padding: 4px 8px;
            border-radius: 6px;
            border: 1px solid #ECEDEE;
        }
        
        .flag-img {
            width: 20px;
            height: 15px;
            object-fit: cover;
            border-radius: 2px;
        }
        
        .currency-text {
            font-size: 14px;
            font-weight: 600;
            color: #050E1A;
        }
        
        input[type="number"]::-webkit-outer-spin-button,
        input[type="number"]::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }
        
        input[type="number"] {
            -moz-appearance: textfield;
            padding-right: 80px;
        }
        
        .class-selection {
            margin-top: 12px;
        }
        
        .selected-classes {
            margin-top: 16px;
        }
        
        .classes-title {
            font-weight: 700;
            color: #050E1A;
            margin-bottom: 12px;
            font-size: 14px;
        }
        
        .class-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        
        .class-tag {
            background: #F0F0F0;
            border: 1px solid #ECEDEE;
            border-radius: 20px;
            padding: 6px 12px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .remove-tag {
            background: #ff6b6b;
            color: white;
            border: none;
            border-radius: 50%;
            width: 16px;
            height: 16px;
            cursor: pointer;
            font-size: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .class-popup, .variation-popup {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        
        .popup-content {
            background: white;
            border-radius: 20px;
            padding: 40px;
            max-width: 600px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
        }
        
        .popup-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 32px;
        }
        
        .popup-title {
            font-size: 24px;
            font-weight: 700;
        }
        
        .close-popup {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #999;
        }
        
        .class-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 12px;
        }
        
        .class-checkbox {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 16px;
            background: #FAFAFB;
            border: 2px solid #ECEDEE;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .class-checkbox:hover {
            border-color: #050E1A;
        }
        
        .class-checkbox:hover span {
            font-weight: 600;
        }
        
        .class-checkbox.selected {
            background: #050E1A;
            color: white;
            border-color: #050E1A;
        }
        
        .checkbox-custom {
            width: 20px;
            height: 20px;
            border: 2px solid #ECEDEE;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: white;
        }
        
        .class-checkbox.selected .checkbox-custom {
            background: white;
            border-color: white;
        }
        
        .checkbox-custom svg {
            width: 14px;
            height: 14px;
            display: none;
        }
        
        .class-checkbox.selected .checkbox-custom svg {
            display: block;
        }
        
        .class-checkbox input[type="checkbox"] {
            display: none;
        }
        
        .variation-section {
            margin-bottom: 24px;
        }
        
        .variation-button {
            background: #050E1A;
            color: white;
            padding: 14px 28px;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 600;
            font-size: 16px;
            font-family: 'Plus Jakarta Sans', sans-serif;
            margin-top: 12px;
        }
        
        .variation-button:hover {
            background: #1a2332;
        }
        
        .selected-variations {
            margin-top: 16px;
        }
        
        .variations-title {
            font-weight: 700;
            color: #050E1A;
            margin-bottom: 12px;
            font-size: 14px;
        }
        
        .variation-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        
        .variation-tag-display {
            background: #F0F0F0;
            border: 1px solid #ECEDEE;
            border-radius: 20px;
            padding: 6px 12px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .variation-type {
            margin-bottom: 32px;
        }
        
        .variation-type-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .add-custom-variation {
            background: #28a745;
            color: white;
            border: none;
            border-radius: 50px;
            width: 32px;
            height: 32px;
            cursor: pointer;
            font-size: 18px;
        }
        
        .variation-options {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 16px;
        }
        
        .variation-tag {
            display: flex;
            align-items: center;
            gap: 8px;
            background: #FAFAFB;
            border: 2px solid #ECEDEE;
            border-radius: 20px;
            padding: 8px 16px;
            font-size: 14px;
            cursor: pointer;
        }
        
        .variation-tag.selected {
            background: #050E1A;
            color: white;
            border-color: #050E1A;
        }
        
        .add-custom-input {
            padding: 8px 12px;
            background: #FAFAFB;
            border: 2px solid #ECEDEE;
            border-radius: 8px;
            font-size: 14px;
            margin-right: 8px;
        }
        
        .add-custom-btn {
            background: #28a745;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
        }
        
        .upload-container {
            margin-bottom: 24px;
        }
        
        .drop-zone {
            border: 3px dashed #ECEDEE;
            background: #FAFAFB;
            padding: 60px 20px;
            text-align: center;
            border-radius: 16px;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 12px;
        }
        
        .drop-zone:hover {
            border-color: #050E1A;
        }
        
        .upload-icon {
            margin-bottom: 16px;
            color: #666;
        }
        
        .upload-icon svg {
            width: 48px;
            height: 48px;
        }
        
        .upload-text {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 8px;
            color: #050E1A;
        }
        
        .upload-hint {
            color: #666;
            font-size: 14px;
        }
        
        .file-input {
            display: none;
        }
        
        .preview-container {
            margin-top: 24px;
            display: none;
        }
        
        .preview-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, 120px);
            gap: 12px;
        }
        
        .preview-item {
            position: relative;
            width: 120px;
            height: 120px;
            border-radius: 12px;
            overflow: hidden;
            cursor: move;
        }
        
        .preview-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .preview-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.7);
            opacity: 0;
            transition: opacity 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .preview-item:hover .preview-overlay {
            opacity: 1;
        }
        
        .delete-preview {
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            color: white;
            border: none;
            border-radius: 50%;
            width: 36px;
            height: 36px;
            cursor: pointer;
            font-size: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .main-image-indicator {
            position: absolute;
            bottom: 8px;
            left: 8px;
            width: 12px;
            height: 12px;
            background: #ff4444;
            border-radius: 50%;
            border: 2px solid white;
            z-index: 10;
        }
        
        .preview-item.dragging {
            opacity: 0.5;
            transform: rotate(5deg);
        }
        
        .form-footer {
            display: flex;
            justify-content: flex-end;
            margin-top: 32px;
        }
        
        .submit-btn {
            background: #050E1A;
            color: white;
            padding: 18px 40px;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            font-family: 'Plus Jakarta Plus', sans-serif;
        }
        
        .submit-btn:hover {
            background: #1a2332;
        }
        
        .notification-alert {
            position: fixed;
            bottom: 100px;
            left: 50%;
            transform: translateX(-50%) translateY(100px);
            background: white;
            border-radius: 50px;
            padding: 16px 24px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.15);
            display: flex;
            align-items: center;
            gap: 12px;
            z-index: 10000;
            font-size: 14px;
            font-weight: 500;
            transition: transform 0.3s ease;
            cursor: pointer;
        }
        
        .notification-alert.show {
            transform: translateX(-50%) translateY(0);
        }
        
        .notification-icon {
            width: 25px;
            height: 25px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        
        .notification-icon.success {
            background: #22c55e;
            color: white;
        }
        
        .notification-icon.error {
            background: #ef4444;
            color: white;
        }
        
        .notification-icon.warning {
            background: #f59e0b;
            color: white;
        }
        
        .notification-icon svg {
            width: 14px;
            height: 14px;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s;
                z-index: 1000;
                left: 0;
            }
            
            .sidebar.open {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
                padding: 20px;
            }
            
            .mobile-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                background: white;
                padding: 16px 20px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                margin-bottom: 20px;
            }
            
            .menu-toggle {
                background: none;
                border: none;
                font-size: 24px;
                cursor: pointer;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .header-container {
                flex-direction: column;
                align-items: flex-start;
                gap: 16px;
            }
            
            .nav-button {
                bottom: 20px;
                right: 20px;
                padding: 14px 24px;
                font-size: 14px;
            }
        }
        
        @media (min-width: 769px) {
            .mobile-header {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="mobile-header">
        <button class="menu-toggle" onclick="toggleSidebar()">☰</button>
        <h2>Gebze Hisar Store</h2>
        <div></div>
    </div>

    <div class="sidebar" id="sidebar">
        <div class="logo">
            <h1>Gebze Hisar Store</h1>
        </div>
        
        <div class="search-container">
            <div class="search-box">
                <svg class="search-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M20 20L15.8033 15.8033M15.8033 15.8033C17.1605 14.4461 18 12.5711 18 10.5C18 6.35786 14.6421 3 10.5 3C6.35786 3 3 6.35786 3 10.5C3 14.6421 6.35786 18 10.5 18C12.5711 18 14.4461 17.1605 15.8033 15.8033Z" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <input type="text" class="search-input" placeholder="Ne Aramıştınız?" id="searchInput" onfocus="showSearchPopup()" onblur="hideSearchPopup()" oninput="filterSearch()">
                <div class="search-hotkey">CTRL + A</div>
            </div>
            
            <div class="search-popup" id="searchPopup">
                <div style="padding: 40px 20px; text-align: center; color: #666; font-size: 16px;">
                    Arama sonucu bulunamadı
                </div>
            </div>
        </div>
        
        <div class="nav-section">
            <div class="nav-title">GENEL</div>
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="index.php" class="nav-link">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M3.5 3.5H10.5V10.5H3.5V3.5Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M3.5 13.5H10.5V20.5H3.5V13.5Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M13.5 3.5H20.5V10.5H13.5V3.5Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M13.5 13.5H20.5V20.5H13.5V13.5Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        Pano
                    </a>
                </li>
                
                <li class="nav-item has-submenu" onclick="toggleSubmenu(this)">
                    <a href="#" class="nav-link">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M15 11C15 12.6569 13.6569 14 12 14C10.3431 14 9 12.6569 9 11M20 7L18 3H6L4 7M20 7H4M20 7V18C20 19.1046 19.1046 20 18 20H6C4.89543 20 4 19.1046 4 18V7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        Sipariş
                    </a>
                    <ul class="submenu">
                        <li class="submenu-item"><a href="admin_orders.php" class="submenu-link">Tüm Siparişler</a></li>
                        <li class="submenu-item"><a href="#" class="submenu-link">Bekleyenler</a></li>
                        <li class="submenu-item"><a href="#" class="submenu-link">Hazırlananlar</a></li>
                        <li class="submenu-item"><a href="#" class="submenu-link">Kargodakiler</a></li>
                    </ul>
                </li>
                
                <li class="nav-item has-submenu open" onclick="toggleSubmenu(this)">
                    <a href="#" class="nav-link active">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M15 11C15 12.6569 13.6569 14 12 14C10.3431 14 9 12.6569 9 11M20 7L18 3H6L4 7M20 7H4M20 7V18C20 19.1046 19.1046 20 18 20H6C4.89543 20 4 19.1046 4 18V7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        Ürün
                    </a>
                    <ul class="submenu">
                        <li class="submenu-item active"><a href="add_product.php" class="submenu-link active">Yeni Ürün</a></li>
                        <li class="submenu-item"><a href="#" class="submenu-link">Giyim</a></li>
                        <li class="submenu-item"><a href="#" class="submenu-link">Aksesuar</a></li>
                        <li class="submenu-item"><a href="#" class="submenu-link">Kırtasiye</a></li>
                    </ul>
                </li>
                
                <li class="nav-item">
                    <a href="student_management.php" class="nav-link">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M16 15H8C5.79086 15 4 16.7909 4 19V21H20V19C20 16.7909 18.2091 15 16 15Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M12 11C14.2091 11 16 9.20914 16 7C16 4.79086 14.2091 3 12 3C9.79086 3 8 4.79086 8 7C8 9.20914 9.79086 11 12 11Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        Öğrenci
                    </a>
                </li>
            </ul>
        </div>
        
        <div class="nav-section">
            <div class="nav-title">AYARLAR</div>
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M21 9V7C21 5.89543 20.1046 5 19 5H5C3.89543 5 3 5.89543 3 7V9M21 9V17C21 18.1046 20.1046 19 19 19H5C3.89543 19 3 18.1046 3 17V9M21 9H3M6 16H10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        POS
                    </a>
                </li>
                
                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M7 21V16H4V4H20V16H12L7 21Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        SMS
                    </a>
                </li>
                
                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M11 3H13C13.5523 3 14 3.44772 14 4V4.56879C14 4.99659 14.2871 5.36825 14.6822 5.53228C15.0775 5.69638 15.5377 5.63384 15.8403 5.33123L16.2426 4.92891C16.6331 4.53838 17.2663 4.53838 17.6568 4.92891L19.071 6.34312C19.4616 6.73365 19.4615 7.36681 19.071 7.75734L18.6688 8.1596C18.3661 8.46223 18.3036 8.92247 18.4677 9.31774C18.6317 9.71287 19.0034 10 19.4313 10L20 10C20.5523 10 21 10.4477 21 11V13C21 13.5523 20.5523 14 20 14H19.4312C19.0034 14 18.6318 14.2871 18.4677 14.6822C18.3036 15.0775 18.3661 15.5377 18.6688 15.8403L19.071 16.2426C19.4616 16.6331 19.4616 17.2663 19.071 17.6568L17.6568 19.071C17.2663 19.4616 16.6331 19.4616 16.2426 19.071L15.8403 18.6688C15.5377 18.3661 15.0775 18.3036 14.6822 18.4677C14.2871 18.6318 14 19.0034 14 19.4312V20C14 20.5523 13.5523 21 13 21H11C10.4477 21 10 20.5523 10 20V19.4313C10 19.0034 9.71287 18.6317 9.31774 18.4677C8.92247 18.3036 8.46223 18.3661 8.1596 18.6688L7.75732 19.071C7.36679 19.4616 6.73363 19.4616 6.34311 19.071L4.92889 17.6568C4.53837 17.2663 4.53837 16.6331 4.92889 16.2426L5.33123 15.8403C5.63384 15.5377 5.69638 15.0775 5.53228 14.6822C5.36825 14.2871 4.99659 14 4.56879 14H4C3.44772 14 3 13.5523 3 13V11C3 10.4477 3.44772 10 4 10L4.56877 10C4.99658 10 5.36825 9.71288 5.53229 9.31776C5.6964 8.9225 5.63386 8.46229 5.33123 8.15966L4.92891 7.75734C4.53838 7.36681 4.53838 6.73365 4.92891 6.34313L6.34312 4.92891C6.73365 4.53839 7.36681 4.53839 7.75734 4.92891L8.15966 5.33123C8.46228 5.63386 8.9225 5.6964 9.31776 5.53229C9.71288 5.36825 10 4.99658 10 4.56876V4C10 3.44772 10.4477 3 11 3Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M14 12C14 13.1046 13.1046 14 12 14C10.8954 14 10 13.1046 10 12C10 10.8954 10.8954 10 12 10C13.1046 10 14 10.8954 14 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        Yardım Merkezi
                    </a>
                </li>
            </ul>
        </div>
        
        <div class="user-profile">
            <div class="user-card" onclick="window.location.href='/admin'">
                <div class="user-info">
                    <div class="user-avatar">A</div>
                    <div class="user-details">
                        <h3>Yönetici</h3>
                        <span>Admin</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="main-content">
        <div class="header-container">
            <h1 class="page-title">Yeni Ürün Ekle</h1>
            <div class="header-buttons">
                <button class="test-button" onclick="testNotification('success')">Ürün Eklendi</button>
                <button class="test-button error" onclick="testNotification('error')">Ürün Eklenemedi</button>
                <button class="test-button warning" onclick="testNotification('warning')">Düzenlemek İstediğinize Emin Misiniz</button>
                <button class="nav-button" onclick="toggleNavPopup()">Navigasyon</button>
            </div>
        </div>
        
        <div class="nav-popup" id="navPopup">
            <div class="nav-popup-content">
                <div class="nav-popup-title">Hızlı Erişim</div>
                <div class="nav-option" onclick="goToOrders()">
                    <div class="nav-option-icon">📋</div>
                    <div class="nav-option-text">Sipariş Ara</div>
                </div>
                <div class="nav-option" onclick="goToStudents()">
                    <div class="nav-option-icon">👤</div>
                    <div class="nav-option-text">Öğrenci Ekle</div>
                </div>
                <div class="nav-option" onclick="goToProducts()">
                    <div class="nav-option-icon">📦</div>
                    <div class="nav-option-text">Ürün Ekle</div>
                </div>
                <div class="nav-option" onclick="goToAI()">
                    <div class="nav-option-icon">🤖</div>
                    <div class="nav-option-text">AI (Yapay Zeka)</div>
                </div>
            </div>
        </div>
        
        <div class="form-container">
            <form method="POST" enctype="multipart/form-data" class="product-form" id="productForm">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Ürün Adı <span class="required">*</span></label>
                        <input type="text" name="name" class="form-input" placeholder="Ürün adını girin" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Ürün Kodu</label>
                        <input type="text" name="product_code" class="form-input" placeholder="Örn: GHS001">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Fiyat <span class="required">*</span></label>
                        <div class="price-container">
                            <input type="number" step="0.01" name="price" class="form-input" placeholder="0.00" required>
                            <div class="currency-flag">
                                <img src="https://cdn-icons-png.flaticon.com/128/9906/9906530.png" alt="TR" class="flag-img">
                                <span class="currency-text">TL</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Kategori</label>
                        <div class="category-container">
                            <select name="category" class="category-dropdown">
                                <option value="">Kategori Seçin</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?= $category ?>"><?= $category ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Barkod Numarası</label>
                        <div class="barcode-container">
                            <input type="text" name="barcode" class="form-input" placeholder="Barkod numarasını girin" id="barcodeInput">
                            <div class="scan-button" onclick="openBarcodeScanner()">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M20 20L15.8033 15.8033M15.8033 15.8033C17.1605 14.4461 18 12.5711 18 10.5C18 6.35786 14.6421 3 10.5 3C6.35786 3 3 6.35786 3 10.5C3 14.6421 6.35786 18 10.5 18C12.5711 18 14.4461 17.1605 15.8033 15.8033Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                Tarat
                            </div>
                        </div>
                    </div>
                    <div></div>
                </div>
                
                <div class="form-full">
                    <div class="form-group">
                        <label class="form-label">Ürün Açıklaması <span class="required">*</span></label>
                        <div class="textarea-container">
                            <textarea name="description" class="form-input" rows="4" placeholder="Ürün açıklamasını yazın" maxlength="2500" required oninput="updateCharCounter(this)"></textarea>
                            <div class="char-counter">
                                <span id="charCount">0</span>/2500
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="form-full">
                    <div class="form-group">
                        <label class="form-label">
                            Hedef Sınıflar <span class="required">*</span>
                            <span class="add-link" onclick="openClassPopup()">Sınıf Ekle</span>
                        </label>
                        <div class="class-selection">
                            <div id="selectedClasses" class="selected-classes" style="display: none;">
                                <div class="classes-title">Sınıflar:</div>
                                <div class="class-tags" id="classTags"></div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="form-full">
                    <div class="variation-section">
                        <div class="form-group">
                            <label class="form-label">Ürün Varyasyonları</label>
                            <button type="button" class="variation-button" onclick="openVariationPopup()">Varyasyon Ekle</button>
                            <div id="selectedVariations" class="selected-variations" style="display: none;">
                                <div class="variations-title">Varyasyonlar:</div>
                                <div class="variation-tags" id="variationTags"></div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="upload-container">
                    <div class="form-group">
                        <label class="form-label">Ürün Resimleri</label>
                        <div class="drop-zone" id="dropZone">
                            <div class="upload-icon">
                                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M4.02693 18.329C4.18385 19.277 5.0075 20 6 20H18C19.1046 20 20 19.1046 20 18V14.1901M4.02693 18.329C4.00922 18.222 4 18.1121 4 18V6C4 4.89543 4.89543 4 6 4H18C19.1046 4 20 4.89543 20 6V14.1901M4.02693 18.329L7.84762 14.5083C8.52765 13.9133 9.52219 13.8482 10.274 14.3494L10.7832 14.6888C11.<path d="M4.02693 18.329C4.18385 19.277 5.0075 20 6 20H18C19.1046 20 20 19.1046 20 18V14.1901M4.02693 18.329C4.00922 18.222 4 18.1121 4 18V6C4 4.89543 4.89543 4 6 4H18C19.1046 4 20 4.89543 20 6V14.1901M4.02693 18.329L7.84762 14.5083C8.52765 13.9133 9.52219 13.8482 10.274 14.3494L10.7832 14.6888C11.5078 15.1719 12.4619 15.1305 13.142 14.5865L15.7901 12.4679C16.4651 11.9279 17.4053 11.8856 18.1228 12.3484C18.2023 12.3997 18.2731 12.4632 18.34 12.5302L20 14.1901M11 9C11 10.1046 10.1046 11 9 11C7.89543 11 7 10.1046 7 9C7 7.89543 7.89543 7 9 7C10.1046 7 11 7.89543 11 9Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </div>
                            <div class="upload-text">Resimleri buraya sürükleyin veya tıklayın</div>
                            <div class="upload-hint">PNG, JPG, GIF formatları desteklenir</div>
                            <input type="file" id="fileInput" name="images[]" multiple accept="image/*" class="file-input">
                        </div>
                        
                        <div class="preview-container" id="previewContainer">
                            <div class="preview-grid" id="previewGrid"></div>
                        </div>
                    </div>
                </div>
                
                <div class="form-footer">
                    <button type="submit" name="add_product" class="submit-btn">Yeni Ürün Ekle</button>
                </div>
            </form>
        </div>
    </div>
    
    <div class="class-popup" id="classPopup">
        <div class="popup-content">
            <div class="popup-header">
                <h3 class="popup-title">Sınıf Seçin</h3>
                <button type="button" class="close-popup" onclick="closeClassPopup()">×</button>
            </div>
            
            <div class="class-grid">
                <?php foreach ($classes as $class): ?>
                    <div class="class-checkbox" onclick="toggleClass(this, '<?= $class ?>')">
                        <div class="checkbox-custom">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M17.0001 9L10 16L7 13" stroke="black" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <span><?= $class ?></span>
                        <input type="checkbox" name="target_classes[]" value="<?= $class ?>">
                    </div>
                <?php endforeach; ?>
            </div>
            
            <button type="button" class="variation-button" onclick="saveClasses()" style="width: 100%; margin-top: 20px;">Sınıfları Kaydet</button>
        </div>
    </div>
    
    <div class="variation-popup" id="variationPopup">
        <div class="popup-content">
            <div class="popup-header">
                <h3 class="popup-title">Varyasyon Ekle</h3>
                <button type="button" class="close-popup" onclick="closeVariationPopup()">×</button>
            </div>
            
            <div class="variation-type">
                <div class="variation-type-title">
                    Renk
                    <button type="button" class="add-custom-variation" onclick="addCustomVariation('renk')">+</button>
                </div>
                <div class="variation-options">
                    <?php foreach ($preset_variations['renk'] as $renk): ?>
                        <div class="variation-tag" onclick="toggleVariation(this, 'renk', '<?= $renk ?>')">
                            <?= $renk ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div id="custom-renk" style="display: none; margin-top: 12px;">
                    <input type="text" class="add-custom-input" placeholder="Yeni renk">
                    <button type="button" class="add-custom-btn" onclick="addCustomVariationValue('renk')">Ekle</button>
                </div>
            </div>
            
            <div class="variation-type">
                <div class="variation-type-title">
                    Beden
                    <button type="button" class="add-custom-variation" onclick="addCustomVariation('beden')">+</button>
                </div>
                <div class="variation-options">
                    <?php foreach ($preset_variations['beden'] as $beden): ?>
                        <div class="variation-tag" onclick="toggleVariation(this, 'beden', '<?= $beden ?>')">
                            <?= $beden ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div id="custom-beden" style="display: none; margin-top: 12px;">
                    <input type="text" class="add-custom-input" placeholder="Yeni beden">
                    <button type="button" class="add-custom-btn" onclick="addCustomVariationValue('beden')">Ekle</button>
                </div>
            </div>
            
            <div class="variation-type">
                <div class="variation-type-title">
                    Numara
                    <button type="button" class="add-custom-variation" onclick="addCustomVariation('numara')">+</button>
                </div>
                <div class="variation-options">
                    <?php foreach ($preset_variations['numara'] as $numara): ?>
                        <div class="variation-tag" onclick="toggleVariation(this, 'numara', '<?= $numara ?>')">
                            <?= $numara ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div id="custom-numara" style="display: none; margin-top: 12px;">
                    <input type="text" class="add-custom-input" placeholder="Yeni numara">
                    <button type="button" class="add-custom-btn" onclick="addCustomVariationValue('numara')">Ekle</button>
                </div>
            </div>
            
            <button type="button" class="variation-button" onclick="saveVariations()" style="width: 100%; margin-top: 20px;">Varyasyonları Kaydet</button>
        </div>
    </div>
    
    <div class="barcode-popup" id="barcodePopup">
        <div class="barcode-popup-content">
            <button class="close-barcode-popup" onclick="closeBarcodeScanner()">×</button>
            
            <div class="barcode-scanner">
                <div class="scanner-frame" id="scannerFrame"></div>
                <div class="scan-line" id="scanLine"></div>
            </div>
            
            <div class="scan-status" id="scanStatus">Tarama yapılıyor...</div>
            
            <div class="stars-container" id="starsContainer"></div>
        </div>
    </div>
    
    <script>
        let selectedFiles = [];
        let selectedVariations = {renk: [], beden: [], numara: []};
        let selectedClasses = [];
        let draggedItem = null;
        
        function updateCharCounter(textarea) {
            const charCount = textarea.value.length;
            document.getElementById('charCount').textContent = charCount;
        }
        
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey && (e.key === 'a' || e.key === 'A')) {
                e.preventDefault();
                document.getElementById('searchInput').focus();
            }
        });
        
        function testNotification(type) {
            let message = '';
            if (type === 'success') {
                message = 'Ürün başarıyla eklendi!';
            } else if (type === 'error') {
                message = 'Ürün eklenemedi, hata oluştu!';
            } else if (type === 'warning') {
                message = 'Düzenlemek istediğinize emin misiniz?';
            }
            showNotification(message, type);
        }
        
        function openBarcodeScanner() {
            document.getElementById('barcodePopup').style.display = 'flex';
            startScanning();
        }

        function closeBarcodeScanner() {
            document.getElementById('barcodePopup').style.display = 'none';
            stopScanning();
        }

        function startScanning() {
            const scanLine = document.getElementById('scanLine');
            const scannerFrame = document.getElementById('scannerFrame');
            const scanStatus = document.getElementById('scanStatus');
            
            scanLine.classList.add('scanning');
            scannerFrame.classList.add('scanning');
            scanStatus.textContent = 'Tarama yapılıyor...';
            scanStatus.classList.remove('failed');
            
            createStars();
            
            setTimeout(() => {
                scanStatus.textContent = 'Tarama başarısız';
                scanStatus.classList.add('failed');
                stopScanning();
            }, 15000);
        }

        function stopScanning() {
            const scanLine = document.getElementById('scanLine');
            const scannerFrame = document.getElementById('scannerFrame');
            
            scanLine.classList.remove('scanning');
            scannerFrame.classList.remove('scanning');
            
            document.getElementById('starsContainer').innerHTML = '';
        }

        function createStars() {
            const container = document.getElementById('starsContainer');
            const stars = ['★', '✦', '✧', '⭐', '✨'];
            
            const starInterval = setInterval(() => {
                if (document.getElementById('barcodePopup').style.display === 'flex') {
                    const star = document.createElement('div');
                    star.className = 'star';
                    star.textContent = stars[Math.floor(Math.random() * stars.length)];
                    star.style.left = Math.random() * 100 + '%';
                    star.style.animationDuration = (2 + Math.random() * 2) + 's';
                    star.style.animationDelay = Math.random() * 1 + 's';
                    
                    container.appendChild(star);
                    
                    setTimeout(() => {
                        if (star.parentNode) {
                            star.parentNode.removeChild(star);
                        }
                    }, 3000);
                } else {
                    clearInterval(starInterval);
                }
            }, 300);
        }
        
        function showSearchPopup() {
            document.getElementById('searchPopup').style.display = 'block';
        }
        
        function hideSearchPopup() {
            setTimeout(() => {
                document.getElementById('searchPopup').style.display = 'none';
            }, 200);
        }
        
        function filterSearch() {
            // Boş döndür
        }
        
        document.getElementById('searchInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                this.value = '';
                filterSearch();
            }
        });
        
        function toggleNavPopup() {
            const popup = document.getElementById('navPopup');
            popup.classList.toggle('show');
        }
        
        document.addEventListener('click', function(e) {
            const popup = document.getElementById('navPopup');
            const button = document.querySelector('.nav-button');
            
            if (!popup.contains(e.target) && !button.contains(e.target)) {
                popup.classList.remove('show');
            }
        });
        
        function goToOrders() {
            window.location.href = 'admin_orders.php';
        }
        
        function goToStudents() {
            window.location.href = 'student_management.php';
        }
        
        function goToProducts() {
            window.location.href = 'products.php';
        }
        
        function goToAI() {
            window.location.href = 'ai_assistant.php';
        }
        
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('open');
        }
        
        function toggleSubmenu(element) {
            element.classList.toggle('open');
        }
        
        function openClassPopup() {
            document.getElementById('classPopup').style.display = 'flex';
        }
        
        function closeClassPopup() {
            document.getElementById('classPopup').style.display = 'none';
        }
        
        function toggleClass(element, className) {
            element.classList.toggle('selected');
            const checkbox = element.querySelector('input[type="checkbox"]');
            checkbox.checked = element.classList.contains('selected');
        }
        
        function saveClasses() {
            selectedClasses = [];
            const checkboxes = document.querySelectorAll('.class-checkbox.selected');
            
            checkboxes.forEach(checkbox => {
                const className = checkbox.querySelector('input').value;
                selectedClasses.push(className);
            });
            
            updateClassDisplay();
            closeClassPopup();
        }
        
        function updateClassDisplay() {
            const container = document.getElementById('selectedClasses');
            const tagsContainer = document.getElementById('classTags');
            
            if (selectedClasses.length > 0) {
                container.style.display = 'block';
                tagsContainer.innerHTML = '';
                
                selectedClasses.forEach(className => {
                    const tag = document.createElement('div');
                    tag.className = 'class-tag';
                    tag.innerHTML = `
                        ${className}
                        <button type="button" class="remove-tag" onclick="removeClass('${className}')">×</button>
                    `;
                    tagsContainer.appendChild(tag);
                });
            } else {
                container.style.display = 'none';
            }
        }
        
        function removeClass(className) {
            selectedClasses = selectedClasses.filter(c => c !== className);
            updateClassDisplay();
            
            const checkbox = document.querySelector(`.class-checkbox input[value="${className}"]`);
            if (checkbox) {
                checkbox.closest('.class-checkbox').classList.remove('selected');
                checkbox.checked = false;
            }
        }
        
        const dropZone = document.getElementById('dropZone');
        const fileInput = document.getElementById('fileInput');
        const previewContainer = document.getElementById('previewContainer');
        const previewGrid = document.getElementById('previewGrid');
        
        dropZone.addEventListener('click', () => fileInput.click());
        
        dropZone.addEventListener('dragover', (e) => {
            e.preventDefault();
            dropZone.style.borderColor = '#050E1A';
        });
        
        dropZone.addEventListener('dragleave', () => {
            dropZone.style.borderColor = '#ECEDEE';
        });
        
        dropZone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropZone.style.borderColor = '#ECEDEE';
            const files = Array.from(e.dataTransfer.files);
            handleFiles(files);
        });
        
        fileInput.addEventListener('change', (e) => {
            const files = Array.from(e.target.files);
            handleFiles(files);
        });
        
        function handleFiles(files) {
            files.forEach(file => {
                if (file.type.startsWith('image/')) {
                    selectedFiles.push(file);
                    createPreview(file, selectedFiles.length - 1);
                }
            });
            
            if (selectedFiles.length > 0) {
                previewContainer.style.display = 'block';
            }
        }
        
        function createPreview(file, index) {
            const reader = new FileReader();
            
            reader.onload = function(e) {
                const previewItem = document.createElement('div');
                previewItem.className = 'preview-item';
                previewItem.draggable = true;
                previewItem.dataset.index = index;
                
                previewItem.innerHTML = `
                    <img src="${e.target.result}" class="preview-image">
                    ${index === 0 ? '<div class="main-image-indicator"></div>' : ''}
                    <div class="preview-overlay">
                        <button type="button" class="delete-preview" onclick="removeFile(this)">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M18 6V18C18 19.1046 17.1046 20 16 20H8C6.89543 20 6 19.1046 6 18V6M18 6H15M18 6H20M6 6H4M6 6H9M15 6V5C15 3.89543 14.1046 3 13 3H11C9.89543 3 9 3.89543 9 5V6M15 6H9" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </button>
                    </div>
                `;
                
                previewItem.addEventListener('dragstart', handleDragStart);
                previewItem.addEventListener('dragover', handleDragOver);
                previewItem.addEventListener('drop', handleDrop);
                previewItem.addEventListener('dragend', handleDragEnd);
                
                previewGrid.appendChild(previewItem);
            };
            
            reader.readAsDataURL(file);
        }
        
        function handleDragStart(e) {
            draggedItem = this;
            this.classList.add('dragging');
            e.dataTransfer.effectAllowed = 'move';
        }
        
        function handleDragOver(e) {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';
        }
        
        function handleDrop(e) {
            e.preventDefault();
            
            if (this !== draggedItem) {
                const draggedIndex = parseInt(draggedItem.dataset.index);
                const targetIndex = parseInt(this.dataset.index);
                
                [selectedFiles[draggedIndex], selectedFiles[targetIndex]] = [selectedFiles[targetIndex], selectedFiles[draggedIndex]];
                
                refreshPreview();
            }
        }
        
        function handleDragEnd() {
            this.classList.remove('dragging');
            draggedItem = null;
        }
        
        function refreshPreview() {
            previewGrid.innerHTML = '';
            selectedFiles.forEach((file, index) => {
                createPreview(file, index);
            });
        }
        
        function removeFile(button) {
            const previewItem = button.closest('.preview-item');
            const index = parseInt(previewItem.dataset.index);
            
            selectedFiles.splice(index, 1);
            
            if (selectedFiles.length === 0) {
                previewContainer.style.display = 'none';
            } else {
                refreshPreview();
            }
            
            updateFileInput();
        }
        
        function updateFileInput() {
            const dataTransfer = new DataTransfer();
            selectedFiles.forEach(file => {
                dataTransfer.items.add(file);
            });
            fileInput.files = dataTransfer.files;
        }
        
        function openVariationPopup() {
            document.getElementById('variationPopup').style.display = 'flex';
        }
        
        function closeVariationPopup() {
            document.getElementById('variationPopup').style.display = 'none';
        }
        
        function toggleVariation(element, type, value) {
            element.classList.toggle('selected');
            
            if (element.classList.contains('selected')) {
                selectedVariations[type].push(value);
            } else {
                const index = selectedVariations[type].indexOf(value);
                if (index > -1) {
                    selectedVariations[type].splice(index, 1);
                }
            }
        }
        
        function addCustomVariation(type) {
            document.getElementById(`custom-${type}`).style.display = 'block';
        }
        
        function addCustomVariationValue(type) {
            const input = document.querySelector(`#custom-${type} .add-custom-input`);
            const value = input.value.trim();
            
            if (value) {
                const variationOptions = document.querySelector(`#custom-${type}`).previousElementSibling;
                const newTag = document.createElement('div');
                newTag.className = 'variation-tag selected';
                newTag.textContent = value;
                newTag.onclick = () => toggleVariation(newTag, type, value);
                
                variationOptions.appendChild(newTag);
                selectedVariations[type].push(value);
                
                input.value = '';
                document.getElementById(`custom-${type}`).style.display = 'none';
            }
        }
        
        function saveVariations() {
            updateVariationDisplay();
            closeVariationPopup();
        }
        
        function updateVariationDisplay() {
            const container = document.getElementById('selectedVariations');
            const tagsContainer = document.getElementById('variationTags');
            
            let hasVariations = false;
            tagsContainer.innerHTML = '';
            
            Object.keys(selectedVariations).forEach(type => {
                if (selectedVariations[type].length > 0) {
                    hasVariations = true;
                    selectedVariations[type].forEach(value => {
                        const tag = document.createElement('div');
                        tag.className = 'variation-tag-display';
                        tag.innerHTML = `
                            ${type}: ${value}
                            <button type="button" class="remove-tag" onclick="removeVariation('${type}', '${value}')">×</button>
                        `;
                        tagsContainer.appendChild(tag);
                    });
                }
            });
            
            container.style.display = hasVariations ? 'block' : 'none';
        }
        
        function removeVariation(type, value) {
            const index = selectedVariations[type].indexOf(value);
            if (index > -1) {
                selectedVariations[type].splice(index, 1);
            }
            updateVariationDisplay();
            
            const variationElement = document.querySelector(`.variation-tag[onclick*="'${type}', '${value}'"]`);
            if (variationElement) {
                variationElement.classList.remove('selected');
            }
        }
        
        document.getElementById('productForm').addEventListener('submit', function(e) {
            selectedClasses.forEach(className => {
                const hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = 'target_classes[]';
                hiddenInput.value = className;
                this.appendChild(hiddenInput);
            });
        });
        
        <?php if ($notification): ?>
            showNotification('<?= $notification ?>', '<?= $notification_type ?>');
            
            setTimeout(() => {
                const url = new URL(window.location);
                url.searchParams.delete('success');
                url.searchParams.delete('error');
                url.searchParams.delete('warning');
                window.history.replaceState({}, document.title, url.pathname);
            }, 1000);
        <?php endif; ?>
        
        function showNotification(message, type) {
            const alert = document.createElement('div');
            alert.className = 'notification-alert';
            
            let iconSvg = '';
            if (type === 'success') {
                iconSvg = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M17.0001 9L10 16L7 13" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
            } else if (type === 'error') {
                iconSvg = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M16 8L8 16M8.00001 8L16 16" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
            } else if (type === 'warning') {
                iconSvg = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 16.99V17M12 7V14" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
            }
            
            alert.innerHTML = `
                <div class="notification-icon ${type}">
                    ${iconSvg}
                </div>
                <span>${message}</span>
            `;
            
            document.body.appendChild(alert);
            
            setTimeout(() => {
                alert.classList.add('show');
            }, 100);
            
            alert.onclick = () => {
                alert.classList.remove('show');
                setTimeout(() => alert.remove(), 300);
            };
        }
    </script>
</body>
</html>
