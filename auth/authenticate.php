<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/../brisas_secure_configs/main_config.php';
require_once APP_ROOT . '/app/helpers/Database.php';
require_once APP_ROOT . '/app/models/User.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $userModel = new User();
    $username = $_POST['username'];
    $password = $_POST['password'];

    $user = $userModel->findByUsername($username);

    if ($user && password_verify($password, $user['password_hash'])) {
        // Contraseña correcta, verificar si 2FA está activado
        if (!empty($user['google_2fa_secret'])) {
            // 2FA está activado, redirigir a la página de verificación
            $_SESSION['2fa_user_id'] = $user['id'];
            header('Location: ../login_2fa.php');
            exit;
        } else {
            // 2FA no está activado, iniciar sesión normalmente
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_role'] = $user['role'];
            log_event("Inicio de sesión exitoso.");
            header('Location: ../admin/');
            exit;
        }
    } else {
        header('Location: ../login.php?error=1');
        exit;
    }
}
?>