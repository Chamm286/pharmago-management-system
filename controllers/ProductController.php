<?php
include_once '../config/database.php';
include_once '../models/Product.php';
include_once '../models/Category.php';
include_once '../models/Manufacturer.php';

class ProductController {
    private $db;
    private $product;
    private $category;
    private $manufacturer;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->product = new Product($this->db);
        $this->category = new Category($this->db);
        $this->manufacturer = new Manufacturer($this->db);
    }

    public function getAllProducts($page = 1, $limit = 12) {
        $offset = ($page - 1) * $limit;
        
        $query = "SELECT p.*, c.category_name, m.manufacturer_name 
                  FROM products p 
                  LEFT JOIN categories c ON p.category_id = c.category_id 
                  LEFT JOIN manufacturers m ON p.manufacturer_id = m.manufacturer_id 
                  WHERE p.is_active = 1 
                  ORDER BY p.created_at DESC 
                  LIMIT :limit OFFSET :offset";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getProductById($product_id) {
        if ($this->product->getProductById($product_id)) {
            return [
                'success' => true,
                'product' => [
                    'product_id' => $this->product->product_id,
                    'product_name' => $this->product->product_name,
                    'product_description' => $this->product->product_description,
                    'short_description' => $this->product->short_description,
                    'category_id' => $this->product->category_id,
                    'manufacturer_id' => $this->product->manufacturer_id,
                    'sku' => $this->product->sku,
                    'price' => $this->product->price,
                    'original_price' => $this->product->original_price,
                    'stock_quantity' => $this->product->stock_quantity,
                    'image_url' => $this->product->image_url,
                    'prescription_required' => $this->product->prescription_required,
                    'is_featured' => $this->product->is_featured,
                    'is_new' => $this->product->is_new,
                    'is_best_seller' => $this->product->is_best_seller,
                    'is_on_sale' => $this->product->is_on_sale,
                    'discount_percent' => $this->product->discount_percent,
                    'sold_count' => $this->product->sold_count,
                    'average_rating' => $this->product->average_rating,
                    'review_count' => $this->product->review_count
                ]
            ];
        } else {
            return ['success' => false, 'message' => 'Sản phẩm không tồn tại'];
        }
    }

    public function getProductsByCategory($category_id, $page = 1, $limit = 12) {
        $offset = ($page - 1) * $limit;
        
        $query = "SELECT p.*, c.category_name, m.manufacturer_name 
                  FROM products p 
                  LEFT JOIN categories c ON p.category_id = c.category_id 
                  LEFT JOIN manufacturers m ON p.manufacturer_id = m.manufacturer_id 
                  WHERE p.category_id = :category_id AND p.is_active = 1 
                  ORDER BY p.created_at DESC 
                  LIMIT :limit OFFSET :offset";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':category_id', $category_id, PDO::PARAM_INT);
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function searchProducts($keywords, $page = 1, $limit = 12) {
        $offset = ($page - 1) * $limit;
        
        $query = "SELECT p.*, c.category_name, m.manufacturer_name 
                  FROM products p 
                  LEFT JOIN categories c ON p.category_id = c.category_id 
                  LEFT JOIN manufacturers m ON p.manufacturer_id = m.manufacturer_id 
                  WHERE (p.product_name LIKE :keywords OR p.product_description LIKE :keywords OR p.short_description LIKE :keywords) 
                  AND p.is_active = 1 
                  ORDER BY p.created_at DESC 
                  LIMIT :limit OFFSET :offset";
        
        $stmt = $this->db->prepare($query);
        $search_keywords = "%{$keywords}%";
        $stmt->bindValue(':keywords', $search_keywords, PDO::PARAM_STR);
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getFeaturedProducts($limit = 8) {
        $query = "SELECT p.*, c.category_name, m.manufacturer_name 
                  FROM products p 
                  LEFT JOIN categories c ON p.category_id = c.category_id 
                  LEFT JOIN manufacturers m ON p.manufacturer_id = m.manufacturer_id 
                  WHERE p.is_featured = 1 AND p.is_active = 1 
                  ORDER BY p.created_at DESC 
                  LIMIT :limit";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getBestSellerProducts($limit = 8) {
        $query = "SELECT p.*, c.category_name, m.manufacturer_name 
                  FROM products p 
                  LEFT JOIN categories c ON p.category_id = c.category_id 
                  LEFT JOIN manufacturers m ON p.manufacturer_id = m.manufacturer_id 
                  WHERE p.is_best_seller = 1 AND p.is_active = 1 
                  ORDER BY p.sold_count DESC 
                  LIMIT :limit";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function createProduct($product_data) {
        $this->product->product_name = $product_data['product_name'];
        $this->product->product_description = $product_data['product_description'];
        $this->product->short_description = $product_data['short_description'];
        $this->product->category_id = $product_data['category_id'];
        $this->product->manufacturer_id = $product_data['manufacturer_id'];
        $this->product->sku = $product_data['sku'];
        $this->product->price = $product_data['price'];
        $this->product->original_price = $product_data['original_price'];
        $this->product->stock_quantity = $product_data['stock_quantity'];
        $this->product->image_url = $product_data['image_url'];
        $this->product->prescription_required = $product_data['prescription_required'];
        $this->product->is_featured = $product_data['is_featured'];
        $this->product->is_new = $product_data['is_new'];
        $this->product->is_best_seller = $product_data['is_best_seller'];
        $this->product->is_on_sale = $product_data['is_on_sale'];
        $this->product->discount_percent = $product_data['discount_percent'];

        if ($this->product->create()) {
            return ['success' => true, 'message' => 'Thêm sản phẩm thành công', 'product_id' => $this->product->product_id];
        } else {
            return ['success' => false, 'message' => 'Thêm sản phẩm thất bại'];
        }
    }

    public function updateProduct($product_id, $product_data) {
        $this->product->product_id = $product_id;
        $this->product->product_name = $product_data['product_name'];
        $this->product->product_description = $product_data['product_description'];
        $this->product->short_description = $product_data['short_description'];
        $this->product->category_id = $product_data['category_id'];
        $this->product->manufacturer_id = $product_data['manufacturer_id'];
        $this->product->sku = $product_data['sku'];
        $this->product->price = $product_data['price'];
        $this->product->original_price = $product_data['original_price'];
        $this->product->stock_quantity = $product_data['stock_quantity'];
        $this->product->image_url = $product_data['image_url'];
        $this->product->prescription_required = $product_data['prescription_required'];
        $this->product->is_featured = $product_data['is_featured'];
        $this->product->is_new = $product_data['is_new'];
        $this->product->is_best_seller = $product_data['is_best_seller'];
        $this->product->is_on_sale = $product_data['is_on_sale'];
        $this->product->discount_percent = $product_data['discount_percent'];
        $this->product->is_active = $product_data['is_active'];

        if ($this->product->update()) {
            return ['success' => true, 'message' => 'Cập nhật sản phẩm thành công'];
        } else {
            return ['success' => false, 'message' => 'Cập nhật sản phẩm thất bại'];
        }
    }

    public function deleteProduct($product_id) {
        $this->product->product_id = $product_id;

        if ($this->product->delete()) {
            return ['success' => true, 'message' => 'Xóa sản phẩm thành công'];
        } else {
            return ['success' => false, 'message' => 'Xóa sản phẩm thất bại'];
        }
    }

    public function getProductsCount() {
        $query = "SELECT COUNT(*) as total FROM products WHERE is_active = 1";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'];
    }

    public function getProductsCountByCategory($category_id) {
        $query = "SELECT COUNT(*) as total FROM products WHERE category_id = ? AND is_active = 1";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(1, $category_id);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'];
    }
}
?>