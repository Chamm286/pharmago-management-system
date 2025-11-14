<?php
include_once '../config/database.php';
include_once '../models/Category.php';
include_once '../models/Product.php';

class CategoryController {
    private $db;
    private $category;
    private $product;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->category = new Category($this->db);
        $this->product = new Product($this->db);
    }

    public function getAllCategories() {
        $stmt = $this->category->getAllCategories();
        $categories = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $categories[] = $row;
        }
        return $categories;
    }

    public function getActiveCategories() {
        $stmt = $this->category->read();
        $categories = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $categories[] = $row;
        }
        return $categories;
    }

    public function getCategoryById($category_id) {
        if ($this->category->getCategoryById($category_id)) {
            return [
                'success' => true,
                'category' => [
                    'category_id' => $this->category->category_id,
                    'category_name' => $this->category->category_name,
                    'category_description' => $this->category->category_description,
                    'image_url' => $this->category->image_url,
                    'parent_id' => $this->category->parent_id,
                    'icon_class' => $this->category->icon_class,
                    'display_order' => $this->category->display_order,
                    'is_active' => $this->category->is_active
                ]
            ];
        } else {
            return ['success' => false, 'message' => 'Danh mục không tồn tại'];
        }
    }

    public function createCategory($category_data) {
        $this->category->category_name = $category_data['category_name'];
        $this->category->category_description = $category_data['category_description'];
        $this->category->image_url = $category_data['image_url'];
        $this->category->parent_id = $category_data['parent_id'];
        $this->category->icon_class = $category_data['icon_class'];
        $this->category->display_order = $category_data['display_order'];
        $this->category->is_active = $category_data['is_active'];

        if ($this->category->create()) {
            return ['success' => true, 'message' => 'Thêm danh mục thành công', 'category_id' => $this->category->category_id];
        } else {
            return ['success' => false, 'message' => 'Thêm danh mục thất bại'];
        }
    }

    public function updateCategory($category_id, $category_data) {
        $this->category->category_id = $category_id;
        $this->category->category_name = $category_data['category_name'];
        $this->category->category_description = $category_data['category_description'];
        $this->category->image_url = $category_data['image_url'];
        $this->category->parent_id = $category_data['parent_id'];
        $this->category->icon_class = $category_data['icon_class'];
        $this->category->display_order = $category_data['display_order'];
        $this->category->is_active = $category_data['is_active'];

        if ($this->category->update()) {
            return ['success' => true, 'message' => 'Cập nhật danh mục thành công'];
        } else {
            return ['success' => false, 'message' => 'Cập nhật danh mục thất bại'];
        }
    }

    public function deleteCategory($category_id) {
        // Check if category has products
        $products_count = $this->product->getProductsCountByCategory($category_id);
        if ($products_count > 0) {
            return ['success' => false, 'message' => 'Không thể xóa danh mục vì còn sản phẩm'];
        }

        $this->category->category_id = $category_id;

        if ($this->category->delete()) {
            return ['success' => true, 'message' => 'Xóa danh mục thành công'];
        } else {
            return ['success' => false, 'message' => 'Xóa danh mục thất bại'];
        }
    }

    public function getCategoriesWithProductCount() {
        $categories = $this->getActiveCategories();
        $result = [];

        foreach ($categories as $category) {
            $product_count = $this->category->getProductsCount($category['category_id']);
            $category['product_count'] = $product_count;
            $result[] = $category;
        }

        return $result;
    }
}
?>