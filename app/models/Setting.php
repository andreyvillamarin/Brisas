<?php
class Setting {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getAllAsAssoc() {
        try {
            $stmt = $this->db->query("SELECT * FROM settings");
            $settings = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $assoc = [];
            foreach ($settings as $setting) {
                $assoc[$setting['setting_key']] = $setting['setting_value'];
            }
            return $assoc;
        } catch (PDOException $e) {
            error_log("Error getting all settings: " . $e->getMessage());
            return [];
        }
    }

    public function updateSetting($key, $value) {
        try {
            // Usar INSERT ... ON DUPLICATE KEY UPDATE para crear o actualizar
            $sql = "INSERT INTO settings (setting_key, setting_value) VALUES (:key, :value) 
                    ON DUPLICATE KEY UPDATE setting_value = :value";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute(['key' => $key, 'value' => $value]);
        } catch (PDOException $e) {
            error_log("Error updating setting: " . $e->getMessage());
            return false;
        }
    }
}
?>