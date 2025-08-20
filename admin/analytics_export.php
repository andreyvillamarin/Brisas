<?php
if (session_status() == PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') { die('Acceso denegado.'); }

require_once __DIR__ . '/../config_loader.php';
require_once APP_ROOT . '/app/helpers/Database.php';
require_once APP_ROOT . '/app/models/Analytics.php';
require_once APP_ROOT . '/app/libs/fpdf/fpdf.php';

$analyticsModel = new Analytics();
$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate = $_GET['end_date'] ?? date('Y-m-t');

// Obtener datos
$topProducts = $analyticsModel->getTopProducts($startDate, $endDate);
$topCustomers = $analyticsModel->getTopCustomers($startDate, $endDate);
$ordersByCategory = $analyticsModel->getOrdersByCategory($startDate, $endDate);

// Crear PDF
$pdf = new FPDF('P', 'mm', 'A4');
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 16);
$pdf->Cell(0, 10, 'Reporte de Analitica', 0, 1, 'C');
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(0, 10, 'Periodo: ' . date('d/m/Y', strtotime($startDate)) . ' - ' . date('d/m/Y', strtotime($endDate)), 0, 1, 'C');
$pdf->Ln(10);

function create_table($pdf, $title, $headers, $data, $columns) {
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 10, $title, 0, 1);
    $pdf->SetFillColor(230,230,230);
    $w = array_fill(0, count($headers), 190/count($headers));
    for($i=0; $i<count($headers); $i++) {
        $pdf->Cell($w[$i], 7, $headers[$i], 1, 0, 'C', true);
    }
    $pdf->Ln();
    $pdf->SetFont('Arial', '', 10);
    foreach($data as $row) {
        for($i=0; $i<count($columns); $i++) {
            $pdf->Cell($w[$i], 6, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $row[$columns[$i]]), 1);
        }
        $pdf->Ln();
    }
    $pdf->Ln(10);
}

create_table($pdf, 'Top 10 Productos Mas Vendidos', ['Producto', 'Unidades'], $topProducts, ['name', 'total_sold']);
create_table($pdf, 'Top 10 Clientes con Mas Pedidos', ['Cliente', 'Pedidos'], $topCustomers, ['customer_name', 'total_orders']);
create_table($pdf, 'Pedidos por Categoria', ['Categoria', 'Unidades'], $ordersByCategory, ['name', 'total_quantity']);

$pdf->Output('D', 'reporte_analitica_' . date('Y-m-d') . '.pdf');
