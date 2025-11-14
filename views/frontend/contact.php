<?php
// views/frontend/contact.php

// Bật hiển thị lỗi để debug
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start session nếu chưa có
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Biến để kiểm tra kết nối database
$db_connected = false;
$branches = [];
$contact_settings = [];
$stats = [];
$google_maps_api_key = 'AIzaSyBg0A--Fgodjk-DEWYSsdsWtAYB-Cbk6T4';

try {
    // Kết nối database
    $base_dir = dirname(__DIR__, 2) . '/';
    $database_config = $base_dir . 'config/database.php';
    
    if (!file_exists($database_config)) {
        throw new Exception("Database config file not found");
    }
    
    require_once $database_config;
    
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception("Cannot connect to database");
    }
    
    $db_connected = true;
    error_log("✅ Database connected successfully in contact page");
    
    // Lấy thông tin chi nhánh từ database
    require_once $base_dir . 'models/Branch.php';
    $branchModel = new Branch($db);
    $branches = $branchModel->getAllActiveBranches();
    
    // Lấy tất cả cài đặt cần thiết
    $settings_query = "SELECT setting_key, setting_value FROM settings 
                      WHERE setting_key IN ('google_maps_api_key', 'contact_email', 'contact_phone', 'contact_address', 
                                           'contact_hotline', 'contact_zalo', 'emergency_delivery_time', 'normal_delivery_time')";
    $stmt = $db->prepare($settings_query);
    $stmt->execute();
    $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    $google_maps_api_key = $settings['google_maps_api_key'] ?? 'AIzaSyBg0A--Fgodjk-DEWYSsdsWtAYB-Cbk6T4';
    $contact_settings = [
        'contact_email' => $settings['contact_email'] ?? 'info@pharmacy.com',
        'contact_phone' => $settings['contact_phone'] ?? '0236 1234 567',
        'contact_address' => $settings['contact_address'] ?? '123 Đường 2/9, Quận Hải Châu, TP. Đà Nẵng',
        'contact_hotline' => $settings['contact_hotline'] ?? '1900 1234',
        'contact_zalo' => $settings['contact_zalo'] ?? '0909123456',
        'emergency_delivery_time' => $settings['emergency_delivery_time'] ?? '60',
        'normal_delivery_time' => $settings['normal_delivery_time'] ?? '120'
    ];
    
} catch (Exception $e) {
    error_log("❌ Contact page database error: " . $e->getMessage());
    // Sử dụng dữ liệu mẫu khi không kết nối được database
    $branches = [
        [
            'branch_id' => 1,
            'branch_name' => 'Trụ sở chính PharmaGo',
            'address' => '123 Đường 2/9, Quận Hải Châu, TP. Đà Nẵng',
            'phone' => '0236 1234 567',
            'email' => 'info@pharmacy.com',
            'opening_hours' => '7:00 - 22:00 (Thứ 2 - Chủ nhật)',
            'latitude' => 16.0544,
            'longitude' => 108.2022,
            'manager_name' => 'Nguyễn Văn A'
        ]
    ];
    
    $contact_settings = [
        'contact_email' => 'info@pharmacy.com',
        'contact_phone' => '0236 1234 567',
        'contact_address' => '123 Đường 2/9, Quận Hải Châu, TP. Đà Nẵng',
        'contact_hotline' => '1900 1234',
        'contact_zalo' => '0909123456',
        'emergency_delivery_time' => '60',
        'normal_delivery_time' => '120'
    ];
    
    $stats = [
        'total_customers' => '15K+',
        'consultations_done' => '50K+',
        'total_branches' => '10+',
        'happy_customers' => '20K+',
        'orders_delivered' => '100K+'
    ];
}

// Thống kê từ database (chỉ khi kết nối được)
if ($db_connected) {
    try {
        $stats_query = "
            SELECT 
                (SELECT COUNT(*) FROM users WHERE role = 'customer' AND is_active = 1) as total_customers,
                (SELECT COUNT(*) FROM contacts WHERE status = 'replied') as consultations_done,
                (SELECT COUNT(*) FROM branches WHERE is_active = 1) as total_branches,
                (SELECT COUNT(*) FROM product_reviews WHERE is_approved = 1 AND rating >= 4) as happy_customers,
                (SELECT COUNT(*) FROM orders WHERE order_status = 'delivered') as orders_delivered
        ";
        $stmt = $db->prepare($stats_query);
        $stmt->execute();
        $stats_data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Format số liệu thống kê
        $stats = [
            'total_customers' => $stats_data['total_customers'] > 1000 ? 
                                round($stats_data['total_customers'] / 1000, 1) . 'K+' : 
                                $stats_data['total_customers'],
            'consultations_done' => $stats_data['consultations_done'] > 1000 ? 
                                   round($stats_data['consultations_done'] / 1000, 1) . 'K+' : 
                                   $stats_data['consultations_done'],
            'total_branches' => $stats_data['total_branches'] . '+',
            'happy_customers' => $stats_data['happy_customers'] > 1000 ? 
                                round($stats_data['happy_customers'] / 1000, 1) . 'K+' : 
                                $stats_data['happy_customers'],
            'orders_delivered' => $stats_data['orders_delivered'] > 1000 ? 
                                 round($stats_data['orders_delivered'] / 1000, 1) . 'K+' : 
                                 $stats_data['orders_delivered']
        ];
    } catch (Exception $e) {
        error_log("Stats query error: " . $e->getMessage());
        $stats = [
            'total_customers' => '15K+',
            'consultations_done' => '50K+',
            'total_branches' => '10+',
            'happy_customers' => '20K+',
            'orders_delivered' => '100K+'
        ];
    }
}

// Xử lý form liên hệ (chỉ khi kết nối được database)
$contact_success = false;
$contact_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['contact_submit'])) {
    if (!$db_connected) {
        $contact_error = "Hiện không thể kết nối đến hệ thống. Vui lòng thử lại sau hoặc liên hệ qua điện thoại.";
    } else {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $subject = trim($_POST['subject'] ?? '');
        $message = trim($_POST['message'] ?? '');
        $interest = isset($_POST['interest']) ? $_POST['interest'] : [];
        $branch_id = isset($_POST['branch_id']) ? intval($_POST['branch_id']) : null;
        
        // Validate dữ liệu
        if (empty($name) || empty($email) || empty($subject) || empty($message)) {
            $contact_error = "Vui lòng điền đầy đủ thông tin bắt buộc (*)";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $contact_error = "Email không hợp lệ";
        } else {
            try {
                // Lưu vào database
                $query = "INSERT INTO contacts (full_name, email, phone, subject, message, branch_id) 
                          VALUES (:name, :email, :phone, :subject, :message, :branch_id)";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':name', $name);
                $stmt->bindParam(':email', $email);
                $stmt->bindParam(':phone', $phone);
                $stmt->bindParam(':subject', $subject);
                $stmt->bindParam(':message', $message);
                $stmt->bindParam(':branch_id', $branch_id);
                
                if ($stmt->execute()) {
                    $contact_success = true;
                    
                    // Reset form
                    $name = $email = $phone = $subject = $message = '';
                    $interest = [];
                    $branch_id = null;
                } else {
                    $contact_error = "Có lỗi xảy ra khi gửi tin nhắn. Vui lòng thử lại.";
                }
            } catch (Exception $e) {
                error_log("Contact form error: " . $e->getMessage());
                $contact_error = "Có lỗi xảy ra khi gửi tin nhắn. Vui lòng thử lại.";
            }
        }
    }
}

// Hàm helper để lấy ảnh
function getImage($filename) {
    $image_path = $_SERVER['DOCUMENT_ROOT'] . '/PHARMAGO/public/assets/images/' . $filename;
    $web_path = '/PHARMAGO/public/assets/images/' . $filename;
    
    if (file_exists($image_path)) {
        return $web_path;
    }
    return 'https://images.unsplash.com/photo-1559757175-0eb30cd8c063?ixlib=rb-4.0.3&auto=format&fit=crop&w=1000&q=80';
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liên hệ - PharmaGo</title>
    <!-- Favicon đơn giản -->
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>💊</text></svg>">
    
    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link rel="stylesheet" href="/PHARMAGO/public/assets/css/contact.css">
    <link rel="stylesheet" href="/PHARMAGO/public/assets/css/style.css">
</head>

<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light sticky-top">
        <div class="container">
            <a class="navbar-brand fw-bold" href="/PHARMAGO/public/">
                <i class="fas fa-prescription-bottle-alt me-2"></i>PharmaGo
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
                        <a class="nav-link active" href="/PHARMAGO/public/contact">
                            <i class="fas fa-phone me-1"></i>Liên hệ
                        </a>
                    </li>
                    <li class="nav-item">
                        <?php if(isset($_SESSION['user_id'])): ?>
                            <a class="nav-link" href="/PHARMAGO/public/auth/logout">
                                <i class="fas fa-sign-out-alt me-1"></i>Đăng xuất
                            </a>
                        <?php else: ?>
                            <a class="nav-link" href="/PHARMAGO/public/auth/login">
                                <i class="fas fa-sign-in-alt me-1"></i>Đăng nhập
                            </a>
                        <?php endif; ?>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Quick Navigation -->
    <div class="quick-nav">
        <a href="#home" title="Lên đầu trang"><i class="fas fa-arrow-up"></i></a>
        <a href="#contact-form" title="Gửi tin nhắn"><i class="fas fa-envelope"></i></a>
        <a href="#branches" title="Chi nhánh"><i class="fas fa-map-marker-alt"></i></a>
        <a href="#map" title="Bản đồ"><i class="fas fa-map"></i></a>
    </div>

    <!-- Scroll to Top Button -->
    <button class="scroll-top" id="scrollTop">
        <i class="fas fa-chevron-up"></i>
    </button>

    <!-- Contact Hero Section -->
    <section class="contact-hero" id="home">
        <div class="container">
            <div class="row align-items-center min-vh-80">
                <div class="col-lg-6">
                    <h1 class="display-4 fw-bold text-white mb-4 animate__animated animate__fadeInLeft">
                        Kết Nối Với PharmaGo
                    </h1>
                    <p class="lead text-white mb-4 animate__animated animate__fadeInLeft">
                        Đồng hành cùng sức khỏe của bạn - Đội ngũ dược sĩ chuyên môn cao luôn sẵn sàng tư vấn 24/7
                    </p>
                    <div class="hero-stats animate__animated animate__fadeInUp">
                        <div class="stat">
                            <span class="stat-number"><?php echo $stats['total_customers'] ?? '15K+'; ?></span>
                            <span class="stat-label">Khách hàng tin tưởng</span>
                        </div>
                        <div class="stat">
                            <span class="stat-number"><?php echo $stats['consultations_done'] ?? '50K+'; ?></span>
                            <span class="stat-label">Tư vấn thành công</span>
                        </div>
                        <div class="stat">
                            <span class="stat-number"><?php echo $stats['total_branches'] ?? '10+'; ?></span>
                            <span class="stat-label">Chi nhánh toàn quốc</span>
                        </div>
                        <div class="stat">
                            <span class="stat-number"><?php echo $stats['happy_customers'] ?? '20K+'; ?></span>
                            <span class="stat-label">Khách hàng hài lòng</span>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 text-center animate__animated animate__fadeInRight">
                    <div class="contact-visual">
                        <img src="<?php echo getImage('contact-hero.jpg'); ?>" 
                             alt="Liên hệ PharmaGo" class="img-fluid rounded-3">
                        <div class="floating-badge">
                            <i class="fas fa-headset"></i>
                            <span>Hỗ trợ 24/7</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Emergency Contact Banner -->
    <section class="emergency-banner">
        <div class="container">
            <div class="emergency-content">
                <div class="emergency-icon">
                    <i class="fas fa-ambulance"></i>
                </div>
                <div class="emergency-text">
                    <h4>Dịch vụ khẩn cấp 24/7</h4>
                    <p>Giao thuốc nhanh trong <?php echo $contact_settings['emergency_delivery_time']; ?> phút - Tư vấn dược sĩ miễn phí</p>
                </div>
                <div class="emergency-contact">
                    <a href="tel:<?php echo $contact_settings['contact_hotline']; ?>" class="btn btn-emergency">
                        <i class="fas fa-phone me-2"></i>Gọi ngay: <?php echo $contact_settings['contact_hotline']; ?>
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Form & Info Section -->
    <section class="contact-main-section" id="contact-form">
        <div class="container">
            <div class="row">
                <!-- Contact Form -->
                <div class="col-lg-8">
                    <div class="contact-form-card">
                        <div class="form-header">
                            <h2 class="section-title mb-2">Gửi Yêu Cầu Tư Vấn</h2>
                            <p class="text-muted">Đội ngũ dược sĩ sẽ liên hệ lại với bạn trong vòng 15 phút</p>
                        </div>
                        
                        <?php if($contact_success): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="fas fa-check-circle me-2"></i>
                                <strong>Thành công!</strong> Cảm ơn bạn đã liên hệ! Chúng tôi sẽ phản hồi trong thời gian sớm nhất.
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php elseif($contact_error): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <strong>Lỗi!</strong> <?php echo $contact_error; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <form method="POST" class="needs-validation" novalidate id="contactForm">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="name" class="form-label">Họ tên <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="name" name="name" 
                                           value="<?php echo isset($name) ? htmlspecialchars($name) : ''; ?>" 
                                           required placeholder="Nhập họ và tên đầy đủ">
                                    <div class="invalid-feedback">Vui lòng nhập họ tên</div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                    <input type="email" class="form-control" id="email" name="email"
                                           value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>" 
                                           required placeholder="email@example.com">
                                    <div class="invalid-feedback">Vui lòng nhập email hợp lệ</div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="phone" class="form-label">Số điện thoại</label>
                                    <input type="tel" class="form-control" id="phone" name="phone"
                                           value="<?php echo isset($phone) ? htmlspecialchars($phone) : ''; ?>"
                                           placeholder="0901 234 567">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="branch_id" class="form-label">Chi nhánh quan tâm</label>
                                    <select class="form-select" id="branch_id" name="branch_id">
                                        <option value="">Chọn chi nhánh</option>
                                        <?php foreach($branches as $branch): ?>
                                        <option value="<?php echo $branch['branch_id']; ?>" 
                                                <?php echo (isset($branch_id) && $branch_id == $branch['branch_id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($branch['branch_name']); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="subject" class="form-label">Chủ đề <span class="text-danger">*</span></label>
                                <select class="form-select" id="subject" name="subject" required>
                                    <option value="">Chọn chủ đề tư vấn</option>
                                    <option value="Tư vấn sản phẩm" <?php echo (isset($subject) && $subject == 'Tư vấn sản phẩm') ? 'selected' : ''; ?>>Tư vấn sản phẩm</option>
                                    <option value="Tư vấn sức khỏe" <?php echo (isset($subject) && $subject == 'Tư vấn sức khỏe') ? 'selected' : ''; ?>>Tư vấn sức khỏe</option>
                                    <option value="Hỗ trợ kỹ thuật" <?php echo (isset($subject) && $subject == 'Hỗ trợ kỹ thuật') ? 'selected' : ''; ?>>Hỗ trợ kỹ thuật</option>
                                    <option value="Đối tác hợp tác" <?php echo (isset($subject) && $subject == 'Đối tác hợp tác') ? 'selected' : ''; ?>>Đối tác hợp tác</option>
                                    <option value="Góp ý - Khiếu nại" <?php echo (isset($subject) && $subject == 'Góp ý - Khiếu nại') ? 'selected' : ''; ?>>Góp ý - Khiếu nại</option>
                                    <option value="Hoạt động cộng đồng" <?php echo (isset($subject) && $subject == 'Hoạt động cộng đồng') ? 'selected' : ''; ?>>Hoạt động cộng đồng</option>
                                    <option value="Khác" <?php echo (isset($subject) && $subject == 'Khác') ? 'selected' : ''; ?>>Khác</option>
                                </select>
                                <div class="invalid-feedback">Vui lòng chọn chủ đề</div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label">Lĩnh vực bạn quan tâm (Có thể chọn nhiều)</label>
                                <div class="interest-tags">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="interest[]" value="Sức khỏe gia đình" 
                                               id="health-family" <?php echo (isset($interest) && in_array('Sức khỏe gia đình', $interest)) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="health-family">
                                            <i class="fas fa-home me-1"></i>Sức khỏe gia đình
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="interest[]" value="Dinh dưỡng" 
                                               id="nutrition" <?php echo (isset($interest) && in_array('Dinh dưỡng', $interest)) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="nutrition">
                                            <i class="fas fa-apple-alt me-1"></i>Dinh dưỡng
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="interest[]" value="Chăm sóc trẻ em" 
                                               id="child-care" <?php echo (isset($interest) && in_array('Chăm sóc trẻ em', $interest)) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="child-care">
                                            <i class="fas fa-baby me-1"></i>Chăm sóc trẻ em
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="interest[]" value="Người cao tuổi" 
                                               id="elderly" <?php echo (isset($interest) && in_array('Người cao tuổi', $interest)) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="elderly">
                                            <i class="fas fa-user-friends me-1"></i>Người cao tuổi
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="interest[]" value="Thuốc kê đơn" 
                                               id="prescription" <?php echo (isset($interest) && in_array('Thuốc kê đơn', $interest)) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="prescription">
                                            <i class="fas fa-file-prescription me-1"></i>Thuốc kê đơn
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="interest[]" value="Thực phẩm chức năng" 
                                               id="supplements" <?php echo (isset($interest) && in_array('Thực phẩm chức năng', $interest)) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="supplements">
                                            <i class="fas fa-capsules me-1"></i>Thực phẩm chức năng
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="message" class="form-label">Nội dung chi tiết <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="message" name="message" rows="6" 
                                          required placeholder="Xin vui lòng mô tả chi tiết tình trạng sức khỏe hoặc yêu cầu của bạn..."><?php echo isset($message) ? htmlspecialchars($message) : ''; ?></textarea>
                                <div class="form-text text-end">
                                    <span id="charCount">0</span>/1000 ký tự
                                </div>
                                <div class="invalid-feedback">Vui lòng nhập nội dung tin nhắn</div>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" name="contact_submit" class="btn btn-primary btn-lg" id="submitBtn">
                                    <i class="fas fa-paper-plane me-2"></i>Gửi Yêu Cầu Tư Vấn
                                </button>
                                <small class="text-center text-muted">
                                    <i class="fas fa-shield-alt me-1"></i>Thông tin của bạn được bảo mật tuyệt đối
                                </small>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Contact Info & Activities -->
                <div class="col-lg-4">
                    <!-- Quick Contact -->
                    <div class="contact-info-card mb-4">
                        <h4 class="mb-4"><i class="fas fa-info-circle me-2"></i>Liên Hệ Nhanh</h4>
                        
                        <div class="contact-item mb-3">
                            <div class="contact-icon">
                                <i class="fas fa-map-marker-alt"></i>
                            </div>
                            <div class="contact-details">
                                <h6>Địa chỉ trụ sở</h6>
                                <p class="mb-0"><?php echo htmlspecialchars($contact_settings['contact_address']); ?></p>
                            </div>
                        </div>
                        
                        <div class="contact-item mb-3">
                            <div class="contact-icon">
                                <i class="fas fa-phone"></i>
                            </div>
                            <div class="contact-details">
                                <h6>Điện thoại</h6>
                                <p class="mb-0"><?php echo htmlspecialchars($contact_settings['contact_phone']); ?></p>
                                <p class="mb-0 text-small text-primary">Hotline: <?php echo $contact_settings['contact_hotline']; ?></p>
                            </div>
                        </div>
                        
                        <div class="contact-item mb-3">
                            <div class="contact-icon">
                                <i class="fas fa-envelope"></i>
                            </div>
                            <div class="contact-details">
                                <h6>Email</h6>
                                <p class="mb-0"><?php echo htmlspecialchars($contact_settings['contact_email']); ?></p>
                            </div>
                        </div>
                        
                        <div class="contact-item">
                            <div class="contact-icon">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="contact-details">
                                <h6>Giờ làm việc</h6>
                                <p class="mb-0">Thứ 2 - Chủ nhật: 7:00 - 22:00</p>
                                <p class="mb-0 text-small text-success">Dịch vụ khẩn cấp: 24/7</p>
                            </div>
                        </div>

                        <div class="quick-contact-buttons mt-4">
                            <a href="tel:<?php echo $contact_settings['contact_phone']; ?>" class="btn btn-outline-primary w-100 mb-2">
                                <i class="fas fa-phone me-2"></i>Gọi ngay
                            </a>
                            <a href="https://zalo.me/<?php echo $contact_settings['contact_zalo']; ?>" class="btn btn-success w-100 mb-2">
                                <i class="fab fa-zalo me-2"></i>Zalo Chat
                            </a>
                            <a href="mailto:<?php echo $contact_settings['contact_email']; ?>" class="btn btn-outline-secondary w-100">
                                <i class="fas fa-envelope me-2"></i>Gửi Email
                            </a>
                        </div>
                    </div>

                    <!-- Why Choose Us -->
                    <div class="activities-card">
                        <h4 class="mb-4"><i class="fas fa-award me-2"></i>Tại Sao Chọn PharmaGo?</h4>
                        
                        <div class="activity-item">
                            <div class="activity-icon">
                                <i class="fas fa-graduation-cap"></i>
                            </div>
                            <div class="activity-content">
                                <h6>Dược sĩ chuyên môn cao</h6>
                                <p class="mb-2">Đội ngũ dược sĩ tốt nghiệp từ các trường đại học y dược hàng đầu</p>
                            </div>
                        </div>
                        
                        <div class="activity-item">
                            <div class="activity-icon">
                                <i class="fas fa-shield-alt"></i>
                            </div>
                            <div class="activity-content">
                                <h6>Thuốc chính hãng 100%</h6>
                                <p class="mb-2">Cam kết thuốc có nguồn gốc rõ ràng, đảm bảo chất lượng</p>
                            </div>
                        </div>
                        
                        <div class="activity-item">
                            <div class="activity-icon">
                                <i class="fas fa-shipping-fast"></i>
                            </div>
                            <div class="activity-content">
                                <h6>Giao hàng siêu tốc</h6>
                                <p class="mb-2">Giao thuốc trong <?php echo $contact_settings['emergency_delivery_time']; ?> phút tại nội thành, <?php echo $contact_settings['normal_delivery_time']; ?> phút ngoại thành</p>
                            </div>
                        </div>
                        
                        <div class="activity-item">
                            <div class="activity-icon">
                                <i class="fas fa-hand-holding-heart"></i>
                            </div>
                            <div class="activity-content">
                                <h6>Tư vấn tận tâm</h6>
                                <p class="mb-2">Tư vấn miễn phí 24/7, theo dõi sức khỏe lâu dài</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Branches Section -->
    <section id="branches" class="branches-section">
        <div class="container">
            <div class="row mb-5">
                <div class="col-12 text-center">
                    <h2 class="section-title">Hệ Thống Chi Nhánh</h2>
                    <p class="lead text-muted">Phục vụ khách hàng trên toàn quốc</p>
                </div>
            </div>
            
            <div class="row">
                <?php if(!empty($branches)): ?>
                    <?php foreach($branches as $branch): ?>
                    <div class="col-lg-6 mb-4">
                        <div class="branch-card">
                            <div class="branch-header">
                                <h5><?php echo htmlspecialchars($branch['branch_name']); ?></h5>
                                <span class="branch-badge <?php echo ($branch['branch_id'] ?? 0) == 1 ? 'main-branch' : ''; ?>">
                                    <?php echo ($branch['branch_id'] ?? 0) == 1 ? 'Trụ sở chính' : 'Chi nhánh'; ?>
                                </span>
                            </div>
                            <div class="branch-body">
                                <div class="branch-info">
                                    <div class="info-item">
                                        <i class="fas fa-map-marker-alt"></i>
                                        <span><?php echo htmlspecialchars($branch['address']); ?></span>
                                    </div>
                                    <div class="info-item">
                                        <i class="fas fa-phone"></i>
                                        <span><?php echo htmlspecialchars($branch['phone']); ?></span>
                                    </div>
                                    <div class="info-item">
                                        <i class="fas fa-envelope"></i>
                                        <span><?php echo htmlspecialchars($branch['email']); ?></span>
                                    </div>
                                    <div class="info-item">
                                        <i class="fas fa-clock"></i>
                                        <span><?php echo htmlspecialchars($branch['opening_hours']); ?></span>
                                    </div>
                                    <?php if(isset($branch['manager_name']) && $branch['manager_name']): ?>
                                    <div class="info-item">
                                        <i class="fas fa-user-tie"></i>
                                        <span>Quản lý: <?php echo htmlspecialchars($branch['manager_name']); ?></span>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <div class="branch-actions">
                                    <button class="btn btn-outline-primary btn-sm" onclick="focusOnMap(<?php echo $branch['latitude']; ?>, <?php echo $branch['longitude']; ?>, '<?php echo htmlspecialchars($branch['branch_name']); ?>')">
                                        <i class="fas fa-map-marked-alt me-1"></i>Xem trên bản đồ
                                    </button>
                                    <a href="tel:<?php echo htmlspecialchars($branch['phone']); ?>" class="btn btn-primary btn-sm">
                                        <i class="fas fa-phone me-1"></i>Gọi ngay
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12 text-center">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            Đang cập nhật thông tin chi nhánh...
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Interactive Map Section -->
    <section id="map" class="map-section">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="map-header">
                        <h3><i class="fas fa-map-marked-alt me-2"></i>Tìm Đường Đến PharmaGo</h3>
                        <p class="text-muted">Nhấp vào các đánh dấu để xem thông tin chi nhánh</p>
                    </div>
                    <div id="googleMap" class="google-map">
                        <div class="text-center py-5 map-loading">
                            <i class="fas fa-spinner fa-spin fa-2x text-primary mb-3"></i>
                            <p class="text-muted">Đang tải bản đồ...</p>
                        </div>
                    </div>
                    <div class="map-controls">
                        <button class="btn btn-outline-primary btn-sm" onclick="locateUser()">
                            <i class="fas fa-location-arrow me-1"></i>Vị trí của tôi
                        </button>
                        <button class="btn btn-outline-secondary btn-sm" onclick="resetMap()">
                            <i class="fas fa-sync-alt me-1"></i>Xem tất cả
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- FAQ Section -->
    <section class="faq-section">
        <div class="container">
            <div class="row mb-5">
                <div class="col-12 text-center">
                    <h2 class="section-title">Câu Hỏi Thường Gặp</h2>
                    <p class="lead text-muted">Giải đáp mọi thắc mắc của bạn</p>
                </div>
            </div>
            
            <div class="row">
                <div class="col-lg-6">
                    <div class="faq-item">
                        <h5><i class="fas fa-question-circle me-2"></i>Làm thế nào để mua thuốc kê đơn?</h5>
                        <p>Bạn cần có đơn thuốc từ bác sĩ. Có thể gửi ảnh đơn thuốc qua Zalo, Email hoặc mang trực tiếp đến chi nhánh.</p>
                    </div>
                    <div class="faq-item">
                        <h5><i class="fas fa-question-circle me-2"></i>Thời gian giao hàng là bao lâu?</h5>
                        <p>Trong nội thành: <?php echo $contact_settings['emergency_delivery_time']; ?> phút. Ngoại thành: <?php echo $contact_settings['normal_delivery_time']; ?> phút. Các tỉnh thành khác: 24-48 giờ tùy khu vực.</p>
                    </div>
                    <div class="faq-item">
                        <h5><i class="fas fa-question-circle me-2"></i>Có được tư vấn sức khỏe miễn phí không?</h5>
                        <p>Hoàn toàn miễn phí! Đội ngũ dược sĩ của chúng tôi luôn sẵn sàng tư vấn 24/7 qua điện thoại, Zalo hoặc trực tiếp.</p>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="faq-item">
                        <h5><i class="fas fa-question-circle me-2"></i>Làm sao để biết thuốc có chính hãng?</h5>
                        <p>Tất cả thuốc đều có tem chống hàng giả và mã vạch để kiểm tra nguồn gốc. Chúng tôi cam kết 100% thuốc chính hãng.</p>
                    </div>
                    <div class="faq-item">
                        <h5><i class="fas fa-question-circle me-2"></i>Có hỗ trợ thanh toán online không?</h5>
                        <p>Có! Chúng tôi hỗ trợ thanh toán qua thẻ ngân hàng, ví điện tử (Momo, ZaloPay) và COD (nhận hàng trả tiền).</p>
                    </div>
                    <div class="faq-item">
                        <h5><i class="fas fa-question-circle me-2"></i>Làm thế nào để trở thành đối tác?</h5>
                        <p>Liên hệ hotline <?php echo $contact_settings['contact_hotline']; ?> hoặc email partner@pharmacy.com để được tư vấn về chính sách đối tác.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 mb-4">
                    <h5><i class="fas fa-prescription-bottle-alt me-2"></i>PharmaGo</h5>
                    <p>Nhà thuốc trực tuyến uy tín hàng đầu Việt Nam. Cam kết chất lượng và dịch vụ tốt nhất cho sức khỏe cộng đồng.</p>
                    <div class="trust-badges">
                        <span class="badge bg-success"><i class="fas fa-shield-alt me-1"></i>Bảo mật</span>
                        <span class="badge bg-primary"><i class="fas fa-truck me-1"></i>Giao nhanh</span>
                        <span class="badge bg-warning"><i class="fas fa-star me-1"></i>Chất lượng</span>
                    </div>
                </div>
                <div class="col-lg-2 col-md-6 mb-4">
                    <h5>Liên kết</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="/PHARMAGO/public/">Trang chủ</a></li>
                        <li class="mb-2"><a href="/PHARMAGO/public/categories">Danh mục</a></li>
                        <li class="mb-2"><a href="/PHARMAGO/public/products">Sản phẩm</a></li>
                        <li class="mb-2"><a href="#contact-form">Liên hệ</a></li>
                    </ul>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <h5>Dịch vụ</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="#">Tư vấn dược sĩ</a></li>
                        <li class="mb-2"><a href="#">Giao thuốc tận nhà</a></li>
                        <li class="mb-2"><a href="#">Theo dõi sức khỏe</a></li>
                        <li class="mb-2"><a href="#">Khám sức khỏe online</a></li>
                    </ul>
                </div>
                <div class="col-lg-3 mb-4">
                    <h5>Theo dõi chúng tôi</h5>
                    <div class="social-icons">
                        <a href="#" class="facebook"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="zalo"><i class="fab fa-zalo"></i></a>
                        <a href="#" class="youtube"><i class="fab fa-youtube"></i></a>
                        <a href="#" class="tiktok"><i class="fab fa-tiktok"></i></a>
                    </div>
                    <div class="app-download mt-4">
                        <h6>Tải ứng dụng</h6>
                        <div class="app-buttons">
                            <a href="#" class="btn btn-dark btn-sm">
                                <i class="fab fa-google-play me-1"></i>Google Play
                            </a>
                            <a href="#" class="btn btn-dark btn-sm mt-2">
                                <i class="fab fa-app-store me-1"></i>App Store
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <hr style="border-color: rgba(255,255,255,0.1);">
            <div class="text-center">
                <p class="mb-0">&copy; <?php echo date('Y'); ?> PharmaGo. All rights reserved. | 
                <a href="#" class="text-white-50">Chính sách bảo mật</a> | 
                <a href="#" class="text-white-50">Điều khoản sử dụng</a></p>
            </div>
        </div>
    </footer>

    <!-- Modal Chỉ đường -->
    <div class="modal fade" id="directionModal" tabindex="-1" aria-labelledby="directionModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="directionModalLabel">
                        <i class="fas fa-route me-2"></i>Chỉ Đường Đến Nhà Thuốc
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <h6 class="text-success" id="branchName"></h6>
                        <p class="text-muted mb-2" id="branchAddress"></p>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Chọn phương tiện:</label>
                        <div class="transport-options">
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="radio" name="transportMode" id="driveMode" value="DRIVING" checked>
                                <label class="form-check-label" for="driveMode">
                                    <i class="fas fa-car text-primary me-2"></i>Ô tô/Xe máy
                                </label>
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="radio" name="transportMode" id="bikeMode" value="BICYCLING">
                                <label class="form-check-label" for="bikeMode">
                                    <i class="fas fa-bicycle text-success me-2"></i>Xe đạp
                                </label>
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="radio" name="transportMode" id="walkMode" value="WALKING">
                                <label class="form-check-label" for="walkMode">
                                    <i class="fas fa-walking text-info me-2"></i>Đi bộ
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="transportMode" id="transitMode" value="TRANSIT">
                                <label class="form-check-label" for="transitMode">
                                    <i class="fas fa-bus text-warning me-2"></i>Xe buýt
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-info">
                        <small>
                            <i class="fas fa-info-circle me-1"></i>
                            Hệ thống sẽ tính toán tuyến đường từ vị trí hiện tại của bạn đến nhà thuốc.
                        </small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="button" class="btn btn-success" onclick="startDirections()">
                        <i class="fas fa-play me-1"></i>Bắt Đầu Chỉ Đường
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Panel hiển thị thông tin tuyến đường -->
    <div id="routeInfo" class="route-panel" style="display: none;">
        <div class="route-header">
            <h6><i class="fas fa-route me-2"></i>Thông Tin Tuyến Đường</h6>
            <button type="button" class="btn-close" onclick="hideRouteInfo()"></button>
        </div>
        <div class="route-content">
            <div id="routeSummary" class="route-summary"></div>
            <div id="routeSteps" class="route-steps"></div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Google Maps API với tính năng chỉ đường -->
    <script>
        // Biến toàn cục
        window.branchesData = <?php echo json_encode($branches); ?>;
        window.defaultLocation = {
            lat: <?php echo !empty($branches) ? $branches[0]['latitude'] : '16.0544'; ?>,
            lng: <?php echo !empty($branches) ? $branches[0]['longitude'] : '108.2022'; ?>
        };
        window.isMapReady = false;
        window.directionsService = null;
        window.directionsRenderer = null;

        console.log("📍 Branches data loaded:", window.branchesData);

        // Load Google Maps API
        function loadGoogleMaps() {
            return new Promise((resolve, reject) => {
                if (window.google && window.google.maps) {
                    console.log("✅ Google Maps already loaded");
                    resolve();
                    return;
                }

                const script = document.createElement('script');
                script.src = 'https://maps.googleapis.com/maps/api/js?key=<?php echo $google_maps_api_key; ?>&callback=initMap&libraries=places,directions';
                script.async = true;
                script.defer = true;
                
                script.onload = () => {
                    console.log("✅ Google Maps script loaded successfully");
                };
                
                script.onerror = (error) => {
                    console.error('❌ Failed to load Google Maps:', error);
                    reject(error);
                };
                
                document.head.appendChild(script);
                
                setTimeout(() => {
                    if (!window.google || !window.google.maps) {
                        reject(new Error('Google Maps loading timeout'));
                    }
                }, 15000);
            });
        }

        // Global callback
        window.initMap = function() {
            console.log("✅ Google Maps API loaded successfully");
            window.isMapReady = true;
            
            if (window.contactPage) {
                setTimeout(() => {
                    window.contactPage.initializeGoogleMaps();
                }, 100);
            }
        };

        // Error handler
        window.gm_authFailure = function() {
            console.error('❌ Google Maps authentication failed');
            if (window.contactPage) {
                window.contactPage.showMapFallback();
            }
        };

        // TÍNH NĂNG CHỈ ĐƯỜNG - QUAN TRỌNG
        window.showDirections = function(branchLat, branchLng, branchName, branchAddress) {
            if (!window.isMapReady) {
                alert('Bản đồ đang khởi tạo. Vui lòng chờ...');
                return;
            }

            // Hiển thị modal chọn phương thức chỉ đường
            const directionModal = new bootstrap.Modal(document.getElementById('directionModal'));
            document.getElementById('branchName').textContent = branchName;
            document.getElementById('branchAddress').textContent = branchAddress;
            
            // Lưu thông tin chi nhánh để sử dụng sau
            window.selectedBranch = {
                lat: branchLat,
                lng: branchLng,
                name: branchName,
                address: branchAddress
            };

            directionModal.show();
        };

        // Bắt đầu chỉ đường
        window.startDirections = function() {
            const transportMode = document.querySelector('input[name="transportMode"]:checked').value;
            
            if (!window.selectedBranch) {
                alert('Không tìm thấy thông tin chi nhánh');
                return;
            }

            if (window.contactPage) {
                window.contactPage.calculateAndDisplayRoute(
                    window.selectedBranch.lat, 
                    window.selectedBranch.lng, 
                    transportMode
                );
            }

            // Đóng modal
            const directionModal = bootstrap.Modal.getInstance(document.getElementById('directionModal'));
            directionModal.hide();
        };

        // Global functions khác
        window.focusOnMap = function(lat, lng, branchName) {
            if (!window.isMapReady) return;
            if (window.contactPage && window.contactPage.map) {
                const position = { lat: parseFloat(lat), lng: parseFloat(lng) };
                window.contactPage.map.setCenter(position);
                window.contactPage.map.setZoom(16);
            }
        };

        window.locateUser = function() {
            if (!window.isMapReady) return;
            if (!navigator.geolocation) {
                alert('Trình duyệt không hỗ trợ định vị.');
                return;
            }

            navigator.geolocation.getCurrentPosition(
                (position) => {
                    const userLocation = {
                        lat: position.coords.latitude,
                        lng: position.coords.longitude
                    };
                    
                    if (window.contactPage && window.contactPage.map) {
                        window.contactPage.map.setCenter(userLocation);
                        window.contactPage.map.setZoom(15);
                    }
                },
                (error) => {
                    alert('Không thể xác định vị trí.');
                }
            );
        };

        window.resetMap = function() {
            if (window.contactPage && window.contactPage.map && window.branchesData.length > 0) {
                const firstBranch = window.branchesData[0];
                window.contactPage.map.setCenter({
                    lat: parseFloat(firstBranch.latitude),
                    lng: parseFloat(firstBranch.longitude)
                });
                window.contactPage.map.setZoom(12);
                
                // Xóa tuyến đường cũ
                if (window.contactPage.directionsRenderer) {
                    window.contactPage.directionsRenderer.setMap(null);
                }
            }
        };

        // Start loading
        document.addEventListener('DOMContentLoaded', function() {
            console.log("📄 DOM loaded, starting Google Maps...");
            loadGoogleMaps().catch(error => {
                console.error('❌ Failed to load Google Maps:', error);
            });
        });
    </script>

    <script src="/PHARMAGO/public/assets/js/contact.js"></script>
</body>
</html>