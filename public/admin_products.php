<?php
// public/admin_products.php
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
    require_once $base_dir . 'models/Product.php';
    require_once $base_dir . 'models/Category.php';

    $database = new Database();
    $db = $database->getConnection();
    
    $productModel = new Product($db);
    $categoryModel = new Category($db);

    // Lấy tất cả sản phẩm với thông tin category
    $query = "SELECT p.*, c.category_name 
              FROM products p 
              LEFT JOIN categories c ON p.category_id = c.category_id 
              ORDER BY p.product_id DESC";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Lấy tất cả danh mục
    $query = "SELECT * FROM categories WHERE is_active = 1 ORDER BY category_name";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Tổng số sản phẩm
    $query = "SELECT COUNT(*) as total FROM products WHERE is_active = 1";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $total_products = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

    // Số sản phẩm sắp hết hàng (stock <= 10)
    $query = "SELECT COUNT(*) as total FROM products WHERE stock_quantity <= 10 AND is_active = 1";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $low_stock_count = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

} catch (Exception $e) {
    error_log("Admin products error: " . $e->getMessage());
    $products = [];
    $categories = [];
    $total_products = 0;
    $low_stock_count = 0;
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
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Sản phẩm - Pharmacy Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo $base_url; ?>/assets/css/admin.css">
    <link rel="stylesheet" href="<?php echo $base_url; ?>/assets/css/admin-products.css">
    <style>
        /* Thêm CSS cần thiết nếu file CSS chưa có */
        .stock-indicator.text-danger { color: #dc3545 !important; font-weight: bold; }
        .stock-indicator.text-warning { color: #ffc107 !important; font-weight: bold; }
        .stock-indicator.text-success { color: #198754 !important; }
        .badge-success { background-color: #198754; }
        .badge-danger { background-color: #dc3545; }
        .badge-info { background-color: #0dcaf0; }
        .badge-warning { background-color: #ffc107; color: #000; }
        .badge-primary { background-color: #0d6efd; }
        .product-avatar {
            width: 40px;
            height: 40px;
            background: #f8f9fa;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6c757d;
        }
    </style>
</head>
<body>
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
                    <a href="admin_products.php" class="menu-item active">
                        <i class="fas fa-pills"></i>
                        <span class="menu-text">Sản Phẩm</span>
                        <span class="menu-badge"><?php echo $total_products; ?></span>
                    </a>
                    <a href="admin_categories.php" class="menu-item">
                        <i class="fas fa-tags"></i>
                        <span class="menu-text">Danh Mục</span>
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
                        <button type="submit" class="menu-item btn-logout" style="background: none; border: none; width: 100%; text-align: left; color: inherit; padding: 0.85rem 1.5rem; display: flex; align-items: center;">
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
                    <h1 class="page-title">Quản lý Sản phẩm</h1>
                </div>
                
                <div class="header-right">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" placeholder="Tìm kiếm sản phẩm..." id="productSearch">
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
                <!-- Bulk Actions Bar -->
                <div class="bulk-actions-bar" id="bulkActionsBar" style="display: none;">
                    <div class="d-flex align-items-center">
                        <span id="selectedCount">0</span> sản phẩm được chọn
                    </div>
                    <div class="bulk-actions">
                        <select class="form-select form-select-sm bulk-action-select" id="bulkAction">
                            <option value="">Hành động hàng loạt</option>
                            <option value="activate">Kích hoạt</option>
                            <option value="deactivate">Vô hiệu hóa</option>
                            <option value="delete">Xóa</option>
                            <option value="export">Export</option>
                        </select>
                        <button class="btn btn-sm btn-outline-secondary" id="clearSelection">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-info">
                                <div class="stat-title">Tổng Sản Phẩm</div>
                                <div class="stat-value"><?php echo $total_products; ?></div>
                                <div class="stat-change positive">
                                    <i class="fas fa-arrow-up"></i>
                                    Tất cả sản phẩm
                                </div>
                            </div>
                            <div class="stat-icon">
                                <i class="fas fa-boxes"></i>
                            </div>
                        </div>
                        <div class="stat-footer">Tất cả sản phẩm trong hệ thống</div>
                    </div>

                    <div class="stat-card success">
                        <div class="stat-header">
                            <div class="stat-info">
                                <div class="stat-title">Sản Phẩm Mới</div>
                                <div class="stat-value">
                                    <?php 
                                    // Đếm sản phẩm mới (created trong 30 ngày)
                                    try {
                                        $query = "SELECT COUNT(*) as total FROM products WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) AND is_active = 1";
                                        $stmt = $db->prepare($query);
                                        $stmt->execute();
                                        $new_products = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
                                        echo $new_products;
                                    } catch (Exception $e) {
                                        echo "0";
                                    }
                                    ?>
                                </div>
                                <div class="stat-change positive">
                                    <i class="fas fa-arrow-up"></i>
                                    Sản phẩm mới
                                </div>
                            </div>
                            <div class="stat-icon">
                                <i class="fas fa-star"></i>
                            </div>
                        </div>
                        <div class="stat-footer">Sản phẩm mới tháng này</div>
                    </div>

                    <div class="stat-card warning">
                        <div class="stat-header">
                            <div class="stat-info">
                                <div class="stat-title">Sắp Hết Hàng</div>
                                <div class="stat-value"><?php echo $low_stock_count; ?></div>
                                <div class="stat-change negative">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    Cần nhập thêm
                                </div>
                            </div>
                            <div class="stat-icon">
                                <i class="fas fa-exclamation-circle"></i>
                            </div>
                        </div>
                        <div class="stat-footer">Cần kiểm tra tồn kho</div>
                    </div>

                    <div class="stat-card info">
                        <div class="stat-header">
                            <div class="stat-info">
                                <div class="stat-title">Đang Giảm Giá</div>
                                <div class="stat-value">
                                    <?php 
                                    // Đếm sản phẩm đang giảm giá
                                    try {
                                        $query = "SELECT COUNT(*) as total FROM products WHERE discount_percent > 0 AND is_active = 1";
                                        $stmt = $db->prepare($query);
                                        $stmt->execute();
                                        $discount_products = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
                                        echo $discount_products;
                                    } catch (Exception $e) {
                                        echo "0";
                                    }
                                    ?>
                                </div>
                                <div class="stat-change positive">
                                    <i class="fas fa-tag"></i>
                                    Đang khuyến mãi
                                </div>
                            </div>
                            <div class="stat-icon">
                                <i class="fas fa-percentage"></i>
                            </div>
                        </div>
                        <div class="stat-footer">Sản phẩm đang giảm giá</div>
                    </div>
                </div>

                <!-- Filter Section -->
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-3">
                                <label class="form-label">Danh mục</label>
                                <select class="form-select" id="categoryFilter">
                                    <option value="">Tất cả danh mục</option>
                                    <?php foreach($categories as $category): ?>
                                    <option value="<?php echo $category['category_id']; ?>">
                                        <?php echo htmlspecialchars($category['category_name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Trạng thái</label>
                                <select class="form-select" id="statusFilter">
                                    <option value="">Tất cả trạng thái</option>
                                    <option value="active">Đang bán</option>
                                    <option value="inactive">Ngừng bán</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Tồn kho</label>
                                <select class="form-select" id="stockFilter">
                                    <option value="">Tất cả</option>
                                    <option value="low">Sắp hết hàng</option>
                                    <option value="out">Hết hàng</option>
                                    <option value="sufficient">Đủ hàng</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <button class="btn btn-primary w-100" id="applyFilters">
                                    <i class="fas fa-filter me-2"></i>Lọc
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Products Table -->
                <div class="table-container">
                    <div class="table-header">
                        <h3 class="table-title">Danh sách Sản phẩm</h3>
                        <div class="table-actions">
                            <a href="?action=add" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>Thêm sản phẩm
                            </a>
                            <button class="btn btn-outline-primary" id="exportProducts">
                                <i class="fas fa-download me-2"></i>Export
                            </button>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table" id="productsTable">
                            <thead>
                                <tr>
                                    <th width="50">
                                        <input type="checkbox" class="form-check-input" id="selectAll">
                                    </th>
                                    <th>ID</th>
                                    <th>Sản phẩm</th>
                                    <th>Danh mục</th>
                                    <th>Giá</th>
                                    <th>Tồn kho</th>
                                    <th>Trạng thái</th>
                                    <th>Hành động</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(!empty($products)): ?>
                                    <?php foreach($products as $product): ?>
                                    <tr data-product-id="<?php echo $product['product_id']; ?>" 
                                        data-category="<?php echo $product['category_id']; ?>"
                                        data-status="<?php echo $product['is_active'] ? 'active' : 'inactive'; ?>"
                                        data-stock="<?php echo $product['stock_quantity'] <= 10 ? 'low' : ($product['stock_quantity'] == 0 ? 'out' : 'sufficient'); ?>">
                                        <td>
                                            <input type="checkbox" class="form-check-input product-select" 
                                                   data-product-id="<?php echo $product['product_id']; ?>">
                                        </td>
                                        <td>#<?php echo $product['product_id']; ?></td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="product-avatar me-3">
                                                    <i class="fas fa-pills"></i>
                                                </div>
                                                <div>
                                                    <div class="fw-bold product-name"><?php echo htmlspecialchars($product['product_name']); ?></div>
                                                    <small class="text-muted"><?php echo htmlspecialchars($product['sku'] ?? ''); ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-primary"><?php echo htmlspecialchars($product['category_name'] ?? 'N/A'); ?></span>
                                        </td>
                                        <td>
                                            <div class="fw-bold text-success"><?php echo number_format($product['price'], 0, ',', '.'); ?>đ</div>
                                            <?php if($product['original_price'] && $product['original_price'] > $product['price']): ?>
                                                <small class="text-muted text-decoration-line-through"><?php echo number_format($product['original_price'], 0, ',', '.'); ?>đ</small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="fw-bold stock-indicator <?php 
                                                echo $product['stock_quantity'] <= 10 ? 'text-danger' : 
                                                    ($product['stock_quantity'] <= 20 ? 'text-warning' : 'text-success'); 
                                            ?>">
                                                <?php echo $product['stock_quantity']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if($product['is_active']): ?>
                                                <span class="badge bg-success status-badge">Đang bán</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger status-badge">Ngừng bán</span>
                                            <?php endif; ?>
                                            
                                            <?php if($product['is_featured']): ?>
                                                <span class="badge bg-info mt-1">Nổi bật</span>
                                            <?php endif; ?>
                                            
                                            <?php if($product['is_new']): ?>
                                                <span class="badge bg-warning mt-1">Mới</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <button class="btn btn-sm btn-outline-primary quick-action-btn" 
                                                        data-action="edit" 
                                                        data-product-id="<?php echo $product['product_id']; ?>"
                                                        title="Sửa">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-info quick-action-btn" 
                                                        data-action="view" 
                                                        data-product-id="<?php echo $product['product_id']; ?>"
                                                        title="Xem">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-warning quick-action-btn" 
                                                        data-action="toggle-status" 
                                                        data-product-id="<?php echo $product['product_id']; ?>"
                                                        title="<?php echo $product['is_active'] ? 'Ngừng bán' : 'Kích hoạt'; ?>">
                                                    <i class="fas fa-power-off"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-danger quick-action-btn" 
                                                        data-action="delete" 
                                                        data-product-id="<?php echo $product['product_id']; ?>"
                                                        title="Xóa">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="text-center py-5 text-muted">
                                            <i class="fas fa-box-open fa-3x mb-3 d-block"></i>
                                            Chưa có sản phẩm nào
                                            <br>
                                            <a href="?action=add" class="btn btn-primary mt-2">
                                                <i class="fas fa-plus me-2"></i>Thêm sản phẩm đầu tiên
                                            </a>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Pagination -->
                <nav aria-label="Page navigation" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <li class="page-item disabled">
                            <a class="page-link" href="#" tabindex="-1">Previous</a>
                        </li>
                        <li class="page-item active"><a class="page-link" href="#">1</a></li>
                        <li class="page-item"><a class="page-link" href="#">2</a></li>
                        <li class="page-item"><a class="page-link" href="#">3</a></li>
                        <li class="page-item">
                            <a class="page-link" href="#">Next</a>
                        </li>
                    </ul>
                </nav>
            </div>
        </main>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Xác nhận xóa</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Bạn có chắc chắn muốn xóa sản phẩm này?</p>
                    <p class="text-danger"><small>Hành động này không thể hoàn tác.</small></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="button" class="btn btn-danger" id="confirmDelete">Xóa</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo $base_url; ?>/assets/js/admin.js"></script>
    <script src="<?php echo $base_url; ?>/assets/js/admin-products.js"></script>
</body>
</html>