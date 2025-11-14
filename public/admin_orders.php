<?php
// public/admin_orders.php
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

    // Xử lý các action
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['update_status'])) {
            $order_id = $_POST['order_id'];
            $order_status = $_POST['order_status'];
            $payment_status = $_POST['payment_status'] ?? null;
            
            if ($orderModel->updateStatus($order_id, $order_status, $payment_status)) {
                $_SESSION['success_message'] = 'Cập nhật trạng thái đơn hàng thành công!';
            } else {
                $_SESSION['error_message'] = 'Có lỗi xảy ra khi cập nhật trạng thái!';
            }
            header('Location: admin_orders.php');
            exit;
        }
        
        if (isset($_POST['delete_order'])) {
            $order_id = $_POST['order_id'];
            if ($orderModel->deleteOrder($order_id)) {
                $_SESSION['success_message'] = 'Xóa đơn hàng thành công!';
            } else {
                $_SESSION['error_message'] = 'Có lỗi xảy ra khi xóa đơn hàng!';
            }
            header('Location: admin_orders.php');
            exit;
        }
    }

    // Xử lý tìm kiếm và lọc
    $search_keyword = $_GET['search'] ?? '';
    $status_filter = $_GET['status'] ?? '';
    $payment_filter = $_GET['payment'] ?? '';
    $start_date = $_GET['start_date'] ?? '';
    $end_date = $_GET['end_date'] ?? '';

    // Lấy dữ liệu đơn hàng
    if (!empty($search_keyword)) {
        $orders = $orderModel->searchOrders($search_keyword);
    } elseif (!empty($status_filter)) {
        $orders = $orderModel->getOrdersByStatus($status_filter);
    } else {
        $orders = $orderModel->getAllOrders();
    }

    // Áp dụng bộ lọc ngày thủ công
    if (!empty($start_date) && !empty($end_date)) {
        $orders = array_filter($orders, function($order) use ($start_date, $end_date) {
            $order_date = date('Y-m-d', strtotime($order['created_at']));
            return $order_date >= $start_date && $order_date <= $end_date;
        });
    }

    // Áp dụng bộ lọc thanh toán thủ công
    if (!empty($payment_filter)) {
        $orders = array_filter($orders, function($order) use ($payment_filter) {
            return $order['payment_status'] === $payment_filter;
        });
    }

    $total_orders = $orderModel->getTotalOrders();
    $pending_orders = $orderModel->getPendingOrdersCount();
    $revenue_today = $orderModel->getRevenueToday();
    $revenue_month = $orderModel->getRevenueThisMonth();

} catch (Exception $e) {
    error_log("Admin orders error: " . $e->getMessage());
    $orders = [];
    $total_orders = 0;
    $pending_orders = 0;
    $revenue_today = 0;
    $revenue_month = 0;
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
        case 'cod': return 'COD';
        case 'bank_transfer': return 'Chuyển khoản';
        case 'credit_card': return 'Thẻ tín dụng';
        case 'e_wallet': return 'Ví điện tử';
        default: return $method;
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Đơn hàng - Pharmacy Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo $base_url; ?>/assets/css/admin.css">
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
                        <span class="menu-badge"><?php echo $pending_orders; ?></span>
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
                    <h1 class="page-title">Quản lý Đơn hàng</h1>
                </div>
                
                <div class="header-right">
                    <form method="GET" action="admin_orders.php" class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" name="search" placeholder="Tìm kiếm đơn hàng..." value="<?php echo htmlspecialchars($search_keyword); ?>">
                    </form>
                    
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

                <!-- Stats Cards -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-info">
                                <div class="stat-title">Tổng Đơn Hàng</div>
                                <div class="stat-value"><?php echo $total_orders; ?></div>
                                <div class="stat-change positive">
                                    <i class="fas fa-arrow-up"></i>
                                    Tất cả đơn hàng
                                </div>
                            </div>
                            <div class="stat-icon">
                                <i class="fas fa-shopping-cart"></i>
                            </div>
                        </div>
                        <div class="stat-footer">Tổng số đơn hàng</div>
                    </div>

                    <div class="stat-card warning">
                        <div class="stat-header">
                            <div class="stat-info">
                                <div class="stat-title">Chờ Xác Nhận</div>
                                <div class="stat-value"><?php echo $pending_orders; ?></div>
                                <div class="stat-change negative">
                                    <i class="fas fa-clock"></i>
                                    Cần xử lý
                                </div>
                            </div>
                            <div class="stat-icon">
                                <i class="fas fa-hourglass-half"></i>
                            </div>
                        </div>
                        <div class="stat-footer">Đơn hàng đang chờ</div>
                    </div>

                    <div class="stat-card success">
                        <div class="stat-header">
                            <div class="stat-info">
                                <div class="stat-title">Doanh Thu Hôm Nay</div>
                                <div class="stat-value"><?php echo number_format($revenue_today, 0, ',', '.'); ?>đ</div>
                                <div class="stat-change positive">
                                    <i class="fas fa-calendar-day"></i>
                                    Hôm nay
                                </div>
                            </div>
                            <div class="stat-icon">
                                <i class="fas fa-dollar-sign"></i>
                            </div>
                        </div>
                        <div class="stat-footer">Tổng doanh thu hôm nay</div>
                    </div>

                    <div class="stat-card info">
                        <div class="stat-header">
                            <div class="stat-info">
                                <div class="stat-title">Doanh Thu Tháng</div>
                                <div class="stat-value"><?php echo number_format($revenue_month, 0, ',', '.'); ?>đ</div>
                                <div class="stat-change positive">
                                    <i class="fas fa-chart-line"></i>
                                    Tháng <?php echo date('m/Y'); ?>
                                </div>
                            </div>
                            <div class="stat-icon">
                                <i class="fas fa-chart-bar"></i>
                            </div>
                        </div>
                        <div class="stat-footer">Doanh thu tháng <?php echo date('m/Y'); ?></div>
                    </div>
                </div>

                <!-- Filter Section -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" action="admin_orders.php">
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <label class="form-label">Trạng thái</label>
                                    <select class="form-select" name="status">
                                        <option value="">Tất cả trạng thái</option>
                                        <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Chờ xác nhận</option>
                                        <option value="confirmed" <?php echo $status_filter === 'confirmed' ? 'selected' : ''; ?>>Đã xác nhận</option>
                                        <option value="processing" <?php echo $status_filter === 'processing' ? 'selected' : ''; ?>>Đang xử lý</option>
                                        <option value="shipped" <?php echo $status_filter === 'shipped' ? 'selected' : ''; ?>>Đang giao</option>
                                        <option value="delivered" <?php echo $status_filter === 'delivered' ? 'selected' : ''; ?>>Đã giao</option>
                                        <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Đã hủy</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Thanh toán</label>
                                    <select class="form-select" name="payment">
                                        <option value="">Tất cả</option>
                                        <option value="paid" <?php echo $payment_filter === 'paid' ? 'selected' : ''; ?>>Đã thanh toán</option>
                                        <option value="pending" <?php echo $payment_filter === 'pending' ? 'selected' : ''; ?>>Chờ thanh toán</option>
                                        <option value="failed" <?php echo $payment_filter === 'failed' ? 'selected' : ''; ?>>Thất bại</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Từ ngày</label>
                                    <input type="date" class="form-control" name="start_date" value="<?php echo $start_date; ?>">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Đến ngày</label>
                                    <input type="date" class="form-control" name="end_date" value="<?php echo $end_date; ?>">
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-filter me-2"></i>Lọc
                                    </button>
                                    <a href="admin_orders.php" class="btn btn-outline-secondary">
                                        <i class="fas fa-redo me-2"></i>Reset
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Orders Table -->
                <div class="table-container">
                    <div class="table-header">
                        <h3 class="table-title">Danh sách Đơn hàng (<?php echo count($orders); ?> đơn)</h3>
                        <div class="table-actions">
                            <button class="btn btn-outline-primary" onclick="exportOrders()">
                                <i class="fas fa-download me-2"></i>Export
                            </button>
                            <button class="btn btn-outline-success" onclick="window.print()">
                                <i class="fas fa-print me-2"></i>In
                            </button>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Mã ĐH</th>
                                    <th>Khách hàng</th>
                                    <th>Ngày đặt</th>
                                    <th>Tổng tiền</th>
                                    <th>Trạng thái</th>
                                    <th>Thanh toán</th>
                                    <th>Hành động</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(!empty($orders)): ?>
                                    <?php foreach($orders as $order): ?>
                                    <?php 
                                        $statusBadge = getStatusBadge($order['order_status']);
                                        $paymentBadge = getPaymentStatusBadge($order['payment_status']);
                                    ?>
                                    <tr>
                                        <td>
                                            <strong>#<?php echo $order['order_id']; ?></strong>
                                            <br>
                                            <small class="text-muted"><?php echo $order['order_code']; ?></small>
                                        </td>
                                        <td>
                                            <div class="fw-bold"><?php echo htmlspecialchars($order['customer_name']); ?></div>
                                            <small class="text-muted"><?php echo htmlspecialchars($order['customer_phone']); ?></small>
                                            <br>
                                            <small class="text-muted"><?php echo htmlspecialchars($order['customer_email']); ?></small>
                                        </td>
                                        <td>
                                            <?php echo date('d/m/Y', strtotime($order['created_at'])); ?>
                                            <br>
                                            <small class="text-muted"><?php echo date('H:i', strtotime($order['created_at'])); ?></small>
                                        </td>
                                        <td>
                                            <div class="fw-bold text-success"><?php echo number_format($order['total_amount'], 0, ',', '.'); ?>đ</div>
                                            <small class="text-muted"><?php echo getPaymentMethodText($order['payment_method']); ?></small>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo $statusBadge[0]; ?>">
                                                <?php echo $statusBadge[1]; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo $paymentBadge[0]; ?>">
                                                <?php echo $paymentBadge[1]; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <button class="btn btn-sm btn-outline-primary" title="Chi tiết" onclick="viewOrderDetails(<?php echo $order['order_id']; ?>)">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-success" title="Cập nhật" onclick="openUpdateModal(<?php echo $order['order_id']; ?>, '<?php echo $order['order_status']; ?>', '<?php echo $order['payment_status']; ?>')">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-danger" title="Xóa" onclick="confirmDelete(<?php echo $order['order_id']; ?>)">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-5 text-muted">
                                            <i class="fas fa-shopping-cart fa-3x mb-3 d-block"></i>
                                            <?php echo !empty($search_keyword) ? 'Không tìm thấy đơn hàng phù hợp' : 'Chưa có đơn hàng nào'; ?>
                                            <br>
                                            <small>Đơn hàng sẽ xuất hiện ở đây khi có khách đặt</small>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Modal Update Status -->
    <div class="modal fade" id="updateModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Cập nhật trạng thái đơn hàng</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="admin_orders.php">
                    <div class="modal-body">
                        <input type="hidden" name="order_id" id="update_order_id">
                        <div class="mb-3">
                            <label class="form-label">Trạng thái đơn hàng</label>
                            <select class="form-select" name="order_status" id="order_status">
                                <option value="pending">Chờ xác nhận</option>
                                <option value="confirmed">Đã xác nhận</option>
                                <option value="processing">Đang xử lý</option>
                                <option value="shipped">Đang giao</option>
                                <option value="delivered">Đã giao</option>
                                <option value="cancelled">Đã hủy</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Trạng thái thanh toán</label>
                            <select class="form-select" name="payment_status" id="payment_status">
                                <option value="pending">Chờ thanh toán</option>
                                <option value="paid">Đã thanh toán</option>
                                <option value="failed">Thất bại</option>
                                <option value="refunded">Hoàn tiền</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                        <button type="submit" name="update_status" class="btn btn-primary">Cập nhật</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Xác nhận xóa</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="admin_orders.php">
                    <div class="modal-body">
                        <input type="hidden" name="order_id" id="delete_order_id">
                        <p>Bạn có chắc chắn muốn xóa đơn hàng này? Hành động này không thể hoàn tác.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                        <button type="submit" name="delete_order" class="btn btn-danger">Xóa</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function openUpdateModal(orderId, orderStatus, paymentStatus) {
            document.getElementById('update_order_id').value = orderId;
            document.getElementById('order_status').value = orderStatus;
            document.getElementById('payment_status').value = paymentStatus;
            new bootstrap.Modal(document.getElementById('updateModal')).show();
        }

        function confirmDelete(orderId) {
            document.getElementById('delete_order_id').value = orderId;
            new bootstrap.Modal(document.getElementById('deleteModal')).show();
        }

        function viewOrderDetails(orderId) {
            window.location.href = 'admin_order_details.php?id=' + orderId;
        }

        function exportOrders() {
            // Chức năng export có thể triển khai sau
            alert('Chức năng export sẽ được triển khai sau!');
        }
    </script>
</body>
</html>