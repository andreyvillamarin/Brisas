<?php

class Category {
    private $db;

    public function __construct() {
        // Usamos nuestra clase Database para obtener la conexión PDO
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Obtiene todas las categorías de la base de datos.
     * @return array Un array de categorías.
     */
    public function getAllCategories() {
        try {
            $stmt = $this->db->query("SELECT * FROM categories ORDER BY name ASC");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            // En una aplicación real, esto debería ser registrado en un log.
            error_log("Error al obtener categorías: " . $e->getMessage());
            return []; // Devolver un array vacío en caso de error.
        }
    }

    public function getById($id) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM categories WHERE id = :id");
            $stmt->execute(['id' => $id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching category by ID: " . $e->getMessage());
            return false;
        }
    }

    public function create($name, $imageUrl) {
        try {
            $stmt = $this->db->prepare("INSERT INTO categories (name, image_url) VALUES (:name, :image_url)");
            $stmt->execute(['name' => $name, 'image_url' => $imageUrl]);
            return $this->db->lastInsertId();
        } catch (PDOException $e) {
            error_log("Error creating category: " . $e->getMessage());
            return false;
        }
    }

    public function update($id, $name, $imageUrl = null) {
        try {
            $sql = "UPDATE categories SET name = :name";
            $params = ['id' => $id, 'name' => $name];

            if ($imageUrl) {
                $sql .= ", image_url = :image_url";
                $params['image_url'] = $imageUrl;
            }

            $sql .= " WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log("Error updating category: " . $e->getMessage());
            return false;
        }
    }

    public function delete($id) {
        try {
            $stmt = $this->db->prepare("DELETE FROM categories WHERE id = :id");
            return $stmt->execute(['id' => $id]);
        } catch (PDOException $e) {
            error_log("Error deleting category: " . $e->getMessage());
            return false;
        }
    }
}
?>