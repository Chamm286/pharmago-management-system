<?php
// views/frontend/categories.php

// Bật hiển thị lỗi để debug
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start session nếu chưa có
if (session_status() === PHP_SESSION_NONE) {
    session_start();
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
    $category_model_path = $base_dir . 'models/Category.php';
    
    if (!file_exists($category_model_path)) {
        throw new Exception('Category model file not found at: ' . $category_model_path);
    }
    require_once $category_model_path;

    // Khởi tạo database và model
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception('Cannot connect to database. Check database configuration.');
    }

    $categoryModel = new Category($db);
    $categories_result = $categoryModel->getAllCategories();

    // SỬA LẠI: CHUYỂN PDOStatement THÀNH ARRAY
    if ($categories_result instanceof PDOStatement) {
        $categories = $categories_result->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $categories = is_array($categories_result) ? $categories_result : [];
    }

    error_log("Categories count: " . count($categories));

} catch (Exception $e) {
    // Xử lý lỗi graceful
    error_log("Categories page error: " . $e->getMessage());
    $categories = [];
    $error_message = "Không thể tải dữ liệu danh mục. Vui lòng quay lại sau.";
    $debug_error = $e->getMessage();
}

// Hàm helper để lấy ảnh danh mục
function getCategoryImage($category) {
    $default_image = 'https://images.unsplash.com/photo-1559757148-5c350d0d3c56?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1000&q=80';
    
    if (isset($category['image_url']) && !empty($category['image_url'])) {
        // SỬA ĐƯỜNG DẪN ẢNH - DÙNG ĐƯỜNG DẪN TUYỆT ĐỐI
        $web_path = '/PHARMAGO/public/assets/images/' . basename($category['image_url']);
        return $web_path;
    }
    
    return $default_image;
}

// Hàm helper để lấy icon danh mục
function getCategoryIcon($category) {
    $default_icon = 'fas fa-pills';
    $icon_mapping = [
        'thuốc kháng sinh' => 'fas fa-bacteria',
        'vitamin' => 'fas fa-capsules',
        'giảm đau' => 'fas fa-head-side-virus',
        'tiêu hóa' => 'fas fa-stomach',
        'tim mạch' => 'fas fa-heartbeat',
        'da liễu' => 'fas fa-hand-holding-medical',
        'thần kinh' => 'fas fa-brain',
        'hô hấp' => 'fas fa-lungs'
    ];
    
    $category_name = strtolower($category['category_name'] ?? '');
    foreach ($icon_mapping as $key => $icon) {
        if (strpos($category_name, $key) !== false) {
            return $icon;
        }
    }
    
    return $category['icon_class'] ?? $default_icon;
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Danh Mục Sản Phẩm - Pharmacy</title>
    
    <!-- CSS - ĐƯỜNG DẪN TUYỆT ĐỐI TỪ ROOT -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link rel="stylesheet" href="/PHARMAGO/public/assets/css/categories.css">
    <link rel="stylesheet" href="/PHARMAGO/public/assets/css/style.css">
</head>
<body>
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
                        <a class="nav-link active" href="/PHARMAGO/public/categories">
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
                        <?php if(isset($_SESSION['user_id'])): ?>
                            <a class="nav-link" href="/PHARMAGO/public/logout">
                                <i class="fas fa-sign-out-alt me-1"></i>Đăng xuất
                            </a>
                        <?php else: ?>
                            <a class="nav-link" href="/PHARMAGO/public/login">
                                <i class="fas fa-sign-in-alt me-1"></i>Đăng nhập
                            </a>
                        <?php endif; ?>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Debug Info (Chỉ hiển thị khi có lỗi) -->
    <?php if(isset($debug_error)): ?>
    <div class="container mt-3">
        <div class="alert alert-danger">
            <h5>Debug Information:</h5>
            <p><strong>Error:</strong> <?php echo htmlspecialchars($debug_error); ?></p>
            <p><strong>Config Path:</strong> <?php echo htmlspecialchars($base_dir . 'config/database.php'); ?></p>
            <p><strong>Config Exists:</strong> <?php echo file_exists($base_dir . 'config/database.php') ? 'Yes' : 'No'; ?></p>
        </div>
    </div>
    <?php endif; ?>

    <!-- Scroll to Top Button -->
    <button class="scroll-top" id="scrollTop">
        <i class="fas fa-chevron-up"></i>
    </button>

    <!-- Hero Section -->
    <section id="home" class="hero-section">
        <div class="container text-center">
            <h1 class="display-4 fw-bold mb-4 animate__animated animate__fadeInDown">Danh Mục Sản Phẩm</h1>
            <p class="lead mb-5 animate__animated animate__fadeInUp">Lựa chọn các sản phẩm chất lượng, an toàn cho sức khỏe gia đình bạn</p>
        </div>
    </section>

    <!-- Categories Grid -->
    <section class="categories-section py-5">
        <div class="container">
            <?php if(isset($error_message)): ?>
                <div class="alert alert-danger text-center">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>
            
            <div class="row mb-5">
                <div class="col-12 text-center">
                    <h2 class="section-title">Tất Cả Danh Mục</h2>
                    <p class="lead text-muted">Khám phá đa dạng các loại thuốc và sản phẩm chăm sóc sức khỏe</p>
                </div>
            </div>
            
            <div class="row">
                <?php if(!empty($categories)): ?>
                    <?php foreach($categories as $category): ?>
                    <div class="col-lg-4 col-md-6 mb-4 animate-fade-in">
                        <div class="category-card card h-100">
                            <div class="category-img-container">
                                <img src="<?php echo getCategoryImage($category); ?>" 
                                     class="category-img" 
                                     alt="<?php echo htmlspecialchars($category['category_name'] ?? $category['name'] ?? 'Danh mục'); ?>"
                                     onerror="this.src='https://images.unsplash.com/photo-1559757148-5c350d0d3c56?ixlib=rb-4.0.3&auto=format&fit=crop&w=1000&q=80'">
                            </div>
                            <div class="card-body text-center">
                                <div class="category-icon mb-3">
                                    <i class="<?php echo getCategoryIcon($category); ?> fa-3x"></i>
                                </div>
                                <h3 class="card-title"><?php echo htmlspecialchars($category['category_name'] ?? $category['name'] ?? 'Danh mục'); ?></h3>
                                <p class="card-text text-muted">
                                    <?php echo htmlspecialchars($category['category_description'] ?? $category['description'] ?? 'Sản phẩm chất lượng cao cho sức khỏe'); ?>
                                </p>
                                <a href="/PHARMAGO/public/category-detail?id=<?php echo $category['category_id'] ?? $category['id'] ?? ''; ?>" 
                                   class="btn btn-primary">
                                    <i class="fas fa-eye me-2"></i>Xem sản phẩm
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12 text-center py-5">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <?php echo isset($error_message) ? $error_message : 'Đang tải danh mục sản phẩm...'; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="stats-section">
        <div class="container">
            <div class="row mb-5">
                <div class="col-12 text-center">
                    <h2 class="section-title text-white">Sản Phẩm Được Tìm Kiếm Nhiều Nhất</h2>
                    <p class="lead text-white-50">Các sản phẩm chăm sóc sức khỏe được ưa chuộng</p>
                </div>
            </div>
            <div class="row">
                <div class="col-md-4 mb-4">
                    <div class="stat-item animate-fade-in">
                        <div class="stat-number">1,200+</div>
                        <h3 class="stat-title">Amoxicillin</h3>
                        <p class="text-white-50">Lượt tìm kiếm tháng này</p>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="stat-item animate-fade-in">
                        <div class="stat-number">950+</div>
                        <h3 class="stat-title">Paracetamol</h3>
                        <p class="text-white-50">Lượt tìm kiếm tháng này</p>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="stat-item animate-fade-in">
                        <div class="stat-number">780+</div>
                        <h3 class="stat-title">Omeprazole</h3>
                        <p class="text-white-50">Lượt tìm kiếm tháng này</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Review Section -->
    <section class="review-section">
        <div class="container">
            <div class="row mb-5">
                <div class="col-12 text-center">
                    <h2 class="section-title">Đánh Giá Của Khách Hàng</h2>
                    <p class="lead text-muted">Những phản hồi chân thực từ khách hàng của chúng tôi</p>
                </div>
            </div>
            <div class="row">
                <div class="col-md-4 mb-4">
                    <div class="review-card animate-fade-in">
                        <div class="review-rating mb-3">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                        </div>
                        <p class="mb-3">"Thuốc chất lượng, hiệu quả tốt. Nhân viên tư vấn nhiệt tình, giao hàng nhanh chóng. Tôi rất hài lòng với dịch vụ!"</p>
                        <div class="d-flex align-items-center">
                            <img src="https://images.unsplash.com/photo-1494790108755-2616b612b786?ixlib=rb-4.0.3&auto=format&fit=crop&w=100&q=80" 
                                 class="rounded-circle me-3" width="50" height="50" alt="Customer"
                                 onerror="this.src='https://ui-avatars.com/api/?name=Nguyen+Thi+A&background=2c7fb8&color=fff'">
                            <div>
                                <h6 class="mb-0">Nguyễn Thị A</h6>
                                <small class="text-muted">Khách hàng thân thiết</small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="review-card animate-fade-in">
                        <div class="review-rating mb-3">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star-half-alt"></i>
                        </div>
                        <p class="mb-3">"Dược sĩ tư vấn rất kỹ lưỡng về cách sử dụng thuốc và tác dụng phụ. Sản phẩm chính hãng, giá cả hợp lý."</p>
                        <div class="d-flex align-items-center">
                            <img src="https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?ixlib=rb-4.0.3&auto=format&fit=crop&w=100&q=80" 
                                 class="rounded-circle me-3" width="50" height="50" alt="Customer"
                                 onerror="this.src='https://ui-avatars.com/api/?name=Tran+Van+B&background=2c7fb8&color=fff'">
                            <div>
                                <h6 class="mb-0">Trần Văn B</h6>
                                <small class="text-muted">Khách hàng mới</small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="review-card animate-fade-in">
                        <div class="review-rating mb-3">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                        </div>
                        <p class="mb-3">"Giao hàng nhanh, đóng gói cẩn thận. Thuốc có tem chống hàng giả rõ ràng. Sẽ tiếp tục ủng hộ nhà thuốc!"</p>
                        <div class="d-flex align-items-center">
                            <img src="https://images.unsplash.com/photo-1438761681033-6461ffad8d80?ixlib=rb-4.0.3&auto=format&fit=crop&w=100&q=80" 
                                 class="rounded-circle me-3" width="50" height="50" alt="Customer"
                                 onerror="this.src='https://ui-avatars.com/api/?name=Le+Thi+C&background=2c7fb8&color=fff'">
                            <div>
                                <h6 class="mb-0">Lê Thị C</h6>
                                <small class="text-muted">Khách hàng thường xuyên</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section id="contact" class="contact-section">
        <div class="container">
            <div class="row">
                <div class="col-lg-8 mx-auto text-center">
                    <h2 class="section-title">Cần Tư Vấn Thêm?</h2>
                    <p class="lead mb-5">Liên hệ ngay với chúng tôi để được tư vấn miễn phí</p>
                    <div class="row">
                        <div class="col-md-4 mb-4">
                            <div class="p-4 rounded contact-info">
                                <i class="fas fa-map-marker-alt fa-2x mb-3"></i>
                                <h5>Địa chỉ</h5>
                                <p class="mb-0">123 Đường 2/9, Quận Hải Châu, TP. Đà Nẵng</p>
                            </div>
                        </div>
                        <div class="col-md-4 mb-4">
                            <div class="p-4 rounded contact-info">
                                <i class="fas fa-phone fa-2x mb-3"></i>
                                <h5>Điện thoại</h5>
                                <p class="mb-0">(028) 123456789</p>
                            </div>
                        </div>
                        <div class="col-md-4 mb-4">
                            <div class="p-4 rounded contact-info">
                                <i class="fas fa-envelope fa-2x mb-3"></i>
                                <h5>Email</h5>
                                <p class="mb-0">info@pharmacy.com</p>
                            </div>
                        </div>
                    </div>
                    <div class="mt-5">
                        <h5 class="mb-3">Giờ làm việc</h5>
                        <p class="text-muted mb-1">Thứ 2 - Chủ nhật: 7:00 - 22:00</p>
                        <p class="text-muted">Dịch vụ khẩn cấp: 24/7</p>
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
                    <h5><i class="fas fa-prescription-bottle-alt me-2"></i>Pharmacy</h5>
                    <p>Địa chỉ tin cậy cho sức khỏe của bạn và gia đình. Cam kết chất lượng và dịch vụ tốt nhất với tiêu chí "Thuốc tốt - Dịch vụ tốt - Giá cả hợp lý".</p>
                </div>
                <div class="col-lg-2 col-md-6 mb-4">
                    <h5>Liên kết</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="/PHARMAGO/public/">Trang chủ</a></li>
                        <li class="mb-2"><a href="/PHARMAGO/public/categories">Danh mục</a></li>
                        <li class="mb-2"><a href="/PHARMAGO/public/products">Sản phẩm</a></li>
                        <li class="mb-2"><a href="#contact">Liên hệ</a></li>
                    </ul>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <h5>Danh mục</h5>
                    <ul class="list-unstyled">
                        <?php if(!empty($categories) && is_array($categories)): ?>
                            <?php foreach(array_slice($categories, 0, 3) as $category): ?>
                            <li class="mb-2">
                                <a href="/PHARMAGO/public/category-detail?id=<?php echo $category['category_id'] ?? $category['id'] ?? ''; ?>">
                                    <?php echo htmlspecialchars($category['category_name'] ?? $category['name'] ?? 'Danh mục'); ?>
                                </a>
                            </li>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <li class="mb-2"><a href="#">Đang tải...</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
                <div class="col-lg-3 mb-4">
                    <h5>Theo dõi chúng tôi</h5>
                    <div class="social-icons">
                        <a href="#"><i class="fab fa-facebook-f"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-youtube"></i></a>
                    </div>
                    <div class="mt-4">
                        <h6>Đăng ký nhận tin</h6>
                        <div class="input-group">
                            <input type="email" class="form-control" placeholder="Email của bạn">
                            <button class="btn btn-light" type="button">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <hr style="border-color: rgba(255,255,255,0.1);">
            <div class="text-center">
                <p class="mb-0">&copy; <?php echo date('Y'); ?> Pharmacy. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Scripts - ĐƯỜNG DẪN TUYỆT ĐỐI TỪ ROOT -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/PHARMAGO/public/assets/js/categories.js"></script>
    <script src="/PHARMAGO/public/assets/js/script.js"></script>
</body>
</html>