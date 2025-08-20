<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/../brisas_secure_configs/main_config.php';
require_once APP_ROOT . '/app/helpers/Database.php';
require_once APP_ROOT . '/app/models/Order.php';

$orderModel = new Order();
$pageTitle = 'Dashboard';
$headerTitle = 'Pedidos';

// Lógica de Acciones (Completar, Archivar)
if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $id = $_GET['id'];
    $redirectUrl = 'index.php?' . ($_SERVER['QUERY_STRING'] ?? '');

    if ($action === 'complete') {
        log_event("Completó el pedido ID: " . $id);
        $orderModel->updateStatus($id, 'completed');
    } elseif ($action === 'archive') {
        log_event("Archivó el pedido ID: " . $id);
        $orderModel->updateStatus($id, 'archived');
    }
    header('Location: ' . $redirectUrl);
    exit;
}

// Lógica de Búsqueda y Filtros
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $searchTerm = $_GET['search'];
    $orders = $orderModel->searchOrders($searchTerm);
    $headerTitle = "Resultados para '" . htmlspecialchars($searchTerm) . "'";
} elseif (isset($_GET['filter'])) {
    $filters = [];
    if ($_GET['filter'] === 'pending' || $_GET['filter'] === 'completed' || $_GET['filter'] === 'archived') {
        $filters['status'] = $_GET['filter'];
    } else {
        $filters['customer_type'] = $_GET['filter'];
    }
    $orders = $orderModel->getOrdersBy($filters);
    $headerTitle = "Pedidos Filtrados: " . htmlspecialchars(ucfirst($_GET['filter']));
} else {
    $selectedDate = $_GET['date'] ?? date('Y-m-d');
    $orders = $orderModel->getOrdersByDate($selectedDate);
    $headerTitle = 'Pedidos para ' . date("d/m/Y", strtotime($selectedDate));
}

include APP_ROOT . '/app/views/admin/layout/header.php';
?>

<div class="container-fluid">
    <!-- Buscador -->
    <div class="card mb-4">
        <div class="card-body">
            <form action="index.php" method="GET" class="row g-3 align-items-center">
                <div class="col-md-8">
                    <input type="text" class="form-control form-control-lg" name="search" placeholder="Buscar por nombre, ciudad, cédula...">
                </div>
                <div class="col-md-4 d-grid">
                    <button type="submit" class="btn btn-primary btn-lg">Buscar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Filtros -->
    <div class="mb-4">
        <strong>Filtros rápidos:</strong>
        <a href="index.php?filter=pending" class="btn btn-outline-secondary btn-sm">Pendientes</a>
        <a href="index.php?filter=completed" class="btn btn-outline-secondary btn-sm">Completados</a>
        <a href="index.php?filter=archived" class="btn btn-outline-secondary btn-sm">Archivados</a>
        <a href="index.php?filter=Distribuidor o Salsamentaria" class="btn btn-outline-secondary btn-sm">Distribuidores</a>
        <a href="index.php?filter=Mercaderista" class="btn btn-outline-secondary btn-sm">Mercaderistas</a>
        <a href="index.php" class="btn btn-link btn-sm">Limpiar filtros</a>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3"><?= $headerTitle ?></h1>
        <div class="d-flex align-items-center">
            <div class="btn-group me-2">
                <a href="export.php?format=xlsx&<?= http_build_query($_GET) ?>" class="btn btn-sm btn-outline-success">Exportar a XLSX</a>
                <a href="export.php?format=pdf&<?= http_build_query($_GET) ?>" class="btn btn-sm btn-outline-danger">Exportar a PDF</a>
            </div>
            <a href="new_order.php" class="btn btn-primary">Crear Pedido Manual</a>
        </div>
    </div>

    <!-- Buscador y Filtros -->
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped align-middle">
                    <thead>
                        <tr>
                            <th>Cliente</th><th>Tipo</th><th>Ciudad</th><th>Estado</th><th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($orders)): ?>
                            <tr><td colspan="5" class="text-center py-4">No se encontraron pedidos.</td></tr>
                        <?php else: ?>
                            <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td><?= htmlspecialchars($order['customer_name']) ?></td>
                                    <td><?= htmlspecialchars($order['customer_type']) ?></td>
                                    <td><?= htmlspecialchars($order['customer_city']) ?></td>
                                    <td>
                                        <?php 
                                            $status_classes = ['pending' => 'bg-warning', 'completed' => 'bg-success', 'archived' => 'bg-secondary'];
                                            $status_class = $status_classes[$order['status']] ?? 'bg-light text-dark';
                                        ?>
                                        <span class="badge <?= $status_class ?>"><?= ucfirst($order['status']) ?></span>
                                    </td>
                                    <td class="text-end">
                                        <button class="btn btn-sm btn-info view-details-btn" data-id="<?= $order['id'] ?>">Ver Detalles</button>
                                        <?php if ($order['status'] === 'pending'): ?>
                                            <a href="?action=complete&id=<?= $order['id'] ?>&<?= http_build_query(array_merge($_GET, ['action'=>null, 'id'=>null])) ?>" class="btn btn-sm btn-success">Completar</a>
                                        <?php elseif ($order['status'] === 'completed'): ?>
                                            <a href="?action=archive&id=<?= $order['id'] ?>&<?= http_build_query(array_merge($_GET, ['action'=>null, 'id'=>null])) ?>" class="btn btn-sm btn-dark">Archivar</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Detalles del Pedido -->
<div class="modal fade" id="order-details-modal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detalles del Pedido</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="order-details-content"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<?php
include APP_ROOT . '/app/views/admin/layout/footer.php';
?>

