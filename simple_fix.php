<?php
// Index.php dosyasını düzelt
$content = '<?php
session_start();
include "config.php";

if (!isset($_SESSION["student_id"])) {
    header("Location: student_login.php");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
$stmt->execute([$_SESSION["student_id"]]);
$student = $stmt->fetch();

$stmt = $pdo->query("SELECT * FROM products ORDER BY created_at DESC");
$products = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Ana Sayfa</title>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial; background: #f5f6fa; padding: 20px; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 15px; margin-bottom: 30px; }
        .products-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; }
        .product-card { background: white; border-radius: 15px; padding: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
        .product-image { width: 100%; height: 200px; object-fit: cover; border-radius: 10px; margin-bottom: 15px; }
        .btn { padding: 10px 20px; border: none; border-radius: 25px; text-decoration: none; font-weight: bold; margin: 5px; display: inline-block; }
        .btn-primary { background: #667eea; color: white; }
        .btn-success { background: #48bb78; color: white; }
        .btn-secondary { background: #718096; color: white; }
    </style>
</head>
<body>
    <div class="header">
        <h1>E-Ticaret Mağazası</h1>
        <p>Hoş geldin, " . htmlspecialchars($student["full_name"]) . "! Sınıf: " . htmlspecialchars($student["class"]) . "</p>
        <a href=\"cart.php\" class=\"btn btn-primary\">🛒 Sepetim</a>
        <a href=\"student_login.php\" class=\"btn btn-secondary\">Çıkış</a>
    </div>
    
    <div class=\"products-grid\">";

foreach ($products as $product) {
    $images = json_decode($product["images"], true) ?: [];
    $first_image = !empty($images) ? $images[0] : "placeholder.jpg";
    
    $content .= "<div class=\"product-card\">
                    <img src=\"uploads/" . htmlspecialchars($first_image) . "\" class=\"product-image\">
                    <h3>" . htmlspecialchars($product["name"]) . "</h3>
                    <p><strong>" . number_format($product["price"], 2) . " ₺</strong></p>
                    <p>Stok: " . $product["stock"] . "</p>
                    
                    <a href=\"product_detail.php?id=" . $product["id"] . "\" class=\"btn btn-primary\">👁️ Detay</a>";
    
    if ($product["stock"] > 0) {
        $content .= "<form method=\"POST\" action=\"add_to_cart.php\" style=\"display: inline;\">
                        <input type=\"hidden\" name=\"product_id\" value=\"" . $product["id"] . "\">
                        <input type=\"hidden\" name=\"quantity\" value=\"1\">
                        <button type=\"submit\" class=\"btn btn-success\">🛒 Sepete Ekle</button>
                    </form>";
    } else {
        $content .= "<button class=\"btn\" style=\"background: #ccc;\" disabled>Stokta Yok</button>";
    }
    
    $content .= "</div>";
}

$content .= "</div>
</body>
</html>";

file_put_contents("/var/www/html/eticaret/index.php", $content);
echo "Index.php düzeltildi!";
?>
