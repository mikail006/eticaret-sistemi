<?php
$content = '<?php
session_start();
include "config.php";

if (!isset($_SESSION["student_id"])) {
    header("Location: student_login.php");
    exit;
}

if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    header("Location: index.php");
    exit;
}

$product_id = $_GET["id"];
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch();

if (!$product) {
    header("Location: index.php");
    exit;
}

$images = json_decode($product["images"], true) ?: [];
?>
<!DOCTYPE html>
<html>
<head>
    <title>Ürün Detayı</title>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial; background: #f5f6fa; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 15px; margin-bottom: 30px; }
        .product-detail { display: grid; grid-template-columns: 1fr 1fr; gap: 40px; background: white; border-radius: 15px; padding: 30px; }
        .main-image { width: 100%; height: 400px; border-radius: 15px; object-fit: cover; margin-bottom: 20px; }
        .btn { padding: 12px 24px; border: none; border-radius: 25px; text-decoration: none; font-weight: bold; margin: 10px 5px; display: inline-block; }
        .btn-primary { background: #667eea; color: white; }
        .btn-success { background: #48bb78; color: white; }
        .btn-secondary { background: #718096; color: white; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Ürün Detayı</h1>
            <a href="index.php" class="btn btn-secondary">Ana Sayfa</a>
            <a href="cart.php" class="btn btn-primary">Sepetim</a>
        </div>
        
        <div class="product-detail">
            <div>';

if (!empty($images)) {
    $content .= '<img src="uploads/' . htmlspecialchars($images[0]) . '" class="main-image">';
} else {
    $content .= '<img src="uploads/placeholder.jpg" class="main-image">';
}

$content .= '</div>
            <div>
                <h1>' . htmlspecialchars($product["name"]) . '</h1>
                <p style="font-size: 24px; color: #667eea; font-weight: bold;">' . number_format($product["price"], 2) . ' ₺</p>
                <p>Stok: ' . $product["stock"] . '</p>';

if ($product["description"]) {
    $content .= '<div style="background: #f7fafc; padding: 20px; border-radius: 10px; margin: 20px 0;">
                    <h3>Açıklama</h3>
                    <p>' . nl2br(htmlspecialchars($product["description"])) . '</p>
                </div>';
}

if ($product["stock"] > 0) {
    $content .= '<form method="POST" action="add_to_cart.php">
                    <input type="hidden" name="product_id" value="' . $product["id"] . '">
                    <input type="hidden" name="quantity" value="1">
                    <button type="submit" class="btn btn-success">Sepete Ekle</button>
                </form>';
} else {
    $content .= '<button class="btn" style="background: #ccc;" disabled>Stokta Yok</button>';
}

$content .= '</div>
        </div>
    </div>
</body>
</html>';

file_put_contents("/var/www/html/eticaret/product_detail.php", $content);
echo "Product detail sayfası oluşturuldu!";
?>
