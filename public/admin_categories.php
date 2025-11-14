<?php
// public/admin_categories.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin_login.php');
    exit;
}

// Base URL
$base_url = 'http://localhost/PHARMAGO/public';

try {
    // Kết nối database
    $base_dir = dirname(__DIR__) . '/';
    require_once $base_dir . 'config/database.php';
    require_once $base_dir . 'models/Category.php';
    require_once $base_dir . 'models/Product.php';

    $database = new Database();
    $db = $database->getConnection();
    
    $categoryModel = new Category($db);
    $productModel = new Product($db);

    // Lấy dữ liệu danh mục với thống kê
    $query = "SELECT c.*, 
                     COUNT(p.product_id) as product_count,
                     COALESCE(SUM(p.view_count), 0) as total_views
              FROM categories c 
              LEFT JOIN products p ON c.category_id = p.category_id AND p.is_active = 1
              WHERE c.is_active = 1
              GROUP BY c.category_id 
              ORDER BY c.display_order ASC, c.category_name ASC";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Thống kê tổng quan
    $total_categories = count($categories);
    $total_products = $productModel->getTotalProducts();
    
    // Đếm danh mục cha và con
    $parent_categories = 0;
    $child_categories = 0;
    foreach ($categories as $category) {
        if ($category['parent_id'] === null) {
            $parent_categories++;
        } else {
            $child_categories++;
        }
    }

} catch (Exception $e) {
    error_log("Admin categories error: " . $e->getMessage());
    $categories = [];
    $total_categories = 0;
    $total_products = 0;
    $parent_categories = 0;
    $child_categories = 0;
}

// Hàm helper
function getInitials($name) {
    if (empty($name)) return 'A';
    $words = explode(' ', $name);
    $initials = '';
    foreach ($words as $word) {
        if (!empty($word)) {
            $initials .= strtoupper($word[0]);
        }
    }
    return substr($initials, 0, 2);
}

function getRoleText($role) {
    switch ($role) {
        case 'admin': return 'Quản trị viên';
        case 'staff': return 'Nhân viên';
        default: return 'Người dùng';
    }
}

function formatNumber($number) {
    if ($number >= 1000) {
        return number_format($number / 1000, 1) . 'K';
    }
    return $number;
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Danh mục - Pharmacy Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo $base_url; ?>/assets/css/admin.css">
    <link rel="stylesheet" href="<?php echo $base_url; ?>/assets/css/admin-categories.css">
</head>
<body class="categories-page">
    <div class="admin-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <a href="admin.php" class="logo">
                    <i class="fas fa-leaf"></i>
                    <span class="logo-text">PharmacyAdmin</span>
                </a>
            </div>
            
            <nav class="sidebar-menu">
                <div class="menu-section">
                    <div class="menu-title">Bảng Điều Khiển</div>
                    <a href="admin.php" class="menu-item">
                        <i class="fas fa-tachometer-alt"></i>
                        <span class="menu-text">Dashboard</span>
                    </a>
                </div>
                
                <div class="menu-section">
                    <div class="menu-title">Quản Lý</div>
                    <a href="admin_products.php" class="menu-item">
                        <i class="fas fa-pills"></i>
                        <span class="menu-text">Sản Phẩm</span>
                    </a>
                    <a href="admin_categories.php" class="menu-item active">
                        <i class="fas fa-tags"></i>
                        <span class="menu-text">Danh Mục</span>
                        <span class="menu-badge"><?php echo $total_categories; ?></span>
                    </a>
                    <a href="admin_orders.php" class="menu-item">
                        <i class="fas fa-shopping-cart"></i>
                        <span class="menu-text">Đơn Hàng</span>
                    </a>
                    <a href="admin_users.php" class="menu-item">
                        <i class="fas fa-users"></i>
                        <span class="menu-text">Người Dùng</span>
                    </a>
                </div>
                
                <div class="menu-section">
                    <div class="menu-title">Hệ Thống</div>
                    <a href="<?php echo $base_url; ?>/" target="_blank" class="menu-item">
                        <i class="fas fa-external-link-alt"></i>
                        <span class="menu-text">Xem Website</span>
                    </a>
                    <form method="POST" action="admin_logout.php" class="d-inline">
                        <button type="submit" class="menu-item btn-logout">
                            <i class="fas fa-sign-out-alt"></i>
                            <span class="menu-text">Đăng Xuất</span>
                        </button>
                    </form>
                </div>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Header -->
            <header class="admin-header">
                <div class="header-left">
                    <button class="toggle-sidebar">
                        <i class="fas fa-bars"></i>
                    </button>
                    <h1 class="page-title">Quản lý Danh mục</h1>
                </div>
                
                <div class="header-right">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" placeholder="Tìm kiếm danh mục..." id="categorySearch">
                    </div>
                    
                    <div class="header-actions">
                        <button class="btn btn-outline-primary" id="exportCategories">
                            <i class="fas fa-download me-2"></i>Export
                        </button>
                    </div>
                    
                    <div class="user-profile">
                        <div class="user-avatar">
                            <?php echo getInitials($_SESSION['admin_name'] ?? 'A'); ?>
                        </div>
                        <div class="user-details">
                            <div class="user-name"><?php echo $_SESSION['admin_name'] ?? 'Admin'; ?></div>
                            <div class="user-role"><?php echo getRoleText($_SESSION['admin_role'] ?? 'staff'); ?></div>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Content -->
            <div class="content">
                <!-- Stats Cards -->
                <div class="categories-stats-grid">
                    <div class="categories-stat-card">
                        <div class="categories-stat-header">
                            <div class="categories-stat-info">
                                <div class="categories-stat-title">Tổng Danh Mục</div>
                                <div class="categories-stat-value"><?php echo $total_categories; ?></div>
                                <div class="categories-stat-change positive">
                                    <i class="fas fa-arrow-up"></i>
                                    Tất cả danh mục
                                </div>
                            </div>
                            <div class="categories-stat-icon">
                                <i class="fas fa-tags"></i>
                            </div>
                        </div>
                        <div class="categories-stat-footer">Danh mục đang hoạt động</div>
                    </div>

                    <div class="categories-stat-card success">
                        <div class="categories-stat-header">
                            <div class="categories-stat-info">
                                <div class="categories-stat-title">Tổng Sản Phẩm</div>
                                <div class="categories-stat-value"><?php echo $total_products; ?></div>
                                <div class="categories-stat-change positive">
                                    <i class="fas fa-box"></i>
                                    Sản phẩm hệ thống
                                </div>
                            </div>
                            <div class="categories-stat-icon">
                                <i class="fas fa-boxes"></i>
                            </div>
                        </div>
                        <div class="categories-stat-footer">Sản phẩm trong tất cả danh mục</div>
                    </div>

                    <div class="categories-stat-card warning">
                        <div class="categories-stat-header">
                            <div class="categories-stat-info">
                                <div class="categories-stat-title">Danh Mục Cha</div>
                                <div class="categories-stat-value"><?php echo $parent_categories; ?></div>
                                <div class="categories-stat-change">
                                    <i class="fas fa-folder"></i>
                                    Danh mục chính
                                </div>
                            </div>
                            <div class="categories-stat-icon">
                                <i class="fas fa-folder-open"></i>
                            </div>
                        </div>
                        <div class="categories-stat-footer">Danh mục cấp cao nhất</div>
                    </div>

                    <div class="categories-stat-card info">
                        <div class="categories-stat-header">
                            <div class="categories-stat-info">
                                <div class="categories-stat-title">Danh Mục Con</div>
                                <div class="categories-stat-value"><?php echo $child_categories; ?></div>
                                <div class="categories-stat-change">
                                    <i class="fas fa-folder-plus"></i>
                                    Danh mục phụ
                                </div>
                            </div>
                            <div class="categories-stat-icon">
                                <i class="fas fa-sitemap"></i>
                            </div>
                        </div>
                        <div class="categories-stat-footer">Danh mục con trong hệ thống</div>
                    </div>
                </div>

                <!-- Action Bar -->
                <div class="action-bar mb-4">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <h3 class="section-title mb-0">Danh sách Danh mục</h3>
                        </div>
                        <div class="col-md-6 text-end">
                            <a href="?action=add" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>Thêm Danh Mục Mới
                            </a>
                            <button class="btn btn-outline-secondary ms-2" id="toggleView">
                                <i class="fas fa-th-large me-2"></i>Grid View
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Categories Grid -->
                <div class="categories-grid" id="categoriesView">
                    <?php if(!empty($categories)): ?>
                        <?php foreach($categories as $category): ?>
                        <div class="category-card" data-category-id="<?php echo $category['category_id']; ?>">
                            <div class="category-card-header">
                                <div class="category-icon-wrapper">
                                    <i class="<?php echo $category['icon_class'] ?? 'fas fa-tag'; ?>"></i>
                                </div>
                                <div class="category-header-content">
                                    <div class="category-name"><?php echo htmlspecialchars($category['category_name']); ?></div>
                                    <span class="category-status-badge <?php echo $category['is_active'] ? 'category-status-active' : 'category-status-inactive'; ?>">
                                        <?php echo $category['is_active'] ? 'Hiển thị' : 'Ẩn'; ?>
                                    </span>
                                </div>
                            </div>
                            
                            <div class="category-card-body">
                                <p class="category-description">
                                    <?php echo htmlspecialchars($category['category_description'] ?? 'Chưa có mô tả cho danh mục này'); ?>
                                </p>
                                
                                <div class="category-meta">
                                    <div class="category-meta-item">
                                        <i class="fas fa-layer-group"></i>
                                        <?php echo $category['parent_id'] ? 'Danh mục con' : 'Danh mục cha'; ?>
                                    </div>
                                    <div class="category-meta-item">
                                        <i class="fas fa-sort-numeric-down"></i>
                                        Thứ tự: <?php echo $category['display_order']; ?>
                                    </div>
                                </div>
                                
                                <div class="category-stats">
                                    <div class="category-stat-item">
                                        <div class="category-stat-number"><?php echo formatNumber($category['product_count']); ?></div>
                                        <div class="category-stat-label">Sản phẩm</div>
                                    </div>
                                    <div class="category-stat-item">
                                        <div class="category-stat-number"><?php echo formatNumber($category['total_views']); ?></div>
                                        <div class="category-stat-label">Lượt xem</div>
                                    </div>
                                </div>
                                
                                <div class="category-actions">
                                    <button class="category-action-btn category-action-view" title="Xem chi tiết">
                                        <i class="fas fa-eye"></i>
                                        <span>Xem</span>
                                    </button>
                                    <button class="category-action-btn category-action-edit" title="Chỉnh sửa">
                                        <i class="fas fa-edit"></i>
                                        <span>Sửa</span>
                                    </button>
                                    <button class="category-action-btn category-action-delete" title="Xóa danh mục">
                                        <i class="fas fa-trash"></i>
                                        <span>Xóa</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="categories-empty-state">
                            <i class="fas fa-tags"></i>
                            <h4>Chưa có danh mục nào</h4>
                            <p>Bắt đầu bằng cách tạo danh mục đầu tiên cho cửa hàng của bạn</p>
                            <a href="?action=add" class="add-category-btn">
                                <i class="fas fa-plus me-2"></i>Tạo Danh Mục Đầu Tiên
                            </a>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Add Category Section -->
                <?php if(!empty($categories)): ?>
                <div class="add-category-section">
                    <a href="?action=add" class="add-category-btn">
                        <i class="fas fa-plus-circle me-2"></i>Thêm Danh Mục Mới
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteCategoryModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Xác nhận xóa</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Bạn có chắc chắn muốn xóa danh mục này?</p>
                    <p class="text-danger"><small>Hành động này không thể hoàn tác. Tất cả sản phẩm trong danh mục sẽ được chuyển sang danh mục mặc định.</small></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteCategory">Xóa</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo $base_url; ?>/assets/js/admin.js"></script>
    <script src="<?php echo $base_url; ?>/assets/js/admin-categories.js"></script>
</body>
</html>