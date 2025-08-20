<?php
if (session_status() == PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) { header('Location: ../login.php'); exit; }

require_once __DIR__ . '/../config_loader.php';
require_once APP_ROOT . '/app/helpers/Database.php';
require_once APP_ROOT . '/app/models/User.php';
require_once APP_ROOT . '/app/models/Setting.php';
require_once APP_ROOT . '/app/libs/GoogleAuthenticator/PHPGangsta/GoogleAuthenticator.php';

$userModel = new User();
$ga = new PHPGangsta_GoogleAuthenticator();

$currentUser = $userModel->getById($_SESSION['user_id']);
$is2faEnabled = !empty($currentUser['google_2fa_secret']);

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'enable') {
    $secret = $ga->createSecret();
    $_SESSION['2fa_temp_secret'] = $secret;
    $qrCodeUrl = $ga->getQRCodeGoogleUrl('BrisasApp', $currentUser['email'], $secret);
} elseif ($action === 'verify') {
    $secret = $_SESSION['2fa_temp_secret'];
    $code = $_POST['code'];
    if ($ga->verifyCode($secret, $code, 2)) { // 2 = 2*30sec clock tolerance
        $userModel->update2FASecret($_SESSION['user_id'], $secret);
        unset($_SESSION['2fa_temp_secret']);
        header('Location: security.php?success=enabled');
        exit;
    } else {
        header('Location: security.php?action=enable&error=1');
        exit;
    }
} elseif ($action === 'disable') {
    $userModel->update2FASecret($_SESSION['user_id'], null);
    header('Location: security.php?success=disabled');
    exit;
}

$pageTitle = 'Seguridad de la Cuenta';

$settingModelForHeader = new Setting();
$settingsForHeader = $settingModelForHeader->getAllAsAssoc();
include APP_ROOT . '/app/views/admin/layout/header.php';
?>
<div class="container-fluid">
    <h1 class="h3 mb-4">Autenticación de Dos Factores (2FA)</h1>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success">2FA ha sido <?= $_GET['success'] === 'enabled' ? 'activada' : 'desactivada' ?> correctamente.</div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <?php if ($is2faEnabled): ?>
                <p class="lead">La autenticación de dos factores está <strong>activa</strong> en tu cuenta.</p>
                <p>Tu cuenta está protegida con una capa adicional de seguridad.</p>
                <form action="security.php" method="POST" onsubmit="return confirm('¿Estás seguro de que quieres desactivar 2FA?')">
                    <input type="hidden" name="action" value="disable">
                    <button type="submit" class="btn btn-danger">Desactivar 2FA</button>
                </form>
            <?php elseif ($action === 'enable'): ?>
                <h5 class="card-title">Configurar 2FA</h5>
                <p>1. Escanea este código QR con tu aplicación de autenticación (Google Authenticator, Authy, etc).</p>
                <div class="text-center my-4">
                    <img src="<?= $qrCodeUrl ?>">
                </div>
                <p>2. Si no puedes escanear el QR, introduce manualmente esta clave:</p>
                <p><code class="fs-5"><?= $secret ?></code></p>
                <p>3. Introduce el código de 6 dígitos generado por tu aplicación para verificar y completar la activación.</p>
                <form action="security.php" method="POST" class="row g-3 align-items-center">
                    <input type="hidden" name="action" value="verify">
                    <div class="col-auto"><input type="text" name="code" class="form-control" placeholder="123456" required></div>
                    <div class="col-auto"><button type="submit" class="btn btn-primary">Verificar y Activar</button></div>
                    <?php if (isset($_GET['error'])): ?><div class="text-danger mt-2">Código incorrecto. Inténtalo de nuevo.</div><?php endif; ?>
                </form>
            <?php else: ?>
                <p class="lead">La autenticación de dos factores está <strong>inactiva</strong>.</p>
                <p>Añade una capa extra de seguridad a tu cuenta. Se te pedirá un código de tu teléfono cada vez que inicies sesión.</p>
                <form action="security.php" method="GET">
                    <input type="hidden" name="action" value="enable">
                    <button type="submit" class="btn btn-primary">Activar 2FA</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php
include APP_ROOT . '/app/views/admin/layout/footer.php';
?>