<?php
if (session_status() == PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') { die('Acceso denegado.'); }

require_once __DIR__ . '/../config_loader.php';
require_once APP_ROOT . '/app/helpers/Database.php';
require_once APP_ROOT . '/app/models/User.php'; // Para obtener el nombre de usuario
require_once APP_ROOT . '/app/models/Setting.php';

// Cargar el log
try {
    $db = Database::getInstance()->getConnection();
    $sql = "SELECT el.*, u.username 
            FROM event_log el 
            LEFT JOIN users u ON el.user_id = u.id 
            ORDER BY el.created_at DESC LIMIT 200"; // Limitar a los últimos 200 eventos
    $stmt = $db->query($sql);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $logs = [];
    error_log("Failed to fetch event log: " . $e->getMessage());
}

$pageTitle = 'Log de Eventos';

$settingModelForHeader = new Setting();
$settingsForHeader = $settingModelForHeader->getAllAsAssoc();
include APP_ROOT . '/app/views/admin/layout/header.php';
?>
<div class="container-fluid">
    <h1 class="h3 mb-4">Log de Eventos del Sistema</h1>
    <div class="card">
        <div class="card-body">
            <p>Mostrando los últimos 200 eventos registrados.</p>
            <div class="table-responsive">
                <table class="table table-striped table-sm">
                    <thead><tr><th>Fecha y Hora</th><th>Usuario</th><th>Acción</th></tr></thead>
                    <tbody>
                        <?php if (empty($logs)): ?>
                            <tr><td colspan="3" class="text-center">No hay eventos registrados.</td></tr>
                        <?php else: ?>
                            <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td><?= htmlspecialchars($log['created_at']) ?></td>
                                    <td><?= htmlspecialchars($log['username'] ?? 'Sistema') ?></td>
                                    <td><?= htmlspecialchars($log['action']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php
include APP_ROOT . '/app/views/admin/layout/footer.php';
?>