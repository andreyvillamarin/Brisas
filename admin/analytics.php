<?php
if (session_status() == PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') { die('Acceso denegado.'); }

require_once __DIR__ . '/../config_loader.php';
require_once APP_ROOT . '/app/helpers/Database.php';
require_once APP_ROOT . '/app/models/Analytics.php';
require_once APP_ROOT . '/app/models/Setting.php';

$analyticsModel = new Analytics();

$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate = $_GET['end_date'] ?? date('Y-m-t');

// Obtener todos los datos para los reportes
$topProducts = $analyticsModel->getTopProducts($startDate, $endDate);
$lessSoldProducts = $analyticsModel->getTopProducts($startDate, $endDate, 10, 'ASC');
$topCustomers = $analyticsModel->getTopCustomers($startDate, $endDate);
$ordersByDay = $analyticsModel->getOrdersByDay($startDate, $endDate);
$ordersByCategory = $analyticsModel->getOrdersByCategory($startDate, $endDate);

$pageTitle = 'Analítica de Ventas';

$settingModelForHeader = new Setting();
$settingsForHeader = $settingModelForHeader->getAllAsAssoc();
include APP_ROOT . '/app/views/admin/layout/header.php';
?>
<!-- Chart.js desde CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="container-fluid">
    <h1 class="h3 mb-4">Analítica</h1>

    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-center">
                <div class="col-md-5"><label class="form-label">Fecha de Inicio</label><input type="date" name="start_date" value="<?= $startDate ?>" class="form-control"></div>
                <div class="col-md-5"><label class="form-label">Fecha de Fin</label><input type="date" name="end_date" value="<?= $endDate ?>" class="form-control"></div>
                <div class="col-md-2 d-grid"><label class="form-label">&nbsp;</label><button type="submit" class="btn btn-primary">Filtrar</button></div>
                <div class="col-md-2 d-grid"><label class="form-label">&nbsp;</label><a href="analytics_export.php?start_date=<?= $startDate ?>&end_date=<?= $endDate ?>" class="btn btn-outline-danger">Exportar a PDF</a></div>
            </form>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6 mb-4"><div class="card h-100"><div class="card-body"><canvas id="topProductsChart"></canvas></div></div></div>
        <div class="col-lg-6 mb-4"><div class="card h-100"><div class="card-body"><canvas id="ordersByCategoryChart"></canvas></div></div></div>
        <div class="col-lg-12 mb-4"><div class="card h-100"><div class="card-body"><canvas id="ordersByDayChart"></canvas></div></div></div>
    </div>

</div>

<script>
const chartColors = ['#aa182c', '#212529', '#6c757d', '#adb5bd', '#dee2e6', '#fd7e14', '#ffc107', '#20c997', '#0dcaf0', '#6610f2'];

// Gráfico de Top Productos
new Chart(document.getElementById('topProductsChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_column($topProducts, 'name')) ?>,
        datasets: [{
            label: 'Unidades Vendidas',
            data: <?= json_encode(array_column($topProducts, 'total_sold')) ?>,
            backgroundColor: chartColors
        }]
    },
    options: { plugins: { title: { display: true, text: 'Top 10 Productos Más Vendidos' } } }
});

// Gráfico de Pedidos por Categoría
new Chart(document.getElementById('ordersByCategoryChart'), {
    type: 'pie',
    data: {
        labels: <?= json_encode(array_column($ordersByCategory, 'name')) ?>,
        datasets: [{
            data: <?= json_encode(array_column($ordersByCategory, 'total_quantity')) ?>,
            backgroundColor: chartColors
        }]
    },
    options: { plugins: { title: { display: true, text: 'Pedidos por Categoría' } } }
});

// Gráfico de Pedidos por Día
new Chart(document.getElementById('ordersByDayChart'), {
    type: 'line',
    data: {
        labels: <?= json_encode(array_column($ordersByDay, 'order_day')) ?>,
        datasets: [{
            label: 'Total Pedidos',
            data: <?= json_encode(array_column($ordersByDay, 'total_orders')) ?>,
            borderColor: '#aa182c',
            tension: 0.1
        }]
    },
    options: { plugins: { title: { display: true, text: 'Pedidos por Día' } } }
});
</script>

<?php
include APP_ROOT . '/app/views/admin/layout/footer.php';
?>