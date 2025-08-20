<?php
if (session_status() == PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) { header('Location: ../login.php'); exit; }

require_once $_SERVER['DOCUMENT_ROOT'] . '/../brisas_secure_configs/main_config.php';
require_once APP_ROOT . '/app/helpers/Database.php';
require_once APP_ROOT . '/app/models/Product.php';
require_once APP_ROOT . '/app/models/Order.php';

$productModel = new Product();
$allProducts = $productModel->getAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $orderModel = new Order();
    $orderId = $orderModel->createOrder($_POST);
    if ($orderId) {
        header('Location: index.php?date=' . date('Y-m-d'));
        exit;
    }
    $error = "Hubo un error al crear el pedido.";
}

$pageTitle = 'Crear Pedido Manual';
include APP_ROOT . '/app/views/admin/layout/header.php';
?>
<div class="container-fluid">
    <h1 class="h3 mb-4">Crear Nuevo Pedido Manualmente</h1>
    <?php if (isset($error)): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>

    <form action="new_order.php" method="POST">
        <div class="card">
            <div class="card-header">Datos del Cliente</div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Tipo de cliente</label>
                        <select class="form-select" name="customer_type" required>
                            <option value="Distribuidor o Salsamentaria">Distribuidor o Salsamentaria</option>
                            <option value="Mercaderista">Mercaderista</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3"><label class="form-label">Nombre</label><input type="text" name="customer_name" class="form-control" required></div>
                    <div class="col-md-6 mb-3"><label class="form-label">Cédula/NIT</label><input type="text" name="customer_id_number" class="form-control"></div>
                    <div class="col-md-6 mb-3"><label class="form-label">Ciudad</label><input type="text" name="customer_city" class="form-control" required></div>
                    <div class="col-md-6 mb-3"><label class="form-label">Email</label><input type="email" name="customer_email" class="form-control"></div>
                    <div class="col-md-6 mb-3"><label class="form-label">Supermercado (si es Mercaderista)</label><input type="text" name="mercaderista_supermarket" class="form-control"></div>
                </div>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-header">Productos del Pedido</div>
            <div class="card-body">
                <div id="product-rows-container">
                    <!-- Las filas de productos se añadirán aquí -->
                </div>
                <button type="button" id="add-product-row-btn" class="btn btn-secondary mt-2">Añadir Producto</button>
            </div>
        </div>

        <div class="mt-4">
            <a href="index.php" class="btn btn-link">Cancelar</a>
            <button type="submit" class="btn btn-primary btn-lg">Guardar Pedido</button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('product-rows-container');
    const addBtn = document.getElementById('add-product-row-btn');
    const productsJson = <?= json_encode($allProducts) ?>;
    let rowIndex = 0;

    function addProductRow() {
        const newRow = document.createElement('div');
        newRow.classList.add('row', 'align-items-end', 'mb-2');
        
        let optionsHtml = '<option value="">Selecciona un producto...</option>';
        productsJson.forEach(p => {
            optionsHtml += `<option value="${p.id}">${p.name}</option>`;
        });

        newRow.innerHTML = `
            <div class="col-md-7">
                <label class="form-label">Producto</label>
                <select class="form-select" name="products[${rowIndex}][id]">${optionsHtml}</select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Cantidad</label>
                <input type="number" class="form-control" name="products[${rowIndex}][quantity]" min="1">
            </div>
            <div class="col-md-2">
                <button type="button" class="btn btn-danger remove-row-btn">Eliminar</button>
            </div>
        `;
        container.appendChild(newRow);
        rowIndex++;
    }

    addBtn.addEventListener('click', addProductRow);

    container.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-row-btn')) {
            e.target.closest('.row').remove();
        }
    });

    // Añadir una fila al inicio
    addProductRow();
});
</script>

<?php
include APP_ROOT . '/app/views/admin/layout/footer.php';
?>
