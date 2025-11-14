<?php
// public/admin_order_details.php
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
    require_once $base_dir . 'models/Order.php';

    $database = new Database();
    $db = $database->getConnection();
    
    $orderModel = new Order($db);

    // Lấy ID đơn hàng từ URL
    $order_id = $_GET['id'] ?? 0;
    
    if (!$order_id) {
        header('Location: admin_orders.php');
        exit;
    }

    // Lấy thông tin đơn hàng
    if (!$orderModel->getOrderById($order_id)) {
        $_SESSION['error_message'] = 'Đơn hàng không tồn tại!';
        header('Location: admin_orders.php');
        exit;
    }

    // Lấy chi tiết đơn hàng
    $order_items = $orderModel->getOrderItems($order_id);

    // Xử lý cập nhật trạng thái
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
        $order_status = $_POST['order_status'];
        $payment_status = $_POST['payment_status'];
        
        if ($orderModel->updateStatus($order_id, $order_status, $payment_status)) {
            $_SESSION['success_message'] = 'Cập nhật trạng thái thành công!';
            // Reload thông tin đơn hàng
            $orderModel->getOrderById($order_id);
        } else {
            $_SESSION['error_message'] = 'Có lỗi xảy ra khi cập nhật trạng thái!';
        }
    }

} catch (Exception $e) {
    error_log("Admin order details error: " . $e->getMessage());
    $_SESSION['error_message'] = 'Có lỗi xảy ra khi tải thông tin đơn hàng!';
    header('Location: admin_orders.php');
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
        default: return 'Người dùng';
    }
}

function getStatusBadge($status) {
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

function getPaymentStatusBadge($status) {
    switch ($status) {
        case 'paid': return ['success', 'Đã thanh toán'];
        case 'pending': return ['warning', 'Chờ thanh toán'];
        case 'failed': return ['danger', 'Thất bại'];
        case 'refunded': return ['info', 'Hoàn tiền'];
        default: return ['secondary', $status];
    }
}

function getPaymentMethodText($method) {
    switch ($method) {
        case 'cod': return 'Thanh toán khi nhận hàng (COD)';
        case 'bank_transfer': return 'Chuyển khoản ngân hàng';
        case 'credit_card': return 'Thẻ tín dụng';
        case 'e_wallet': return 'Ví điện tử';
        default: return $method;
    }
}

function getStatusTimeline($status) {
    $timeline = [
        'pending' => ['Chờ xác nhận', 'fas fa-clock'],
        'confirmed' => ['Đã xác nhận', 'fas fa-check-circle'],
        'processing' => ['Đang xử lý', 'fas fa-cog'],
        'shipped' => ['Đang giao', 'fas fa-shipping-fast'],
        'delivered' => ['Đã giao', 'fas fa-box-open']
    ];
    
    $current_status = $status;
    $result = [];
    
    foreach ($timeline as $key => $value) {
        $result[$key] = [
            'title' => $value[0],
            'icon' => $value[1],
            'active' => $key === $current_status,
            'completed' => array_search($key, array_keys($timeline)) <= array_search($current_status, array_keys($timeline))
        ];
    }
    
    return $result;
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi tiết Đơn hàng #<?php echo $orderModel->order_code; ?> - Pharmacy Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo $base_url; ?>/assets/css/admin.css">
    <link rel="stylesheet" href="<?php echo $base_url; ?>/assets/css/admin_order_details.css">
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
                    <a href="admin_orders.php" class="menu-item active">
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
                    <h1 class="page-title">Chi tiết Đơn hàng</h1>
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

                <?php if (isset($_SESSION['error_message'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo $_SESSION['error_message']; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php unset($_SESSION['error_message']); ?>
                <?php endif; ?>

                <div class="row">
                    <!-- Thông tin đơn hàng -->
                    <div class="col-md-8">
                        <div class="card mb-4">
                            <div class="card-header bg-primary text-white">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-shopping-cart me-2"></i>
                                    Đơn hàng #<?php echo $orderModel->order_code; ?>
                                </h5>
                            </div>
                            <div class="card-body">
                                <!-- Timeline trạng thái -->
                                <div class="mb-4">
                                    <h6>Trạng thái đơn hàng</h6>
                                    <div class="timeline">
                                        <?php $timeline = getStatusTimeline($orderModel->order_status); ?>
                                        <?php foreach ($timeline as $status => $item): ?>
                                            <div class="timeline-item <?php echo $item['completed'] ? 'completed' : ''; ?> <?php echo $item['active'] ? 'active' : ''; ?>">
                                                <div class="d-flex align-items-center">
                                                    <i class="<?php echo $item['icon']; ?> me-2 text-<?php echo $item['completed'] ? 'success' : 'secondary'; ?>"></i>
                                                    <span class="<?php echo $item['completed'] ? 'fw-bold text-success' : 'text-muted'; ?>">
                                                        <?php echo $item['title']; ?>
                                                    </span>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>

                                <!-- Chi tiết sản phẩm -->
                                <h6>Chi tiết sản phẩm</h6>
                                <div class="table-responsive">
                                    <table class="table table-bordered">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Sản phẩm</th>
                                                <th>Đơn giá</th>
                                                <th>Số lượng</th>
                                                <th>Thành tiền</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if(!empty($order_items)): ?>
                                                <?php foreach($order_items as $item): ?>
                                                <tr>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <?php if(!empty($item['image_url'])): ?>
                                                                <img src="<?php echo $base_url . '/' . $item['image_url']; ?>" alt="<?php echo htmlspecialchars($item['product_name']); ?>" class="product-image me-3">
                                                            <?php else: ?>
                                                                <div class="product-image-placeholder me-3">
                                                                    <i class="fas fa-pills text-muted"></i>
                                                                </div>
                                                            <?php endif; ?>
                                                            <div>
                                                                <div class="fw-bold"><?php echo htmlspecialchars($item['product_name']); ?></div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td><?php echo number_format($item['product_price'], 0, ',', '.'); ?>đ</td>
                                                    <td><?php echo $item['quantity']; ?></td>
                                                    <td class="fw-bold text-success"><?php echo number_format($item['total_price'], 0, ',', '.'); ?>đ</td>
                                                </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="4" class="text-center text-muted">Không có sản phẩm</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <td colspan="3" class="text-end fw-bold">Tạm tính:</td>
                                                <td class="fw-bold"><?php echo number_format($orderModel->subtotal_amount, 0, ',', '.'); ?>đ</td>
                                            </tr>
                                            <tr>
                                                <td colspan="3" class="text-end fw-bold">Giảm giá:</td>
                                                <td class="fw-bold text-danger">-<?php echo number_format($orderModel->discount_amount, 0, ',', '.'); ?>đ</td>
                                            </tr>
                                            <tr>
                                                <td colspan="3" class="text-end fw-bold">Phí vận chuyển:</td>
                                                <td class="fw-bold"><?php echo number_format($orderModel->shipping_fee, 0, ',', '.'); ?>đ</td>
                                            </tr>
                                            <tr>
                                                <td colspan="3" class="text-end fw-bold fs-5">Tổng cộng:</td>
                                                <td class="fw-bold fs-5 text-success"><?php echo number_format($orderModel->total_amount, 0, ',', '.'); ?>đ</td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Thông tin khách hàng và cập nhật -->
                    <div class="col-md-4">
                        <!-- Thông tin khách hàng -->
                        <div class="card mb-4">
                            <div class="card-header bg-info text-white">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-user me-2"></i>
                                    Thông tin khách hàng
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <strong>Họ tên:</strong><br>
                                    <?php echo htmlspecialchars($orderModel->customer_name); ?>
                                </div>
                                <div class="mb-3">
                                    <strong>Email:</strong><br>
                                    <?php echo htmlspecialchars($orderModel->customer_email); ?>
                                </div>
                                <div class="mb-3">
                                    <strong>Số điện thoại:</strong><br>
                                    <?php echo htmlspecialchars($orderModel->customer_phone); ?>
                                </div>
                                <div class="mb-3">
                                    <strong>Địa chỉ:</strong><br>
                                    <?php echo nl2br(htmlspecialchars($orderModel->customer_address)); ?>
                                </div>
                                <?php if(!empty($orderModel->order_notes)): ?>
                                <div class="mb-3">
                                    <strong>Ghi chú:</strong><br>
                                    <?php echo nl2br(htmlspecialchars($orderModel->order_notes)); ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Cập nhật trạng thái -->
                        <div class="card status-update-card">
                            <div class="card-header bg-warning text-dark">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-edit me-2"></i>
                                    Cập nhật trạng thái
                                </h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="admin_order_details.php?id=<?php echo $order_id; ?>" id="statusUpdateForm">
                                    <div class="mb-3">
                                        <label class="form-label">Trạng thái đơn hàng</label>
                                        <select class="form-select" name="order_status" required id="orderStatus">
                                            <option value="pending" <?php echo $orderModel->order_status === 'pending' ? 'selected' : ''; ?>>Chờ xác nhận</option>
                                            <option value="confirmed" <?php echo $orderModel->order_status === 'confirmed' ? 'selected' : ''; ?>>Đã xác nhận</option>
                                            <option value="processing" <?php echo $orderModel->order_status === 'processing' ? 'selected' : ''; ?>>Đang xử lý</option>
                                            <option value="shipped" <?php echo $orderModel->order_status === 'shipped' ? 'selected' : ''; ?>>Đang giao</option>
                                            <option value="delivered" <?php echo $orderModel->order_status === 'delivered' ? 'selected' : ''; ?>>Đã giao</option>
                                            <option value="cancelled" <?php echo $orderModel->order_status === 'cancelled' ? 'selected' : ''; ?>>Đã hủy</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Trạng thái thanh toán</label>
                                        <select class="form-select" name="payment_status" required id="paymentStatus">
                                            <option value="pending" <?php echo $orderModel->payment_status === 'pending' ? 'selected' : ''; ?>>Chờ thanh toán</option>
                                            <option value="paid" <?php echo $orderModel->payment_status === 'paid' ? 'selected' : ''; ?>>Đã thanh toán</option>
                                            <option value="failed" <?php echo $orderModel->payment_status === 'failed' ? 'selected' : ''; ?>>Thất bại</option>
                                            <option value="refunded" <?php echo $orderModel->payment_status === 'refunded' ? 'selected' : ''; ?>>Hoàn tiền</option>
                                        </select>
                                    </div>
                                    <button type="submit" name="update_status" class="btn btn-primary w-100" id="updateBtn">
                                        <i class="fas fa-save me-2"></i>Cập nhật
                                    </button>
                                </form>
                            </div>
                        </div>

                        <!-- Thông tin thanh toán -->
                        <div class="card mt-4">
                            <div class="card-header bg-success text-white">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-credit-card me-2"></i>
                                    Thông tin thanh toán
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-2">
                                    <strong>Phương thức:</strong><br>
                                    <?php echo getPaymentMethodText($orderModel->payment_method); ?>
                                </div>
                                <div class="mb-2">
                                    <strong>Trạng thái:</strong><br>
                                    <?php 
                                        $paymentBadge = getPaymentStatusBadge($orderModel->payment_status);
                                    ?>
                                    <span class="badge status-badge bg-<?php echo $paymentBadge[0]; ?>">
                                        <?php echo $paymentBadge[1]; ?>
                                    </span>
                                </div>
                                <div class="mb-2">
                                    <strong>Ngày đặt:</strong><br>
                                    <?php echo date('d/m/Y H:i', strtotime($orderModel->created_at)); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="d-flex justify-content-between mt-4">
                    <a href="admin_orders.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Quay lại
                    </a>
                    <div>
                        <button class="btn btn-outline-primary" onclick="window.print()">
                            <i class="fas fa-print me-2"></i>In đơn hàng
                        </button>
                        <button class="btn btn-outline-info" onclick="exportOrderData()">
                            <i class="fas fa-download me-2"></i>Xuất dữ liệu
                        </button>
                        <a href="admin_orders.php" class="btn btn-primary">
                            <i class="fas fa-list me-2"></i>Danh sách đơn hàng
                        </a>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo $base_url; ?>/assets/js/admin_order_details.js"></script>
</body>
</html>