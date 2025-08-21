<?php
class Product {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getAll() {
        try {
            // Unimos la tabla de productos con la de categorías para obtener el nombre de la categoría
            $sql = "SELECT p.*, c.name AS category_name 
                    FROM products p 
                    LEFT JOIN categories c ON p.category_id = c.id 
                    ORDER BY p.name ASC";
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching all products: " . $e->getMessage());
            return [];
        }
    }

    public function getById($id) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM products WHERE id = :id");
            $stmt->execute(['id' => $id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching product by ID: " . $e->getMessage());
            return false;
        }
    }

    public function create($name, $categoryId, $imageUrl) {
        try {
            $stmt = $this->db->prepare("INSERT INTO products (name, category_id, image_url) VALUES (:name, :category_id, :image_url)");
            $stmt->execute(['name' => $name, 'category_id' => $categoryId, 'image_url' => $imageUrl]);
            return $this->db->lastInsertId();
        } catch (PDOException $e) {
            error_log("Error creating product: " . $e->getMessage());
            return false;
        }
    }

    public function update($id, $name, $categoryId, $imageUrl = null) {
        try {
            $sql = "UPDATE products SET name = :name, category_id = :category_id";
            $params = ['id' => $id, 'name' => $name, 'category_id' => $categoryId];

            if ($imageUrl) {
                $sql .= ", image_url = :image_url";
                $params['image_url'] = $imageUrl;
            }

            $sql .= " WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log("Error updating product: " . $e->getMessage());
            return false;
        }
    }

    public function searchProducts($term) {
        try {
            $sql = "SELECT p.*, c.name AS category_name 
                    FROM products p 
                    LEFT JOIN categories c ON p.category_id = c.id 
                    WHERE p.name LIKE :term
                    ORDER BY p.name ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['term' => '%' . $term . '%']);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error searching products: " . $e->getMessage());
            return [];
        }
    }

    public function delete($id) {
        try {
            $stmt = $this->db->prepare("DELETE FROM products WHERE id = :id");
            return $stmt->execute(['id' => $id]);
        } catch (PDOException $e) {
            error_log("Error deleting product: " . $e->getMessage());
            return false;
        }
    }
}
?>