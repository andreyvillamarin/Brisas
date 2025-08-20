<?php
// Archivo de login
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Si el usuario ya está logueado, redirigir al dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: admin/');
    exit;
}

require_once __DIR__ . '/config_loader.php';
require_once APP_ROOT . '/app/helpers/Database.php';
require_once APP_ROOT . '/app/models/Setting.php';
$settingModel = new Setting();
$settings = $settingModel->getAllAsAssoc();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Panel de Administración</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <?php if (!empty($settings['logo_backend_url'])): ?>
        <link rel="icon" href="<?= htmlspecialchars($settings['logo_backend_url']) ?>">
    <?php endif; ?>
    <style>
        :root { --brisas-red: #aa182c; }
        body { display: flex; align-items: center; justify-content: center; min-height: 100vh; background-color: #f8f9fa; }
        .login-card { max-width: 400px; width: 100%; }
        .btn-primary { background-color: var(--brisas-red); border-color: var(--brisas-red); }
        .btn-primary:hover { background-color: #861323; border-color: #861323; }
    </style>
</head>
<body>
    <div class="card login-card shadow">
        <div class="card-body p-5">
            <div class="text-center mb-4">
                <?php if (!empty($settings['logo_frontend_url'])): ?>
                    <img src="<?= htmlspecialchars($settings['logo_frontend_url']) ?>" alt="Logo" style="max-height: 70px;">
                <?php else: ?>
                    <h3 class="mb-0">Acceso de Administración</h3>
                <?php endif; ?>
            </div>
            <?php if(isset($_GET['error'])): ?>
                <div class="alert alert-danger">Usuario o contraseña incorrectos.</div>
            <?php endif; ?>
            <form action="auth/authenticate.php" method="POST">
                <div class="mb-3">
                    <label for="username" class="form-label">Usuario</label>
                    <input type="text" class="form-control" id="username" name="username" required>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Contraseña</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary">Ingresar</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>