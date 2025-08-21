<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../config_loader.php';
require_once APP_ROOT . '/app/helpers/Database.php';
require_once APP_ROOT . '/app/models/Order.php';

if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'No se proporciono ID de pedido']);
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

// Añadir traducción del estado
if (isset($orderData['details']['status'])) {
    $status_translations = [
        'pending' => 'Pendiente',
        'completed' => 'Completado',
        'archived' => 'Archivado'
    ];
    $status = $orderData['details']['status'];
    $orderData['details']['status_translated'] = $status_translations[$status] ?? ucfirst($status);
}


echo json_encode($orderData);
?>