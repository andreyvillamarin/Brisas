<?php
if (session_status() == PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) exit('Acceso denegado');

require_once __DIR__ . '/../config_loader.php';
require_once APP_ROOT . '/app/helpers/Database.php';
require_once APP_ROOT . '/app/models/Order.php';
require_once APP_ROOT . '/app/libs/SimpleXLSXGen.php';

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
        $data = [];
        // Header row
        $header = [
            'ID Pedido', 'Fecha', 'Cliente', 'Tipo Cliente', 'Ciudad', 'Cédula/NIT', 
            'Email', 'Supermercado', 'Estado', 'Producto', 'Cantidad'
        ];
        $data[] = $header;

        // Data rows
        if (!empty($items)) {
            foreach ($items as $item) {
                $data[] = [
                    $order['id'],
                    $order['created_at'],
                    $order['customer_name'],
                    $order['customer_type'],
                    $order['customer_city'],
                    $order['customer_id_number'],
                    $order['customer_email'] ?? '',
                    $order['mercaderista_supermarket'] ?? '',
                    $order['status'],
                    ($item['name'] . (!empty($item['promotion_text']) ? ' (' . $item['promotion_text'] . ')' : '')),
                    $item['quantity']
                ];
            }
        } else {
            // If there are no items, show the order details anyway
            $data[] = [
                $order['id'],
                $order['created_at'],
                $order['customer_name'],
                $order['customer_type'],
                $order['customer_city'],
                $order['customer_id_number'],
                $order['customer_email'] ?? '',
                $order['mercaderista_supermarket'] ?? '',
                $order['status'],
                'Sin productos', // No product
                ''  // No quantity
            ];
        }

        \Shuchkin\SimpleXLSXGen::fromArray($data)->downloadAs($filename);
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
            $productName = $item['name'] . (!empty($item['promotion_text']) ? ' (' . $item['promotion_text'] . ')' : '');
            $pdf->Cell(130, 6, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $productName), 1);
            $pdf->Cell(40, 6, $item['quantity'], 1, 1, 'C');
        }

        $pdf->Output('D', $filename);
        exit;
    }
}

// --- Lógica para exportar listas completas con detalles ---

$orders = [];
$selectedDate = $_GET['date'] ?? date('Y-m-d');

// Decidir qué método usar basado en los parámetros GET
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $orders = $orderModel->searchOrders($_GET['search']);
    // Nota: La búsqueda no trae detalles de items.
} elseif (isset($_GET['filter'])) {
    $filters = [];
    if ($_GET['filter'] === 'pending' || $_GET['filter'] === 'completed' || $_GET['filter'] === 'archived') {
        $filters['status'] = $_GET['filter'];
    } else {
        $filters['customer_type'] = $_GET['filter'];
    }
    $orders = $orderModel->getOrdersBy($filters);
    // Nota: El filtro no trae detalles de items.
} else {
    $orders = $orderModel->getOrdersByDateWithDetails($selectedDate);
}


$filename = "pedidos_detallado_" . date('Y-m-d', strtotime($selectedDate)) . "." . $format;

if ($format === 'xlsx') {
    $data = [];

    // Add a header to the document
    $reportDate = date('d/m/Y', strtotime($selectedDate));
    $data[] = ['Reporte detallado (' . $reportDate . ')'];
    $data[] = []; // Add an empty row for spacing
    
    // Header row for the table
    $header = [
        'ID Pedido', 'Fecha', 'Cliente', 'Tipo Cliente', 'Ciudad', 'Cédula/NIT', 
        'Email', 'Supermercado', 'Estado', 'Producto', 'Cantidad'
    ];
    $data[] = $header;

    // Data rows
    foreach ($orders as $order) {
        if (!empty($order['items'])) {
            $isFirstItem = true;
            foreach ($order['items'] as $item) {
                if ($isFirstItem) {
                    $data[] = [
                        $order['id'],
                        $order['created_at'],
                        $order['customer_name'],
                        $order['customer_type'],
                        $order['customer_city'],
                        $order['customer_id_number'],
                        $order['customer_email'] ?? '',
                        $order['mercaderista_supermarket'] ?? '',
                        $order['status'],
                        ($item['name'] . (!empty($item['promotion_text']) ? ' (' . $item['promotion_text'] . ')' : '')),
                        $item['quantity']
                    ];
                    $isFirstItem = false;
                } else {
                    $data[] = [
                        '', '', '', '', '', '', '', '', '',
                        ($item['name'] . (!empty($item['promotion_text']) ? ' (' . $item['promotion_text'] . ')' : '')),
                        $item['quantity']
                    ];
                }
            }
        } else {
            // If there are no items, show the order details anyway
            $data[] = [
                $order['id'],
                $order['created_at'],
                $order['customer_name'],
                $order['customer_type'],
                $order['customer_city'],
                $order['customer_id_number'],
                $order['customer_email'] ?? '',
                $order['mercaderista_supermarket'] ?? '',
                $order['status'],
                'Sin productos', // No product
                ''  // No quantity
            ];
        }
    }

    \Shuchkin\SimpleXLSXGen::fromArray($data)->downloadAs($filename);
    exit;
}

if ($format === 'pdf') {
    require_once APP_ROOT . '/app/libs/fpdf/fpdf.php';
    $pdf = new FPDF('P', 'mm', 'A4');
    $pdf->AddPage();
    $pdf->SetFont('Arial', 'B', 16);
    $pdf->Cell(0, 10, 'Reporte Detallado - ' . date('d/m/Y', strtotime($selectedDate)), 0, 1, 'C');
    $pdf->Ln(5);

    foreach ($orders as $order) {
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(0, 7, 'Pedido #' . $order['id'], 0, 1);
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(40, 6, 'Fecha:', 0);
        $pdf->Cell(0, 6, date('Y-m-d H:i', strtotime($order['created_at'])), 0, 1);
        $pdf->Cell(40, 6, 'Cliente:', 0);
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
        $pdf->Cell(40, 6, 'Estado:', 0);
        $pdf->Cell(0, 6, $order['status'], 0, 1);
        $pdf->Ln(2);

        // Items del pedido
        if (!empty($order['items'])) {
            $pdf->SetFont('Arial', 'B', 10);
            $pdf->Cell(150, 7, 'Producto', 1, 0, 'C');
            $pdf->Cell(30, 7, 'Cantidad', 1, 1, 'C');
            $pdf->SetFont('Arial', '', 10);
            foreach ($order['items'] as $item) {
                $productName = $item['name'] . (!empty($item['promotion_text']) ? ' (' . $item['promotion_text'] . ')' : '');
                $x = $pdf->GetX();
                $y = $pdf->GetY();
                $pdf->MultiCell(150, 6, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $productName), 1);
                $newY = $pdf->GetY();
                $height = $newY - $y;
                $pdf->SetXY($x + 150, $y);
                $pdf->Cell(30, $height, $item['quantity'], 1, 1, 'C');
            }
        } else {
            if(isset($_GET['search']) || isset($_GET['filter'])) {
                $pdf->Cell(0, 6, iconv('UTF-8', 'ISO-8859-1//TRANSLIT','Detalles de productos no incluidos en esta vista.'), 1, 1);
            } else {
                $pdf->Cell(0, 6, 'Este pedido no tiene productos.', 1, 1);
            }
        }
        $pdf->Ln(7); // Espacio entre pedidos
    }

    $pdf->Output('D', $filename);
    exit;
}
?>