<?php
class Cart {
    private $conn;
    private $table_name = "cart";

    public $cart_id;
    public $user_id;
    public $product_id;
    public $quantity;
    public $added_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getCartItems($user_id) {
        $query = "SELECT c.*, p.product_name, p.price, p.image_url, p.stock_quantity, 
                         (p.price * c.quantity) as item_total
                  FROM " . $this->table_name . " c
                  JOIN products p ON c.product_id = p.product_id
                  WHERE c.user_id = ? AND p.is_active = 1
                  ORDER BY c.added_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $user_id);
        $stmt->execute();
        return $stmt;
    }

    public function addToCart() {
        // Check if item already exists in cart
        $check_query = "SELECT cart_id, quantity FROM " . $this->table_name . " 
                        WHERE user_id = ? AND product_id = ?";
        $check_stmt = $this->conn->prepare($check_query);
        $check_stmt->bindParam(1, $this->user_id);
        $check_stmt->bindParam(2, $this->product_id);
        $check_stmt->execute();

        if ($check_stmt->rowCount() > 0) {
            // Update quantity if item exists
            $row = $check_stmt->fetch(PDO::FETCH_ASSOC);
            $new_quantity = $row['quantity'] + $this->quantity;
            
            $update_query = "UPDATE " . $this->table_name . " 
                             SET quantity = ? 
                             WHERE cart_id = ?";
            $update_stmt = $this->conn->prepare($update_query);
            $update_stmt->bindParam(1, $new_quantity);
            $update_stmt->bindParam(2, $row['cart_id']);
            
            return $update_stmt->execute();
        } else {
            // Insert new item
            $insert_query = "INSERT INTO " . $this->table_name . " 
                             (user_id, product_id, quantity) 
                             VALUES (?, ?, ?)";
            $insert_stmt = $this->conn->prepare($insert_query);
            $insert_stmt->bindParam(1, $this->user_id);
            $insert_stmt->bindParam(2, $this->product_id);
            $insert_stmt->bindParam(3, $this->quantity);
            
            if ($insert_stmt->execute()) {
                $this->cart_id = $this->conn->lastInsertId();
                return true;
            }
            return false;
        }
    }

    public function updateQuantity($cart_id, $quantity) {
        $query = "UPDATE " . $this->table_name . " 
                  SET quantity = ? 
                  WHERE cart_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $quantity);
        $stmt->bindParam(2, $cart_id);
        
        return $stmt->execute();
    }

    public function removeFromCart($cart_id) {
        $query = "DELETE FROM " . $this->table_name . " WHERE cart_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $cart_id);
        
        return $stmt->execute();
    }

    public function clearCart($user_id) {
        $query = "DELETE FROM " . $this->table_name . " WHERE user_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $user_id);
        
        return $stmt->execute();
    }

    public function getCartTotal($user_id) {
        $query = "SELECT SUM(p.price * c.quantity) as cart_total
                  FROM " . $this->table_name . " c
                  JOIN products p ON c.product_id = p.product_id
                  WHERE c.user_id = ? AND p.is_active = 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $user_id);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['cart_total'] ? $row['cart_total'] : 0;
    }

    public function getCartItemsCount($user_id) {
        $query = "SELECT SUM(quantity) as total_items 
                  FROM " . $this->table_name . " 
                  WHERE user_id = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $user_id);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total_items'] ? $row['total_items'] : 0;
    }
}
?>