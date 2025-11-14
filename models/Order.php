<?php
class Order {
    private $conn;
    private $table_name = "orders";

    public $order_id;
    public $order_code;
    public $user_id;
    public $customer_name;
    public $customer_email;
    public $customer_phone;
    public $customer_address;
    public $order_notes;
    public $subtotal_amount;
    public $discount_amount;
    public $shipping_fee;
    public $total_amount;
    public $payment_method;
    public $payment_status;
    public $order_status;
    public $created_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create() {
        // Generate order code
        $this->order_code = 'PHARM' . date('Ymd') . str_pad(rand(0, 999), 3, '0', STR_PAD_LEFT);
        
        $query = "INSERT INTO " . $this->table_name . " 
                  SET order_code=:order_code, user_id=:user_id, customer_name=:customer_name, 
                      customer_email=:customer_email, customer_phone=:customer_phone, 
                      customer_address=:customer_address, order_notes=:order_notes, 
                      subtotal_amount=:subtotal_amount, discount_amount=:discount_amount, 
                      shipping_fee=:shipping_fee, total_amount=:total_amount, 
                      payment_method=:payment_method, payment_status=:payment_status, 
                      order_status=:order_status";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(":order_code", $this->order_code);
        $stmt->bindParam(":user_id", $this->user_id);
        $stmt->bindParam(":customer_name", $this->customer_name);
        $stmt->bindParam(":customer_email", $this->customer_email);
        $stmt->bindParam(":customer_phone", $this->customer_phone);
        $stmt->bindParam(":customer_address", $this->customer_address);
        $stmt->bindParam(":order_notes", $this->order_notes);
        $stmt->bindParam(":subtotal_amount", $this->subtotal_amount);
        $stmt->bindParam(":discount_amount", $this->discount_amount);
        $stmt->bindParam(":shipping_fee", $this->shipping_fee);
        $stmt->bindParam(":total_amount", $this->total_amount);
        $stmt->bindParam(":payment_method", $this->payment_method);
        $stmt->bindParam(":payment_status", $this->payment_status);
        $stmt->bindParam(":order_status", $this->order_status);
        
        if ($stmt->execute()) {
            $this->order_id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    public function getOrdersByUser($user_id) {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE user_id = ? 
                  ORDER BY created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $user_id);
        $stmt->execute();
        return $stmt;
    }

    public function getAllOrders() {
        $query = "SELECT o.*, u.full_name as customer_name, u.email as customer_email, u.phone as customer_phone 
                  FROM " . $this->table_name . " o
                  LEFT JOIN users u ON o.user_id = u.user_id
                  ORDER BY o.created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getOrderById($order_id) {
        $query = "SELECT o.*, u.full_name, u.username 
                  FROM " . $this->table_name . " o
                  LEFT JOIN users u ON o.user_id = u.user_id
                  WHERE o.order_id = ? LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $order_id);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->order_id = $row['order_id'];
            $this->order_code = $row['order_code'];
            $this->user_id = $row['user_id'];
            $this->customer_name = $row['customer_name'];
            $this->customer_email = $row['customer_email'];
            $this->customer_phone = $row['customer_phone'];
            $this->customer_address = $row['customer_address'];
            $this->order_notes = $row['order_notes'];
            $this->subtotal_amount = $row['subtotal_amount'];
            $this->discount_amount = $row['discount_amount'];
            $this->shipping_fee = $row['shipping_fee'];
            $this->total_amount = $row['total_amount'];
            $this->payment_method = $row['payment_method'];
            $this->payment_status = $row['payment_status'];
            $this->order_status = $row['order_status'];
            return true;
        }
        return false;
    }

    public function updateStatus($order_id, $order_status, $payment_status = null) {
        $query = "UPDATE " . $this->table_name . " 
                  SET order_status = :order_status";
        
        if ($payment_status) {
            $query .= ", payment_status = :payment_status";
        }
        
        $query .= " WHERE order_id = :order_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":order_status", $order_status);
        if ($payment_status) {
            $stmt->bindParam(":payment_status", $payment_status);
        }
        $stmt->bindParam(":order_id", $order_id);
        
        return $stmt->execute();
    }

    public function getOrderItems($order_id) {
        $query = "SELECT oi.*, p.image_url, p.product_name
                  FROM order_items oi
                  LEFT JOIN products p ON oi.product_id = p.product_id
                  WHERE oi.order_id = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $order_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getOrderStats() {
        $query = "SELECT 
                    COUNT(*) as total_orders,
                    SUM(total_amount) as total_revenue,
                    AVG(total_amount) as avg_order_value,
                    COUNT(CASE WHEN order_status = 'delivered' THEN 1 END) as completed_orders,
                    COUNT(CASE WHEN order_status = 'pending' THEN 1 END) as pending_orders
                  FROM " . $this->table_name;
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // CÁC PHƯƠNG THỨC MỚI CẦN THIẾT CHO ADMIN
    public function getTotalOrders() {
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name;
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'];
    }

    public function getPendingOrdersCount() {
        $query = "SELECT COUNT(*) as pending_count FROM " . $this->table_name . " WHERE order_status = 'pending'";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['pending_count'];
    }

    public function getRevenueToday() {
        $query = "SELECT SUM(total_amount) as revenue_today 
                  FROM " . $this->table_name . " 
                  WHERE DATE(created_at) = CURDATE() 
                  AND order_status IN ('delivered', 'shipped', 'processing')";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['revenue_today'] ? $result['revenue_today'] : 0;
    }

    public function getRevenueThisMonth() {
        $query = "SELECT SUM(total_amount) as revenue_month 
                  FROM " . $this->table_name . " 
                  WHERE MONTH(created_at) = MONTH(CURDATE()) 
                  AND YEAR(created_at) = YEAR(CURDATE())
                  AND order_status IN ('delivered', 'shipped', 'processing')";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['revenue_month'] ? $result['revenue_month'] : 0;
    }

    public function getRecentOrders($limit = 10) {
        $query = "SELECT o.*, u.full_name as customer_name 
                  FROM " . $this->table_name . " o
                  LEFT JOIN users u ON o.user_id = u.user_id
                  ORDER BY o.created_at DESC 
                  LIMIT :limit";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getOrdersByStatus($status) {
        $query = "SELECT o.*, u.full_name as customer_name 
                  FROM " . $this->table_name . " o
                  LEFT JOIN users u ON o.user_id = u.user_id
                  WHERE o.order_status = :status
                  ORDER BY o.created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':status', $status);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function searchOrders($keyword) {
        $query = "SELECT o.*, u.full_name as customer_name 
                  FROM " . $this->table_name . " o
                  LEFT JOIN users u ON o.user_id = u.user_id
                  WHERE o.order_code LIKE :keyword 
                     OR o.customer_name LIKE :keyword 
                     OR o.customer_phone LIKE :keyword 
                     OR o.customer_email LIKE :keyword
                  ORDER BY o.created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $keyword = "%$keyword%";
        $stmt->bindParam(':keyword', $keyword);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function deleteOrder($order_id) {
        // First delete order items
        $query_items = "DELETE FROM order_items WHERE order_id = :order_id";
        $stmt_items = $this->conn->prepare($query_items);
        $stmt_items->bindParam(':order_id', $order_id);
        $stmt_items->execute();

        // Then delete the order
        $query = "DELETE FROM " . $this->table_name . " WHERE order_id = :order_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':order_id', $order_id);
        
        return $stmt->execute();
    }

    public function getMonthlyRevenue($year = null) {
        if (!$year) {
            $year = date('Y');
        }
        
        $query = "SELECT 
                    MONTH(created_at) as month,
                    SUM(total_amount) as revenue,
                    COUNT(*) as order_count
                  FROM " . $this->table_name . " 
                  WHERE YEAR(created_at) = :year 
                  AND order_status IN ('delivered', 'shipped', 'processing')
                  GROUP BY MONTH(created_at)
                  ORDER BY month";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':year', $year);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>