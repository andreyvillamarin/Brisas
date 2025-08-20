<?php
if (session_status() == PHP_SESSION_NONE) session_start();

// Si no estamos en el proceso de 2FA, o si ya estamos logueados, no deberíamos estar aquí.
if (!isset($_SESSION['2fa_user_id']) || isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/config_loader.php';
require_once APP_ROOT . '/app/helpers/Database.php';
require_once APP_ROOT . '/app/models/User.php';
require_once APP_ROOT . '/app/libs/GoogleAuthenticator/PHPGangsta/GoogleAuthenticator.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userModel = new User();
    $ga = new PHPGangsta_GoogleAuthenticator();
    
    $user = $userModel->getUserWithSecret($_SESSION['2fa_user_id']);
    $code = $_POST['code'];

    if ($user && $ga->verifyCode($user['google_2fa_secret'], $code, 2)) {
        // Código correcto, completar el login
        unset($_SESSION['2fa_user_id']);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['user_role'] = $user['role'];
        header('Location: admin/');
        exit;
    } else {
        $error = "Código incorrecto.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Verificación de Dos Factores</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>:root { --brisas-red: #aa182c; } body { display: flex; align-items: center; justify-content: center; min-height: 100vh; background-color: #f8f9fa; } .login-card { max-width: 400px; width: 100%; } .btn-primary { background-color: var(--brisas-red); border-color: var(--brisas-red); } .btn-primary:hover { background-color: #861323; border-color: #861323; }</style>
</head>
<body>
    <div class="card login-card shadow">
        <div class="card-body p-5">
            <h3 class="text-center mb-4">Verificación de Seguridad</h3>
            <p class="text-center text-muted">Introduce el código de 6 dígitos de tu aplicación de autenticación.</p>
            <?php if(isset($error)): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>
            <form action="login_2fa.php" method="POST">
                <div class="mb-3">
                    <label for="code" class="form-label">Código de 6 dígitos</label>
                    <input type="text" class="form-control" id="code" name="code" required autofocus>
                </div>
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary">Verificar</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>