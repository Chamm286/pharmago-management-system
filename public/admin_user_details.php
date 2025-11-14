<?php
// public/admin_user_details.php
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
    require_once $base_dir . 'models/User.php';
    require_once $base_dir . 'models/Order.php';

    $database = new Database();
    $db = $database->getConnection();
    
    $userModel = new User($db);
    $orderModel = new Order($db);

    // Lấy ID người dùng từ URL
    $user_id = $_GET['id'] ?? 0;
    
    if (!$user_id) {
        header('Location: admin_users.php');
        exit;
    }

    // Lấy thông tin người dùng
    if (!$userModel->getUserById($user_id)) {
        $_SESSION['error_message'] = 'Người dùng không tồn tại!';
        header('Location: admin_users.php');
        exit;
    }

    // Lấy lịch sử đơn hàng của người dùng
    $user_orders = $orderModel->getOrdersByUser($user_id);

} catch (Exception $e) {
    error_log("Admin user details error: " . $e->getMessage());
    $_SESSION['error_message'] = 'Có lỗi xảy ra khi tải thông tin người dùng!';
    header('Location: admin_users.php');
    exit;
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
        case 'customer': return 'Khách hàng';
        default: return 'Người dùng';
    }
}

function getRoleBadge($role) {
    switch ($role) {
        case 'admin': return ['danger', 'Quản trị viên'];
        case 'staff': return ['warning', 'Nhân viên'];
        case 'customer': return ['success', 'Khách hàng'];
        default: return ['secondary', $role];
    }
}

function getStatusBadge($is_active) {
    return $is_active ? 
        ['success', 'Hoạt động'] : 
        ['danger', 'Đã khóa'];
}

function getOrderStatusBadge($status) {
    switch ($status) {
        case 'pending': return ['warning', 'Chờ xác nhận'];
        case 'confirmed': return ['info', 'Đã xác nhận'];
        case 'processing': return ['primary', 'Đang xử lý'];
        case 'shipped': return ['info', 'Đang giao'];
        case 'delivered': return ['success', 'Đã giao'];
        case 'cancelled': return ['danger', 'Đã hủy'];
        default: return ['secondary', $status];
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi tiết Người dùng - Pharmacy Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo $base_url; ?>/assets/css/admin.css">
    <style>
        .user-profile-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            border-radius: 12px;
            margin-bottom: 2rem;
        }
        .user-avatar-large {
            width: 100px;
            height: 100px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2rem;
            font-weight: 700;
            border: 4px solid white;
        }
        .info-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            margin-bottom: 1.5rem;
            height: 100%;
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
                    <a href="admin_products.php" class="menu-item">
                        <i class="fas fa-pills"></i>
                        <span class="menu-text">Sản Phẩm</span>
                    </a>
                    <a href="admin_categories.php" class="menu-item">
                        <i class="fas fa-tags"></i>
                        <span class="menu-text">Danh Mục</span>
                    </a>
                    <a href="admin_orders.php" class="menu-item">
                        <i class="fas fa-shopping-cart"></i>
                        <span class="menu-text">Đơn Hàng</span>
                    </a>
                    <a href="admin_users.php" class="menu-item active">
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
                    <h1 class="page-title">Chi tiết Người dùng</h1>
                </div>
                
                <div class="header-right">
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
                <!-- Hiển thị thông báo -->
                <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo $_SESSION['success_message']; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php unset($_SESSION['success_message']); ?>
                <?php endif; ?>

                <!-- User Profile Header -->
                <div class="user-profile-header">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <div class="user-avatar-large">
                                <?php echo getInitials($userModel->full_name); ?>
                            </div>
                        </div>
                        <div class="col">
                            <h1 class="mb-1"><?php echo htmlspecialchars($userModel->full_name); ?></h1>
                            <p class="mb-1">@<?php echo htmlspecialchars($userModel->username); ?></p>
                            <div class="d-flex gap-3">
                                <?php 
                                    $roleBadge = getRoleBadge($userModel->role);
                                    $statusBadge = getStatusBadge($userModel->is_active);
                                ?>
                                <span class="badge bg-<?php echo $roleBadge[0]; ?>">
                                    <?php echo $roleBadge[1]; ?>
                                </span>
                                <span class="badge bg-<?php echo $statusBadge[0]; ?>">
                                    <?php echo $statusBadge[1]; ?>
                                </span>
                            </div>
                        </div>
                        <div class="col-auto">
                            <a href="admin_users.php" class="btn btn-light me-2">
                                <i class="fas fa-arrow-left me-2"></i>Quay lại
                            </a>
                            <a href="admin_user_edit.php?id=<?php echo $userModel->user_id; ?>" class="btn btn-warning">
                                <i class="fas fa-edit me-2"></i>Sửa thông tin
                            </a>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Thông tin cá nhân -->
                    <div class="col-md-6">
                        <div class="info-card">
                            <h5 class="card-title mb-3">
                                <i class="fas fa-user me-2 text-primary"></i>
                                Thông tin cá nhân
                            </h5>
                            <div class="row">
                                <div class="col-12 mb-3">
                                    <label class="form-label fw-bold">Họ và tên</label>
                                    <p class="mb-0"><?php echo htmlspecialchars($userModel->full_name); ?></p>
                                </div>
                                <div class="col-6 mb-3">
                                    <label class="form-label fw-bold">Tên đăng nhập</label>
                                    <p class="mb-0">@<?php echo htmlspecialchars($userModel->username); ?></p>
                                </div>
                                <div class="col-6 mb-3">
                                    <label class="form-label fw-bold">Email</label>
                                    <p class="mb-0"><?php echo htmlspecialchars($userModel->email); ?></p>
                                </div>
                                <div class="col-6 mb-3">
                                    <label class="form-label fw-bold">Số điện thoại</label>
                                    <p class="mb-0"><?php echo $userModel->phone ? htmlspecialchars($userModel->phone) : '<em class="text-muted">Chưa cập nhật</em>'; ?></p>
                                </div>
                                <div class="col-6 mb-3">
                                    <label class="form-label fw-bold">Ngày tham gia</label>
                                    <p class="mb-0"><?php echo date('d/m/Y H:i', strtotime($userModel->created_at)); ?></p>
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-bold">Địa chỉ</label>
                                    <p class="mb-0"><?php echo $userModel->address ? nl2br(htmlspecialchars($userModel->address)) : '<em class="text-muted">Chưa cập nhật</em>'; ?></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Thống kê -->
                    <div class="col-md-6">
                        <div class="info-card">
                            <h5 class="card-title mb-3">
                                <i class="fas fa-chart-bar me-2 text-success"></i>
                                Thống kê
                            </h5>
                            <div class="row text-center">
                                <div class="col-4">
                                    <div class="border rounded p-3">
                                        <h3 class="text-primary mb-1"><?php echo count($user_orders); ?></h3>
                                        <small class="text-muted">Tổng đơn hàng</small>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="border rounded p-3">
                                        <h3 class="text-success mb-1">
                                            <?php 
                                                $completed_orders = array_filter($user_orders, function($order) {
                                                    return $order['order_status'] === 'delivered';
                                                });
                                                echo count($completed_orders);
                                            ?>
                                        </h3>
                                        <small class="text-muted">Đơn đã giao</small>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="border rounded p-3">
                                        <h3 class="text-warning mb-1">
                                            <?php 
                                                $pending_orders = array_filter($user_orders, function($order) {
                                                    return in_array($order['order_status'], ['pending', 'confirmed', 'processing']);
                                                });
                                                echo count($pending_orders);
                                            ?>
                                        </h3>
                                        <small class="text-muted">Đang xử lý</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Thông tin tài khoản -->
                        <div class="info-card">
                            <h5 class="card-title mb-3">
                                <i class="fas fa-shield-alt me-2 text-info"></i>
                                Thông tin tài khoản
                            </h5>
                            <div class="row">
                                <div class="col-6 mb-2">
                                    <strong>Vai trò:</strong><br>
                                    <span class="badge bg-<?php echo $roleBadge[0]; ?>">
                                        <?php echo $roleBadge[1]; ?>
                                    </span>
                                </div>
                                <div class="col-6 mb-2">
                                    <strong>Trạng thái:</strong><br>
                                    <span class="badge bg-<?php echo $statusBadge[0]; ?>">
                                        <?php echo $statusBadge[1]; ?>
                                    </span>
                                </div>
                                <div class="col-12 mb-2">
                                    <strong>ID người dùng:</strong><br>
                                    <code><?php echo $userModel->user_id; ?></code>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Lịch sử đơn hàng -->
                <div class="info-card">
                    <h5 class="card-title mb-3">
                        <i class="fas fa-shopping-cart me-2 text-warning"></i>
                        Lịch sử đơn hàng
                    </h5>
                    <?php if(!empty($user_orders)): ?>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th>Mã đơn hàng</th>
                                        <th>Ngày đặt</th>
                                        <th>Tổng tiền</th>
                                        <th>Trạng thái</th>
                                        <th>Thanh toán</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($user_orders as $order): ?>
                                        <?php $orderStatusBadge = getOrderStatusBadge($order['order_status']); ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo $order['order_code']; ?></strong>
                                            </td>
                                            <td><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></td>
                                            <td class="fw-bold text-success"><?php echo number_format($order['total_amount'], 0, ',', '.'); ?>đ</td>
                                            <td>
                                                <span class="badge bg-<?php echo $orderStatusBadge[0]; ?>">
                                                    <?php echo $orderStatusBadge[1]; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo $order['payment_status'] === 'paid' ? 'success' : 'warning'; ?>">
                                                    <?php echo $order['payment_status'] === 'paid' ? 'Đã thanh toán' : 'Chờ thanh toán'; ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4 text-muted">
                            <i class="fas fa-shopping-cart fa-3x mb-3"></i>
                            <p>Người dùng chưa có đơn hàng nào</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>