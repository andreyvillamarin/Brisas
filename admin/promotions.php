<?php
if (session_status() == PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) { header('Location: ../login.php'); exit; }

require_once __DIR__ . '/../config_loader.php';
require_once APP_ROOT . '/app/helpers/Database.php';
require_once APP_ROOT . '/app/models/Product.php';
require_once APP_ROOT . '/app/models/Promotion.php';
require_once APP_ROOT . '/app/models/Setting.php';
require_once APP_ROOT . '/app/helpers/log_helper.php';

$promotionModel = new Promotion();
$productModel = new Product();
$action = $_GET['action'] ?? 'list';
$pageTitle = 'Gestion de Promociones';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Si la cantidad está vacía o es 0, se guarda como 0.
    $min_quantity = !empty($_POST['min_quantity']) ? (int)$_POST['min_quantity'] : 0;
    $max_quantity = !empty($_POST['max_quantity']) ? (int)$_POST['max_quantity'] : 0;

    $data = [
        'product_id' => $_POST['product_id'],
        'promo_description' => $_POST['promo_description'],
        'min_quantity' => $min_quantity,
        'max_quantity' => $max_quantity,
        'start_date' => $_POST['start_date'],
        'end_date' => $_POST['end_date']
    ];

    if ($_POST['form_action'] === 'create') {
        $newId = $promotionModel->create($data);
        log_event("Creó la promoción", "promotion", $newId);
    } elseif ($_POST['form_action'] === 'update') {
        $id = $_POST['id'];
        $promotionModel->update($id, $data);
        log_event("Actualizó la promoción", "promotion", $id);
    }
    header('Location: promotions.php');
    exit;
}

if ($action === 'delete' && isset($_GET['id'])) {
    log_event("Eliminó la promoción", "promotion", $_GET['id']);
    $promotionModel->delete($_GET['id']);
    header('Location: promotions.php');
    exit;
}

$settingModelForHeader = new Setting();
$settingsForHeader = $settingModelForHeader->getAllAsAssoc();
include APP_ROOT . '/app/views/admin/layout/header.php';
?>
<div class="container-fluid">
    <?php if ($action === 'list'): ?>
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="h3">Promociones</h1>
            <a href="promotions.php?action=new" class="btn btn-primary">Crear Nueva Promoción</a>
        </div>
        <div class="card">
            <div class="card-body">
                <table class="table table-striped">
                    <thead><tr><th>Producto</th><th>Descripcion</th><th>Fechas</th><th>Acciones</th></tr></thead>
                    <tbody>
                        <?php $promotions = $promotionModel->getAll(); ?>
                        <?php if (empty($promotions)): ?>
                            <tr><td colspan="4" class="text-center">No hay promociones creadas.</td></tr>
                        <?php else: ?>
                            <?php foreach ($promotions as $promo): ?>
                                <tr>
                                    <td><?= htmlspecialchars($promo['product_name']) ?></td>
                                    <td><?= htmlspecialchars($promo['promo_description']) ?></td>
                                    <td><?= date('d/m/y', strtotime($promo['start_date'])) ?> - <?= date('d/m/y', strtotime($promo['end_date'])) ?></td>
                                    <td>
                                        <a href="promotions.php?action=edit&id=<?= $promo['id'] ?>" class="btn btn-sm btn-secondary">Editar</a>
                                        <a href="promotions.php?action=delete&id=<?= $promo['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Estás seguro?')">Eliminar</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    <?php elseif ($action === 'new' || $action === 'edit'): ?>
        <?php
        $isEdit = $action === 'edit';
        $promo = $isEdit ? $promotionModel->getById($_GET['id']) : null;
        $allProducts = $productModel->getAll();
        ?>
        <h1 class="h3"><?= $isEdit ? 'Editar Promoción' : 'Crear Nueva Promoción' ?></h1>
        <div class="card">
            <div class="card-body">
                <form action="promotions.php" method="POST">
                    <input type="hidden" name="form_action" value="<?= $isEdit ? 'update' : 'create' ?>">
                    <?php if ($isEdit): ?><input type="hidden" name="id" value="<?= $promo['id'] ?>"><?php endif; ?>

                    <div class="mb-3">
                        <label class="form-label">Producto a promocionar</label>
                        <select class="form-select" name="product_id" required>
                            <option value="">Selecciona un producto...</option>
                            <?php foreach ($allProducts as $product): ?>
                                <option value="<?= $product['id'] ?>" <?= ($promo && $promo['product_id'] == $product['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($product['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Descripción de la promoción (Ej: Pague 10 lleve 12)</label>
                        <input type="text" class="form-control" name="promo_description" value="<?= htmlspecialchars($promo['promo_description'] ?? '') ?>" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3"><label class="form-label">Mínimo a pedir (opcional)</label><input type="number" name="min_quantity" class="form-control" value="<?= $promo['min_quantity'] ?? '' ?>" placeholder="0 o en blanco para no aplicar"></div>
                        <div class="col-md-6 mb-3"><label class="form-label">Máximo a pedir (opcional)</label><input type="number" name="max_quantity" class="form-control" value="<?= $promo['max_quantity'] ?? '' ?>" placeholder="0 o en blanco para no aplicar"></div>
                    </div>
                     <div class="row">
                        <div class="col-md-6 mb-3"><label class="form-label">Fecha de Inicio</label><input type="date" name="start_date" class="form-control" value="<?= $promo['start_date'] ?? '' ?>" required></div>
                        <div class="col-md-6 mb-3"><label class="form-label">Fecha de Fin</label><input type="date" name="end_date" class="form-control" value="<?= $promo['end_date'] ?? '' ?>" required></div>
                    </div>
                    <a href="promotions.php" class="btn btn-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary"><?= $isEdit ? 'Actualizar' : 'Crear' ?></button>
                </form>
            </div>
        </div>
    <?php endif; ?>
</div>
<?php
include APP_ROOT . '/app/views/admin/layout/footer.php';
?>