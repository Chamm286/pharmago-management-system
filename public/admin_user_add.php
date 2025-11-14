<?php
// public/admin_user_add.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin_login.php');
    exit;
}

// Base URL
$base_url = 'http://localhost/PHARMAGO/public';

// Khởi tạo biến
$errors = [];
$form_data = [
    'username' => '',
    'email' => '',
    'full_name' => '',
    'phone' => '',
    'address' => '',
    'role' => 'customer',
    'is_active' => 1
];

try {
    // Kết nối database
    $base_dir = dirname(__DIR__) . '/';
    require_once $base_dir . 'config/database.php';
    require_once $base_dir . 'models/User.php';

    $database = new Database();
    $db = $database->getConnection();
    
    $userModel = new User($db);

    // Xử lý form thêm người dùng
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Lấy dữ liệu từ form
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $full_name = trim($_POST['full_name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $role = $_POST['role'] ?? 'customer';
        $is_active = isset($_POST['is_active']) ? 1 : 0;

        // Lưu dữ liệu form để hiển thị lại nếu có lỗi
        $form_data = [
            'username' => $username,
            'email' => $email,
            'full_name' => $full_name,
            'phone' => $phone,
            'address' => $address,
            'role' => $role,
            'is_active' => $is_active
        ];

        // Validate form
        if (empty($username)) {
            $errors['username'] = 'Tên đăng nhập là bắt buộc';
        } elseif (strlen($username) < 3) {
            $errors['username'] = 'Tên đăng nhập phải có ít nhất 3 ký tự';
        } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            $errors['username'] = 'Tên đăng nhập chỉ được chứa chữ cái, số và dấu gạch dưới';
        } elseif ($userModel->usernameExists($username)) {
            $errors['username'] = 'Tên đăng nhập đã tồn tại';
        }

        if (empty($email)) {
            $errors['email'] = 'Email là bắt buộc';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Email không hợp lệ';
        } elseif ($userModel->emailExists($email)) {
            $errors['email'] = 'Email đã tồn tại';
        }

        if (empty($password)) {
            $errors['password'] = 'Mật khẩu là bắt buộc';
        } elseif (strlen($password) < 6) {
            $errors['password'] = 'Mật khẩu phải có ít nhất 6 ký tự';
        }

        if ($password !== $confirm_password) {
            $errors['confirm_password'] = 'Mật khẩu xác nhận không khớp';
        }

        if (empty($full_name)) {
            $errors['full_name'] = 'Họ tên là bắt buộc';
        } elseif (strlen($full_name) < 2) {
            $errors['full_name'] = 'Họ tên phải có ít nhất 2 ký tự';
        }

        // Nếu không có lỗi, thêm người dùng
        if (empty($errors)) {
            $userModel->username = $username;
            $userModel->email = $email;
            $userModel->password_hash = password_hash($password, PASSWORD_DEFAULT);
            $userModel->full_name = $full_name;
            $userModel->phone = $phone;
            $userModel->address = $address;
            $userModel->role = $role;
            $userModel->is_active = $is_active;

            if ($userModel->create()) {
                $_SESSION['success_message'] = 'Thêm người dùng thành công!';
                header('Location: admin_users.php');
                exit;
            } else {
                $errors['general'] = 'Có lỗi xảy ra khi thêm người dùng. Vui lòng thử lại.';
                error_log("Failed to create user: " . $username);
            }
        }
    }

} catch (Exception $e) {
    error_log("Admin user add error: " . $e->getMessage());
    $errors['general'] = 'Có lỗi xảy ra khi kết nối database. Vui lòng thử lại.';
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
    <title>Thêm Người dùng - Pharmacy Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo $base_url; ?>/assets/css/admin.css">
    <link rel="stylesheet" href="<?php echo $base_url; ?>/assets/css/admin_users.css">
    <style>
        .password-strength {
            height: 5px;
            margin-top: 5px;
            border-radius: 2px;
            transition: all 0.3s ease;
        }
        .password-weak { background-color: #dc3545; width: 25%; }
        .password-medium { background-color: #ffc107; width: 50%; }
        .password-strong { background-color: #28a745; width: 75%; }
        .password-very-strong { background-color: #20c997; width: 100%; }
        
        .form-section {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }
        
        .form-section-header {
            border-bottom: 2px solid #e9ecef;
            padding-bottom: 1rem;
            margin-bottom: 2rem;
        }
        
        .role-info {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 1rem;
            margin-top: 1rem;
        }
        
        .role-info ul {
            margin-bottom: 0;
        }
        
        .role-info li {
            margin-bottom: 0.5rem;
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
                    <h1 class="page-title">Thêm Người dùng</h1>
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
                        <li class="breadcrumb-item active">Thêm Người dùng</li>
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
                                    <i class="fas fa-user-plus me-2 text-primary"></i>
                                    Thông tin người dùng
                                </h3>
                                <p class="text-muted mb-0">Nhập thông tin cơ bản của người dùng mới</p>
                            </div>

                            <form method="POST" action="admin_user_add.php" id="userForm" novalidate>
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
                                                   value="<?php echo htmlspecialchars($form_data['username']); ?>" 
                                                   required
                                                   placeholder="Nhập tên đăng nhập">
                                            <?php if (isset($errors['username'])): ?>
                                                <div class="invalid-feedback">
                                                    <?php echo $errors['username']; ?>
                                                </div>
                                            <?php else: ?>
                                                <div class="form-text">Tên đăng nhập phải là duy nhất và không chứa ký tự đặc biệt.</div>
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
                                                   value="<?php echo htmlspecialchars($form_data['email']); ?>" 
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

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="password" class="form-label required-field">
                                                Mật khẩu
                                            </label>
                                            <input type="password" 
                                                   class="form-control <?php echo isset($errors['password']) ? 'is-invalid' : ''; ?>" 
                                                   id="password" 
                                                   name="password" 
                                                   required
                                                   placeholder="Nhập mật khẩu">
                                            <div class="password-strength" id="passwordStrength"></div>
                                            <?php if (isset($errors['password'])): ?>
                                                <div class="invalid-feedback">
                                                    <?php echo $errors['password']; ?>
                                                </div>
                                            <?php else: ?>
                                                <div class="form-text">Mật khẩu phải có ít nhất 6 ký tự.</div>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="confirm_password" class="form-label required-field">
                                                Xác nhận mật khẩu
                                            </label>
                                            <input type="password" 
                                                   class="form-control <?php echo isset($errors['confirm_password']) ? 'is-invalid' : ''; ?>" 
                                                   id="confirm_password" 
                                                   name="confirm_password" 
                                                   required
                                                   placeholder="Nhập lại mật khẩu">
                                            <?php if (isset($errors['confirm_password'])): ?>
                                                <div class="invalid-feedback">
                                                    <?php echo $errors['confirm_password']; ?>
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
                                           value="<?php echo htmlspecialchars($form_data['full_name']); ?>" 
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
                                                   value="<?php echo htmlspecialchars($form_data['phone']); ?>" 
                                                   placeholder="Nhập số điện thoại">
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="role" class="form-label">Vai trò</label>
                                            <select class="form-select" id="role" name="role">
                                                <option value="customer" <?php echo $form_data['role'] === 'customer' ? 'selected' : ''; ?>>Khách hàng</option>
                                                <option value="staff" <?php echo $form_data['role'] === 'staff' ? 'selected' : ''; ?>>Nhân viên</option>
                                                <option value="admin" <?php echo $form_data['role'] === 'admin' ? 'selected' : ''; ?>>Quản trị viên</option>
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
                                              placeholder="Nhập địa chỉ"><?php echo htmlspecialchars($form_data['address']); ?></textarea>
                                </div>

                                <div class="mb-4">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" 
                                               type="checkbox" 
                                               id="is_active" 
                                               name="is_active" 
                                               value="1" 
                                               <?php echo $form_data['is_active'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="is_active">
                                            Kích hoạt tài khoản
                                        </label>
                                    </div>
                                    <div class="form-text">
                                        Nếu không kích hoạt, người dùng sẽ không thể đăng nhập vào hệ thống.
                                    </div>
                                </div>

                                <div class="d-flex justify-content-between">
                                    <a href="admin_users.php" class="btn btn-secondary">
                                        <i class="fas fa-arrow-left me-2"></i>Quay lại
                                    </a>
                                    <div>
                                        <button type="button" class="btn btn-outline-info me-2" onclick="generatePassword()">
                                            <i class="fas fa-dice me-2"></i>Tạo mật khẩu
                                        </button>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save me-2"></i>Thêm người dùng
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <!-- Role Information -->
                        <div class="form-section">
                            <h5 class="mb-3">
                                <i class="fas fa-info-circle me-2 text-info"></i>
                                Thông tin vai trò
                            </h5>
                            
                            <div class="role-info">
                                <h6 class="text-success">Khách hàng</h6>
                                <ul class="small">
                                    <li>Mua sắm sản phẩm</li>
                                    <li>Đặt hàng trực tuyến</li>
                                    <li>Xem lịch sử đơn hàng</li>
                                    <li>Cập nhật thông tin cá nhân</li>
                                </ul>

                                <h6 class="text-warning">Nhân viên</h6>
                                <ul class="small">
                                    <li>Tất cả quyền của Khách hàng</li>
                                    <li>Quản lý sản phẩm, danh mục</li>
                                    <li>Xử lý đơn hàng</li>
                                    <li>Quản lý kho hàng</li>
                                </ul>

                                <h6 class="text-danger">Quản trị viên</h6>
                                <ul class="small">
                                    <li>Tất cả quyền của Nhân viên</li>
                                    <li>Quản lý người dùng</li>
                                    <li>Cấu hình hệ thống</li>
                                    <li>Toàn quyền truy cập</li>
                                </ul>
                            </div>
                        </div>

                        <!-- Quick Tips -->
                        <div class="form-section">
                            <h5 class="mb-3">
                                <i class="fas fa-lightbulb me-2 text-warning"></i>
                                Mẹo nhanh
                            </h5>
                            <div class="small">
                                <div class="mb-2">
                                    <strong>Tên đăng nhập:</strong> Sử dụng chữ thường, không dấu, không ký tự đặc biệt.
                                </div>
                                <div class="mb-2">
                                    <strong>Mật khẩu:</strong> Kết hợp chữ hoa, chữ thường, số và ký tự đặc biệt để bảo mật tốt hơn.
                                </div>
                                <div class="mb-2">
                                    <strong>Vai trò:</strong> Chỉ cấp quyền Quản trị viên cho những người thực sự cần thiết.
                                </div>
                                <div>
                                    <strong>Kích hoạt:</strong> Có thể tạo tài khoản nhưng chưa kích hoạt để chờ xác nhận.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Password strength indicator
        function setupPasswordStrength() {
            const passwordInput = document.getElementById('password');
            const strengthBar = document.getElementById('passwordStrength');
            
            if (passwordInput && strengthBar) {
                passwordInput.addEventListener('input', function() {
                    const password = this.value;
                    const strength = calculatePasswordStrength(password);
                    
                    // Reset classes
                    strengthBar.className = 'password-strength';
                    
                    // Add strength class
                    if (password.length === 0) {
                        strengthBar.style.width = '0%';
                    } else {
                        switch (strength) {
                            case 'weak':
                                strengthBar.classList.add('password-weak');
                                break;
                            case 'medium':
                                strengthBar.classList.add('password-medium');
                                break;
                            case 'strong':
                                strengthBar.classList.add('password-strong');
                                break;
                            case 'very-strong':
                                strengthBar.classList.add('password-very-strong');
                                break;
                        }
                    }
                });
            }
        }

        function calculatePasswordStrength(password) {
            let score = 0;
            
            if (!password) return 'weak';
            
            // Length check
            if (password.length >= 6) score++;
            if (password.length >= 8) score++;
            if (password.length >= 12) score++;
            
            // Character variety
            if (/[a-z]/.test(password)) score++;
            if (/[A-Z]/.test(password)) score++;
            if (/[0-9]/.test(password)) score++;
            if (/[^a-zA-Z0-9]/.test(password)) score++;
            
            if (score >= 6) return 'very-strong';
            if (score >= 4) return 'strong';
            if (score >= 3) return 'medium';
            return 'weak';
        }

        // Generate random password
        function generatePassword() {
            const length = 12;
            const charset = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*";
            let password = "";
            
            for (let i = 0; i < length; i++) {
                password += charset.charAt(Math.floor(Math.random() * charset.length));
            }
            
            const passwordInput = document.getElementById('password');
            const confirmInput = document.getElementById('confirm_password');
            
            if (passwordInput) passwordInput.value = password;
            if (confirmInput) confirmInput.value = password;
            
            // Trigger password strength calculation
            if (passwordInput) {
                passwordInput.dispatchEvent(new Event('input'));
            }
            
            showToast('Đã tạo mật khẩu ngẫu nhiên!', 'success');
        }

        // Show toast notification
        function showToast(message, type = 'info') {
            // Remove existing toasts
            const existingToasts = document.querySelectorAll('.custom-toast');
            existingToasts.forEach(toast => toast.remove());
            
            const toast = document.createElement('div');
            toast.className = `custom-toast alert alert-${type} alert-dismissible fade show`;
            toast.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 9999;
                min-width: 300px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            `;
            
            toast.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            document.body.appendChild(toast);
            
            // Auto remove after 5 seconds
            setTimeout(() => {
                if (toast.parentNode) {
                    toast.remove();
                }
            }, 5000);
        }

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            setupPasswordStrength();
        });
    </script>
</body>
</html>