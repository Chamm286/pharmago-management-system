<?php
include_once '../config/database.php';
include_once '../models/Cart.php';
include_once '../models/Product.php';

class CartController {
    private $db;
    private $cart;
    private $product;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->cart = new Cart($this->db);
        $this->product = new Product($this->db);
    }

    public function getCartItems($user_id) {
        $stmt = $this->cart->getCartItems($user_id);
        $cart_items = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $cart_items[] = $row;
        }
        return $cart_items;
    }

    public function addToCart($user_id, $product_id, $quantity = 1) {
        // Check if product exists and is active
        if (!$this->product->getProductById($product_id)) {
            return ['success' => false, 'message' => 'Sản phẩm không tồn tại'];
        }

        // Check stock availability
        if ($this->product->stock_quantity < $quantity) {
            return ['success' => false, 'message' => 'Số lượng sản phẩm trong kho không đủ'];
        }

        $this->cart->user_id = $user_id;
        $this->cart->product_id = $product_id;
        $this->cart->quantity = $quantity;

        if ($this->cart->addToCart()) {
            return ['success' => true, 'message' => 'Đã thêm vào giỏ hàng'];
        } else {
            return ['success' => false, 'message' => 'Thêm vào giỏ hàng thất bại'];
        }
    }

    public function updateCartItem($cart_id, $quantity) {
        if ($quantity <= 0) {
            return $this->removeFromCart($cart_id);
        }

        // Get cart item to check product stock
        $query = "SELECT c.*, p.stock_quantity, p.product_name 
                  FROM cart c 
                  JOIN products p ON c.product_id = p.product_id 
                  WHERE c.cart_id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(1, $cart_id);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $cart_item = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($cart_item['stock_quantity'] < $quantity) {
                return ['success' => false, 'message' => 'Số lượng sản phẩm "' . $cart_item['product_name'] . '" trong kho không đủ'];
            }
        }

        if ($this->cart->updateQuantity($cart_id, $quantity)) {
            return ['success' => true, 'message' => 'Cập nhật giỏ hàng thành công'];
        } else {
            return ['success' => false, 'message' => 'Cập nhật giỏ hàng thất bại'];
        }
    }

    public function removeFromCart($cart_id) {
        if ($this->cart->removeFromCart($cart_id)) {
            return ['success' => true, 'message' => 'Đã xóa sản phẩm khỏi giỏ hàng'];
        } else {
            return ['success' => false, 'message' => 'Xóa sản phẩm thất bại'];
        }
    }

    public function clearCart($user_id) {
        if ($this->cart->clearCart($user_id)) {
            return ['success' => true, 'message' => 'Đã xóa toàn bộ giỏ hàng'];
        } else {
            return ['success' => false, 'message' => 'Xóa giỏ hàng thất bại'];
        }
    }

    public function getCartTotal($user_id) {
        return $this->cart->getCartTotal($user_id);
    }

    public function getCartItemsCount($user_id) {
        return $this->cart->getCartItemsCount($user_id);
    }

    public function validateCart($user_id) {
        $cart_items = $this->getCartItems($user_id);
        $errors = [];

        foreach ($cart_items as $item) {
            if ($item['stock_quantity'] < $item['quantity']) {
                $errors[] = 'Sản phẩm "' . $item['product_name'] . '" chỉ còn ' . $item['stock_quantity'] . ' sản phẩm trong kho';
            }

            if (!$item['is_active']) {
                $errors[] = 'Sản phẩm "' . $item['product_name'] . '" đã ngừng kinh doanh';
            }
        }

        if (count($errors) > 0) {
            return ['success' => false, 'errors' => $errors];
        }

        return ['success' => true];
    }
}
?>