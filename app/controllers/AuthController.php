<?php

class AuthController {

    private $userModel;

    public function __construct() {
        // Este modelo lo crearemos a continuación
        if (file_exists(APP_ROOT . '/app/models/User.php')) {
            require_once APP_ROOT . '/app/models/User.php';
            $this->userModel = new User();
        }
    }

    public function login() {
        // Si ya está logueado, redirigir al dashboard
        if (isset($_SESSION['user_id'])) {
            header('Location: ' . APP_URL . '/admin/dashboard');
            exit;
        }
        // Mostrar el formulario de login
        require_once APP_ROOT . '/app/views/admin/login.php';
    }

    public function authenticate() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $username = $_POST['username'];
            $password = $_POST['password'];

            $user = $this->userModel->findByUsername($username);

            if ($user && password_verify($password, $user['password_hash'])) {
                // Contraseña correcta, crear sesión
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['user_role'] = $user['role'];
                header('Location: ' . APP_URL . '/admin/dashboard');
                exit;
            } else {
                // Error de autenticación
                header('Location: ' . APP_URL . '/auth/login?error=1');
                exit;
            }
        }
    }

    public function logout() {
        session_destroy();
        header('Location: ' . url('auth/login'));
        exit;
    }
}
?>