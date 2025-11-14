<?php
// public/admin.php

// Bật hiển thị lỗi để debug
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Debug session
error_log("Session data: " . print_r($_SESSION, true));

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin_login.php');
    exit;
}

// Đảm bảo các session variables tồn tại
if (!isset($_SESSION['admin_role'])) {
    $_SESSION['admin_role'] = 'staff';
    error_log("Warning: admin_role not set in session, defaulting to staff");
}

// Base URL
$base_url = 'http://localhost/PHARMAGO/public';

try {
    // Kết nối database
    $base_dir = dirname(__DIR__) . '/';
    
    require_once $base_dir . 'config/database.php';
    require_once $base_dir . 'models/Product.php';
    require_once $base_dir . 'models/Category.php';
    require_once $base_dir . 'models/User.php';
    require_once $base_dir . 'models/Order.php';

    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception('Cannot connect to database');
    }

    // Khởi tạo models
    $productModel = new Product($db);
    $categoryModel = new Category($db);
    $userModel = new User($db);
    $orderModel = new Order($db);

    // Lấy thống kê từ database - SỬA CÁC METHODS KHÔNG TỒN TẠI
    try {
        // Sửa getTotalProducts() thành phương thức đơn giản
        $stmt = $db->query("SELECT COUNT(*) as total FROM products WHERE is_active = 1");
        $total_products = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    } catch (Exception $e) {
        $total_products = 0;
        error_log("Error getting total products: " . $e->getMessage());
    }

    try {
        $stmt = $db->query("SELECT COUNT(*) as total FROM categories WHERE is_active = 1");
        $total_categories = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    } catch (Exception $e) {
        $total_categories = 0;
        error_log("Error getting total categories: " . $e->getMessage());
    }

    try {
        $stmt = $db->query("SELECT COUNT(*) as total FROM users WHERE is_active = 1");
        $total_users = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    } catch (Exception $e) {
        $total_users = 0;
        error_log("Error getting total users: " . $e->getMessage());
    }

    try {
        $stmt = $db->query("SELECT COUNT(*) as total FROM orders");
        $total_orders = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    } catch (Exception $e) {
        $total_orders = 0;
        error_log("Error getting total orders: " . $e->getMessage());
    }

    try {
        $today = date('Y-m-d');
        $stmt = $db->prepare("SELECT COALESCE(SUM(total_amount), 0) as revenue FROM orders WHERE DATE(created_at) = ? AND order_status = 'delivered'");
        $stmt->execute([$today]);
        $revenue_today = $stmt->fetch(PDO::FETCH_ASSOC)['revenue'] ?? 0;
    } catch (Exception $e) {
        $revenue_today = 0;
        error_log("Error getting today's revenue: " . $e->getMessage());
    }

    try {
        $current_month = date('Y-m');
        $stmt = $db->prepare("SELECT COALESCE(SUM(total_amount), 0) as revenue FROM orders WHERE DATE_FORMAT(created_at, '%Y-%m') = ? AND order_status = 'delivered'");
        $stmt->execute([$current_month]);
        $revenue_month = $stmt->fetch(PDO::FETCH_ASSOC)['revenue'] ?? 0;
    } catch (Exception $e) {
        $revenue_month = 0;
        error_log("Error getting monthly revenue: " . $e->getMessage());
    }

    try {
        $stmt = $db->query("SELECT COUNT(*) as total FROM orders WHERE order_status IN ('pending', 'confirmed', 'processing')");
        $pending_orders = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    } catch (Exception $e) {
        $pending_orders = 0;
        error_log("Error getting pending orders: " . $e->getMessage());
    }

    // Lấy dữ liệu cho các bảng - SỬA CÁC METHODS
    try {
        $stmt = $db->query("SELECT order_id, order_code, customer_name, order_date, total_amount, order_status as status 
                           FROM orders 
                           ORDER BY created_at DESC 
                           LIMIT 5");
        $recent_orders = $stmt->fetchAll(PDO::FETCH_ASSOC) ?? [];
    } catch (Exception $e) {
        $recent_orders = [];
        error_log("Error getting recent orders: " . $e->getMessage());
    }

    try {
        $stmt = $db->query("SELECT product_id, product_name, price, stock_quantity, created_at 
                           FROM products 
                           WHERE is_active = 1 
                           ORDER BY created_at DESC 
                           LIMIT 5");
        $recent_products = $stmt->fetchAll(PDO::FETCH_ASSOC) ?? [];
    } catch (Exception $e) {
        $recent_products = [];
        error_log("Error getting recent products: " . $e->getMessage());
    }

    try {
        $stmt = $db->query("SELECT product_id, product_name, price, stock_quantity 
                           FROM products 
                           WHERE is_active = 1 AND stock_quantity <= 10 
                           ORDER BY stock_quantity ASC 
                           LIMIT 5");
        $low_stock_products = $stmt->fetchAll(PDO::FETCH_ASSOC) ?? [];
    } catch (Exception $e) {
        $low_stock_products = [];
        error_log("Error getting low stock products: " . $e->getMessage());
    }

    try {
        $stmt = $db->query("SELECT product_id, product_name, price, sold_count, stock_quantity 
                           FROM products 
                           WHERE is_active = 1 
                           ORDER BY sold_count DESC 
                           LIMIT 5");
        $top_products = $stmt->fetchAll(PDO::FETCH_ASSOC) ?? [];
    } catch (Exception $e) {
        $top_products = [];
        error_log("Error getting top products: " . $e->getMessage());
    }

} catch (Exception $e) {
    error_log("Admin page error: " . $e->getMessage());
    $error_message = "Lỗi hệ thống: " . $e->getMessage();
    
    // KHÔNG CÓ DỮ LIỆU MẪU - CHỈ SET VỀ 0
    $total_products = 0;
    $total_categories = 0;
    $total_users = 0;
    $total_orders = 0;
    $revenue_today = 0;
    $revenue_month = 0;
    $pending_orders = 0;
    $recent_orders = [];
    $recent_products = [];
    $low_stock_products = [];
    $top_products = [];
}

// Xử lý đăng xuất
if (isset($_POST['logout'])) {
    session_destroy();
    header('Location: admin_login.php');
    exit;
}

// Hàm helper để lấy role text
function getRoleText($role) {
    switch ($role) {
        case 'admin':
            return 'Quản trị viên';
        case 'staff':
            return 'Nhân viên';
        case 'customer':
            return 'Khách hàng';
        default:
            return 'Người dùng';
    }
}

// Hàm helper để lấy chữ cái đầu cho avatar
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
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - PharmaGo Admin</title>
    
    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo $base_url; ?>/assets/css/admin.css">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <a href="admin.php" class="logo">
                    <i class="fas fa-leaf"></i>
                    <span class="logo-text">PharmaGo</span>
                </a>
            </div>
            
            <nav class="sidebar-menu">
                <div class="menu-section">
                    <div class="menu-title">Bảng Điều Khiển</div>
                    <a href="admin.php" class="menu-item active">
                        <i class="fas fa-tachometer-alt"></i>
                        <span class="menu-text">Dashboard</span>
                    </a>
                </div>
                
                <div class="menu-section">
                    <div class="menu-title">Quản Lý</div>
                    <a href="admin_products.php" class="menu-item">
                        <i class="fas fa-pills"></i>
                        <span class="menu-text">Sản Phẩm</span>
                        <span class="menu-badge"><?php echo $total_products; ?></span>
                    </a>
                    <a href="admin_categories.php" class="menu-item">
                        <i class="fas fa-tags"></i>
                        <span class="menu-text">Danh Mục</span>
                        <span class="menu-badge"><?php echo $total_categories; ?></span>
                    </a>
                    <a href="admin_orders.php" class="menu-item">
                        <i class="fas fa-shopping-cart"></i>
                        <span class="menu-text">Đơn Hàng</span>
                        <span class="menu-badge"><?php echo $pending_orders; ?></span>
                    </a>
                    <a href="admin_users.php" class="menu-item">
                        <i class="fas fa-users"></i>
                        <span class="menu-text">Người Dùng</span>
                        <span class="menu-badge"><?php echo $total_users; ?></span>
                    </a>
                </div>
                
                <div class="menu-section">
                    <div class="menu-title">Thống Kê</div>
                    <a href="admin_reports.php" class="menu-item">
                        <i class="fas fa-chart-bar"></i>
                        <span class="menu-text">Báo Cáo</span>
                    </a>
                    <a href="admin_inventory.php" class="menu-item">
                        <i class="fas fa-boxes"></i>
                        <span class="menu-text">Tồn Kho</span>
                    </a>
                </div>
                
                <div class="menu-section">
                    <div class="menu-title">Hệ Thống</div>
                    <a href="<?php echo $base_url; ?>/" target="_blank" class="menu-item">
                        <i class="fas fa-external-link-alt"></i>
                        <span class="menu-text">Xem Website</span>
                    </a>
                    <form method="POST" class="d-inline">
                        <button type="submit" name="logout" class="menu-item btn-logout" style="background: none; border: none; width: 100%; text-align: left; color: inherit; padding: 0.85rem 1.5rem; display: flex; align-items: center;">
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
                    <h1 class="page-title">Dashboard</h1>
                </div>
                
                <div class="header-right">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" placeholder="Tìm kiếm...">
                    </div>
                    
                    <div class="header-actions">
                        <button class="notification-btn">
                            <i class="fas fa-bell"></i>
                            <span class="notification-badge">3</span>
                        </button>
                        <button class="message-btn">
                            <i class="fas fa-envelope"></i>
                        </button>
                    </div>
                    
                    <div class="user-profile">
                        <div class="user-avatar">
                            <?php echo getInitials($_SESSION['admin_name'] ?? 'Admin'); ?>
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
                <!-- Welcome Banner -->
                <div class="welcome-banner">
                    <div class="welcome-content">
                        <h1>Chào mừng trở lại, <?php echo $_SESSION['admin_name'] ?? 'Admin'; ?>! 👋</h1>
                        <p>Hôm nay là <?php echo date('l, d/m/Y'); ?> - Quản lý hiệu quả hệ thống nhà thuốc của bạn</p>
                        
                        <div class="welcome-stats">
                            <div class="welcome-stat">
                                <i class="fas fa-shopping-cart"></i>
                                <div>
                                    <div class="stat-value"><?php echo $total_orders; ?></div>
                                    <div class="stat-label">Tổng đơn hàng</div>
                                </div>
                            </div>
                            <div class="welcome-stat">
                                <i class="fas fa-dollar-sign"></i>
                                <div>
                                    <div class="stat-value"><?php echo number_format($revenue_today, 0, ',', '.'); ?>đ</div>
                                    <div class="stat-label">Doanh thu hôm nay</div>
                                </div>
                            </div>
                            <div class="welcome-stat">
                                <i class="fas fa-users"></i>
                                <div>
                                    <div class="stat-value"><?php echo $total_users; ?></div>
                                    <div class="stat-label">Tổng người dùng</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if(isset($error_message)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <?php echo $error_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Statistics Grid -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-info">
                                <div class="stat-title">Tổng Doanh Thu</div>
                                <div class="stat-value"><?php echo number_format($revenue_month, 0, ',', '.'); ?>đ</div>
                                <div class="stat-change positive">
                                    <i class="fas fa-arrow-up"></i>
                                    12.5% so với tháng trước
                                </div>
                            </div>
                            <div class="stat-icon">
                                <i class="fas fa-chart-line"></i>
                            </div>
                        </div>
                        <div class="stat-footer">Doanh thu tháng <?php echo date('m/Y'); ?></div>
                    </div>

                    <div class="stat-card success">
                        <div class="stat-header">
                            <div class="stat-info">
                                <div class="stat-title">Tổng Sản Phẩm</div>
                                <div class="stat-value"><?php echo $total_products; ?></div>
                                <div class="stat-change positive">
                                    <i class="fas fa-arrow-up"></i>
                                    5.2% tăng trưởng
                                </div>
                            </div>
                            <div class="stat-icon">
                                <i class="fas fa-pills"></i>
                            </div>
                        </div>
                        <div class="stat-footer"><?php echo count($low_stock_products); ?> sản phẩm sắp hết hàng</div>
                    </div>

                    <div class="stat-card info">
                        <div class="stat-header">
                            <div class="stat-info">
                                <div class="stat-title">Đơn Hàng</div>
                                <div class="stat-value"><?php echo $total_orders; ?></div>
                                <div class="stat-change positive">
                                    <i class="fas fa-arrow-up"></i>
                                    8.7% so với tháng trước
                                </div>
                            </div>
                            <div class="stat-icon">
                                <i class="fas fa-shopping-cart"></i>
                            </div>
                        </div>
                        <div class="stat-footer"><?php echo $pending_orders; ?> đơn hàng đang chờ xử lý</div>
                    </div>

                    <div class="stat-card warning">
                        <div class="stat-header">
                            <div class="stat-info">
                                <div class="stat-title">Người Dùng</div>
                                <div class="stat-value"><?php echo $total_users; ?></div>
                                <div class="stat-change positive">
                                    <i class="fas fa-arrow-up"></i>
                                    15.3% tăng trưởng
                                </div>
                            </div>
                            <div class="stat-icon">
                                <i class="fas fa-users"></i>
                            </div>
                        </div>
                        <div class="stat-footer">123 người dùng mới tháng này</div>
                    </div>
                </div>

                <!-- Charts Section -->
                <div class="charts-section">
                    <div class="chart-container">
                        <div class="chart-header">
                            <h3 class="chart-title">Doanh Thu 7 Ngày Gần Nhất</h3>
                            <div class="chart-actions">
                                <button class="chart-action-btn active">7D</button>
                                <button class="chart-action-btn">1M</button>
                                <button class="chart-action-btn">3M</button>
                            </div>
                        </div>
                        <div class="chart-area">
                            <canvas id="revenueChart"></canvas>
                        </div>
                    </div>

                    <div class="chart-container">
                        <div class="chart-header">
                            <h3 class="chart-title">Phân Loại Bán Hàng</h3>
                        </div>
                        <div class="chart-area">
                            <canvas id="salesChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Tables Section -->
                <div class="tables-section">
                    <div class="row">
                        <div class="col-lg-6">
                            <div class="table-section">
                                <div class="table-container">
                                    <div class="table-header">
                                        <h3 class="table-title">Đơn Hàng Gần Đây</h3>
                                        <div class="table-actions">
                                            <a href="admin_orders.php" class="btn btn-sm btn-outline-primary">Xem Tất Cả</a>
                                        </div>
                                    </div>
                                    <div class="table-responsive">
                                        <table class="table">
                                            <thead>
                                                <tr>
                                                    <th>Mã ĐH</th>
                                                    <th>Khách Hàng</th>
                                                    <th>Ngày</th>
                                                    <th>Tổng Tiền</th>
                                                    <th>Trạng Thái</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if(!empty($recent_orders)): ?>
                                                    <?php foreach($recent_orders as $order): ?>
                                                    <tr>
                                                        <td>#<?php echo $order['order_code']; ?></td>
                                                        <td><?php echo htmlspecialchars($order['customer_name'] ?? 'N/A'); ?></td>
                                                        <td><?php echo date('d/m/Y', strtotime($order['order_date'])); ?></td>
                                                        <td><?php echo number_format($order['total_amount'], 0, ',', '.'); ?>đ</td>
                                                        <td>
                                                            <span class="badge badge-<?php 
                                                                switch($order['status']) {
                                                                    case 'delivered': echo 'success'; break;
                                                                    case 'processing': echo 'primary'; break;
                                                                    case 'pending': echo 'warning'; break;
                                                                    case 'cancelled': echo 'danger'; break;
                                                                    default: echo 'secondary';
                                                                }
                                                            ?>">
                                                                <?php 
                                                                $status_text = [
                                                                    'delivered' => 'Hoàn thành',
                                                                    'processing' => 'Đang xử lý',
                                                                    'pending' => 'Chờ xác nhận',
                                                                    'cancelled' => 'Đã hủy'
                                                                ];
                                                                echo $status_text[$order['status']] ?? $order['status']; 
                                                                ?>
                                                            </span>
                                                        </td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <tr>
                                                        <td colspan="5" class="text-center py-4 text-muted">
                                                            <i class="fas fa-shopping-cart fa-2x mb-3 d-block"></i>
                                                            Chưa có đơn hàng nào
                                                        </td>
                                                    </tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-6">
                            <div class="table-section">
                                <div class="table-container">
                                    <div class="table-header">
                                        <h3 class="table-title">Sản Phẩm Sắp Hết Hàng</h3>
                                        <div class="table-actions">
                                            <a href="admin_products.php" class="btn btn-sm btn-outline-warning">Quản Lý</a>
                                        </div>
                                    </div>
                                    <div class="table-responsive">
                                        <table class="table">
                                            <thead>
                                                <tr>
                                                    <th>Sản Phẩm</th>
                                                    <th>Tồn Kho</th>
                                                    <th>Giá</th>
                                                    <th>Trạng Thái</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if(!empty($low_stock_products)): ?>
                                                    <?php foreach($low_stock_products as $product): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($product['product_name']); ?></td>
                                                        <td>
                                                            <span class="fw-bold <?php echo $product['stock_quantity'] <= 5 ? 'text-danger' : 'text-warning'; ?>">
                                                                <?php echo $product['stock_quantity']; ?>
                                                            </span>
                                                        </td>
                                                        <td><?php echo number_format($product['price'], 0, ',', '.'); ?>đ</td>
                                                        <td>
                                                            <?php if($product['stock_quantity'] <= 5): ?>
                                                                <span class="badge badge-danger">Sắp hết</span>
                                                            <?php else: ?>
                                                                <span class="badge badge-warning">Còn ít</span>
                                                            <?php endif; ?>
                                                        </td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <tr>
                                                        <td colspan="4" class="text-center py-4 text-success">
                                                            <i class="fas fa-check-circle fa-2x mb-3 d-block"></i>
                                                            Tất cả sản phẩm đều đủ hàng
                                                        </td>
                                                    </tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo $base_url; ?>/assets/js/admin.js"></script>
    
    <script>
        // Initialize charts
        document.addEventListener('DOMContentLoaded', function() {
            // Revenue Chart
            const revenueCtx = document.getElementById('revenueChart').getContext('2d');
            new Chart(revenueCtx, {
                type: 'line',
                data: {
                    labels: ['T2', 'T3', 'T4', 'T5', 'T6', 'T7', 'CN'],
                    datasets: [{
                        label: 'Doanh thu',
                        data: [12000000, 19000000, 15000000, 22000000, 18000000, 25000000, 21000000],
                        borderColor: '#28a745',
                        backgroundColor: 'rgba(40, 167, 69, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: '#28a745',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 5
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return `Doanh thu: ${context.parsed.y.toLocaleString('vi-VN')}đ`;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)'
                            },
                            ticks: {
                                callback: function(value) {
                                    return value.toLocaleString('vi-VN') + 'đ';
                                }
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });

            // Sales Chart
            const salesCtx = document.getElementById('salesChart').getContext('2d');
            new Chart(salesCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Kháng sinh', 'Giảm đau', 'Vitamin', 'Tiêu hóa', 'Da liễu'],
                    datasets: [{
                        data: [35, 25, 20, 12, 8],
                        backgroundColor: [
                            '#28a745',
                            '#17a2b8',
                            '#ffc107',
                            '#6f42c1',
                            '#e83e8c'
                        ],
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '65%',
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 20,
                                usePointStyle: true
                            }
                        }
                    }
                }
            });
        });
    </script>
</body>
</html>