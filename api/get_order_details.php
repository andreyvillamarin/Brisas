<?php
header('Content-Type: application/json');

require_once $_SERVER['DOCUMENT_ROOT'] . '/../brisas_secure_configs/main_config.php';
require_once APP_ROOT . '/app/helpers/Database.php';
require_once APP_ROOT . '/app/models/Order.php';

if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'No se proporcionó ID de pedido']);
    exit;
}

$orderId = (int)$_GET['id'];
$orderModel = new Order();
$orderData = $orderModel->getOrderWithItems($orderId);

if (!$orderData) {
    http_response_code(404);
    echo json_encode(['error' => 'Pedido no encontrado']);
    exit;
}

echo json_encode($orderData);
?>