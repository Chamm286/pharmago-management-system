<?php
// public/admin_user_edit.php
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

    // Xử lý form cập nhật
    $errors = [];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $full_name = trim($_POST['full_name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $role = $_POST['role'] ?? 'customer';
        $is_active = isset($_POST['is_active']) ? 1 : 0;

        // Validate form
        if (empty($username)) {
            $errors['username'] = 'Tên đăng nhập là bắt buộc';
        } elseif (strlen($username) < 3) {
            $errors['username'] = 'Tên đăng nhập phải có ít nhất 3 ký tự';
        } elseif ($username !== $userModel->username && $userModel->usernameExists($username)) {
            $errors['username'] = 'Tên đăng nhập đã tồn tại';
        }

        if (empty($email)) {
            $errors['email'] = 'Email là bắt buộc';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Email không hợp lệ';
        } elseif ($email !== $userModel->email && $userModel->emailExists($email)) {
            $errors['email'] = 'Email đã tồn tại';
        }

        if (empty($full_name)) {
            $errors['full_name'] = 'Họ tên là bắt buộc';
        } elseif (strlen($full_name) < 2) {
            $errors['full_name'] = 'Họ tên phải có ít nhất 2 ký tự';
        }

        // Nếu không có lỗi, cập nhật người dùng
        if (empty($errors)) {
            $userModel->username = $username;
            $userModel->email = $email;
            $userModel->full_name = $full_name;
            $userModel->phone = $phone;
            $userModel->address = $address;
            $userModel->role = $role;
            $userModel->is_active = $is_active;

            if ($userModel->update()) {
                $_SESSION['success_message'] = 'Cập nhật thông tin người dùng thành công!';
                header('Location: admin_user_details.php?id=' . $user_id);
                exit;
            } else {
                $errors['general'] = 'Có lỗi xảy ra khi cập nhật thông tin. Vui lòng thử lại.';
            }
        }
    }

} catch (Exception $e) {
    error_log("Admin user edit error: " . $e->getMessage());
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
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sửa Người dùng - Pharmacy Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo $base_url; ?>/assets/css/admin.css">
    <style>
        .form-section {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }
        .required-field::after {
            content: " *";
            color: #dc3545;
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
                    <h1 class="page-title">Sửa Người dùng</h1>
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
                <!-- Breadcrumb -->
                <nav aria-label="breadcrumb" class="mb-4">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="admin.php">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="admin_users.php">Quản lý Người dùng</a></li>
                        <li class="breadcrumb-item"><a href="admin_user_details.php?id=<?php echo $userModel->user_id; ?>"><?php echo htmlspecialchars($userModel->full_name); ?></a></li>
                        <li class="breadcrumb-item active">Sửa thông tin</li>
                    </ol>
                </nav>

                <!-- Hiển thị lỗi -->
                <?php if (isset($errors['general'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo $errors['general']; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="row">
                    <div class="col-lg-8">
                        <div class="form-section">
                            <div class="form-section-header">
                                <h3 class="mb-0">
                                    <i class="fas fa-user-edit me-2 text-primary"></i>
                                    Chỉnh sửa thông tin
                                </h3>
                                <p class="text-muted mb-0">Cập nhật thông tin người dùng <?php echo htmlspecialchars($userModel->full_name); ?></p>
                            </div>

                            <form method="POST" action="admin_user_edit.php?id=<?php echo $userModel->user_id; ?>" id="userForm">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="username" class="form-label required-field">
                                                Tên đăng nhập
                                            </label>
                                            <input type="text" 
                                                   class="form-control <?php echo isset($errors['username']) ? 'is-invalid' : ''; ?>" 
                                                   id="username" 
                                                   name="username" 
                                                   value="<?php echo htmlspecialchars($_POST['username'] ?? $userModel->username); ?>" 
                                                   required
                                                   placeholder="Nhập tên đăng nhập">
                                            <?php if (isset($errors['username'])): ?>
                                                <div class="invalid-feedback">
                                                    <?php echo $errors['username']; ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="email" class="form-label required-field">
                                                Email
                                            </label>
                                            <input type="email" 
                                                   class="form-control <?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>" 
                                                   id="email" 
                                                   name="email" 
                                                   value="<?php echo htmlspecialchars($_POST['email'] ?? $userModel->email); ?>" 
                                                   required
                                                   placeholder="Nhập địa chỉ email">
                                            <?php if (isset($errors['email'])): ?>
                                                <div class="invalid-feedback">
                                                    <?php echo $errors['email']; ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="full_name" class="form-label required-field">
                                        Họ và tên
                                    </label>
                                    <input type="text" 
                                           class="form-control <?php echo isset($errors['full_name']) ? 'is-invalid' : ''; ?>" 
                                           id="full_name" 
                                           name="full_name" 
                                           value="<?php echo htmlspecialchars($_POST['full_name'] ?? $userModel->full_name); ?>" 
                                           required
                                           placeholder="Nhập họ và tên đầy đủ">
                                    <?php if (isset($errors['full_name'])): ?>
                                        <div class="invalid-feedback">
                                            <?php echo $errors['full_name']; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="phone" class="form-label">Số điện thoại</label>
                                            <input type="tel" 
                                                   class="form-control" 
                                                   id="phone" 
                                                   name="phone" 
                                                   value="<?php echo htmlspecialchars($_POST['phone'] ?? $userModel->phone); ?>" 
                                                   placeholder="Nhập số điện thoại">
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="role" class="form-label">Vai trò</label>
                                            <select class="form-select" id="role" name="role">
                                                <option value="customer" <?php echo ($_POST['role'] ?? $userModel->role) === 'customer' ? 'selected' : ''; ?>>Khách hàng</option>
                                                <option value="staff" <?php echo ($_POST['role'] ?? $userModel->role) === 'staff' ? 'selected' : ''; ?>>Nhân viên</option>
                                                <option value="admin" <?php echo ($_POST['role'] ?? $userModel->role) === 'admin' ? 'selected' : ''; ?>>Quản trị viên</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="address" class="form-label">Địa chỉ</label>
                                    <textarea class="form-control" 
                                              id="address" 
                                              name="address" 
                                              rows="3" 
                                              placeholder="Nhập địa chỉ"><?php echo htmlspecialchars($_POST['address'] ?? $userModel->address); ?></textarea>
                                </div>

                                <div class="mb-4">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" 
                                               type="checkbox" 
                                               id="is_active" 
                                               name="is_active" 
                                               value="1" 
                                               <?php echo ($_POST['is_active'] ?? $userModel->is_active) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="is_active">
                                            Kích hoạt tài khoản
                                        </label>
                                    </div>
                                    <div class="form-text">
                                        Nếu không kích hoạt, người dùng sẽ không thể đăng nhập vào hệ thống.
                                    </div>
                                </div>

                                <div class="d-flex justify-content-between">
                                    <a href="admin_user_details.php?id=<?php echo $userModel->user_id; ?>" class="btn btn-secondary">
                                        <i class="fas fa-arrow-left me-2"></i>Quay lại
                                    </a>
                                    <div>
                                        <a href="admin_user_details.php?id=<?php echo $userModel->user_id; ?>" class="btn btn-outline-secondary me-2">
                                            Hủy
                                        </a>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save me-2"></i>Cập nhật
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <!-- User Info -->
                        <div class="form-section">
                            <h5 class="mb-3">
                                <i class="fas fa-info-circle me-2 text-info"></i>
                                Thông tin người dùng
                            </h5>
                            <div class="small">
                                <div class="mb-2">
                                    <strong>ID người dùng:</strong><br>
                                    <code><?php echo $userModel->user_id; ?></code>
                                </div>
                                <div class="mb-2">
                                    <strong>Ngày tạo:</strong><br>
                                    <?php echo date('d/m/Y H:i', strtotime($userModel->created_at)); ?>
                                </div>
                                <div class="mb-2">
                                    <strong>Lần cập nhật cuối:</strong><br>
                                    <?php echo $userModel->updated_at ? date('d/m/Y H:i', strtotime($userModel->updated_at)) : 'Chưa cập nhật'; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Quick Actions -->
                        <div class="form-section">
                            <h5 class="mb-3">
                                <i class="fas fa-bolt me-2 text-warning"></i>
                                Hành động nhanh
                            </h5>
                            <div class="d-grid gap-2">
                                <a href="admin_user_details.php?id=<?php echo $userModel->user_id; ?>" class="btn btn-outline-primary">
                                    <i class="fas fa-eye me-2"></i>Xem chi tiết
                                </a>
                                <button type="button" class="btn btn-outline-info" onclick="changeRole(<?php echo $userModel->user_id; ?>, '<?php echo $userModel->role; ?>')">
                                    <i class="fas fa-user-tag me-2"></i>Đổi vai trò
                                </button>
                                <?php if($userModel->user_id != ($_SESSION['admin_id'] ?? 0)): ?>
                                <button type="button" class="btn btn-outline-<?php echo $userModel->is_active ? 'danger' : 'success'; ?>" 
                                        onclick="toggleStatus(<?php echo $userModel->user_id; ?>, <?php echo $userModel->is_active ? 'true' : 'false'; ?>)">
                                    <i class="fas fa-<?php echo $userModel->is_active ? 'lock' : 'unlock'; ?> me-2"></i>
                                    <?php echo $userModel->is_active ? 'Khóa tài khoản' : 'Mở khóa tài khoản'; ?>
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Chuyển hướng đến trang đổi vai trò
        function changeRole(userId, currentRole) {
            window.location.href = 'admin_users.php?action=change_role&id=' + userId + '&current_role=' + currentRole;
        }

        // Chuyển hướng đến trang đổi trạng thái
        function toggleStatus(userId, isActive) {
            window.location.href = 'admin_users.php?action=toggle_status&id=' + userId + '&current_status=' + (isActive ? '1' : '0');
        }
    </script>
</body>
</html>