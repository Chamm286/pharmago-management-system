<?php
class Category {
    private $conn;
    private $table_name = "categories";

    public $category_id;
    public $category_name;
    public $category_description;
    public $image_url;
    public $parent_id;
    public $icon_class;
    public $display_order;
    public $is_active;
    public $created_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function read() {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE is_active = 1 
                  ORDER BY display_order ASC, category_name ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function getAllCategories() {
        $query = "SELECT * FROM " . $this->table_name . " 
                  ORDER BY display_order ASC, category_name ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function getCategoryById($category_id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE category_id = ? LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $category_id);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->category_id = $row['category_id'];
            $this->category_name = $row['category_name'];
            $this->category_description = $row['category_description'];
            $this->image_url = $row['image_url'];
            $this->parent_id = $row['parent_id'];
            $this->icon_class = $row['icon_class'];
            $this->display_order = $row['display_order'];
            $this->is_active = $row['is_active'];
            return true;
        }
        return false;
    }

    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET category_name=:category_name, category_description=:category_description, 
                      image_url=:image_url, parent_id=:parent_id, icon_class=:icon_class, 
                      display_order=:display_order, is_active=:is_active";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(":category_name", $this->category_name);
        $stmt->bindParam(":category_description", $this->category_description);
        $stmt->bindParam(":image_url", $this->image_url);
        $stmt->bindParam(":parent_id", $this->parent_id);
        $stmt->bindParam(":icon_class", $this->icon_class);
        $stmt->bindParam(":display_order", $this->display_order);
        $stmt->bindParam(":is_active", $this->is_active);
        
        if ($stmt->execute()) {
            $this->category_id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    public function update() {
        $query = "UPDATE " . $this->table_name . " 
                  SET category_name=:category_name, category_description=:category_description, 
                      image_url=:image_url, parent_id=:parent_id, icon_class=:icon_class, 
                      display_order=:display_order, is_active=:is_active 
                  WHERE category_id=:category_id";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(":category_name", $this->category_name);
        $stmt->bindParam(":category_description", $this->category_description);
        $stmt->bindParam(":image_url", $this->image_url);
        $stmt->bindParam(":parent_id", $this->parent_id);
        $stmt->bindParam(":icon_class", $this->icon_class);
        $stmt->bindParam(":display_order", $this->display_order);
        $stmt->bindParam(":is_active", $this->is_active);
        $stmt->bindParam(":category_id", $this->category_id);
        
        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE category_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->category_id);
        
        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    public function getProductsCount($category_id) {
        $query = "SELECT COUNT(*) as total_products FROM products WHERE category_id = ? AND is_active = 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $category_id);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total_products'];
    }

    // ==================== CÁC PHƯƠNG THỨC MỚI CHO ABOUT.PHP ====================

    /**
     * Lấy tổng số danh mục (cho about.php)
     */
    public function getTotalCategories() {
        $query = "SELECT COUNT(*) as total FROM categories WHERE is_active = 1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'] ?? 0;
    }

    /**
     * Lấy danh mục có nhiều sản phẩm nhất
     */
    public function getPopularCategories($limit = 6) {
        $query = "SELECT c.*, COUNT(p.product_id) as product_count 
                  FROM categories c 
                  LEFT JOIN products p ON c.category_id = p.category_id AND p.is_active = 1 
                  WHERE c.is_active = 1 
                  GROUP BY c.category_id 
                  ORDER BY product_count DESC 
                  LIMIT :limit";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Lấy danh mục cha
     */
    public function getParentCategories() {
        $query = "SELECT * FROM categories 
                  WHERE parent_id IS NULL AND is_active = 1 
                  ORDER BY display_order ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Lấy danh mục con theo parent_id
     */
    public function getSubcategories($parent_id) {
        $query = "SELECT * FROM categories 
                  WHERE parent_id = :parent_id AND is_active = 1 
                  ORDER BY display_order ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':parent_id', $parent_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Lấy tất cả danh mục với số lượng sản phẩm
     */
    public function getAllCategoriesWithProductCount() {
        $query = "SELECT c.*, COUNT(p.product_id) as product_count 
                  FROM categories c 
                  LEFT JOIN products p ON c.category_id = p.category_id AND p.is_active = 1 
                  WHERE c.is_active = 1 
                  GROUP BY c.category_id 
                  ORDER BY c.display_order ASC, c.category_name ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Kiểm tra danh mục có tồn tại không
     */
    public function categoryExists($category_id) {
        $query = "SELECT category_id FROM categories WHERE category_id = ? AND is_active = 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $category_id);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    /**
     * Lấy đường dẫn ảnh danh mục
     */
    public function getCategoryImage($category_id) {
        $query = "SELECT image_url FROM categories WHERE category_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $category_id);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row['image_url'];
        }
        return null;
    }

    /**
     * Cập nhật ảnh danh mục
     */
    public function updateCategoryImage($category_id, $image_url) {
        $query = "UPDATE categories SET image_url = :image_url WHERE category_id = :category_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':image_url', $image_url);
        $stmt->bindParam(':category_id', $category_id);
        return $stmt->execute();
    }

    /**
     * Lấy danh mục theo tên
     */
    public function getCategoryByName($category_name) {
        $query = "SELECT * FROM categories WHERE category_name = ? AND is_active = 1 LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $category_name);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
        return null;
    }

    /**
     * Tìm kiếm danh mục
     */
    public function searchCategories($keyword) {
        $query = "SELECT * FROM categories 
                  WHERE (category_name LIKE :keyword OR category_description LIKE :keyword) 
                  AND is_active = 1 
                  ORDER BY display_order ASC";
        
        $stmt = $this->conn->prepare($query);
        $keyword = "%{$keyword}%";
        $stmt->bindParam(':keyword', $keyword);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>