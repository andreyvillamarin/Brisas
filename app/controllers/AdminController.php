<?php

class AdminController {

    public function __construct() {
        // Proteger todas las rutas del admin
        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . url('auth/login'));
            exit;
        }
    }

    // Dashboard principal
    public function index() {
        $this->dashboard();
    }

    public function dashboard() {
        $data = ['title' => 'Dashboard'];
        $this->view('admin/dashboard', $data);
    }

    // Método para cargar vistas con la plantilla del admin
    public function view($view, $data = []) {
        if (file_exists(APP_ROOT . '/app/views/' . $view . '.php')) {
            // Extraer datos para que estén disponibles en la vista
            extract($data);

            require_once APP_ROOT . '/app/views/admin/layout/header.php';
            require_once APP_ROOT . '/app/views/' . $view . '.php';
            require_once APP_ROOT . '/app/views/admin/layout/footer.php';
        } else {
            die('La vista no existe.');
        }
    }
}
?>