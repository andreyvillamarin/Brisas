<?php
/**
 * Registra una acción en el log de eventos.
 * @param string $action La descripción de la acción realizada.
 */
function log_event($action) {
    if (session_status() == PHP_SESSION_NONE) session_start();

    if (isset($_SESSION['user_id'])) {
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("INSERT INTO event_log (user_id, action) VALUES (:user_id, :action)");
            $stmt->execute(['user_id' => $_SESSION['user_id'], 'action' => $action]);
        } catch (Exception $e) {
            error_log("Failed to log event: " . $e->getMessage());
        }
    }
}
?>