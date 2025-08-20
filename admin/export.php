<?php
if (session_status() == PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) exit('Acceso denegado');

require_once __DIR__ . '/../config_loader.php';
require_once APP_ROOT . '/app/helpers/Database.php';
require_once APP_ROOT . '/app/models/Order.php';

$orderModel = new Order();
$format = $_GET['format'] ?? 'xlsx';
$orderId = $_GET['id'] ?? null;

// Si se proporciona un ID, se exporta un solo pedido
if ($orderId) {
    $orderData = $orderModel->getOrderWithItems($orderId);
    if (!$orderData || !$orderData['details']) {
        exit('Pedido no encontrado.');
    }
    
    $order = $orderData['details'];
    $items = $orderData['items'];
    $filename = "pedido_" . $order['id'] . "_" . date('Y-m-d') . "." . $format;

    if ($format === 'xlsx') {
        require_once APP_ROOT . '/app/libs/SimpleXLSXGen.php';
        $data = [];
        // Datos del cliente y pedido
        $data[] = ['Campo', 'Valor'];
        $data[] = ['ID Pedido', $order['id']];
        $data[] = ['Fecha', $order['created_at']];
        $data[] = ['Cliente', $order['customer_name']];
        $data[] = ['Tipo Cliente', $order['customer_type']];
        $data[] = ['Ciudad', $order['customer_city']];
        $data[] = ['ID Cliente', $order['customer_id_number']];
        $data[] = ['Email', $order['customer_email'] ?? ''];
        $data[] = ['Supermercado', $order['mercaderista_supermarket'] ?? ''];
        $data[] = ['Estado', $order['status']];
        $data[] = ['','']; // Separador
        
        // Cabeceras de productos
        $data[] = ['Producto', 'Cantidad'];
        
        // Items del pedido
        foreach ($items as $item) {
            $data[] = [$item['name'], $item['quantity']];
        }

        \SimpleXLSXGen::fromArray($data)->downloadAs($filename);
        exit;
    }

    if ($format === 'pdf') {
        require_once APP_ROOT . '/app/libs/fpdf/fpdf.php';
        $pdf = new FPDF('P', 'mm', 'A4');
        $pdf->AddPage();
        $pdf->SetFont('Arial', 'B', 16);
        $pdf->Cell(0, 10, 'Detalle del Pedido #' . $order['id'], 0, 1, 'C');
        $pdf->Ln(10);

        // Información del Cliente
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(0, 7, 'Informacion del Cliente', 0, 1);
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(40, 6, 'Nombre:', 0);
        $pdf->Cell(0, 6, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $order['customer_name']), 0, 1);
        $pdf->Cell(40, 6, 'Tipo:', 0);
        $pdf->Cell(0, 6, $order['customer_type'], 0, 1);
        $pdf->Cell(40, 6, 'Ciudad:', 0);
        $pdf->Cell(0, 6, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $order['customer_city']), 0, 1);
        $pdf->Cell(40, 6, 'Cedula/NIT:', 0);
        $pdf->Cell(0, 6, $order['customer_id_number'], 0, 1);
        if (!empty($order['customer_email'])) {
            $pdf->Cell(40, 6, 'Email:', 0);
            $pdf->Cell(0, 6, $order['customer_email'], 0, 1);
        }
        if (!empty($order['mercaderista_supermarket'])) {
            $pdf->Cell(40, 6, 'Supermercado:', 0);
            $pdf->Cell(0, 6, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $order['mercaderista_supermarket']), 0, 1);
        }

        $pdf->Ln(5);

        // Información del Pedido
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(0, 7, 'Informacion del Pedido', 0, 1);
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(40, 6, 'Fecha:', 0);
        $pdf->Cell(0, 6, date('Y-m-d H:i', strtotime($order['created_at'])), 0, 1);
        $pdf->Cell(40, 6, 'Estado:', 0);
        $pdf->Cell(0, 6, $order['status'], 0, 1);

        $pdf->Ln(10);
        
        // Tabla de Productos
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(0, 7, 'Productos', 0, 1);
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(130, 7, 'Producto', 1, 0, 'C');
        $pdf->Cell(40, 7, 'Cantidad', 1, 1, 'C');

        $pdf->SetFont('Arial', '', 10);
        foreach ($items as $item) {
            $pdf->Cell(130, 6, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $item['name']), 1);
            $pdf->Cell(40, 6, $item['quantity'], 1, 1, 'C');
        }

        $pdf->Output('D', $filename);
        exit;
    }
}

// --- Lógica anterior para exportar listas completas ---

$orders = [];
// Reutilizar la lógica de filtrado y búsqueda del dashboard
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $orders = $orderModel->searchOrders($_GET['search']);
} elseif (isset($_GET['filter'])) {
    $filters = [];
    if ($_GET['filter'] === 'pending' || $_GET['filter'] === 'completed' || $_GET['filter'] === 'archived') {
        $filters['status'] = $_GET['filter'];
    } else {
        $filters['customer_type'] = $_GET['filter'];
    }
    $orders = $orderModel->getOrdersBy($filters);
} else {
    $selectedDate = $_GET['date'] ?? date('Y-m-d');
    $orders = $orderModel->getOrdersByDate($selectedDate);
}

$filename = "pedidos_" . date('Y-m-d') . "." . $format;

if ($format === 'xlsx') {
    require_once APP_ROOT . '/app/libs/SimpleXLSXGen.php';
    $data = [];
    $data[] = ['ID', 'Fecha', 'Cliente', 'Tipo', 'Ciudad', 'ID Cliente', 'Email', 'Supermercado', 'Estado'];
    foreach ($orders as $order) {
        $data[] = [
            $order['id'],
            $order['created_at'],
            $order['customer_name'],
            $order['customer_type'],
            $order['customer_city'],
            $order['customer_id_number'],
            $order['customer_email'] ?? '',
            $order['mercaderista_supermarket'] ?? '',
            $order['status']
        ];
    }
    \SimpleXLSXGen::fromArray($data)->downloadAs($filename);
    exit;
}

if ($format === 'pdf') {
    require_once APP_ROOT . '/app/libs/fpdf/fpdf.php';
    $pdf = new FPDF('L', 'mm', 'A4');
    $pdf->AddPage();
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 10, 'Reporte de Pedidos - ' . date('d/m/Y'), 0, 1, 'C');
    $pdf->Ln(5);

    $pdf->SetFont('Arial', 'B', 8);
    $headers = ['ID', 'Fecha', 'Cliente', 'Tipo', 'Ciudad', 'ID Cliente', 'Estado'];
    $widths = [10, 30, 60, 40, 40, 40, 20];
    for($i=0; $i<count($headers); $i++) {
        $pdf->Cell($widths[$i], 7, $headers[$i], 1, 0, 'C');
    }
    $pdf->Ln();

    $pdf->SetFont('Arial', '', 8);
    foreach($orders as $order) {
        $pdf->Cell($widths[0], 6, $order['id'], 1);
        $pdf->Cell($widths[1], 6, date('Y-m-d H:i', strtotime($order['created_at'])), 1);
        $pdf->Cell($widths[2], 6, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $order['customer_name']), 1);
        $pdf->Cell($widths[3], 6, $order['customer_type'], 1);
        $pdf->Cell($widths[4], 6, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $order['customer_city']), 1);
        $pdf->Cell($widths[5], 6, $order['customer_id_number'], 1);
        $pdf->Cell($widths[6], 6, $order['status'], 1);
        $pdf->Ln();
    }

    $pdf->Output('D', $filename);
    exit;
}
?>