<?php
if (session_status() == PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) { header('Location: ../login.php'); exit; }

require_once __DIR__ . '/../config_loader.php';
require_once APP_ROOT . '/app/helpers/Database.php';
require_once APP_ROOT . '/app/models/Product.php';
require_once APP_ROOT . '/app/models/Order.php';
require_once APP_ROOT . '/app/models/Setting.php';
require_once APP_ROOT . '/app/models/Promotion.php'; // Incluir Promotion model

$productModel = new Product();
$promotionModel = new Promotion();

// Obtener todos los productos y añadirles info por defecto
$allProducts = array_map(function($p) {
    $p['is_promo'] = false;
    $p['min'] = 1;
    $p['step'] = 1;
    return $p;
}, $productModel->getAll());

// Obtener promociones activas y formatearlas
$activePromos = array_map(function($p) {
    return [
        'id' => $p['product_id'],
        'name' => $p['product_name'] . ' (Promo: ' . $p['promo_description'] . ')',
        'is_promo' => true,
        'min' => (int)$p['min_quantity'] > 0 ? (int)$p['min_quantity'] : 1,
        'step' => (int)$p['min_quantity'] > 0 ? (int)$p['min_quantity'] : 1
    ];
}, $promotionModel->getActive());

// Combinar la lista de productos y promociones
$selectableProducts = array_merge($allProducts, $activePromos);

// Ordenar por nombre
usort($selectableProducts, function($a, $b) {
    return strcmp($a['name'], $b['name']);
});


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

$settingModelForHeader = new Setting();
$settingsForHeader = $settingModelForHeader->getAllAsAssoc();
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
    const productsData = <?= json_encode($selectableProducts) ?>;
    const productsMap = new Map(productsData.map(p => [`${p.id}-${p.is_promo}`, p]));
    let rowIndex = 0;

    function addProductRow() {
        const newRow = document.createElement('div');
        newRow.classList.add('row', 'align-items-end', 'mb-2');
        newRow.dataset.rowIndex = rowIndex;
        
        let optionsHtml = '<option value="">Selecciona un producto...</option>';
        productsData.forEach(p => {
            const optionValue = `${p.id}-${p.is_promo}`;
            optionsHtml += `<option value="${optionValue}">${p.name}</option>`;
        });

        newRow.innerHTML = `
            <div class="col-md-6">
                <label class="form-label">Producto</label>
                <input type="hidden" name="products[${rowIndex}][id]" class="product-id-input">
                <select class="form-select product-select">${optionsHtml}</select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Cantidad</label>
                <input type="number" class="form-control quantity-input" name="products[${rowIndex}][quantity]" min="1" step="1" required>
            </div>
            <div class="col-md-2">
                <label class="form-label">Precio</label>
                <input type="number" class="form-control price-input" name="products[${rowIndex}][price]" min="0" step="any" placeholder="Auto">
            </div>
            <div class="col-md-2">
                <button type="button" class="btn btn-danger remove-row-btn" style="margin-top: 30px;">Eliminar</button>
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

    container.addEventListener('change', function(e) {
        if (e.target.classList.contains('product-select')) {
            const selectedValue = e.target.value;
            const product = productsMap.get(selectedValue);
            const row = e.target.closest('.row');
            const quantityInput = row.querySelector('.quantity-input');
            const idInput = row.querySelector('.product-id-input');

            if (product) {
                quantityInput.min = product.min;
                quantityInput.step = product.step;
                quantityInput.value = product.min;
                idInput.value = product.id; // Set the actual product ID
            } else {
                quantityInput.min = 1;
                quantityInput.step = 1;
                quantityInput.value = '';
                idInput.value = '';
            }
        }
    });

    // Initial row
    addProductRow();
});
</script>

<?php
include APP_ROOT . '/app/views/admin/layout/footer.php';
?>
