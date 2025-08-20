<?php
class Promotion {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getAll() {
        try {
            $sql = "SELECT promo.*, p.name AS product_name 
                    FROM promotions promo
                    JOIN products p ON promo.product_id = p.id
                    ORDER BY promo.end_date DESC";
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting all promotions: " . $e->getMessage());
            return [];
        }
    }

    public function getActive() {
        try {
            $sql = "SELECT promo.*, p.name AS product_name, p.image_url AS product_image
                    FROM promotions promo
                    JOIN products p ON promo.product_id = p.id
                    WHERE CURDATE() BETWEEN promo.start_date AND promo.end_date
                    ORDER BY promo.created_at DESC";
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting active promotions: " . $e->getMessage());
            return [];
        }
    }

    public function getById($id) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM promotions WHERE id = :id");
            $stmt->execute(['id' => $id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting promotion by ID: " . $e->getMessage());
            return false;
        }
    }

    public function create($data) {
        try {
            $sql = "INSERT INTO promotions (product_id, promo_description, min_quantity, max_quantity, start_date, end_date) 
                    VALUES (:product_id, :promo_description, :min_quantity, :max_quantity, :start_date, :end_date)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($data);
            return $this->db->lastInsertId();
        } catch (PDOException $e) {
            error_log("Error creating promotion: " . $e->getMessage());
            return false;
        }
    }

    public function update($id, $data) {
        try {
            $data['id'] = $id;
            $sql = "UPDATE promotions SET 
                        product_id = :product_id, 
                        promo_description = :promo_description, 
                        min_quantity = :min_quantity, 
                        max_quantity = :max_quantity, 
                        start_date = :start_date, 
                        end_date = :end_date 
                    WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute($data);
        } catch (PDOException $e) {
            error_log("Error updating promotion: " . $e->getMessage());
            return false;
        }
    }

    public function delete($id) {
        try {
            $stmt = $this->db->prepare("DELETE FROM promotions WHERE id = :id");
            return $stmt->execute(['id' => $id]);
        } catch (PDOException $e) {
            error_log("Error deleting promotion: " . $e->getMessage());
            return false;
        }
    }
}
?>