<?php
// views/frontend/login.php

// Bật hiển thị lỗi để debug
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start session nếu chưa có
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Nếu đã đăng nhập, chuyển hướng về trang chủ
if(isset($_SESSION['user_id'])) {
    header("Location: /PHARMAGO/public/");
    exit;
}

try {
    // SỬA LẠI ĐƯỜNG DẪN - ĐƠN GIẢN HƠN
    $base_dir = dirname(__DIR__, 2) . '/'; // Lên 2 level từ views/frontend/ -> root project
    
    $config_path = $base_dir . 'config/database.php';
    
    // Kiểm tra file config
    if (!file_exists($config_path)) {
        throw new Exception('Database config file not found at: ' . $config_path);
    }
    require_once $config_path;
    
    // Kiểm tra file models
    $user_model_path = $base_dir . 'models/User.php';
    
    if (!file_exists($user_model_path)) {
        throw new Exception('User model file not found at: ' . $user_model_path);
    }
    require_once $user_model_path;

    // Khởi tạo database và models
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception('Cannot connect to database. Check database configuration.');
    }

    $user = new User($db);

    // Xử lý đăng nhập
    $login_error = '';
    $register_error = '';
    $register_success = '';

    if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login'])) {
        $username = $_POST['username'];
        $password = $_POST['password'];
        
        if($user->login($username, $password)) {
            $_SESSION['user_id'] = $user->user_id;
            $_SESSION['username'] = $user->username;
            $_SESSION['full_name'] = $user->full_name;
            $_SESSION['email'] = $user->email;
            $_SESSION['role'] = $user->role;
            
            // Chuyển hướng sau khi đăng nhập thành công
            header("Location: /PHARMAGO/public/");
            exit;
        } else {
            $login_error = "Tên đăng nhập hoặc mật khẩu không đúng!";
        }
    }

    // Xử lý đăng ký
    if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['register'])) {
        // Lấy dữ liệu từ form
        $full_name = $_POST['full_name'];
        $username = $_POST['username'];
        $email = $_POST['email'];
        $phone = $_POST['phone'];
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Validate
        if($password !== $confirm_password) {
            $register_error = "Mật khẩu xác nhận không khớp!";
        } elseif(strlen($password) < 6) {
            $register_error = "Mật khẩu phải có ít nhất 6 ký tự!";
        } else {
            // Gán giá trị cho user object
            $user->full_name = $full_name;
            $user->username = $username;
            $user->email = $email;
            $user->phone = $phone;
            
            if($user->register($password)) {
                $register_success = "Đăng ký thành công! Vui lòng đăng nhập.";
            } else {
                $register_error = "Tên đăng nhập hoặc email đã tồn tại!";
            }
        }
    }

} catch (Exception $e) {
    error_log("Login page error: " . $e->getMessage());
    $login_error = '';
    $register_error = '';
    $register_success = '';
    $error_message = "Hệ thống đang bảo trì. Vui lòng thử lại sau.";
    $debug_error = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nhà thuốc Pharmacy - Đăng nhập/Đăng ký</title>
    <link rel="icon" type="image/x-icon" href="/PHARMAGO/public/assets/images/favicon.ico">
    
    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #2e7d32;
            --secondary-color: #81c784;
            --light-color: #e8f5e9;
            --dark-color: #1b5e20;
            --accent-color: #ffab00;
            --error-color: #dc3545;
            --success-color: #28a745;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            overflow-x: hidden;
        }

        /* Navigation */
        .navbar {
            background-color: white;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
            padding: 12px 0;
        }

        .navbar-brand {
            font-weight: 700;
            color: var(--primary-color) !important;
            font-size: 1.8rem;
            display: flex;
            align-items: center;
        }

        .navbar-brand i {
            font-size: 1.5em;
            margin-right: 10px;
            color: var(--primary-color);
        }

        .nav-link {
            color: var(--dark-color) !important;
            font-weight: 500;
            margin: 0 8px;
            position: relative;
            padding: 8px 12px !important;
            transition: all 0.3s ease;
        }

        .nav-link i {
            margin-right: 6px;
            width: 20px;
            text-align: center;
        }

        .nav-link:after {
            content: '';
            position: absolute;
            width: 0;
            height: 3px;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            background-color: var(--primary-color);
            transition: all 0.3s ease;
            border-radius: 3px 3px 0 0;
        }

        .nav-link:hover:after,
        .nav-link.active:after {
            width: 80%;
        }

        .nav-link:hover,
        .nav-link.active {
            color: var(--primary-color) !important;
        }

        /* Container chính */
        .main-container {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
            min-height: calc(100vh - 76px);
        }

        /* Container đăng nhập/đăng ký */
        .auth-container {
            display: flex;
            max-width: 1000px;
            width: 100%;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            min-height: 600px;
            position: relative;
            animation: fadeIn 0.6s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Phần hình ảnh/giới thiệu */
        .auth-hero {
            flex: 1;
            background: linear-gradient(135deg, var(--primary-color), var(--dark-color));
            color: white;
            padding: 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .hero-content {
            position: relative;
            z-index: 2;
        }

        .hero-icon {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.9;
        }

        .hero-content h2 {
            font-size: 2.2rem;
            margin-bottom: 15px;
            font-weight: 700;
        }

        .hero-content p {
            font-size: 1.1rem;
            opacity: 0.9;
            line-height: 1.6;
        }

        .hero-decoration {
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
        }

        .decoration-1 {
            width: 200px;
            height: 200px;
            top: -50px;
            right: -50px;
        }

        .decoration-2 {
            width: 150px;
            height: 150px;
            bottom: -30px;
            left: -30px;
        }

        /* Phần form */
        .auth-forms {
            flex: 1;
            padding: 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
        }

        .form-section {
            transition: all 0.4s ease-in-out;
            width: 100%;
        }

        .form-hidden {
            opacity: 0;
            transform: translateX(50px);
            position: absolute;
            top: 40px;
            pointer-events: none;
        }

        .form-visible {
            opacity: 1;
            transform: translateX(0);
            position: relative;
        }

        /* Header form */
        .auth-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .auth-header i {
            font-size: 2.5rem;
            color: var(--primary-color);
            margin-bottom: 15px;
            display: block;
        }

        .auth-header h2 {
            color: var(--dark-color);
            font-weight: 700;
            margin-bottom: 8px;
            font-size: 1.8rem;
        }

        .auth-header p {
            color: #6c757d;
            font-size: 0.95rem;
        }

        /* Form controls */
        .form-group {
            margin-bottom: 20px;
            position: relative;
        }

        .form-label {
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 8px;
            display: block;
        }

        .input-with-icon {
            position: relative;
        }

        .input-with-icon i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--secondary-color);
            z-index: 2;
        }

        .form-control {
            width: 100%;
            height: 50px;
            padding: 12px 15px 12px 45px;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            background: #fff;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(46, 125, 50, 0.25);
            outline: none;
        }

        /* Button */
        .btn-auth {
            width: 100%;
            height: 50px;
            background: linear-gradient(135deg, var(--primary-color), var(--dark-color));
            border: none;
            border-radius: 10px;
            color: white;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-top: 10px;
        }

        .btn-auth:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(46, 125, 50, 0.3);
        }

        .btn-auth:active {
            transform: translateY(0);
        }

        .btn-auth.loading {
            pointer-events: none;
            opacity: 0.7;
        }

        .btn-auth.loading i {
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        /* Footer form */
        .auth-footer {
            text-align: center;
            margin-top: 25px;
            color: #6c757d;
            font-size: 0.95rem;
        }

        .auth-link {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
        }

        .auth-link:hover {
            color: var(--dark-color);
        }

        .auth-link::after {
            content: '';
            position: absolute;
            width: 0;
            height: 2px;
            bottom: -2px;
            left: 0;
            background-color: var(--primary-color);
            transition: width 0.3s ease;
        }

        .auth-link:hover::after {
            width: 100%;
        }

        /* Utilities */
        .form-check {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-check-input {
            width: 18px;
            height: 18px;
            margin-top: 0;
        }

        .form-check-input:checked {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .form-check-label {
            font-size: 0.9rem;
            color: #6c757d;
        }

        .forgot-link {
            color: var(--primary-color);
            text-decoration: none;
            font-size: 0.9rem;
            transition: color 0.3s ease;
        }

        .forgot-link:hover {
            color: var(--dark-color);
        }

        /* Alert messages */
        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: none;
            font-size: 0.95rem;
        }

        .alert-danger {
            background-color: rgba(220, 53, 69, 0.1);
            color: var(--error-color);
            border-left: 4px solid var(--error-color);
        }

        .alert-success {
            background-color: rgba(40, 167, 69, 0.1);
            color: var(--success-color);
            border-left: 4px solid var(--success-color);
        }

        /* Password strength */
        .password-strength {
            font-size: 0.875rem;
            margin-top: 0.25rem;
            font-weight: 500;
        }

        .field-error {
            font-size: 0.875rem;
            margin-top: 0.25rem;
        }

        /* Footer */
        .footer {
            background: linear-gradient(135deg, var(--dark-color), var(--primary-color));
            color: white;
            padding: 40px 0 20px;
            margin-top: auto;
        }

        .footer h5 {
            color: var(--light-color);
            font-weight: 600;
            margin-bottom: 20px;
            position: relative;
        }

        .footer h5:after {
            content: '';
            position: absolute;
            left: 0;
            bottom: -8px;
            width: 40px;
            height: 3px;
            background-color: var(--accent-color);
            border-radius: 2px;
        }

        .footer p {
            color: rgba(255, 255, 255, 0.8);
            line-height: 1.6;
        }

        .footer ul {
            list-style: none;
            padding: 0;
        }

        .footer ul li {
            margin-bottom: 10px;
        }

        .footer ul a {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .footer ul a:hover {
            color: var(--accent-color);
            transform: translateX(5px);
        }

        .social-icons {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }

        .social-icons a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            color: white;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .social-icons a:hover {
            background: var(--accent-color);
            transform: translateY(-3px);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .auth-container {
                flex-direction: column;
                max-width: 500px;
            }
            
            .auth-hero {
                padding: 30px 20px;
                min-height: 200px;
            }
            
            .hero-content h2 {
                font-size: 1.8rem;
            }
            
            .auth-forms {
                padding: 30px 25px;
            }
            
            .auth-header h2 {
                font-size: 1.6rem;
            }

            .navbar-brand {
                font-size: 1.5rem;
            }
        }

        @media (max-width: 480px) {
            .main-container {
                padding: 20px 15px;
            }
            
            .auth-forms {
                padding: 25px 20px;
            }
            
            .auth-hero {
                padding: 25px 15px;
            }
            
            .hero-content h2 {
                font-size: 1.5rem;
            }
            
            .auth-header h2 {
                font-size: 1.4rem;
            }
        }

        /* Debug Info */
        .debug-info {
            position: fixed;
            bottom: 10px;
            right: 10px;
            background: rgba(0,0,0,0.8);
            color: white;
            padding: 10px;
            border-radius: 5px;
            font-size: 12px;
            z-index: 1000;
            max-width: 300px;
        }
    </style>
</head>
<body>
    <!-- Debug Info (Chỉ hiển thị khi có lỗi) -->
    <?php if(isset($debug_error)): ?>
    <div class="debug-info">
        <strong>Debug Information:</strong><br>
        <strong>Error:</strong> <?php echo htmlspecialchars($debug_error); ?><br>
        <strong>Config Path:</strong> <?php echo htmlspecialchars($base_dir . 'config/database.php'); ?><br>
        <strong>Config Exists:</strong> <?php echo file_exists($base_dir . 'config/database.php') ? 'Yes' : 'No'; ?>
    </div>
    <?php endif; ?>

    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light sticky-top">
        <div class="container">
            <a class="navbar-brand fw-bold" href="/PHARMAGO/public/">
                <i class="fas fa-prescription-bottle-alt me-2"></i>Pharmacy
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="/PHARMAGO/public/">
                            <i class="fas fa-home me-1"></i>Trang chủ
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/PHARMAGO/public/categories">
                            <i class="fas fa-list me-1"></i>Danh mục
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/PHARMAGO/public/products">
                            <i class="fas fa-pills me-1"></i>Sản phẩm
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/PHARMAGO/public/about">
                            <i class="fas fa-info-circle me-1"></i>Giới thiệu
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/PHARMAGO/public/services">
                            <i class="fas fa-concierge-bell me-1"></i>Dịch vụ
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/PHARMAGO/public/contact">
                            <i class="fas fa-phone me-1"></i>Liên hệ
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="/PHARMAGO/public/login">
                            <i class="fas fa-sign-in-alt me-1"></i>Đăng nhập
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Auth Container -->
    <div class="main-container">
        <div class="auth-container">
            <!-- Phần hình ảnh/giới thiệu -->
            <div class="auth-hero">
                <div class="hero-decoration decoration-1"></div>
                <div class="hero-decoration decoration-2"></div>
                <div class="hero-content">
                    <i class="hero-icon fas fa-user-md"></i>
                    <h2>Chào Mừng Trở Lại</h2>
                    <p>Đăng nhập để tiếp tục trải nghiệm dịch vụ chăm sóc sức khỏe tốt nhất từ Pharmacy.</p>
                </div>
            </div>

            <!-- Phần form -->
            <div class="auth-forms">
                <!-- Login Form -->
                <div id="login-section" class="form-section <?php echo isset($_GET['action']) && $_GET['action'] == 'register' ? 'form-hidden' : 'form-visible'; ?>">
                    <div class="auth-header">
                        <i class="fas fa-sign-in-alt"></i>
                        <h2>Đăng Nhập</h2>
                        <p>Vui lòng nhập thông tin tài khoản của bạn</p>
                    </div>
                    
                    <?php if(isset($login_error) && $login_error): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <?php echo htmlspecialchars($login_error); ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if(isset($register_success) && $register_success): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle me-2"></i>
                        <?php echo htmlspecialchars($register_success); ?>
                    </div>
                    <?php endif; ?>
                    
                    <form id="login-form" method="POST" action="">
                        <input type="hidden" name="login" value="1">
                        
                        <div class="form-group">
                            <label for="login-username" class="form-label">Tên đăng nhập hoặc Email</label>
                            <div class="input-with-icon">
                                <i class="fas fa-user"></i>
                                <input type="text" class="form-control" id="login-username" name="username" 
                                       value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" 
                                       placeholder="Nhập tên đăng nhập hoặc email" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="login-password" class="form-label">Mật khẩu</label>
                            <div class="input-with-icon">
                                <i class="fas fa-lock"></i>
                                <input type="password" class="form-control" id="login-password" name="password" 
                                       placeholder="Nhập mật khẩu" required>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="remember" name="remember">
                                <label class="form-check-label" for="remember">Ghi nhớ đăng nhập</label>
                            </div>
                            <a href="#forgot-password" class="forgot-link">Quên mật khẩu?</a>
                        </div>
                        
                        <button type="submit" class="btn btn-auth">
                            <i class="fas fa-sign-in-alt"></i> ĐĂNG NHẬP
                        </button>
                        
                        <div class="auth-footer">
                            Chưa có tài khoản? <a href="#" class="auth-link" id="show-register">Đăng ký ngay</a>
                        </div>
                    </form>
                </div>
                
                <!-- Register Form -->
                <div id="register-section" class="form-section <?php echo isset($_GET['action']) && $_GET['action'] == 'register' ? 'form-visible' : 'form-hidden'; ?>">
                    <div class="auth-header">
                        <i class="fas fa-user-plus"></i>
                        <h2>Đăng Ký Tài Khoản</h2>
                        <p>Tạo tài khoản để sử dụng dịch vụ của chúng tôi</p>
                    </div>
                    
                    <?php if(isset($register_error) && $register_error): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <?php echo htmlspecialchars($register_error); ?>
                    </div>
                    <?php endif; ?>
                    
                    <form id="register-form" method="POST" action="">
                        <input type="hidden" name="register" value="1">
                        
                        <div class="form-group">
                            <label for="register-name" class="form-label">Họ và tên</label>
                            <div class="input-with-icon">
                                <i class="fas fa-user"></i>
                                <input type="text" class="form-control" id="register-name" name="full_name" 
                                       value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>" 
                                       placeholder="Nhập họ và tên" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="register-phone" class="form-label">Số điện thoại</label>
                            <div class="input-with-icon">
                                <i class="fas fa-phone"></i>
                                <input type="tel" class="form-control" id="register-phone" name="phone" 
                                       value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>" 
                                       placeholder="Nhập số điện thoại" required
                                       oninput="formatPhoneNumber(this)">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="register-email" class="form-label">Email</label>
                            <div class="input-with-icon">
                                <i class="fas fa-envelope"></i>
                                <input type="email" class="form-control" id="register-email" name="email" 
                                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" 
                                       placeholder="Nhập email" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="register-username" class="form-label">Tên đăng nhập</label>
                            <div class="input-with-icon">
                                <i class="fas fa-user-tag"></i>
                                <input type="text" class="form-control" id="register-username" name="username" 
                                       value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" 
                                       placeholder="Nhập tên đăng nhập" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="register-password" class="form-label">Mật khẩu</label>
                            <div class="input-with-icon">
                                <i class="fas fa-lock"></i>
                                <input type="password" class="form-control" id="register-password" name="password" 
                                       placeholder="Nhập mật khẩu" required>
                            </div>
                            <div id="password-strength" class="password-strength"></div>
                            <small class="text-muted">Mật khẩu phải có ít nhất 6 ký tự</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="register-confirm-password" class="form-label">Xác nhận mật khẩu</label>
                            <div class="input-with-icon">
                                <i class="fas fa-lock"></i>
                                <input type="password" class="form-control" id="register-confirm-password" name="confirm_password" 
                                       placeholder="Nhập lại mật khẩu" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="agree-terms" name="agree_terms" required>
                                <label class="form-check-label" for="agree-terms">
                                    Tôi đồng ý với <a href="/PHARMAGO/public/terms" class="auth-link">điều khoản dịch vụ</a>
                                </label>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-auth">
                            <i class="fas fa-user-plus"></i> ĐĂNG KÝ
                        </button>
                        
                        <div class="auth-footer">
                            Đã có tài khoản? <a href="#" class="auth-link" id="show-login">Đăng nhập ngay</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 mb-4">
                    <h5><i class="fas fa-prescription-bottle-alt me-2"></i>Pharmacy</h5>
                    <p>Địa chỉ tin cậy cho sức khỏe của bạn và gia đình. Cam kết chất lượng và dịch vụ tốt nhất.</p>
                </div>
                <div class="col-lg-2 col-md-6 mb-4">
                    <h5>Liên kết</h5>
                    <ul>
                        <li><a href="/PHARMAGO/public/">Trang chủ</a></li>
                        <li><a href="/PHARMAGO/public/categories">Danh mục</a></li>
                        <li><a href="/PHARMAGO/public/products">Sản phẩm</a></li>
                        <li><a href="/PHARMAGO/public/contact">Liên hệ</a></li>
                    </ul>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <h5>Thông tin</h5>
                    <ul>
                        <li><a href="/PHARMAGO/public/about">Về chúng tôi</a></li>
                        <li><a href="/PHARMAGO/public/services">Dịch vụ</a></li>
                        <li><a href="/PHARMAGO/public/terms">Điều khoản</a></li>
                    </ul>
                </div>
                <div class="col-lg-3 mb-4">
                    <h5>Theo dõi chúng tôi</h5>
                    <div class="social-icons">
                        <a href="#"><i class="fab fa-facebook-f"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                    </div>
                </div>
            </div>
            <hr style="border-color: rgba(255,255,255,0.1);">
            <div class="text-center">
                <p class="mb-0">&copy; <?php echo date('Y'); ?> Pharmacy. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        class AuthManager {
            constructor() {
                this.init();
            }

            init() {
                this.setupEventListeners();
                this.handleUrlParams();
                this.setupFormValidation();
            }

            setupEventListeners() {
                // Chuyển đổi giữa đăng nhập và đăng ký
                document.getElementById('show-register')?.addEventListener('click', (e) => {
                    e.preventDefault();
                    this.showRegister();
                });

                document.getElementById('show-login')?.addEventListener('click', (e) => {
                    e.preventDefault();
                    this.showLogin();
                });

                // Xử lý submit form
                document.getElementById('login-form')?.addEventListener('submit', (e) => this.handleLogin(e));
                document.getElementById('register-form')?.addEventListener('submit', (e) => this.handleRegister(e));

                // Real-time validation
                this.setupRealTimeValidation();
            }

            handleUrlParams() {
                const urlParams = new URLSearchParams(window.location.search);
                const action = urlParams.get('action');
                
                if (action === 'register') {
                    this.showRegister();
                } else {
                    this.showLogin();
                }
            }

            showRegister() {
                this.hideAllSections();
                document.getElementById('register-section').classList.remove('form-hidden');
                document.getElementById('register-section').classList.add('form-visible');
                this.updateUrl('register');
                this.updateHeroContent('register');
            }

            showLogin() {
                this.hideAllSections();
                document.getElementById('login-section').classList.remove('form-hidden');
                document.getElementById('login-section').classList.add('form-visible');
                this.updateUrl('login');
                this.updateHeroContent('login');
            }

            hideAllSections() {
                document.querySelectorAll('.form-section').forEach(section => {
                    section.classList.remove('form-visible');
                    section.classList.add('form-hidden');
                });
            }

            updateUrl(action) {
                const url = new URL(window.location);
                url.searchParams.set('action', action);
                window.history.replaceState({}, '', url);
            }

            updateHeroContent(type) {
                const heroIcon = document.querySelector('.hero-icon');
                const heroTitle = document.querySelector('.hero-content h2');
                const heroText = document.querySelector('.hero-content p');

                if (type === 'register') {
                    heroIcon.className = 'hero-icon fas fa-user-plus';
                    heroTitle.textContent = 'Tham Gia Cùng Chúng Tôi';
                    heroText.textContent = 'Đăng ký tài khoản để nhận nhiều ưu đãi và trải nghiệm dịch vụ tốt nhất từ Pharmacy.';
                } else {
                    heroIcon.className = 'hero-icon fas fa-user-md';
                    heroTitle.textContent = 'Chào Mừng Trở Lại';
                    heroText.textContent = 'Đăng nhập để tiếp tục trải nghiệm dịch vụ chăm sóc sức khỏe tốt nhất từ Pharmacy.';
                }
            }

            setupFormValidation() {
                // Password strength indicator
                const passwordInput = document.getElementById('register-password');
                if (passwordInput) {
                    passwordInput.addEventListener('input', (e) => this.checkPasswordStrength(e.target.value));
                }

                // Confirm password validation
                const confirmPassword = document.getElementById('register-confirm-password');
                if (confirmPassword) {
                    confirmPassword.addEventListener('input', () => this.validatePasswordMatch());
                }
            }

            setupRealTimeValidation() {
                // Email validation
                const emailInput = document.getElementById('register-email');
                if (emailInput) {
                    emailInput.addEventListener('blur', (e) => this.validateEmail(e.target.value));
                }

                // Phone validation
                const phoneInput = document.getElementById('register-phone');
                if (phoneInput) {
                    phoneInput.addEventListener('blur', (e) => this.validatePhone(e.target.value));
                }
            }

            checkPasswordStrength(password) {
                const strengthIndicator = document.getElementById('password-strength');
                if (!strengthIndicator) return;

                let strength = 0;
                let feedback = '';

                if (password.length >= 6) strength++;
                if (password.match(/[a-z]+/)) strength++;
                if (password.match(/[A-Z]+/)) strength++;
                if (password.match(/[0-9]+/)) strength++;
                if (password.match(/[!@#$%^&*(),.?":{}|<>]+/)) strength++;

                switch (strength) {
                    case 0:
                    case 1:
                        feedback = 'Rất yếu';
                        strengthIndicator.style.color = '#dc3545';
                        break;
                    case 2:
                        feedback = 'Yếu';
                        strengthIndicator.style.color = '#fd7e14';
                        break;
                    case 3:
                        feedback = 'Trung bình';
                        strengthIndicator.style.color = '#ffc107';
                        break;
                    case 4:
                        feedback = 'Mạnh';
                        strengthIndicator.style.color = '#20c997';
                        break;
                    case 5:
                        feedback = 'Rất mạnh';
                        strengthIndicator.style.color = '#198754';
                        break;
                }

                strengthIndicator.textContent = `Độ mạnh mật khẩu: ${feedback}`;
            }

            validatePasswordMatch() {
                const password = document.getElementById('register-password')?.value;
                const confirmPassword = document.getElementById('register-confirm-password')?.value;
                const confirmInput = document.getElementById('register-confirm-password');

                if (!confirmInput) return;

                if (password && confirmPassword && password !== confirmPassword) {
                    confirmInput.style.borderColor = '#dc3545';
                    this.showFieldError('register-confirm-password', 'Mật khẩu xác nhận không khớp');
                } else {
                    confirmInput.style.borderColor = '#198754';
                    this.hideFieldError('register-confirm-password');
                }
            }

            validateEmail(email) {
                const emailInput = document.getElementById('register-email');
                if (!emailInput) return;

                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                
                if (email && !emailRegex.test(email)) {
                    emailInput.style.borderColor = '#dc3545';
                    this.showFieldError('register-email', 'Email không hợp lệ');
                    return false;
                } else {
                    emailInput.style.borderColor = '#198754';
                    this.hideFieldError('register-email');
                    return true;
                }
            }

            validatePhone(phone) {
                const phoneInput = document.getElementById('register-phone');
                if (!phoneInput) return;

                const phoneRegex = /^(0|\+84)[3|5|7|8|9][0-9]{8}$/;
                
                if (phone && !phoneRegex.test(phone)) {
                    phoneInput.style.borderColor = '#dc3545';
                    this.showFieldError('register-phone', 'Số điện thoại không hợp lệ');
                    return false;
                } else {
                    phoneInput.style.borderColor = '#198754';
                    this.hideFieldError('register-phone');
                    return true;
                }
            }

            showFieldError(fieldId, message) {
                let errorElement = document.getElementById(`${fieldId}-error`);
                if (!errorElement) {
                    const input = document.getElementById(fieldId);
                    errorElement = document.createElement('div');
                    errorElement.id = `${fieldId}-error`;
                    errorElement.className = 'field-error text-danger small mt-1';
                    input.parentNode.appendChild(errorElement);
                }
                errorElement.textContent = message;
            }

            hideFieldError(fieldId) {
                const errorElement = document.getElementById(`${fieldId}-error`);
                if (errorElement) {
                    errorElement.remove();
                }
            }

            async handleLogin(e) {
                e.preventDefault();
                
                const formData = new FormData(e.target);
                const username = formData.get('username');
                const password = formData.get('password');

                if (!this.validateLoginForm(username, password)) {
                    return;
                }

                const button = e.target.querySelector('button[type="submit"]');
                this.setLoadingState(button, true);

                try {
                    // Form sẽ được submit bình thường qua PHP
                    // Đây chỉ là validation phía client
                    e.target.submit();
                } catch (error) {
                    this.showToast('Có lỗi xảy ra khi đăng nhập', 'error');
                    console.error('Login error:', error);
                    this.setLoadingState(button, false);
                }
            }

            async handleRegister(e) {
                e.preventDefault();
                
                const formData = new FormData(e.target);
                const userData = {
                    full_name: formData.get('full_name'),
                    username: formData.get('username'),
                    email: formData.get('email'),
                    phone: formData.get('phone'),
                    password: formData.get('password'),
                    confirm_password: formData.get('confirm_password')
                };

                if (!this.validateRegisterForm(userData)) {
                    return;
                }

                const button = e.target.querySelector('button[type="submit"]');
                this.setLoadingState(button, true);

                try {
                    // Form sẽ được submit bình thường qua PHP
                    // Đây chỉ là validation phía client
                    e.target.submit();
                } catch (error) {
                    this.showToast('Có lỗi xảy ra khi đăng ký', 'error');
                    console.error('Register error:', error);
                    this.setLoadingState(button, false);
                }
            }

            validateLoginForm(username, password) {
                if (!username.trim()) {
                    this.showToast('Vui lòng nhập tên đăng nhập hoặc email', 'error');
                    return false;
                }

                if (!password.trim()) {
                    this.showToast('Vui lòng nhập mật khẩu', 'error');
                    return false;
                }

                return true;
            }

            validateRegisterForm(userData) {
                const { full_name, username, email, phone, password, confirm_password } = userData;

                if (!full_name.trim()) {
                    this.showToast('Vui lòng nhập họ và tên', 'error');
                    return false;
                }

                if (!username.trim()) {
                    this.showToast('Vui lòng nhập tên đăng nhập', 'error');
                    return false;
                }

                if (!this.validateEmail(email)) {
                    this.showToast('Email không hợp lệ', 'error');
                    return false;
                }

                if (!this.validatePhone(phone)) {
                    this.showToast('Số điện thoại không hợp lệ', 'error');
                    return false;
                }

                if (password.length < 6) {
                    this.showToast('Mật khẩu phải có ít nhất 6 ký tự', 'error');
                    return false;
                }

                if (password !== confirm_password) {
                    this.showToast('Mật khẩu xác nhận không khớp', 'error');
                    return false;
                }

                return true;
            }

            setLoadingState(button, isLoading) {
                if (isLoading) {
                    button.classList.add('loading');
                    button.disabled = true;
                    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang xử lý...';
                } else {
                    button.classList.remove('loading');
                    button.disabled = false;
                    const originalText = button.closest('form').id === 'login-form' 
                        ? '<i class="fas fa-sign-in-alt"></i> ĐĂNG NHẬP'
                        : '<i class="fas fa-user-plus"></i> ĐĂNG KÝ';
                    button.innerHTML = originalText;
                }
            }

            showToast(message, type = 'info') {
                // Tạo toast container nếu chưa có
                let container = document.getElementById('toast-container');
                if (!container) {
                    container = document.createElement('div');
                    container.id = 'toast-container';
                    container.style.cssText = `
                        position: fixed;
                        top: 20px;
                        right: 20px;
                        z-index: 9999;
                        max-width: 400px;
                    `;
                    document.body.appendChild(container);
                }

                // Tạo toast
                const toast = document.createElement('div');
                toast.className = `alert alert-${type === 'success' ? 'success' : 'danger'} alert-dismissible fade show`;
                toast.innerHTML = `
                    <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-triangle'} me-2"></i>
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                `;

                container.appendChild(toast);

                // Tự động xóa sau 5 giây
                setTimeout(() => {
                    if (toast.parentNode) {
                        toast.remove();
                    }
                }, 5000);
            }
        }

        // Utility functions
        function formatPhoneNumber(input) {
            let value = input.value.replace(/\D/g, '');
            if (value.startsWith('84')) {
                value = '0' + value.slice(2);
            }
            input.value = value;
        }

        // Khởi tạo khi DOM loaded
        document.addEventListener('DOMContentLoaded', () => {
            new AuthManager();
        });
    </script>
</body>
</html>