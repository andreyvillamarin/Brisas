<?php
class Analytics {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    private function getReportData($sql, $params) {
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Analytics error: " . $e->getMessage());
            return [];
        }
    }

    public function getTopProducts($startDate, $endDate, $limit = 10, $order = 'DESC') {
        $sql = "SELECT p.name, SUM(oi.quantity) as total_sold
                FROM order_items oi
                JOIN products p ON oi.product_id = p.id
                JOIN orders o ON oi.order_id = o.id
                WHERE o.created_at BETWEEN :start_date AND :end_date
                GROUP BY p.name
                ORDER BY total_sold $order
                LIMIT :limit";
        return $this->getReportData($sql, ['start_date' => $startDate, 'end_date' => $endDate, ':limit' => $limit]);
    }

    public function getTopCustomers($startDate, $endDate, $limit = 10, $order = 'DESC') {
        $sql = "SELECT customer_name, COUNT(id) as total_orders
                FROM orders
                WHERE created_at BETWEEN :start_date AND :end_date
                GROUP BY customer_name
                ORDER BY total_orders $order
                LIMIT :limit";
        return $this->getReportData($sql, ['start_date' => $startDate, 'end_date' => $endDate, ':limit' => $limit]);
    }
    
    public function getOrdersByDay($startDate, $endDate) {
        $sql = "SELECT DATE(created_at) as order_day, COUNT(id) as total_orders
                FROM orders
                WHERE created_at BETWEEN :start_date AND :end_date
                GROUP BY order_day
                ORDER BY order_day ASC";
        return $this->getReportData($sql, ['start_date' => $startDate, 'end_date' => $endDate]);
    }

    public function getOrdersByCategory($startDate, $endDate) {
        $sql = "SELECT c.name, SUM(oi.quantity) as total_quantity
                FROM order_items oi
                JOIN products p ON oi.product_id = p.id
                JOIN categories c ON p.category_id = c.id
                JOIN orders o ON oi.order_id = o.id
                WHERE o.created_at BETWEEN :start_date AND :end_date
                GROUP BY c.name
                ORDER BY total_quantity DESC";
        return $this->getReportData($sql, ['start_date' => $startDate, 'end_date' => $endDate]);
    }

    public function getOrdersPerDayOfMonth($month) {
        $year = date('Y', strtotime($month));
        $month_num = date('m', strtotime($month));
        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month_num, $year);

        // Initialize array with all days of the month
        $result = [];
        for ($day = 1; $day <= $daysInMonth; $day++) {
            $result[$day] = [
                'order_day' => $year . '-' . $month_num . '-' . str_pad($day, 2, '0', STR_PAD_LEFT),
                'total_orders' => 0
            ];
        }

        $sql = "SELECT DAY(created_at) as day, COUNT(id) as total_orders
                FROM orders
                WHERE YEAR(created_at) = :year AND MONTH(created_at) = :month_num
                GROUP BY day
                ORDER BY day ASC";
        
        $params = [':year' => $year, ':month_num' => $month_num];
        $data = $this->getReportData($sql, $params);

        // Merge database data into the result array
        foreach ($data as $row) {
            $day = (int)$row['day'];
            $result[$day]['total_orders'] = (int)$row['total_orders'];
        }

        return array_values($result);
    }
}
?>
