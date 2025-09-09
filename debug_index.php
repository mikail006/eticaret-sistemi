<?php
session_start();
include 'config.php';

if (!isset($_SESSION['student_id'])) {
    header('Location: student_login.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
$stmt->execute([$_SESSION['student_id']]);
$student = $stmt->fetch();

echo "<h2>Debug Bilgileri:</h2>";
echo "Öğrenci ID: " . $_SESSION['student_id'] . "<br>";
echo "Öğrenci Sınıf: '" . $student['class'] . "'<br>";
echo "Aranacak JSON: '\"" . $student['class'] . "\"'<br><br>";

// SQL sorguyu debug et
$student_class = $student['class'];
$sql = "SELECT * FROM products WHERE JSON_CONTAINS(classes, ?)";
echo "SQL: " . $sql . "<br>";
echo "Parameter: '\"" . $student_class . "\"'<br><br>";

$stmt = $pdo->prepare($sql);
$stmt->execute(['"' . $student_class . '"']);
$products = $stmt->fetchAll();

echo "Bulunan ürün sayısı: " . count($products) . "<br><br>";

if (count($products) > 0) {
    echo "<h3>Bulunan Ürünler:</h3>";
    foreach ($products as $product) {
        echo "ID: " . $product['id'] . " - " . $product['name'] . " - Classes: " . $product['classes'] . "<br>";
    }
} else {
    echo "<h3>Hiç ürün bulunamadı!</h3>";
    
    // Tüm ürünleri de göster
    $all_products = $pdo->query("SELECT id, name, classes FROM products")->fetchAll();
    echo "<h4>Tüm ürünler:</h4>";
    foreach ($all_products as $product) {
        echo "ID: " . $product['id'] . " - " . $product['name'] . " - Classes: " . $product['classes'] . "<br>";
    }
}
?>
