<?php
// public/admin_users.php
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

    $database = new Database();
    $db = $database->getConnection();
    
    $userModel = new User($db);

    // Xử lý các action
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Toggle status individual
        if (isset($_POST['toggle_status'])) {
            $user_id = $_POST['user_id'];
            $current_status = $_POST['current_status'];
            $new_status = $current_status ? 0 : 1;
            
            $userModel->is_active = $new_status;
            $userModel->user_id = $user_id;
            
            if ($userModel->update()) {
                $_SESSION['success_message'] = 'Cập nhật trạng thái thành công!';
            } else {
                $_SESSION['error_message'] = 'Có lỗi xảy ra khi cập nhật trạng thái!';
            }
            header('Location: admin_users.php');
            exit;
        }
        
        // Delete user individual
        if (isset($_POST['delete_user'])) {
            $user_id = $_POST['user_id'];
            if ($userModel->delete($user_id)) {
                $_SESSION['success_message'] = 'Xóa người dùng thành công!';
            } else {
                $_SESSION['error_message'] = 'Có lỗi xảy ra khi xóa người dùng!';
            }
            header('Location: admin_users.php');
            exit;
        }
        
        // Update role individual
        if (isset($_POST['update_role'])) {
            $user_id = $_POST['user_id'];
            $new_role = $_POST['new_role'];
            
            $userModel->role = $new_role;
            $userModel->user_id = $user_id;
            
            if ($userModel->update()) {
                $_SESSION['success_message'] = 'Cập nhật vai trò thành công!';
            } else {
                $_SESSION['error_message'] = 'Có lỗi xảy ra khi cập nhật vai trò!';
            }
            header('Location: admin_users.php');
            exit;
        }

        // Bulk actions
        if (isset($_POST['bulk_action'])) {
            $user_ids = $_POST['user_ids'] ?? [];
            
            if (empty($user_ids)) {
                $_SESSION['error_message'] = 'Vui lòng chọn ít nhất một người dùng!';
                header('Location: admin_users.php');
                exit;
            }

            switch ($_POST['bulk_action']) {
                case 'delete':
                    $success_count = 0;
                    foreach ($user_ids as $user_id) {
                        if ($userModel->delete($user_id)) {
                            $success_count++;
                        }
                    }
                    $_SESSION['success_message'] = "Đã xóa thành công $success_count người dùng!";
                    break;
                    
                case 'update_status':
                    $new_status = $_POST['new_status'];
                    $success_count = 0;
                    foreach ($user_ids as $user_id) {
                        $userModel->is_active = $new_status;
                        $userModel->user_id = $user_id;
                        if ($userModel->update()) {
                            $success_count++;
                        }
                    }
                    $status_text = $new_status ? 'kích hoạt' : 'khóa';
                    $_SESSION['success_message'] = "Đã $status_text thành công $success_count người dùng!";
                    break;
                    
                case 'update_role':
                    $new_role = $_POST['new_role'];
                    $success_count = 0;
                    foreach ($user_ids as $user_id) {
                        $userModel->role = $new_role;
                        $userModel->user_id = $user_id;
                        if ($userModel->update()) {
                            $success_count++;
                        }
                    }
                    $_SESSION['success_message'] = "Đã cập nhật vai trò thành công cho $success_count người dùng!";
                    break;
            }
            
            header('Location: admin_users.php');
            exit;
        }
    }

    // Xử lý tìm kiếm và lọc
    $search_keyword = $_GET['search'] ?? '';
    $role_filter = $_GET['role'] ?? '';
    $status_filter = $_GET['status'] ?? '';

    // Lấy dữ liệu người dùng
    if (!empty($search_keyword)) {
        $users = $userModel->searchUsers($search_keyword);
    } elseif (!empty($role_filter)) {
        $users = $userModel->getUsersByRole($role_filter);
    } elseif (!empty($status_filter)) {
        $users = $userModel->getUsersByStatus($status_filter === 'active' ? 1 : 0);
    } else {
        $users = $userModel->getAllUsers();
    }

    // Lấy thống kê
    $total_users = $userModel->getTotalUsers();
    $admin_count = $userModel->getAdminCount();
    $customer_count = $userModel->getCustomerCount();
    $staff_count = $userModel->getStaffCount();

} catch (Exception $e) {
    error_log("Admin users error: " . $e->getMessage());
    $users = [];
    $total_users = 0;
    $admin_count = 0;
    $customer_count = 0;
    $staff_count = 0;
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
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Người dùng - Pharmacy Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo $base_url; ?>/assets/css/admin.css">
    <link rel="stylesheet" href="<?php echo $base_url; ?>/assets/css/admin_users.css">
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
                        <span class="menu-badge"><?php echo $total_users; ?></span>
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
                    <h1 class="page-title">Quản lý Người dùng</h1>
                </div>
                
                <div class="header-right">
                    <form method="GET" action="admin_users.php" class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" name="search" placeholder="Tìm kiếm người dùng..." value="<?php echo htmlspecialchars($search_keyword); ?>">
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
                                <div class="stat-title">Tổng Người Dùng</div>
                                <div class="stat-value"><?php echo $total_users; ?></div>
                                <div class="stat-change positive">
                                    <i class="fas fa-arrow-up"></i>
                                    Tất cả người dùng
                                </div>
                            </div>
                            <div class="stat-icon">
                                <i class="fas fa-users"></i>
                            </div>
                        </div>
                        <div class="stat-footer">Tất cả người dùng hệ thống</div>
                    </div>

                    <div class="stat-card success">
                        <div class="stat-header">
                            <div class="stat-info">
                                <div class="stat-title">Khách Hàng</div>
                                <div class="stat-value"><?php echo $customer_count; ?></div>
                                <div class="stat-change positive">
                                    <i class="fas fa-user-plus"></i>
                                    Người dùng mua hàng
                                </div>
                            </div>
                            <div class="stat-icon">
                                <i class="fas fa-user"></i>
                            </div>
                        </div>
                        <div class="stat-footer">Người dùng mua hàng</div>
                    </div>

                    <div class="stat-card warning">
                        <div class="stat-header">
                            <div class="stat-info">
                                <div class="stat-title">Quản Trị Viên</div>
                                <div class="stat-value"><?php echo $admin_count; ?></div>
                                <div class="stat-change">
                                    <i class="fas fa-shield-alt"></i>
                                    Quyền cao nhất
                                </div>
                            </div>
                            <div class="stat-icon">
                                <i class="fas fa-crown"></i>
                            </div>
                        </div>
                        <div class="stat-footer">Người quản lý hệ thống</div>
                    </div>

                    <div class="stat-card info">
                        <div class="stat-header">
                            <div class="stat-info">
                                <div class="stat-title">Nhân Viên</div>
                                <div class="stat-value"><?php echo $staff_count; ?></div>
                                <div class="stat-change">
                                    <i class="fas fa-user-tie"></i>
                                    Hỗ trợ hệ thống
                                </div>
                            </div>
                            <div class="stat-icon">
                                <i class="fas fa-user-cog"></i>
                            </div>
                        </div>
                        <div class="stat-footer">Nhân viên quản lý</div>
                    </div>
                </div>

                <!-- Bulk Actions -->
                <div class="bulk-actions d-none mb-3 p-3 bg-light rounded border">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-users text-primary me-2"></i>
                            <span id="selectedUsersCount" class="fw-bold text-primary">0</span>
                            <span class="ms-1">người dùng được chọn</span>
                        </div>
                        <div class="btn-group">
                            <button class="btn btn-outline-success bulk-action-btn" data-action="bulk-activate">
                                <i class="fas fa-check me-2"></i>Kích hoạt
                            </button>
                            <button class="btn btn-outline-warning bulk-action-btn" data-action="bulk-deactivate">
                                <i class="fas fa-ban me-2"></i>Khóa
                            </button>
                            <button class="btn btn-outline-info bulk-action-btn" data-action="bulk-role">
                                <i class="fas fa-user-tag me-2"></i>Đổi vai trò
                            </button>
                            <button class="btn btn-outline-danger bulk-action-btn" data-action="bulk-delete">
                                <i class="fas fa-trash me-2"></i>Xóa
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Filter Section -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" action="admin_users.php" id="userFilters">
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <label class="form-label">Vai trò</label>
                                    <select class="form-select" name="role" id="roleFilter">
                                        <option value="">Tất cả vai trò</option>
                                        <option value="admin" <?php echo $role_filter === 'admin' ? 'selected' : ''; ?>>Quản trị viên</option>
                                        <option value="staff" <?php echo $role_filter === 'staff' ? 'selected' : ''; ?>>Nhân viên</option>
                                        <option value="customer" <?php echo $role_filter === 'customer' ? 'selected' : ''; ?>>Khách hàng</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Trạng thái</label>
                                    <select class="form-select" name="status" id="statusFilter">
                                        <option value="">Tất cả trạng thái</option>
                                        <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Hoạt động</option>
                                        <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>Đã khóa</option>
                                    </select>
                                </div>
                                <div class="col-md-6 d-flex align-items-end">
                                    <button type="submit" class="btn btn-primary me-2">
                                        <i class="fas fa-filter me-2"></i>Lọc
                                    </button>
                                    <a href="admin_users.php" class="btn btn-outline-secondary me-2">
                                        <i class="fas fa-redo me-2"></i>Reset
                                    </a>
                                    <button type="button" class="btn btn-outline-info" onclick="exportUsers()">
                                        <i class="fas fa-download me-2"></i>Export
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Users Table -->
                <div class="table-container">
                    <div class="table-header">
                        <h3 class="table-title">Danh sách Người dùng (<?php echo count($users); ?> người)</h3>
                        <div class="table-actions">
                            <a href="admin_user_add.php" class="btn btn-primary">
                                <i class="fas fa-user-plus me-2"></i>Thêm người dùng
                            </a>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover user-table">
                            <thead class="table-light">
                                <tr>
                                    <th width="40">
                                        <input type="checkbox" id="selectAllUsers" class="form-check-input">
                                    </th>
                                    <th data-sort="name">Người dùng</th>
                                    <th data-sort="email">Thông tin</th>
                                    <th data-sort="role">Vai trò</th>
                                    <th data-sort="status">Trạng thái</th>
                                    <th data-sort="created_at">Ngày tạo</th>
                                    <th width="180">Hành động</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(!empty($users)): ?>
                                    <?php foreach($users as $user): ?>
                                    <?php 
                                        $roleBadge = getRoleBadge($user['role']);
                                        $statusBadge = getStatusBadge($user['is_active']);
                                    ?>
                                    <tr data-user-id="<?php echo $user['user_id']; ?>" 
                                        data-role="<?php echo $user['role']; ?>" 
                                        data-status="<?php echo $user['is_active'] ? 'active' : 'inactive'; ?>">
                                        <td>
                                            <input type="checkbox" class="form-check-input user-select" data-user-id="<?php echo $user['user_id']; ?>">
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="user-avatar me-3">
                                                    <?php echo getInitials($user['full_name']); ?>
                                                </div>
                                                <div>
                                                    <div class="fw-bold user-name"><?php echo htmlspecialchars($user['full_name']); ?></div>
                                                    <small class="text-muted user-username">@<?php echo htmlspecialchars($user['username']); ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="small">
                                                <div class="user-email"><i class="fas fa-envelope me-2 text-muted"></i><?php echo htmlspecialchars($user['email']); ?></div>
                                                <?php if($user['phone']): ?>
                                                <div class="user-phone"><i class="fas fa-phone me-2 text-muted"></i><?php echo htmlspecialchars($user['phone']); ?></div>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo $roleBadge[0]; ?> user-role">
                                                <?php echo $roleBadge[1]; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo $statusBadge[0]; ?> user-status">
                                                <?php echo $statusBadge[1]; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php echo date('d/m/Y', strtotime($user['created_at'])); ?>
                                            <br>
                                            <small class="text-muted"><?php echo date('H:i', strtotime($user['created_at'])); ?></small>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <button class="btn btn-outline-primary user-action-btn" 
                                                        data-action="view" 
                                                        data-user-id="<?php echo $user['user_id']; ?>" 
                                                        title="Xem chi tiết">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button class="btn btn-outline-warning user-action-btn" 
                                                        data-action="edit" 
                                                        data-user-id="<?php echo $user['user_id']; ?>" 
                                                        title="Sửa thông tin">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-outline-info user-action-btn" 
                                                        data-action="reset-password" 
                                                        data-user-id="<?php echo $user['user_id']; ?>" 
                                                        title="Đặt lại mật khẩu">
                                                    <i class="fas fa-key"></i>
                                                </button>
                                                <?php if($user['user_id'] != ($_SESSION['admin_id'] ?? 0)): ?>
                                                <button class="btn btn-outline-<?php echo $user['is_active'] ? 'danger' : 'success'; ?> status-toggle" 
                                                        data-user-id="<?php echo $user['user_id']; ?>" 
                                                        data-status="<?php echo $user['is_active'] ? 'active' : 'inactive'; ?>"
                                                        title="<?php echo $user['is_active'] ? 'Khóa tài khoản' : 'Mở khóa tài khoản'; ?>">
                                                    <i class="fas fa-<?php echo $user['is_active'] ? 'lock' : 'unlock'; ?>"></i>
                                                </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-5 text-muted">
                                            <i class="fas fa-users fa-3x mb-3 d-block"></i>
                                            <?php echo !empty($search_keyword) ? 'Không tìm thấy người dùng phù hợp' : 'Chưa có người dùng nào'; ?>
                                            <br>
                                            <a href="admin_user_add.php" class="btn btn-primary mt-2">
                                                <i class="fas fa-user-plus me-2"></i>Thêm người dùng đầu tiên
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

    <!-- Change Role Modal -->
    <div class="modal fade" id="changeRoleModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Thay đổi vai trò</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="admin_users.php">
                    <div class="modal-body">
                        <input type="hidden" name="user_id" id="role_user_id">
                        <div class="mb-3">
                            <label class="form-label">Vai trò mới</label>
                            <select class="form-select" name="new_role" id="new_role" required>
                                <option value="customer">Khách hàng</option>
                                <option value="staff">Nhân viên</option>
                                <option value="admin">Quản trị viên</option>
                            </select>
                        </div>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Thay đổi vai trò có thể ảnh hưởng đến quyền truy cập của người dùng.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                        <button type="submit" name="update_role" class="btn btn-primary">Cập nhật</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Toggle Status Modal -->
    <div class="modal fade" id="toggleStatusModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="toggleStatusTitle">Xác nhận thay đổi trạng thái</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="admin_users.php">
                    <div class="modal-body">
                        <input type="hidden" name="user_id" id="status_user_id">
                        <input type="hidden" name="current_status" id="current_status">
                        <p id="toggleStatusMessage">Bạn có chắc chắn muốn thay đổi trạng thái của người dùng này?</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                        <button type="submit" name="toggle_status" class="btn btn-warning">Xác nhận</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bulk Role Modal -->
    <div class="modal fade" id="bulkRoleModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Thay đổi vai trò hàng loạt</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="admin_users.php" id="bulkRoleForm">
                    <div class="modal-body">
                        <input type="hidden" name="bulk_action" value="update_role">
                        <div class="mb-3">
                            <label class="form-label">Vai trò mới</label>
                            <select class="form-select" name="new_role" required>
                                <option value="customer">Khách hàng</option>
                                <option value="staff">Nhân viên</option>
                                <option value="admin">Quản trị viên</option>
                            </select>
                        </div>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            Thao tác này sẽ áp dụng cho <span id="bulkUserCount" class="fw-bold">0</span> người dùng đã chọn.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                        <button type="submit" class="btn btn-primary">Cập nhật</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo $base_url; ?>/assets/js/admin_users.js"></script>
</body>
</html>