<?php
if (session_status() == PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    die('Acceso denegado. Esta sección es solo para administradores.');
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/../brisas_secure_configs/main_config.php';
require_once APP_ROOT . '/app/helpers/Database.php';
require_once APP_ROOT . '/app/models/Setting.php';

$settingModel = new Setting();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $success = true;
    $errorMessage = null;

    // Guardar ajustes de texto
    $textSettings = ['store_status', 'store_message', 'brevo_api_key', 'google_recaptcha_key', 'admin_notification_email', 'brevo_user', 'google_recaptcha_secret'];
    foreach ($textSettings as $key) {
        if (isset($_POST[$key])) {
            if (!$settingModel->updateSetting($key, $_POST[$key])) {
                $success = false;
                $errorMessage = "Error al guardar el ajuste: {$key}";
            }
        }
    }

    // Manejar subida de logos
    $logoKeys = ['logo_frontend_url', 'logo_backend_url'];
    foreach ($logoKeys as $key) {
        if (isset($_FILES[$key]) && $_FILES[$key]['error'] == UPLOAD_ERR_OK) {
            $uploadDir = APP_ROOT . '/uploads/logos/';
            if (!is_dir($uploadDir) || !is_writable($uploadDir)) {
                $success = false;
                $errorMessage = "Error: El directorio de subida de logos no existe o no tiene permisos de escritura.";
                continue;
            }

            $imageName = $key . '-' . uniqid() . '-' . basename($_FILES[$key]['name']);
            $targetFile = $uploadDir . $imageName;
            if (move_uploaded_file($_FILES[$key]['tmp_name'], $targetFile)) {
                $newPath = 'uploads/logos/' . $imageName;
                if ($settingModel->updateSetting($key, $newPath)) {
                    $currentSettings = $settingModel->getAllAsAssoc();
                    $oldLogoPath = APP_ROOT . '/' . ($currentSettings[$key] ?? '');
                    if ($oldLogoPath !== $targetFile && file_exists($oldLogoPath) && is_file($oldLogoPath)) {
                        unlink($oldLogoPath);
                    }
                } else {
                    $success = false;
                    $errorMessage = "Error al guardar la ruta del logo en la base de datos.";
                }
            } else {
                $success = false;
                $errorMessage = "Error al mover el archivo subido. Revisa los permisos.";
            }
        } elseif (isset($_FILES[$key]) && $_FILES[$key]['error'] != UPLOAD_ERR_NO_FILE) {
            $success = false;
            $errorMessage = "Hubo un error al subir el archivo. Código de error: " . $_FILES[$key]['error'];
        }
    }

    $redirectParams = $success ? 'success=1' : 'error=' . urlencode($errorMessage);
    header('Location: settings.php?' . $redirectParams);
    exit;
}

$settings = $settingModel->getAllAsAssoc();
$pageTitle = 'Configuración General';
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
                        <input type="file" name="logo_frontend_url" class="form-control">
                        <?php if (!empty($settings['logo_frontend_url'])): ?><img src="../<?= $settings['logo_frontend_url'] ?>" class="mt-2" style="max-height: 50px;"><?php endif; ?>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Logo del Backend</label>
                        <input type="file" name="logo_backend_url" class="form-control">
                        <?php if (!empty($settings['logo_backend_url'])): ?><img src="../<?= $settings['logo_backend_url'] ?>" class="mt-2" style="max-height: 50px; background-color: #343a40; padding: 5px;"><?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-header">Claves de API</div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">API Key de Brevo (para correos)</label>
                    <input type="text" name="brevo_api_key" class="form-control" value="<?= htmlspecialchars($settings['brevo_api_key'] ?? '') ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Clave del sitio de Google reCAPTCHA v3</label>
                    <input type="text" name="google_recaptcha_key" class="form-control" value="<?= htmlspecialchars($settings['google_recaptcha_key'] ?? '') ?>">
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
    document.querySelectorAll('input[type="file"]').forEach(input => {
        input.addEventListener('change', function(e) {
            const previewTarget = document.querySelector(e.target.dataset.preview);
            if (e.target.files && e.target.files[0] && previewTarget) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    previewTarget.src = event.target.result;
                    previewTarget.style.display = 'block';
                }
                reader.readAsDataURL(e.target.files[0]);
            }
        });
    });
});
</script>

<?php
include APP_ROOT . '/app/views/admin/layout/footer.php';
?>