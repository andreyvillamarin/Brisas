<?php
class Order {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getOrdersByDate($date) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM orders WHERE DATE(created_at) = :order_date ORDER BY created_at DESC");
            $stmt->execute(['order_date' => $date]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting orders by date: " . $e->getMessage());
            return [];
        }
    }

    public function getOrdersBy($filters) {
        try {
            $sql = "SELECT * FROM orders";
            $whereClauses = [];
            $params = [];

            if (!empty($filters['status'])) {
                $whereClauses[] = "status = :status";
                $params[':status'] = $filters['status'];
            }
            if (!empty($filters['customer_type'])) {
                $whereClauses[] = "customer_type = :customer_type";
                $params[':customer_type'] = $filters['customer_type'];
            }

            if (!empty($whereClauses)) {
                $sql .= " WHERE " . implode(' AND ', $whereClauses);
            }
            
            $sql .= " ORDER BY created_at DESC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error getting orders by filter: " . $e->getMessage());
            return [];
        }
    }

    public function getOrderWithItems($orderId) {
        $order = [];
        try {
            // Obtener datos del pedido
            $stmt = $this->db->prepare("SELECT * FROM orders WHERE id = :id");
            $stmt->execute(['id' => $orderId]);
            $order['details'] = $stmt->fetch(PDO::FETCH_ASSOC);

            // Obtener productos del pedido
            $sqlItems = "SELECT oi.quantity, p.name, p.image_url 
                         FROM order_items oi 
                         JOIN products p ON oi.product_id = p.id 
                         WHERE oi.order_id = :order_id";
            $stmtItems = $this->db->prepare($sqlItems);
            $stmtItems->execute(['order_id' => $orderId]);
            $order['items'] = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

            return $order;
        } catch (PDOException $e) {
            error_log("Error getting order with items: " . $e->getMessage());
            return false;
        }
    }

    public function searchOrders($searchTerm) {
        try {
            $sql = "SELECT * FROM orders 
                    WHERE customer_name LIKE :term 
                    OR customer_city LIKE :term 
                    OR customer_id_number LIKE :term
                    ORDER BY created_at DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['term' => '%' . $searchTerm . '%']);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error searching orders: " . $e->getMessage());
            return [];
        }
    }

    public function createOrder($data) {
        try {
            $this->db->beginTransaction();

            $sql = "INSERT INTO orders (customer_type, customer_name, customer_id_number, customer_city, customer_email, mercaderista_supermarket, status) 
                    VALUES (:customer_type, :customer_name, :customer_id_number, :customer_city, :customer_email, :mercaderista_supermarket, :status)";
            
            $stmt = $this->db->prepare($sql);
            
            $customerName = $data['customer_name'] ?? ($data['mercaderista_name'] ?? 'N/A');
            $customerIdNumber = $data['customer_id_number'] ?? 'N/A';
            
            $stmt->execute([
                ':customer_type' => $data['customer_type'],
                ':customer_name' => $customerName,
                ':customer_id_number' => $customerIdNumber,
                ':customer_city' => $data['customer_city'],
                ':customer_email' => $data['customer_email'] ?: null,
                ':mercaderista_supermarket' => $data['mercaderista_supermarket'] ?? null,
                ':status' => $data['status'] ?? 'pending'
            ]);

            $orderId = $this->db->lastInsertId();

            $sqlItems = "INSERT INTO order_items (order_id, product_id, quantity) VALUES (:order_id, :product_id, :quantity)";
            $stmtItems = $this->db->prepare($sqlItems);

            foreach ($data['products'] as $product) {
                if (!empty($product['id']) && !empty($product['quantity'])) {
                    $stmtItems->execute([
                        ':order_id' => $orderId,
                        ':product_id' => $product['id'],
                        ':quantity' => $product['quantity']
                    ]);
                }
            }

            $this->db->commit();
            return $orderId;

        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Error creating manual order: " . $e->getMessage());
            return false;
        }
    }
}
?>