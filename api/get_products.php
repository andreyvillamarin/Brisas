<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../config_loader.php';
require_once APP_ROOT . '/app/helpers/Database.php';
require_once APP_ROOT . '/app/models/Product.php';

if (!isset($_GET['category_id'])) {
    echo json_encode([]);
    exit;
}

$categoryId = (int)$_GET['category_id'];
$productModel = new Product();

try {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("SELECT id, name, image_url FROM products WHERE category_id = :category_id ORDER BY name ASC");
    $stmt->execute(['category_id' => $categoryId]);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($products);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
