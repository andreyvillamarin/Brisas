<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

require_once __DIR__ . '/../config_loader.php';
require_once APP_ROOT . '/app/helpers/Database.php';
require_once APP_ROOT . '/app/models/Product.php';
require_once APP_ROOT . '/app/models/Category.php'; // Necesario para el dropdown
require_once APP_ROOT . '/app/models/Setting.php';
require_once APP_ROOT . '/app/helpers/log_helper.php';

$productModel = new Product();
$categoryModel = new Category(); // Para poblar el select
$action = $_GET['action'] ?? 'list';
$pageTitle = 'Gestion de Productos';

// Lógica para manejar acciones POST (crear, actualizar)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $name = $_POST['name'] ?? '';
    $categoryId = $_POST['category_id'] ?? null;
    $imageUrl = null;

    // Manejo de la subida de imagen
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $uploadDir = APP_ROOT . '/uploads/products/';
        $imageName = uniqid() . '-' . basename($_FILES['image']['name']);
        $targetFile = $uploadDir . $imageName;
        if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
            $imageUrl = 'uploads/products/' . $imageName;
            if ($id && !empty($_POST['current_image'])) {
                $oldImageFile = APP_ROOT . '/' . $_POST['current_image'];
                if (file_exists($oldImageFile)) unlink($oldImageFile);
            }
        }
    }

    switch ($_POST['form_action']) {
        case 'create':
            $newId = $productModel->create($name, $categoryId, $imageUrl);
            log_event("Creó el producto", "product", $newId);
            break;
        case 'update':
            $currentImage = $_POST['current_image'] ?? null;
            $productModel->update($id, $name, $categoryId, $imageUrl ?? $currentImage);
            log_event("Actualizó el producto", "product", $id);
            break;
    }
    header('Location: products.php');
    exit;
}

// Lógica para la acción de eliminar
if ($action === 'delete' && isset($_GET['id'])) {
    $id = $_GET['id'];
    log_event("Eliminó el producto", "product", $id);
    
    $product = $productModel->getById($id);
    if ($product && !empty($product['image_url'])) {
        $imageFile = APP_ROOT . '/' . $product['image_url'];
        if (file_exists($imageFile)) unlink($imageFile);
    }
    $productModel->delete($id);
    header('Location: products.php');
    exit;
}

$settingModelForHeader = new Setting();
$settingsForHeader = $settingModelForHeader->getAllAsAssoc();
include APP_ROOT . '/app/views/admin/layout/header.php';
?>
<div class="container-fluid">
    <?php if ($action === 'list'): ?>
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="h3">Productos</h1>
            <a href="products.php?action=new" class="btn btn-primary">Crear Nuevo Producto</a>
        </div>
        <div class="card mb-3">
            <div class="card-body">
                <input type="text" id="product-search-input" class="form-control" placeholder="Filtrar productos por nombre...">
            </div>
        </div>
        <div class="card">
            <div class="card-body">
                <table class="table table-striped">
                    <thead><tr><th>Imagen</th><th>Nombre</th><th>Categoría</th><th>Acciones</th></tr></thead>
                    <tbody id="products-table-body">
                        <?php $products = $productModel->getAll(); ?>
                        <?php if (empty($products)): ?>
                            <tr><td colspan="4" class="text-center">No hay productos creados.</td></tr>
                        <?php else: ?>
                            <?php foreach ($products as $product): ?>
                                <tr>
                                    <td><img src="../<?= htmlspecialchars($product['image_url'] ?? 'assets/img/placeholder.png') ?>" alt="" class="category-thumbnail"></td>
                                    <td><?= htmlspecialchars($product['name']) ?></td>
                                    <td><?= htmlspecialchars($product['category_name'] ?? 'N/A') ?></td>
                                    <td>
                                        <a href="products.php?action=edit&id=<?= $product['id'] ?>" class="btn btn-sm btn-secondary">Editar</a>
                                        <a href="products.php?action=delete&id=<?= $product['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Estás seguro?')">Eliminar</a>
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
        $product = $isEdit ? $productModel->getById($_GET['id']) : null;
        $allCategories = $categoryModel->getAllCategories();
        ?>
        <h1 class="h3"><?= $isEdit ? 'Editar Producto' : 'Crear Nuevo Producto' ?></h1>
        <div class="card">
            <div class="card-body">
                <form action="products.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="form_action" value="<?= $isEdit ? 'update' : 'create' ?>">
                    <?php if ($isEdit): ?>
                        <input type="hidden" name="id" value="<?= $product['id'] ?>">
                        <input type="hidden" name="current_image" value="<?= $product['image_url'] ?>">
                    <?php endif; ?>

                    <div class="mb-3">
                        <label for="name" class="form-label">Nombre del Producto</label>
                        <input type="text" class="form-control" id="name" name="name" value="<?= htmlspecialchars($product['name'] ?? '') ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="category_id" class="form-label">Categoría</label>
                        <select class="form-select" id="category_id" name="category_id" required>
                            <option value="">Selecciona una categoría</option>
                            <?php foreach ($allCategories as $category): ?>
                                <option value="<?= $category['id'] ?>" <?= ($product && $product['category_id'] == $category['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($category['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="image" class="form-label">Imagen del Producto</label>
                        <input type="file" class="form-control" id="image" name="image" accept="image/*">
                        <div class="mt-2">
                            <img id="image-preview" src="../<?= htmlspecialchars($product['image_url'] ?? 'assets/img/placeholder.png') ?>" alt="Previsualización" class="category-thumbnail">
                        </div>
                    </div>

                    <a href="products.php" class="btn btn-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary"><?= $isEdit ? 'Actualizar' : 'Crear' ?> Producto</button>
                </form>
            </div>
        </div>
    <?php endif; ?>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Image preview script
    const imageInput = document.getElementById('image');
    const imagePreview = document.getElementById('image-preview');
    if (imageInput && imagePreview) {
        imageInput.addEventListener('change', function(event) {
            const file = event.target.files[0];
            if (file) {
                imagePreview.src = URL.createObjectURL(file);
            }
        });
    }

    // Live search script
    const searchInput = document.getElementById('product-search-input');
    const tableBody = document.getElementById('products-table-body');
    if (searchInput && tableBody) {
        const rows = tableBody.getElementsByTagName('tr');
        searchInput.addEventListener('input', function() {
            const searchTerm = searchInput.value.toLowerCase();
            for (let i = 0; i < rows.length; i++) {
                const nameCell = rows[i].getElementsByTagName('td')[1]; // 2nd cell is product name
                if (nameCell) {
                    const name = nameCell.textContent || nameCell.innerText;
                    if (name.toLowerCase().indexOf(searchTerm) > -1) {
                        rows[i].style.display = '';
                    } else {
                        rows[i].style.display = 'none';
                    }
                }
            }
        });
    }
});
</script>
<?php
include APP_ROOT . '/app/views/admin/layout/footer.php';
?>