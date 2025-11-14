<?php
// public/admin_login.php - SIMPLIFIED VERSION
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/database.php';

// Nếu đã đăng nhập, chuyển hướng đến admin
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: admin.php');
    exit;
}

// Xử lý đăng nhập
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // DEBUG: Hiển thị thông tin đăng nhập
    error_log("=== LOGIN ATTEMPT ===");
    error_log("Username: " . $username);
    error_log("Password length: " . strlen($password));
    
    if (empty($username) || empty($password)) {
        $error = "Vui lòng nhập đầy đủ thông tin!";
    } else {
        try {
            $database = new Database();
            $db = $database->getConnection();
            
            if ($db) {
                error_log("✅ Database connected successfully");
                
                // Query đơn giản - lấy user từ database
                $query = "SELECT user_id, username, password_hash, full_name, role, email 
                          FROM users 
                          WHERE (username = :username OR email = :username) 
                          AND role IN ('admin', 'staff')";
                
                $stmt = $db->prepare($query);
                $stmt->bindParam(':username', $username);
                $stmt->execute();
                
                error_log("🔍 Query executed. Rows found: " . $stmt->rowCount());
                
                if ($stmt->rowCount() === 1) {
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    error_log("👤 User found: " . $user['username']);
                    error_log("📧 Email: " . $user['email']);
                    error_log("🎭 Role: " . $user['role']);
                    error_log("🔐 Password hash: " . $user['password_hash']);
                    
                    // Kiểm tra mật khẩu
                    if (password_verify($password, $user['password_hash'])) {
                        error_log("✅ Password verified successfully!");
                        
                        // Đăng nhập thành công
                        $_SESSION['admin_logged_in'] = true;
                        $_SESSION['admin_id'] = $user['user_id'];
                        $_SESSION['admin_username'] = $user['username'];
                        $_SESSION['admin_name'] = $user['full_name'];
                        $_SESSION['admin_role'] = $user['role'];
                        $_SESSION['admin_email'] = $user['email'];
                        
                        error_log("🎉 Login successful! Redirecting to admin.php");
                        
                        header('Location: admin.php');
                        exit;
                    } else {
                        error_log("❌ Password verification FAILED!");
                        error_log("Input password: " . $password);
                        error_log("Stored hash: " . $user['password_hash']);
                        $error = "Mật khẩu không đúng!";
                    }
                } else {
                    error_log("❌ User not found or no permission");
                    $error = "Tài khoản không tồn tại hoặc không có quyền truy cập!";
                }
            } else {
                $error = "Không thể kết nối database!";
                error_log("❌ Database connection failed");
            }
        } catch (Exception $e) {
            $error = "Lỗi hệ thống: " . $e->getMessage();
            error_log("💥 Exception: " . $e->getMessage());
        }
    }
    
    // Nếu có lỗi, ghi log
    if (!empty($error)) {
        error_log("🚨 Login error: " . $error);
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng Nhập Admin - Pharmacy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            overflow: hidden;
            max-width: 400px;
            width: 100%;
        }
        .login-header {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        .login-body {
            padding: 2rem;
        }
        .btn-login {
            background: linear-gradient(135deg, #28a745, #20c997);
            border: none;
            color: white;
            width: 100%;
            padding: 12px;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="login-header">
            <div class="logo mb-3">
                <i class="fas fa-prescription-bottle-alt fa-3x"></i>
            </div>
            <h1>Đăng Nhập Admin</h1>
            <p class="mb-0">Hệ thống quản lý nhà thuốc</p>
        </div>
        
        <div class="login-body">
            <?php if(isset($error) && !empty($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <form method="POST" class="login-form">
                <!-- ĐÃ LOẠI BỎ CSRF TOKEN -->
                
                <div class="mb-3">
                    <label class="form-label">Tên đăng nhập hoặc Email</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                        <input type="text" name="username" class="form-control" placeholder="Nhập username hoặc email" required 
                               value="<?php echo htmlspecialchars($_POST['username'] ?? 'tramntb.24it@vku.udn.vn'); ?>">
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Mật khẩu</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                        <input type="password" name="password" class="form-control" placeholder="Nhập mật khẩu" required
                               value="password123">
                        <button type="button" class="input-group-text toggle-password">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <div class="form-text">Mật khẩu mặc định: password123</div>
                </div>
                
                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="rememberMe" name="remember_me">
                    <label class="form-check-label" for="rememberMe">Ghi nhớ đăng nhập</label>
                </div>
                
                <button type="submit" name="login" class="btn btn-login">
                    <i class="fas fa-sign-in-alt me-2"></i>
                    Đăng nhập
                </button>
            </form>

            <div class="text-center mt-3">
                <a href="/PHARMAGO/public/" class="text-decoration-none">
                    <i class="fas fa-arrow-left me-1"></i>Về trang chủ
                </a>
            </div>
            
            <!-- Thông tin test -->
            <div class="mt-4 p-3 bg-light rounded">
                <h6>🔧 Thông tin test:</h6>
                <small class="text-muted">
                    <strong>Username:</strong> tramntb.24it@vku.udn.vn<br>
                    <strong>Password:</strong> password<br>
                    <strong>Role:</strong> admin
                </small>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle password visibility
        document.querySelector('.toggle-password').addEventListener('click', function() {
            const passwordInput = document.querySelector('input[name="password"]');
            const icon = this.querySelector('i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
    </script>
</body>
</html>