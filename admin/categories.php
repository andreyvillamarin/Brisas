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
require_once APP_ROOT . '/app/models/Category.php';
require_once APP_ROOT . '/app/models/Setting.php';
require_once APP_ROOT . '/app/helpers/log_helper.php';

$categoryModel = new Category();
$action = $_GET['action'] ?? 'list';
$pageTitle = 'Gestion de Categorías';

$settingModelForHeader = new Setting();
$settingsForHeader = $settingModelForHeader->getAllAsAssoc();

// Lógica para manejar acciones POST (crear, actualizar)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $name = $_POST['name'] ?? '';
    $imageUrl = null;

    // Manejo de la subida de imagen
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $uploadDir = APP_ROOT . '/uploads/categories/';
        $imageName = uniqid() . '-' . basename($_FILES['image']['name']);
        $targetFile = $uploadDir . $imageName;
        if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
            $imageUrl = 'uploads/categories/' . $imageName;
            
            // Si estamos actualizando, borrar la imagen anterior
            if ($id && !empty($_POST['current_image'])) {
                $oldImageFile = APP_ROOT . '/' . $_POST['current_image'];
                if (file_exists($oldImageFile)) {
                    unlink($oldImageFile);
                }
            }
        }
    }

    switch ($_POST['form_action']) {
        case 'create':
            $newId = $categoryModel->create($name, $imageUrl);
            log_event("Creó la categoría", "category", $newId);
            break;
        case 'update':
            $currentImage = $_POST['current_image'] ?? null;
            $categoryModel->update($id, $name, $imageUrl ?? $currentImage);
            log_event("Actualizó la categoría", "category", $id);
            break;
    }
    header('Location: categories.php');
    exit;
}

// Lógica para la acción de eliminar
if ($action === 'delete' && isset($_GET['id'])) {
    $id = $_GET['id'];
    // Log before deleting to ensure we can fetch the name
    log_event("Eliminó la categoría", "category", $id);
    
    // Fetch category to get image url for deletion
    $category = $categoryModel->getById($id);
    if ($category && !empty($category['image_url'])) {
        $imageFile = APP_ROOT . '/' . $category['image_url'];
        if (file_exists($imageFile)) {
            unlink($imageFile);
        }
    }
    $categoryModel->delete($id);
    header('Location: categories.php');
    exit;
}

// Cargar la plantilla de cabecera
include APP_ROOT . '/app/views/admin/layout/header.php';

// Contenido principal de la página
?>
<div class="container-fluid">
    <?php if ($action === 'list'): ?>
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="h3">Categorías de Productos</h1>
            <a href="categories.php?action=new" class="btn btn-primary">Crear Nueva Categoría</a>
        </div>
        <div class="card">
            <div class="card-body">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Imagen</th>
                            <th>Nombre</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $categories = $categoryModel->getAllCategories(); ?>
                        <?php if (empty($categories)): ?>
                            <tr><td colspan="3" class="text-center">No hay categorías creadas.</td></tr>
                        <?php else: ?>
                            <?php foreach ($categories as $category): ?>
                                <tr>
                                    <td><img src="../<?= htmlspecialchars($category['image_url'] ?? 'assets/img/placeholder.png') ?>" alt="<?= htmlspecialchars($category['name']) ?>" class="category-thumbnail"></td>
                                    <td><?= htmlspecialchars($category['name']) ?></td>
                                    <td>
                                        <a href="categories.php?action=edit&id=<?= $category['id'] ?>" class="btn btn-sm btn-secondary">Editar</a>
                                        <a href="categories.php?action=delete&id=<?= $category['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Estás seguro de que quieres eliminar esta categoría?')">Eliminar</a>
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
        $category = $isEdit ? $categoryModel->getById($_GET['id']) : null;
        ?>
        <h1 class="h3"><?= $isEdit ? 'Editar Categoría' : 'Crear Nueva Categoría' ?></h1>
        <div class="card">
            <div class="card-body">
                <form action="categories.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="form_action" value="<?= $isEdit ? 'update' : 'create' ?>">
                    <?php if ($isEdit): ?>
                        <input type="hidden" name="id" value="<?= $category['id'] ?>">
                        <input type="hidden" name="current_image" value="<?= $category['image_url'] ?>">
                    <?php endif; ?>

                    <div class="mb-3">
                        <label for="name" class="form-label">Nombre de la Categoría</label>
                        <input type="text" class="form-control" id="name" name="name" value="<?= htmlspecialchars($category['name'] ?? '') ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="image" class="form-label">Imagen de la Categoría</label>
                        <input type="file" class="form-control" id="image" name="image" accept="image/*">
                        <div class="mt-2">
                            <img id="image-preview" src="../<?= htmlspecialchars($category['image_url'] ?? 'assets/img/placeholder.png') ?>" alt="Previsualización" class="category-thumbnail">
                        </div>
                    </div>

                    <a href="categories.php" class="btn btn-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary"><?= $isEdit ? 'Actualizar' : 'Crear' ?> Categoría</button>
                </form>
            </div>
        </div>
    <?php endif; ?>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
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
});
</script>
<?php
// Cargar la plantilla de pie de página
include APP_ROOT . '/app/views/admin/layout/footer.php';
?>