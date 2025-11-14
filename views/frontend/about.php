<?php
// views/frontend/about.php

// Bật hiển thị lỗi để debug
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start session nếu chưa có
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    // Đường dẫn base
    $base_dir = dirname(__DIR__, 2) . '/';
    
    $config_path = $base_dir . 'config/database.php';
    
    // Kiểm tra file config
    if (!file_exists($config_path)) {
        throw new Exception('Database config file not found at: ' . $config_path);
    }
    require_once $config_path;
    
    // Kiểm tra file models
    $product_model_path = $base_dir . 'models/Product.php';
    $category_model_path = $base_dir . 'models/Category.php';
    
    if (!file_exists($product_model_path)) {
        throw new Exception('Product model file not found at: ' . $product_model_path);
    }
    require_once $product_model_path;
    
    if (!file_exists($category_model_path)) {
        throw new Exception('Category model file not found at: ' . $category_model_path);
    }
    require_once $category_model_path;

    // Khởi tạo database và models
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception('Cannot connect to database. Check database configuration.');
    }

    $productModel = new Product($db);
    $categoryModel = new Category($db);

    // Lấy dữ liệu thống kê từ database
    try {
        // Lấy tổng số sản phẩm
        $total_products = $productModel->getTotalProducts();
        
        // Lấy tổng số danh mục
        $total_categories = $categoryModel->getTotalCategories();
        
        // Lấy sản phẩm bán chạy
        $best_sellers = $productModel->getBestSellers(3);
        
    } catch (Exception $e) {
        error_log("Error getting statistics: " . $e->getMessage());
        $total_products = 0;
        $total_categories = 0;
        $best_sellers = [];
    }

} catch (Exception $e) {
    error_log("About page error: " . $e->getMessage());
    $total_products = 0;
    $total_categories = 0;
    $best_sellers = [];
}

// Hàm helper để lấy ảnh
function getImagePath($filename) {
    $image_path = $_SERVER['DOCUMENT_ROOT'] . '/PHARMAGO/public/assets/images/' . $filename;
    $web_path = '/PHARMAGO/public/assets/images/' . $filename;
    
    if (file_exists($image_path)) {
        return $web_path;
    }
    return 'https://images.unsplash.com/photo-1551076805-e1869033e561?ixlib=rb-4.0.3&auto=format&fit=crop&w=1000&q=80';
}

// Hàm helper để lấy ảnh sản phẩm
function getProductImage($product) {
    $default_image = 'https://images.unsplash.com/photo-1584308666744-24d5c474f2ae?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=80';
    
    $image_keys = ['image_url', 'image', 'product_image', 'main_image'];
    $found_image = null;
    
    foreach ($image_keys as $key) {
        if (isset($product[$key]) && !empty($product[$key])) {
            $found_image = $product[$key];
            break;
        }
    }
    
    if ($found_image) {
        if (filter_var($found_image, FILTER_VALIDATE_URL)) {
            return $found_image;
        }
        
        $image_path = $_SERVER['DOCUMENT_ROOT'] . '/PHARMAGO/public/assets/images/' . basename($found_image);
        $web_path = '/PHARMAGO/public/assets/images/' . basename($found_image);
        
        if (file_exists($image_path)) {
            return $web_path;
        }
        
        if (strpos($found_image, 'images/') === 0) {
            $web_path = '/PHARMAGO/public/assets/' . $found_image;
            $image_path = $_SERVER['DOCUMENT_ROOT'] . $web_path;
            
            if (file_exists($image_path)) {
                return $web_path;
            }
        }
    }
    
    return $default_image;
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giới Thiệu - Pharmacy - Nhà Thuốc Uy Tín Hàng Đầu</title>
    <link rel="icon" type="image/x-icon" href="/PHARMAGO/public/assets/images/favicon.ico">
    
    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link rel="stylesheet" href="/PHARMAGO/public/assets/css/home.css">
    <link rel="stylesheet" href="/PHARMAGO/public/assets/css/about.css">
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
                        <a class="nav-link active" href="/PHARMAGO/public/about">
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

    <!-- Quick Navigation -->
    <div class="quick-nav">
        <a href="#home" title="Lên đầu trang"><i class="fas fa-arrow-up"></i></a>
        <a href="#mission" title="Sứ mệnh"><i class="fas fa-bullseye"></i></a>
        <a href="#timeline" title="Hành trình"><i class="fas fa-history"></i></a>
        <a href="#ai-assistant" title="AI Tư vấn"><i class="fas fa-robot"></i></a>
    </div>

    <!-- Scroll to Top Button -->
    <button class="scroll-top" id="scrollTop">
        <i class="fas fa-chevron-up"></i>
    </button>

    <!-- Hero Section -->
    <section id="home" class="about-hero-section">
        <div class="container">
            <div class="row align-items-center min-vh-80">
                <div class="col-lg-6">
                    <h1 class="display-4 fw-bold text-white mb-4 animate__animated animate__fadeInLeft">
                        Vì Sức Khỏe <span class="text-warning">Cộng Đồng</span>
                    </h1>
                    <p class="lead text-white mb-5 animate__animated animate__fadeInLeft">
                        Hơn 15 năm đồng hành cùng sức khỏe người Việt. Chúng tôi cam kết mang đến những giải pháp chăm sóc sức khỏe toàn diện và tin cậy nhất.
                    </p>
                    <div class="hero-stats animate__animated animate__fadeInUp">
                        <div class="stat">
                            <div class="stat-number">15+</div>
                            <div class="stat-label">Năm kinh nghiệm</div>
                        </div>
                        <div class="stat">
                            <div class="stat-number"><?php echo number_format($total_products); ?>+</div>
                            <div class="stat-label">Sản phẩm đa dạng</div>
                        </div>
                        <div class="stat">
                            <div class="stat-number"><?php echo number_format($total_categories); ?>+</div>
                            <div class="stat-label">Danh mục phong phú</div>
                        </div>
                        <div class="stat">
                            <div class="stat-number">100%</div>
                            <div class="stat-label">Sản phẩm chính hãng</div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 text-center">
                    <div class="hero-visual animate__animated animate__fadeInRight">
                        <div class="floating-elements">
                            <div class="floating-element element-1">
                                <i class="fas fa-heartbeat"></i>
                            </div>
                            <div class="floating-element element-2">
                                <i class="fas fa-stethoscope"></i>
                            </div>
                            <div class="floating-element element-3">
                                <i class="fas fa-capsules"></i>
                            </div>
                        </div>
                        <img src="<?php echo getImagePath('about-hero.jpg'); ?>" alt="Nhà thuốc Pharmacy" 
                             class="img-fluid rounded-3 hero-image">
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Mission Vision Section -->
    <section id="mission" class="mission-vision-section py-5">
        <div class="container">
            <div class="row align-items-stretch g-5">
                <div class="col-lg-6">
                    <div class="mission-card card border-0 shadow-lg h-100">
                        <div class="card-body p-5 d-flex flex-column">
                            <div class="icon-wrapper mb-4">
                                <i class="fas fa-bullseye"></i>
                            </div>
                            <h2 class="card-title h3 text-primary mb-4">Sứ Mệnh Của Chúng Tôi</h2>
                            <p class="card-text fs-5 text-dark flex-grow-1">
                                Cung cấp các sản phẩm dược phẩm chất lượng cao, dịch vụ tư vấn chuyên nghiệp và giải pháp chăm sóc sức khỏe toàn diện cho mọi gia đình Việt Nam.
                            </p>
                            <ul class="list-unstyled mt-4">
                                <li class="mb-3 d-flex align-items-center">
                                    <i class="fas fa-check text-success me-3 fs-5"></i>
                                    <span>Đảm bảo 100% sản phẩm chính hãng</span>
                                </li>
                                <li class="mb-3 d-flex align-items-center">
                                    <i class="fas fa-check text-success me-3 fs-5"></i>
                                    <span>Tư vấn bởi dược sĩ chuyên môn cao</span>
                                </li>
                                <li class="mb-3 d-flex align-items-center">
                                    <i class="fas fa-check text-success me-3 fs-5"></i>
                                    <span>Giá cả hợp lý, minh bạch</span>
                                </li>
                                <li class="mb-0 d-flex align-items-center">
                                    <i class="fas fa-check text-success me-3 fs-5"></i>
                                    <span>Dịch vụ 24/7 tận tâm</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="vision-card card border-0 shadow-lg h-100">
                        <div class="card-body p-5 d-flex flex-column">
                            <div class="icon-wrapper mb-4">
                                <i class="fas fa-eye"></i>
                            </div>
                            <h2 class="card-title h3 text-primary mb-4">Tầm Nhìn Tương Lai</h2>
                            <p class="card-text fs-5 text-dark flex-grow-1">
                                Trở thành hệ thống nhà thuốc tin cậy hàng đầu, tiên phong trong ứng dụng công nghệ 4.0 để nâng cao chất lượng dịch vụ chăm sóc sức khỏe cộng đồng.
                            </p>
                            <div class="vision-goals mt-4">
                                <div class="goal-item">
                                    <div class="goal-year">2025</div>
                                    <div class="goal-content">
                                        <h5 class="text-success mb-2">Mở rộng hệ thống</h5>
                                        <p class="mb-0 text-dark">Phát triển thành chuỗi 20 nhà thuốc trên toàn quốc</p>
                                    </div>
                                </div>
                                <div class="goal-item">
                                    <div class="goal-year">2026</div>
                                    <div class="goal-content">
                                        <h5 class="text-success mb-2">Ứng dụng thông minh</h5>
                                        <p class="mb-0 text-dark">Ra mắt app di động với AI tư vấn sức khỏe</p>
                                    </div>
                                </div>
                                <div class="goal-item">
                                    <div class="goal-year">2027</div>
                                    <div class="goal-content">
                                        <h5 class="text-success mb-2">Y học cá nhân hóa</h5>
                                        <p class="mb-0 text-dark">Tiên phong trong giải pháp chăm sóc sức khỏe cá nhân hóa</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Core Values -->
    <section class="core-values-section py-5">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="section-title">Giá Trị Cốt Lõi</h2>
                <p class="lead text-muted">Những nguyên tắc định hướng mọi hoạt động của Pharmacy</p>
            </div>
            <div class="row g-4 justify-content-center">
                <div class="col-lg-4 col-md-6">
                    <div class="value-card text-center p-4 h-100">
                        <div class="value-icon mb-4">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <h4 class="text-primary mb-3">Chất Lượng</h4>
                        <p class="text-dark mb-0">Cam kết 100% sản phẩm chính hãng, có nguồn gốc rõ ràng, đảm bảo tiêu chuẩn chất lượng cao nhất từ các nhà sản xuất uy tín.</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="value-card text-center p-4 h-100">
                        <div class="value-icon mb-4">
                            <i class="fas fa-heart"></i>
                        </div>
                        <h4 class="text-primary mb-3">Tận Tâm</h4>
                        <p class="text-dark mb-0">Đặt sức khỏe và lợi ích của khách hàng lên hàng đầu với sự tư vấn tận tình, chuyên nghiệp từ đội ngũ dược sĩ giàu kinh nghiệm.</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="value-card text-center p-4 h-100">
                        <div class="value-icon mb-4">
                            <i class="fas fa-lightbulb"></i>
                        </div>
                        <h4 class="text-primary mb-3">Đổi Mới</h4>
                        <p class="text-dark mb-0">Ứng dụng công nghệ tiên tiến, AI để mang đến trải nghiệm chăm sóc sức khỏe thông minh, hiện đại và tiện lợi nhất.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- AI Assistant Section -->
    <section id="ai-assistant" class="ai-assistant-section py-5">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-5 mb-5 mb-lg-0">
                    <h2 class="section-title mb-4">Trợ Lý AI Thông Minh</h2>
                    <p class="lead text-muted mb-4">Tư vấn sức khỏe 24/7 với AI Gemini của Google</p>
                    
                    <div class="ai-features">
                        <div class="feature-item">
                            <div class="feature-icon">
                                <i class="fas fa-robot"></i>
                            </div>
                            <div class="feature-content">
                                <h5 class="text-primary">AI Gemini</h5>
                                <p class="mb-0 text-dark">Công nghệ AI tiên tiến nhất từ Google</p>
                            </div>
                        </div>
                        <div class="feature-item">
                            <div class="feature-icon">
                                <i class="fas fa-microphone"></i>
                            </div>
                            <div class="feature-content">
                                <h5 class="text-primary">Nhận diện giọng nói</h5>
                                <p class="mb-0 text-dark">Chat bằng giọng nói tiện lợi</p>
                            </div>
                        </div>
                        <div class="feature-item">
                            <div class="feature-icon">
                                <i class="fas fa-camera"></i>
                            </div>
                            <div class="feature-content">
                                <h5 class="text-primary">Phân tích ảnh</h5>
                                <p class="mb-0 text-dark">Tải ảnh lên để được tư vấn</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <div class="d-grid gap-2">
                            <button class="btn btn-primary btn-lg" onclick="startVoiceRecognition()">
                                <i class="fas fa-microphone me-2"></i>Nói chuyện với AI
                            </button>
                            <button class="btn btn-outline-primary btn-lg" onclick="document.getElementById('imageInput').click()">
                                <i class="fas fa-camera me-2"></i>Tải ảnh lên
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-7">
                    <div class="ai-chat-container">
                        <div class="ai-chat-header">
                            <div class="ai-avatar">
                                <i class="fas fa-robot"></i>
                            </div>
                            <div class="ai-info">
                                <h5 class="mb-1 text-white">Pharmacy AI Assistant</h5>
                                <span class="status online">Powered by Gemini AI</span>
                            </div>
                            <div class="ai-actions">
                                <button class="btn btn-sm btn-light me-2" onclick="startVoiceRecognition()" title="Nhận diện giọng nói">
                                    <i class="fas fa-microphone"></i>
                                </button>
                                <button class="btn btn-sm btn-light" onclick="clearChat()" title="Xóa chat">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="ai-chat-messages" id="aiChatMessages">
                            <div class="message ai-message">
                                <div class="message-content">
                                    <strong>Xin chào! 👋</strong><br><br>
                                    Tôi là trợ lý AI của Pharmacy, được hỗ trợ bởi <strong>Google Gemini AI</strong>. 
                                    Tôi có thể giúp bạn:
                                    <br>• Tư vấn về thuốc và sức khỏe
                                    <br>• Hướng dẫn sử dụng thuốc
                                    <br>• Thông tin về tác dụng phụ
                                    <br>• Gợi ý sản phẩm phù hợp
                                    <br>• Phân tích triệu chứng qua ảnh
                                    <br><br>
                                    Hãy cho tôi biết tình trạng sức khỏe của bạn!
                                </div>
                            </div>
                        </div>
                        
                        <div class="ai-chat-input-container">
                            <div class="ai-chat-input">
                                <textarea id="aiMessageInput" placeholder="Nhập câu hỏi về sức khỏe của bạn hoặc sử dụng giọng nói..." rows="2"></textarea>
                                <div class="chat-buttons">
                                    <button class="btn btn-sm btn-outline-primary me-2" onclick="startVoiceRecognition()" title="Nhận diện giọng nói">
                                        <i class="fas fa-microphone"></i>
                                    </button>
                                    <button id="sendAiMessage" class="btn btn-primary">
                                        <i class="fas fa-paper-plane"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="voice-status" id="voiceStatus" style="display: none;">
                                <i class="fas fa-microphone text-primary me-2"></i>
                                <span id="voiceStatusText">Đang nghe...</span>
                            </div>
                        </div>
                        
                        <input type="file" id="imageInput" accept="image/*" style="display: none;" onchange="handleImageUpload(this.files[0])">
                        
                        <div class="ai-disclaimer">
                            <i class="fas fa-exclamation-triangle text-warning me-2"></i>
                            <small>Tư vấn AI chỉ mang tính tham khảo. Vui lòng tham khảo ý kiến bác sĩ cho các vấn đề nghiêm trọng.</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Timeline Section -->
    <section id="timeline" class="timeline-section py-5 bg-light">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="section-title">Hành Trình Phát Triển</h2>
                <p class="lead text-muted">15 năm đồng hành cùng sức khỏe cộng đồng</p>
            </div>
            
            <div class="timeline-container">
                <div class="timeline-progress">
                    <div class="timeline-progress-bar"></div>
                </div>
                
                <div class="timeline-items">
                    <div class="timeline-item" data-year="2009">
                        <div class="timeline-dot"></div>
                        <div class="timeline-content">
                            <div class="timeline-year">2009</div>
                            <h4 class="text-primary">Thành lập Pharmacy</h4>
                            <p class="text-dark">Khởi đầu với một nhà thuốc nhỏ tại Đà Nẵng, mang sứ mệnh chăm sóc sức khỏe cộng đồng với đội ngũ 5 dược sĩ tận tâm.</p>
                        </div>
                    </div>
                    
                    <div class="timeline-item" data-year="2014">
                        <div class="timeline-dot"></div>
                        <div class="timeline-content">
                            <div class="timeline-year">2014</div>
                            <h4 class="text-primary">Mở rộng hệ thống</h4>
                            <p class="text-dark">Phát triển thành chuỗi 5 nhà thuốc tại miền Trung, khẳng định vị thế trên thị trường dược phẩm.</p>
                        </div>
                    </div>
                    
                    <div class="timeline-item" data-year="2019">
                        <div class="timeline-dot"></div>
                        <div class="timeline-content">
                            <div class="timeline-year">2019</div>
                            <h4 class="text-primary">Ứng dụng công nghệ</h4>
                            <p class="text-dark">Triển khai hệ thống quản lý hiện đại và dịch vụ đặt hàng trực tuyến, phục vụ hơn 10,000 khách hàng.</p>
                        </div>
                    </div>
                    
                    <div class="timeline-item" data-year="2024">
                        <div class="timeline-dot"></div>
                        <div class="timeline-content">
                            <div class="timeline-year">2024</div>
                            <h4 class="text-primary">AI Integration</h4>
                            <p class="text-dark">Triển khai trợ lý AI tư vấn sức khỏe thông minh với Google Gemini, tiên phong trong ứng dụng công nghệ 4.0 vào lĩnh vực dược phẩm.</p>
                        </div>
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
                        <li class="mb-2"><a href="/PHARMAGO/public/contact">Liên hệ</a></li>
                    </ul>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <h5>Thông tin</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="#mission">Sứ mệnh & Tầm nhìn</a></li>
                        <li class="mb-2"><a href="#timeline">Hành trình phát triển</a></li>
                        <li class="mb-2"><a href="#ai-assistant">AI Tư vấn</a></li>
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

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/PHARMAGO/public/assets/js/home.js"></script>
    <script src="/PHARMAGO/public/assets/js/about.js"></script>
    <script src="/PHARMAGO/public/assets/js/script.js"></script>
</body>
</html>