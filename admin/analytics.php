<?php
if (session_status() == PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') { die('Acceso denegado.'); }

require_once __DIR__ . '/../config_loader.php';
require_once APP_ROOT . '/app/helpers/Database.php';
require_once APP_ROOT . '/app/models/Analytics.php';
require_once APP_ROOT . '/app/models/Setting.php';

// --- Local function for debugging ---
function getOrdersPerDayOfMonth_local($month, $db) {
    $year = date('Y', strtotime($month));
    $month_num = date('m', strtotime($month));
    $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month_num, $year);

    $result = [];
    for ($day = 1; $day <= $daysInMonth; $day++) {
        $result[$day] = [
            'order_day' => $year . '-' . $month_num . '-' . str_pad($day, 2, '0', STR_PAD_LEFT),
            'total_orders' => 0
        ];
    }

    $sql = "SELECT DAY(created_at) as day, COUNT(id) as total_orders
            FROM orders
            WHERE YEAR(created_at) = :year AND MONTH(created_at) = :month_num
            GROUP BY day
            ORDER BY day ASC";
    
    try {
        $stmt = $db->prepare($sql);
        $stmt->execute([':year' => $year, ':month_num' => $month_num]);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Analytics error (local function): " . $e->getMessage());
        $data = [];
    }

    foreach ($data as $row) {
        $day = (int)$row['day'];
        $result[$day]['total_orders'] = (int)$row['total_orders'];
    }

    return array_values($result);
}
// --- End of local function ---

$analyticsModel = new Analytics();
$db_connection_for_local_function = Database::getInstance()->getConnection();

$selectedMonth = $_GET['month'] ?? date('Y-m');
$startDate = $selectedMonth . '-01';
$endDate = date('Y-m-t', strtotime($startDate));

// Obtener todos los datos para los reportes
$topProducts = $analyticsModel->getTopProducts($startDate, $endDate);
$lessSoldProducts = $analyticsModel->getTopProducts($startDate, $endDate, 10, 'ASC');
$topCustomers = $analyticsModel->getTopCustomers($startDate, $endDate);
$ordersByDay = getOrdersPerDayOfMonth_local($selectedMonth, $db_connection_for_local_function);
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
                <div class="col-md-5"><label class="form-label">Seleccionar Mes</label><input type="month" name="month" value="<?= $selectedMonth ?>" class="form-control"></div>
                <div class="col-md-2 d-grid"><label class="form-label">&nbsp;</label><button type="submit" class="btn btn-primary">Filtrar</button></div>
                <div class="col-md-2 d-grid"><label class="form-label">&nbsp;</label><a href="analytics_export.php?month=<?= $selectedMonth ?>" class="btn btn-outline-danger">Exportar a PDF</a></div>
            </form>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-header">Top 10 Clientes</div>
                <div class="card-body">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Cliente</th>
                                <th class="text-end">Total Pedidos</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($topCustomers as $customer): ?>
                            <tr>
                                <td><?= htmlspecialchars($customer['customer_name']) ?></td>
                                <td class="text-end"><?= $customer['total_orders'] ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-6 mb-4"><div class="card h-100"><div class="card-body"><canvas id="topProductsChart"></canvas></div></div></div>
    </div>
    <div class="row">
        <div class="col-lg-4 mb-4"><div class="card h-100"><div class="card-body"><canvas id="ordersByCategoryChart"></canvas></div></div></div>
        <div class="col-lg-8 mb-4"><div class="card h-100"><div class="card-body"><canvas id="ordersByDayChart"></canvas></div></div></div>
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
    type: 'bar',
    data: {
        labels: <?= json_encode(array_column($ordersByDay, 'order_day')) ?>,
        datasets: [{
            label: 'Total Pedidos',
            data: <?= json_encode(array_column($ordersByDay, 'total_orders')) ?>,
            backgroundColor: '#aa182c'
        }]
    },
    options: { 
        plugins: { 
            title: { 
                display: true, 
                text: 'Pedidos por Día del Mes' 
            } 
        },
        scales: {
            x: {
                ticks: {
                    callback: function(value, index, values) {
                        // Mostrar solo el día del mes
                        return new Date(this.getLabelForValue(value)).getDate();
                    }
                }
            }
        }
    }
});
</script>

<?php
include APP_ROOT . '/app/views/admin/layout/footer.php';
?>