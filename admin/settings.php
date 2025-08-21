<?php
if (session_status() == PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    die('Acceso denegado. Esta sección es solo para administradores.');
}

require_once __DIR__ . '/../config_loader.php';
require_once APP_ROOT . '/app/helpers/Database.php';
require_once APP_ROOT . '/app/models/Setting.php';

$settingModel = new Setting();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $success = true;
    $errorMessage = null;
    $oldSettings = $settingModel->getAllAsAssoc();

    // Guardar ajustes de texto
    $textSettings = ['store_status', 'store_message', 'brevo_api_key', 'google_recaptcha_key', 'admin_notification_email', 'brevo_user', 'google_recaptcha_secret', 'sidebar_logo_height', 'sender_email', 'sender_name'];
    foreach ($textSettings as $key) {
        if (isset($_POST[$key])) {
             // No actualiza si el valor está vacío y es una clave (para no sobreescribir con un string vacío si no se quiere cambiar)
            if ((strpos($key, 'key') !== false || strpos($key, 'secret') !== false) && empty($_POST[$key])) {
                continue;
            }
            if (!$settingModel->updateSetting($key, $_POST[$key])) {
                $success = false;
                $errorMessage = "Error al guardar el ajuste: {$key}";
                break; // Salir del bucle si hay un error
            }
        }
    }

    // Manejar subida de logos si los ajustes de texto se guardaron bien
    if ($success) {
        $logoKeys = ['logo_frontend_url', 'logo_backend_url'];
        foreach ($logoKeys as $key) {
            if (isset($_FILES[$key]) && $_FILES[$key]['error'] == UPLOAD_ERR_OK) {
                $uploadDir = APP_ROOT . '/uploads/logos/';
                if (!is_dir($uploadDir) || !is_writable($uploadDir)) {
                    $success = false;
                    $errorMessage = "Error: El directorio de subida de logos no existe o no tiene permisos de escritura.";
                    break;
                }

                $imageName = $key . '-' . uniqid() . '-' . basename($_FILES[$key]['name']);
                $targetFile = $uploadDir . $imageName;

                if (move_uploaded_file($_FILES[$key]['tmp_name'], $targetFile)) {
                    $newPath = 'uploads/logos/' . $imageName;
                    $oldLogoPath = APP_ROOT . '/' . ($oldSettings[$key] ?? '');

                    if ($settingModel->updateSetting($key, $newPath)) {
                        // Borrar el logo antiguo solo si la actualización de la BD fue exitosa
                        if (!empty($oldSettings[$key]) && file_exists($oldLogoPath) && is_file($oldLogoPath)) {
                            unlink($oldLogoPath);
                        }
                    } else {
                        $success = false;
                        $errorMessage = "Error al guardar la ruta del logo en la base de datos.";
                        unlink($targetFile); // Borrar el archivo nuevo si la BD falla
                        break;
                    }
                } else {
                    $success = false;
                    $errorMessage = "Error al mover el archivo subido. Revisa los permisos.";
                    break;
                }
            } elseif (isset($_FILES[$key]) && $_FILES[$key]['error'] != UPLOAD_ERR_NO_FILE) {
                $success = false;
                $errorMessage = "Hubo un error al subir el archivo. Código de error: " . $_FILES[$key]['error'];
                break;
            }
        }
    }

    $redirectParams = $success ? 'success=1' : 'error=' . urlencode($errorMessage);
    header('Location: settings.php?' . $redirectParams);
    exit;
}

$settings = $settingModel->getAllAsAssoc();
$pageTitle = 'Configuración General';

$settingsForHeader = $settings; // Use the already fetched settings
include APP_ROOT . '/app/views/admin/layout/header.php';
?>
<div class="container-fluid">
    <h1 class="h3 mb-4">Configuración General</h1>
    <?php if (isset($_GET['success'])): ?><div class="alert alert-success">Ajustes guardados correctamente.</div><?php endif; ?>
    <?php if (isset($_GET['error'])): ?><div class="alert alert-danger"><?= htmlspecialchars($_GET['error']) ?></div><?php endif; ?>

    <form action="settings.php" method="POST" enctype="multipart/form-data">
        <div class="card">
            <div class="card-header">Estado de la Tienda</div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Estado del Frontend</label>
                    <select class="form-select" name="store_status">
                        <option value="open" <?= ($settings['store_status'] ?? '') == 'open' ? 'selected' : '' ?>>Abierta</option>
                        <option value="closed" <?= ($settings['store_status'] ?? '') == 'closed' ? 'selected' : '' ?>>Cerrada</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Mensaje de Tienda Cerrada (soporta HTML básico)</label>
                    <textarea name="store_message" class="form-control" rows="3"><?= htmlspecialchars($settings['store_message'] ?? '') ?></textarea>
                </div>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-header">Personalización</div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Logo del Frontend</label>
                        <input type="file" name="logo_frontend_url" id="logo_frontend_input" class="form-control">
                        <img id="frontend-logo-preview" src="../<?= htmlspecialchars($settings['logo_frontend_url'] ?? 'assets/img/placeholder.png') ?>" class="mt-2" style="max-height: 50px;">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Logo del Backend</label>
                        <input type="file" name="logo_backend_url" id="logo_backend_input" class="form-control">
                        <img id="backend-logo-preview" src="../<?= htmlspecialchars($settings['logo_backend_url'] ?? 'assets/img/placeholder.png') ?>" class="mt-2" style="max-height: 50px; background-color: #343a40; padding: 5px;">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Altura del logo en el menú lateral (en píxeles)</label>
                        <input type="number" name="sidebar_logo_height" class="form-control" value="<?= htmlspecialchars($settings['sidebar_logo_height'] ?? '50') ?>">
                    </div>
                </div>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-header">Configuración de Correos y API</div>
            <div class="card-body">
                <h5 class="card-title">Brevo (para envío de correos)</h5>
                <div class="mb-3">
                    <label class="form-label">API Key de Brevo</label>
                    <input type="password" name="brevo_api_key" class="form-control" placeholder="Dejar en blanco para no cambiar" value="">
                    <small class="form-text text-muted">La clave actual está guardada. Introduce una nueva solo si quieres cambiarla.</small>
                </div>
                <div class="mb-3">
                    <label class="form-label">Usuario de Brevo (Email de la cuenta)</label>
                    <input type="email" name="brevo_user" class="form-control" value="<?= htmlspecialchars($settings['brevo_user'] ?? '') ?>" placeholder="ej: usuario@dominio.com">
                </div>
                <div class="mb-3">
                    <label class="form-label">Email del Remitente</label>
                    <input type="email" name="sender_email" class="form-control" value="<?= htmlspecialchars($settings['sender_email'] ?? '') ?>" placeholder="ej: no-reply@tu-dominio.com">
                </div>
                <div class="mb-3">
                    <label class="form-label">Nombre del Remitente</label>
                    <input type="text" name="sender_name" class="form-control" value="<?= htmlspecialchars($settings['sender_name'] ?? '') ?>" placeholder="ej: Nombre de tu Tienda">
                </div>
                <hr>
                <h5 class="card-title">Notificaciones</h5>
                <div class="mb-3">
                    <label class="form-label">Email para Notificaciones de Administrador</label>
                    <input type="email" name="admin_notification_email" class="form-control" value="<?= htmlspecialchars($settings['admin_notification_email'] ?? '') ?>" placeholder="ej: admin@tu-dominio.com">
                </div>
                <hr>
                <h5 class="card-title">Google reCAPTCHA v3</h5>
                <div class="mb-3">
                    <label class="form-label">Clave del Sitio (Site Key)</label>
                    <input type="text" name="google_recaptcha_key" class="form-control" value="<?= htmlspecialchars($settings['google_recaptcha_key'] ?? '') ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Clave Secreta (Secret Key)</label>
                    <input type="password" name="google_recaptcha_secret" class="form-control" placeholder="Dejar en blanco para no cambiar" value="">
                    <small class="form-text text-muted">La clave actual está guardada. Introduce una nueva solo si quieres cambiarla.</small>
                </div>
            </div>
        </div>

        <div class="mt-4">
            <button type="submit" class="btn btn-primary btn-lg">Guardar Configuración</button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const frontendInput = document.getElementById('logo_frontend_input');
    const frontendPreview = document.getElementById('frontend-logo-preview');
    const backendInput = document.getElementById('logo_backend_input');
    const backendPreview = document.getElementById('backend-logo-preview');

    if (frontendInput && frontendPreview) {
        frontendInput.addEventListener('change', function(event) {
            const file = event.target.files[0];
            if (file) {
                frontendPreview.src = URL.createObjectURL(file);
            }
        });
    }

    if (backendInput && backendPreview) {
        backendInput.addEventListener('change', function(event) {
            const file = event.target.files[0];
            if (file) {
                backendPreview.src = URL.createObjectURL(file);
            }
        });
    }
});
</script>

<?php
include APP_ROOT . '/app/views/admin/layout/footer.php';
?>