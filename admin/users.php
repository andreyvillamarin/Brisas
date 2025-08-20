<?php
if (session_status() == PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    // Si no es admin, redirigir al dashboard con un mensaje de error (o simplemente morir)
    die('Acceso denegado. Esta sección es solo para administradores.');
}

require_once __DIR__ . '/../config_loader.php';
require_once APP_ROOT . '/app/helpers/Database.php';
require_once APP_ROOT . '/app/models/User.php';
require_once APP_ROOT . '/app/models/Setting.php';
require_once APP_ROOT . '/app/helpers/log_helper.php';

$userModel = new User();
$action = $_GET['action'] ?? 'list';
$pageTitle = 'Gestion de Usuarios';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'username' => $_POST['username'],
        'email' => $_POST['email'],
        'role' => $_POST['role'],
        'password' => $_POST['password'] ?? ''
    ];

    if ($_POST['form_action'] === 'create') {
        $newId = $userModel->create($data);
        log_event("Creó el usuario", "user", $newId);
    } elseif ($_POST['form_action'] === 'update') {
        $id = $_POST['id'];
        $userModel->update($id, $data);
        log_event("Actualizó el usuario", "user", $id);
    }
    header('Location: users.php');
    exit;
}

if ($action === 'delete' && isset($_GET['id'])) {
    $idToDelete = $_GET['id'];
    // Un admin no se puede borrar a sí mismo
    if ($idToDelete != $_SESSION['user_id']) {
        log_event("Eliminó el usuario", "user", $idToDelete);
        $userModel->delete($idToDelete);
    }
    header('Location: users.php');
    exit;
}

$settingModelForHeader = new Setting();
$settingsForHeader = $settingModelForHeader->getAllAsAssoc();
include APP_ROOT . '/app/views/admin/layout/header.php';
?>
<div class="container-fluid">
    <?php if ($action === 'list'): ?>
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="h3">Usuarios del Sistema</h1>
            <a href="users.php?action=new" class="btn btn-primary">Crear Nuevo Usuario</a>
        </div>
        <div class="card">
            <div class="card-body">
                <table class="table table-striped">
                    <thead><tr><th>Usuario</th><th>Email</th><th>Rol</th><th>Acciones</th></tr></thead>
                    <tbody>
                        <?php $users = $userModel->getAll(); ?>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?= htmlspecialchars($user['username']) ?></td>
                                <td><?= htmlspecialchars($user['email']) ?></td>
                                <td><span class="badge bg-info"><?= ucfirst($user['role']) ?></span></td>
                                <td>
                                    <a href="users.php?action=edit&id=<?= $user['id'] ?>" class="btn btn-sm btn-secondary">Editar</a>
                                    <?php if ($user['id'] != $_SESSION['user_id']): // No mostrar botón de eliminar para el usuario actual ?>
                                        <a href="users.php?action=delete&id=<?= $user['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Estás seguro?')">Eliminar</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    <?php elseif ($action === 'new' || $action === 'edit'): ?>
        <?php
        $isEdit = $action === 'edit';
        $user = $isEdit ? $userModel->getById($_GET['id']) : null;
        ?>
        <h1 class="h3"><?= $isEdit ? 'Editar Usuario' : 'Crear Nuevo Usuario' ?></h1>
        <div class="card">
            <div class="card-body">
                <form action="users.php" method="POST">
                    <input type="hidden" name="form_action" value="<?= $isEdit ? 'update' : 'create' ?>">
                    <?php if ($isEdit): ?><input type="hidden" name="id" value="<?= $user['id'] ?>"><?php endif; ?>

                    <div class="row">
                        <div class="col-md-6 mb-3"><label class="form-label">Nombre de Usuario</label><input type="text" name="username" class="form-control" value="<?= htmlspecialchars($user['username'] ?? '') ?>" required></div>
                        <div class="col-md-6 mb-3"><label class="form-label">Email</label><input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email'] ?? '') ?>" required></div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Rol</label>
                            <select name="role" class="form-select" required>
                                <option value="collaborator" <?= ($user && $user['role'] == 'collaborator') ? 'selected' : '' ?>>Colaborador</option>
                                <option value="admin" <?= ($user && $user['role'] == 'admin') ? 'selected' : '' ?>>Administrador</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Contraseña</label>
                            <input type="password" name="password" class="form-control" <?= $isEdit ? '' : 'required' ?>>
                            <?php if ($isEdit): ?><div class="form-text">Dejar en blanco para no cambiar la contraseña.</div><?php endif; ?>
                        </div>
                    </div>
                    <a href="users.php" class="btn btn-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary"><?= $isEdit ? 'Actualizar' : 'Crear' ?></button>
                </form>
            </div>
        </div>
    <?php endif; ?>
</div>
<?php
include APP_ROOT . '/app/views/admin/layout/footer.php';
?>