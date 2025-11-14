<?php
// public/check_categories.php
require_once '../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "<h2>Kiểm tra Categories trong Database</h2>";
    
    // Kiểm tra tất cả categories
    $query = "SELECT category_id, category_name, is_active FROM categories";
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($categories)) {
        echo "<p style='color: red;'>Không có categories nào trong database!</p>";
    } else {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Tên</th><th>Active</th><th>Link</th></tr>";
        foreach ($categories as $category) {
            echo "<tr>";
            echo "<td>{$category['category_id']}</td>";
            echo "<td>{$category['category_name']}</td>";
            echo "<td>{$category['is_active']}</td>";
            echo "<td><a href='/PHARMAGO/public/category-detail?id={$category['category_id']}' target='_blank'>Xem</a></td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Kiểm tra category ID từ URL
    $category_id = $_GET['id'] ?? 'not set';
    echo "<h3>Category ID từ URL: " . htmlspecialchars($category_id) . "</h3>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>