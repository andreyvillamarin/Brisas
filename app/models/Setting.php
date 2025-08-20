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
            // Check if the setting exists first
            $stmt = $this->db->prepare("SELECT setting_key FROM settings WHERE setting_key = :key");
            $stmt->execute(['key' => $key]);

            if ($stmt->fetch()) {
                // If it exists, UPDATE it
                $updateStmt = $this->db->prepare("UPDATE settings SET setting_value = :value WHERE setting_key = :key");
                return $updateStmt->execute(['key' => $key, 'value' => $value]);
            } else {
                // If it does not exist, INSERT it
                $insertStmt = $this->db->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (:key, :value)");
                return $insertStmt->execute(['key' => $key, 'value' => $value]);
            }
        } catch (PDOException $e) {
            error_log("Error updating setting '{$key}': " . $e->getMessage());
            return false;
        }
    }
}
?>