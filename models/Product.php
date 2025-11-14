<?php
class Product {
    private $conn;
    private $table_name = "products";
    public $product_id;
    public $product_name;
    public $product_description;
    public $short_description;
    public $category_id;
    public $manufacturer_id;
    public $sku;
    public $price;
    public $original_price;
    public $stock_quantity;
    public $image_url;
    public $prescription_required;
    public $is_featured;
    public $is_new;
    public $is_best_seller;
    public $is_on_sale;
    public $discount_percent;
    public $sold_count;
    public $average_rating;
    public $review_count;
    public $is_active;
    public $created_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function read() {
        $query = "SELECT p.*, c.category_name, m.manufacturer_name 
                  FROM " . $this->table_name . " p 
                  LEFT JOIN categories c ON p.category_id = c.category_id 
                  LEFT JOIN manufacturers m ON p.manufacturer_id = m.manufacturer_id 
                  WHERE p.is_active = 1 
                  ORDER BY p.created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function readByCategory($category_id) {
        $query = "SELECT p.*, c.category_name, m.manufacturer_name 
                  FROM " . $this->table_name . " p 
                  LEFT JOIN categories c ON p.category_id = c.category_id 
                  LEFT JOIN manufacturers m ON p.manufacturer_id = m.manufacturer_id 
                  WHERE p.category_id = ? AND p.is_active = 1 
                  ORDER BY p.created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $category_id);
        $stmt->execute();
        return $stmt;
    }

    public function getProductById($product_id) {
        $query = "SELECT p.*, c.category_name, m.manufacturer_name 
                  FROM " . $this->table_name . " p 
                  LEFT JOIN categories c ON p.category_id = c.category_id 
                  LEFT JOIN manufacturers m ON p.manufacturer_id = m.manufacturer_id 
                  WHERE p.product_id = ? LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $product_id);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->product_id = $row['product_id'];
            $this->product_name = $row['product_name'];
            $this->product_description = $row['product_description'];
            $this->short_description = $row['short_description'];
            $this->category_id = $row['category_id'];
            $this->manufacturer_id = $row['manufacturer_id'];
            $this->sku = $row['sku'];
            $this->price = $row['price'];
            $this->original_price = $row['original_price'];
            $this->stock_quantity = $row['stock_quantity'];
            $this->image_url = $row['image_url'];
            $this->prescription_required = $row['prescription_required'];
            $this->is_featured = $row['is_featured'];
            $this->is_new = $row['is_new'];
            $this->is_best_seller = $row['is_best_seller'];
            $this->is_on_sale = $row['is_on_sale'];
            $this->discount_percent = $row['discount_percent'];
            $this->sold_count = $row['sold_count'];
            $this->average_rating = $row['average_rating'];
            $this->review_count = $row['review_count'];
            $this->is_active = $row['is_active'];
            return true;
        }
        return false;
    }

    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET product_name=:product_name, product_description=:product_description, 
                      short_description=:short_description, category_id=:category_id, 
                      manufacturer_id=:manufacturer_id, sku=:sku, price=:price, 
                      original_price=:original_price, stock_quantity=:stock_quantity, 
                      image_url=:image_url, prescription_required=:prescription_required, 
                      is_featured=:is_featured, is_new=:is_new, is_best_seller=:is_best_seller, 
                      is_on_sale=:is_on_sale, discount_percent=:discount_percent";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(":product_name", $this->product_name);
        $stmt->bindParam(":product_description", $this->product_description);
        $stmt->bindParam(":short_description", $this->short_description);
        $stmt->bindParam(":category_id", $this->category_id);
        $stmt->bindParam(":manufacturer_id", $this->manufacturer_id);
        $stmt->bindParam(":sku", $this->sku);
        $stmt->bindParam(":price", $this->price);
        $stmt->bindParam(":original_price", $this->original_price);
        $stmt->bindParam(":stock_quantity", $this->stock_quantity);
        $stmt->bindParam(":image_url", $this->image_url);
        $stmt->bindParam(":prescription_required", $this->prescription_required);
        $stmt->bindParam(":is_featured", $this->is_featured);
        $stmt->bindParam(":is_new", $this->is_new);
        $stmt->bindParam(":is_best_seller", $this->is_best_seller);
        $stmt->bindParam(":is_on_sale", $this->is_on_sale);
        $stmt->bindParam(":discount_percent", $this->discount_percent);
        
        if ($stmt->execute()) {
            $this->product_id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    public function update() {
        $query = "UPDATE " . $this->table_name . " 
                  SET product_name=:product_name, product_description=:product_description, 
                      short_description=:short_description, category_id=:category_id, 
                      manufacturer_id=:manufacturer_id, sku=:sku, price=:price, 
                      original_price=:original_price, stock_quantity=:stock_quantity, 
                      image_url=:image_url, prescription_required=:prescription_required, 
                      is_featured=:is_featured, is_new=:is_new, is_best_seller=:is_best_seller, 
                      is_on_sale=:is_on_sale, discount_percent=:discount_percent, 
                      is_active=:is_active 
                  WHERE product_id=:product_id";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(":product_name", $this->product_name);
        $stmt->bindParam(":product_description", $this->product_description);
        $stmt->bindParam(":short_description", $this->short_description);
        $stmt->bindParam(":category_id", $this->category_id);
        $stmt->bindParam(":manufacturer_id", $this->manufacturer_id);
        $stmt->bindParam(":sku", $this->sku);
        $stmt->bindParam(":price", $this->price);
        $stmt->bindParam(":original_price", $this->original_price);
        $stmt->bindParam(":stock_quantity", $this->stock_quantity);
        $stmt->bindParam(":image_url", $this->image_url);
        $stmt->bindParam(":prescription_required", $this->prescription_required);
        $stmt->bindParam(":is_featured", $this->is_featured);
        $stmt->bindParam(":is_new", $this->is_new);
        $stmt->bindParam(":is_best_seller", $this->is_best_seller);
        $stmt->bindParam(":is_on_sale", $this->is_on_sale);
        $stmt->bindParam(":discount_percent", $this->discount_percent);
        $stmt->bindParam(":is_active", $this->is_active);
        $stmt->bindParam(":product_id", $this->product_id);
        
        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE product_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->product_id);
        
        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    public function search($keywords) {
        $query = "SELECT p.*, c.category_name, m.manufacturer_name 
                  FROM " . $this->table_name . " p 
                  LEFT JOIN categories c ON p.category_id = c.category_id 
                  LEFT JOIN manufacturers m ON p.manufacturer_id = m.manufacturer_id 
                  WHERE (p.product_name LIKE ? OR p.product_description LIKE ? OR p.short_description LIKE ?) 
                  AND p.is_active = 1 
                  ORDER BY p.created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $keywords = "%{$keywords}%";
        $stmt->bindParam(1, $keywords);
        $stmt->bindParam(2, $keywords);
        $stmt->bindParam(3, $keywords);
        $stmt->execute();
        return $stmt;
    }

    public function getFeaturedProducts($limit = 6) {
        $query = "SELECT p.*, c.category_name, m.manufacturer_name 
                  FROM " . $this->table_name . " p 
                  LEFT JOIN categories c ON p.category_id = c.category_id 
                  LEFT JOIN manufacturers m ON p.manufacturer_id = m.manufacturer_id 
                  WHERE p.is_featured = 1 AND p.is_active = 1 
                  ORDER BY p.created_at DESC 
                  LIMIT :limit";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt;
    }

    public function getBestSellerProducts($limit = 8) {
        $query = "SELECT p.*, c.category_name, m.manufacturer_name 
                  FROM " . $this->table_name . " p 
                  LEFT JOIN categories c ON p.category_id = c.category_id 
                  LEFT JOIN manufacturers m ON p.manufacturer_id = m.manufacturer_id 
                  WHERE p.is_best_seller = 1 AND p.is_active = 1 
                  ORDER BY p.sold_count DESC 
                  LIMIT :limit";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt;
    }

    // ==================== CÁC PHƯƠNG THỨC MỚI CHO ABOUT.PHP ====================

    /**
     * Lấy tổng số sản phẩm
     */
    public function getTotalProducts() {
        $query = "SELECT COUNT(*) as total FROM products WHERE is_active = 1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'] ?? 0;
    }

    /**
     * Lấy sản phẩm bán chạy (dựa trên sold_count)
     */
    public function getBestSellers($limit = 3) {
        $query = "SELECT * FROM products 
                  WHERE is_active = 1 AND sold_count > 0 
                  ORDER BY sold_count DESC 
                  LIMIT :limit";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Lấy sản phẩm mới nhất
     */
    public function getNewProducts($limit = 4) {
        $query = "SELECT * FROM products 
                  WHERE is_active = 1 
                  ORDER BY created_at DESC 
                  LIMIT :limit";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Lấy sản phẩm đang giảm giá
     */
    public function getDiscountedProducts($limit = 4) {
        $query = "SELECT * FROM products 
                  WHERE is_active = 1 AND is_on_sale = 1 AND discount_percent > 0 
                  ORDER BY discount_percent DESC 
                  LIMIT :limit";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Cập nhật số lượng đã bán
     */
    public function updateSoldCount($product_id, $quantity) {
        $query = "UPDATE products SET sold_count = sold_count + :quantity WHERE product_id = :product_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':quantity', $quantity);
        $stmt->bindParam(':product_id', $product_id);
        return $stmt->execute();
    }

    /**
     * Cập nhật rating trung bình
     */
    public function updateAverageRating($product_id) {
        $query = "UPDATE products p 
                  SET average_rating = (
                      SELECT AVG(rating) FROM product_reviews 
                      WHERE product_id = :product_id AND is_approved = 1
                  ),
                  review_count = (
                      SELECT COUNT(*) FROM product_reviews 
                      WHERE product_id = :product_id AND is_approved = 1
                  )
                  WHERE product_id = :product_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':product_id', $product_id);
        return $stmt->execute();
    }

    // ==================== CÁC PHƯƠNG THỨC CÒN THIẾU ====================

    /**
     * Lấy sản phẩm theo manufacturer (nhà sản xuất)
     */
    public function getProductsByManufacturer($manufacturer_id, $limit = 10) {
        $query = "SELECT p.*, c.category_name, m.manufacturer_name 
                  FROM " . $this->table_name . " p 
                  LEFT JOIN categories c ON p.category_id = c.category_id 
                  LEFT JOIN manufacturers m ON p.manufacturer_id = m.manufacturer_id 
                  WHERE p.manufacturer_id = :manufacturer_id AND p.is_active = 1 
                  ORDER BY p.created_at DESC 
                  LIMIT :limit";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':manufacturer_id', $manufacturer_id);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt;
    }

    /**
     * Lấy sản phẩm đang giảm giá (có join với category và manufacturer)
     */
    public function getDiscountedProductsWithDetails($limit = 8) {
        $query = "SELECT p.*, c.category_name, m.manufacturer_name 
                  FROM " . $this->table_name . " p 
                  LEFT JOIN categories c ON p.category_id = c.category_id 
                  LEFT JOIN manufacturers m ON p.manufacturer_id = m.manufacturer_id 
                  WHERE p.is_on_sale = 1 AND p.discount_percent > 0 AND p.is_active = 1 
                  ORDER BY p.discount_percent DESC 
                  LIMIT :limit";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt;
    }

    /**
     * Lấy sản phẩm mới nhất (có join với category và manufacturer)
     */
    public function getNewProductsWithDetails($limit = 8) {
        $query = "SELECT p.*, c.category_name, m.manufacturer_name 
                  FROM " . $this->table_name . " p 
                  LEFT JOIN categories c ON p.category_id = c.category_id 
                  LEFT JOIN manufacturers m ON p.manufacturer_id = m.manufacturer_id 
                  WHERE p.is_active = 1 
                  ORDER BY p.created_at DESC 
                  LIMIT :limit";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt;
    }

    /**
     * Tìm kiếm nâng cao với filter
     */
    public function advancedSearch($keywords, $category_id = null, $min_price = null, $max_price = null, $sort_by = 'created_at', $sort_order = 'DESC') {
        $query = "SELECT p.*, c.category_name, m.manufacturer_name 
                  FROM " . $this->table_name . " p 
                  LEFT JOIN categories c ON p.category_id = c.category_id 
                  LEFT JOIN manufacturers m ON p.manufacturer_id = m.manufacturer_id 
                  WHERE (p.product_name LIKE :keywords OR p.product_description LIKE :keywords OR p.short_description LIKE :keywords) 
                  AND p.is_active = 1";
        
        $params = [':keywords' => "%{$keywords}%"];

        if ($category_id) {
            $query .= " AND p.category_id = :category_id";
            $params[':category_id'] = $category_id;
        }

        if ($min_price !== null) {
            $query .= " AND p.price >= :min_price";
            $params[':min_price'] = $min_price;
        }

        if ($max_price !== null) {
            $query .= " AND p.price <= :max_price";
            $params[':max_price'] = $max_price;
        }

        // Validate sort column to prevent SQL injection
        $allowed_sort_columns = ['price', 'created_at', 'product_name', 'sold_count', 'average_rating'];
        $sort_by = in_array($sort_by, $allowed_sort_columns) ? $sort_by : 'created_at';
        $sort_order = strtoupper($sort_order) === 'ASC' ? 'ASC' : 'DESC';

        $query .= " ORDER BY p.{$sort_by} {$sort_order}";

        $stmt = $this->conn->prepare($query);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->execute();
        return $stmt;
    }

    /**
     * Lấy sản phẩm liên quan (cùng category)
     */
    public function getRelatedProducts($product_id, $category_id, $limit = 4) {
        $query = "SELECT p.*, c.category_name, m.manufacturer_name 
                  FROM " . $this->table_name . " p 
                  LEFT JOIN categories c ON p.category_id = c.category_id 
                  LEFT JOIN manufacturers m ON p.manufacturer_id = m.manufacturer_id 
                  WHERE p.category_id = :category_id AND p.product_id != :product_id AND p.is_active = 1 
                  ORDER BY p.sold_count DESC, p.created_at DESC 
                  LIMIT :limit";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':category_id', $category_id);
        $stmt->bindParam(':product_id', $product_id);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt;
    }

    /**
     * Cập nhật lượt xem sản phẩm
     */
    public function incrementViewCount($product_id) {
        $query = "UPDATE products SET view_count = view_count + 1 WHERE product_id = :product_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':product_id', $product_id);
        return $stmt->execute();
    }

    /**
     * Lấy sản phẩm có lượt xem cao nhất
     */
    public function getMostViewedProducts($limit = 5) {
        $query = "SELECT p.*, c.category_name, m.manufacturer_name 
                  FROM " . $this->table_name . " p 
                  LEFT JOIN categories c ON p.category_id = c.category_id 
                  LEFT JOIN manufacturers m ON p.manufacturer_id = m.manufacturer_id 
                  WHERE p.is_active = 1 AND p.view_count > 0 
                  ORDER BY p.view_count DESC 
                  LIMIT :limit";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt;
    }

    /**
     * Kiểm tra SKU đã tồn tại chưa (cho create/update)
     */
    public function skuExists($sku, $exclude_product_id = null) {
        $query = "SELECT product_id FROM " . $this->table_name . " WHERE sku = :sku";
        
        if ($exclude_product_id) {
            $query .= " AND product_id != :exclude_product_id";
        }
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':sku', $sku);
        
        if ($exclude_product_id) {
            $stmt->bindParam(':exclude_product_id', $exclude_product_id);
        }
        
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    /**
     * Lấy số lượng sản phẩm trong kho
     */
    public function getStockQuantity($product_id) {
        $query = "SELECT stock_quantity FROM " . $this->table_name . " WHERE product_id = :product_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':product_id', $product_id);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row['stock_quantity'];
        }
        return 0;
    }

    /**
     * Cập nhật số lượng tồn kho
     */
    public function updateStockQuantity($product_id, $new_quantity) {
        $query = "UPDATE products SET stock_quantity = :quantity WHERE product_id = :product_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':quantity', $new_quantity);
        $stmt->bindParam(':product_id', $product_id);
        return $stmt->execute();
    }

    /**
     * Lấy thống kê sản phẩm
     */
    public function getProductStatistics() {
        $query = "SELECT 
                    COUNT(*) as total_products,
                    SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_products,
                    SUM(CASE WHEN is_featured = 1 THEN 1 ELSE 0 END) as featured_products,
                    SUM(CASE WHEN is_on_sale = 1 THEN 1 ELSE 0 END) as on_sale_products,
                    SUM(CASE WHEN stock_quantity = 0 THEN 1 ELSE 0 END) as out_of_stock_products,
                    AVG(price) as average_price,
                    SUM(sold_count) as total_sold
                  FROM " . $this->table_name;
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Lấy sản phẩm sắp hết hàng (dưới mức tối thiểu)
     */
    public function getLowStockProducts($threshold = 10) {
        $query = "SELECT p.*, c.category_name, m.manufacturer_name 
                  FROM " . $this->table_name . " p 
                  LEFT JOIN categories c ON p.category_id = c.category_id 
                  LEFT JOIN manufacturers m ON p.manufacturer_id = m.manufacturer_id 
                  WHERE p.stock_quantity <= :threshold AND p.is_active = 1 
                  ORDER BY p.stock_quantity ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':threshold', $threshold, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt;
    }

    /**
     * Lấy tất cả sản phẩm với phân trang
     */
    public function readPaging($page = 1, $records_per_page = 10) {
        $offset = ($page - 1) * $records_per_page;
        
        $query = "SELECT p.*, c.category_name, m.manufacturer_name 
                  FROM " . $this->table_name . " p 
                  LEFT JOIN categories c ON p.category_id = c.category_id 
                  LEFT JOIN manufacturers m ON p.manufacturer_id = m.manufacturer_id 
                  WHERE p.is_active = 1 
                  ORDER BY p.created_at DESC 
                  LIMIT :offset, :records_per_page";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindParam(':records_per_page', $records_per_page, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt;
    }

    /**
     * Đếm tổng số trang cho phân trang
     */
    public function count() {
        $query = "SELECT COUNT(*) as total_rows FROM " . $this->table_name . " WHERE is_active = 1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total_rows'];
    }

    /**
     * Lấy sản phẩm random (cho recommendations)
     */
    public function getRandomProducts($limit = 4) {
        $query = "SELECT p.*, c.category_name, m.manufacturer_name 
                  FROM " . $this->table_name . " p 
                  LEFT JOIN categories c ON p.category_id = c.category_id 
                  LEFT JOIN manufacturers m ON p.manufacturer_id = m.manufacturer_id 
                  WHERE p.is_active = 1 
                  ORDER BY RAND() 
                  LIMIT :limit";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt;
    }
}
?>