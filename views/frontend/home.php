<?php
// views/frontend/home.php

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

    // Lấy dữ liệu - SỬA LẠI PHẦN NÀY
    try {
        $featured_result = $productModel->getFeaturedProducts(6);
        // Chuyển PDOStatement thành array nếu cần
        if ($featured_result instanceof PDOStatement) {
            $featured_products = $featured_result->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $featured_products = is_array($featured_result) ? $featured_result : [];
        }
        error_log("Featured products count: " . count($featured_products));
    } catch (Exception $e) {
        error_log("Error getting featured products: " . $e->getMessage());
        $featured_products = [];
    }

    try {
        $categories_result = $categoryModel->getAllCategories();
        // Chuyển PDOStatement thành array nếu cần
        if ($categories_result instanceof PDOStatement) {
            $categories = $categories_result->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $categories = is_array($categories_result) ? $categories_result : [];
        }
        error_log("Categories count: " . count($categories));
    } catch (Exception $e) {
        error_log("Error getting categories: " . $e->getMessage());
        $categories = [];
    }

} catch (Exception $e) {
    error_log("Home page error: " . $e->getMessage());
    $featured_products = [];
    $categories = [];
    $error_message = "Hệ thống đang bảo trì. Vui lòng quay lại sau.";
    $debug_error = $e->getMessage();
}

// Hàm helper để lấy ảnh sản phẩm
function getProductImage($product) {
    $default_image = 'https://images.unsplash.com/photo-1584308666744-24d5c474f2ae?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=80';
    
    // SỬA LẠI KEY NAME - có thể dùng 'image' thay vì 'image_url'
    $image_key = isset($product['image_url']) ? 'image_url' : 'image';
    
    if (isset($product[$image_key]) && !empty($product[$image_key])) {
        // SỬA ĐƯỜNG DẪN ẢNH
        $image_path = $_SERVER['DOCUMENT_ROOT'] . '/PHARMAGO/public/assets/images/' . basename($product[$image_key]);
        $web_path = '/PHARMAGO/public/assets/images/' . basename($product[$image_key]);
        
        if (file_exists($image_path)) {
            return $web_path;
        }
    }
    
    return $default_image;
}

// Hàm helper để lấy ảnh danh mục
function getCategoryImage($category) {
    $default_image = 'https://images.unsplash.com/photo-1559757148-5c350d0d3c56?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1000&q=80';
    
    $image_key = isset($category['image_url']) ? 'image_url' : 'image';
    
    if (isset($category[$image_key]) && !empty($category[$image_key])) {
        $image_path = $_SERVER['DOCUMENT_ROOT'] . '/PHARMAGO/public/assets/images/' . basename($category[$image_key]);
        $web_path = '/PHARMAGO/public/assets/images/' . basename($category[$image_key]);
        
        if (file_exists($image_path)) {
            return $web_path;
        }
    }
    
    return $default_image;
}

// Hàm helper để lấy đường dẫn ảnh hero
function getHeroImage($filename) {
    $image_path = $_SERVER['DOCUMENT_ROOT'] . '/PHARMAGO/public/assets/images/' . $filename;
    $web_path = '/PHARMAGO/public/assets/images/' . $filename;
    
    if (file_exists($image_path)) {
        return $web_path;
    }
    return 'https://images.unsplash.com/photo-1559757148-5c350d0d3c56?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1000&q=80';
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nhà Thuốc Pharmacy - Chăm sóc sức khỏe toàn diện</title>
    <link rel="icon" type="image/x-icon" href="/PHARMAGO/public/assets/images/favicon.ico">
    
    <!-- CSS - SỬA ĐƯỜNG DẪN ĐÚNG -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link rel="stylesheet" href="/PHARMAGO/public/assets/css/home.css">
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
                        <a class="nav-link active" href="/PHARMAGO/public/">
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

    <!-- Quick Navigation -->
    <div class="quick-nav">
        <a href="#home" title="Lên đầu trang"><i class="fas fa-arrow-up"></i></a>
        <a href="/PHARMAGO/public/products" title="Sản phẩm"><i class="fas fa-pills"></i></a>
        <a href="/PHARMAGO/public/categories" title="Danh mục"><i class="fas fa-list"></i></a>
        <a href="#contact" title="Liên hệ"><i class="fas fa-phone"></i></a>
    </div>

    <!-- Scroll to Top Button -->
    <button class="scroll-top" id="scrollTop">
        <i class="fas fa-chevron-up"></i>
    </button>

    <!-- Hero Section -->
    <section id="home" class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h1 class="display-4 fw-bold mb-4 animate__animated animate__fadeInLeft">Chăm Sóc Sức Khỏe Chuyên Nghiệp</h1>
                    <p class="lead mb-5 animate__animated animate__fadeInLeft">Pharmacy cung cấp đầy đủ các loại thuốc chất lượng cao cùng dịch vụ tư vấn sức khỏe chuyên nghiệp từ đội ngũ dược sĩ giàu kinh nghiệm.</p>
                    <div class="d-flex flex-wrap gap-3 animate__animated animate__fadeInUp">
                        <a href="/PHARMAGO/public/products" class="btn btn-primary btn-lg">Đặt mua ngay</a>
                        <a href="#about" class="btn btn-outline-light btn-lg">Tìm hiểu thêm</a>
                    </div>
                </div>
                <div class="col-lg-6 text-center animate__animated animate__fadeInRight">
                    <img src="<?php echo getHeroImage('Hinh2.jpg'); ?>" alt="Nhà thuốc Pharmacy" class="img-fluid hero-image">
                </div>
            </div>
        </div>
    </section>

    <!-- Featured Products -->
    <section id="products" class="featured-products">
        <div class="container">
            <div class="row mb-5">
                <div class="col-12 text-center">
                    <h2 class="section-title">Sản Phẩm Nổi Bật</h2>
                    <p class="lead text-muted">Các sản phẩm được ưa chuộng nhất</p>
                </div>
            </div>
            
            <?php if(isset($error_message)): ?>
                <div class="alert alert-warning text-center">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?php echo $error_message; ?>
                    <?php if(isset($debug_error)): ?>
                        <br><small class="text-muted">Technical details have been logged.</small>
                    <?php endif; ?>
                </div>
            <?php elseif(!empty($featured_products)): ?>
                <div class="row">
                    <?php foreach($featured_products as $product): ?>
                    <div class="col-lg-4 col-md-6 mb-4 animate-fade-in">
                        <div class="card product-card h-100">
                            <div class="product-img-container">
                                <img src="<?php echo getProductImage($product); ?>" 
                                     class="product-img" 
                                     alt="<?php echo htmlspecialchars($product['product_name'] ?? $product['name'] ?? 'Sản phẩm'); ?>">
                                <?php if($product['is_best_seller'] ?? false): ?>
                                    <span class="product-badge">Bán chạy</span>
                                <?php elseif($product['is_new'] ?? false): ?>
                                    <span class="product-badge">Mới</span>
                                <?php endif; ?>
                            </div>
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title"><?php echo htmlspecialchars($product['product_name'] ?? $product['name'] ?? 'Sản phẩm'); ?></h5>
                                <p class="card-text text-muted flex-grow-1">
                                    <?php echo htmlspecialchars($product['short_description'] ?? $product['description'] ?? 'Sản phẩm chất lượng cao'); ?>
                                </p>
                                <div class="product-rating mb-2">
                                    <?php $rating = $product['average_rating'] ?? 4.5; ?>
                                    <?php for($i = 1; $i <= 5; $i++): ?>
                                        <i class="fas fa-star <?php echo $i <= $rating ? 'text-warning' : 'text-muted'; ?>"></i>
                                    <?php endfor; ?>
                                    <span class="ms-1 text-muted">(<?php echo $product['review_count'] ?? 0; ?>)</span>
                                </div>
                                <div class="mt-auto">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <span class="product-price fw-bold">
                                            <?php echo number_format($product['price'] ?? 0, 0, ',', '.'); ?>đ
                                        </span>
                                        <?php if(isset($product['original_price']) && $product['original_price'] > $product['price']): ?>
                                            <span class="product-old-price text-muted text-decoration-line-through">
                                                <?php echo number_format($product['original_price'], 0, ',', '.'); ?>đ
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="d-grid gap-2">
                                        <a href="/PHARMAGO/public/product-detail?id=<?php echo $product['product_id'] ?? $product['id'] ?? ''; ?>" class="btn btn-primary">
                                            <i class="fas fa-eye me-2"></i>Xem chi tiết
                                        </a>
                                        <?php if(isset($_SESSION['user_id'])): ?>
                                            <button class="btn btn-outline-primary add-to-cart" 
                                                    data-product-id="<?php echo $product['product_id'] ?? $product['id'] ?? ''; ?>">
                                                <i class="fas fa-cart-plus me-2"></i>Thêm vào giỏ
                                            </button>
                                        <?php else: ?>
                                            <a href="/PHARMAGO/public/login" class="btn btn-outline-primary">
                                                <i class="fas fa-sign-in-alt me-2"></i>Đăng nhập để mua
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-5">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Chưa có sản phẩm nổi bật. Vui lòng quay lại sau!
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="text-center mt-4">
                <a href="/PHARMAGO/public/products" class="btn btn-outline-primary btn-lg">
                    Xem tất cả sản phẩm <i class="fas fa-arrow-right ms-2"></i>
                </a>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section id="about" class="about-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h2 class="section-title">Về Chúng Tôi</h2>
                    <p class="lead fw-bold text-dark">Nhà thuốc Pharmacy với hơn 15 năm kinh nghiệm trong lĩnh vực chăm sóc sức khỏe.</p>
                    <p class="text-muted">Chúng tôi cam kết cung cấp các sản phẩm dược phẩm chính hãng với chất lượng đảm bảo. Với phương châm "Vì sức khỏe cộng đồng", đội ngũ dược sĩ của chúng tôi luôn sẵn sàng tư vấn nhiệt tình, chính xác cho khách hàng.</p>
                    <div class="row mt-4">
                        <div class="col-4 text-center">
                            <div class="stats-box">
                                <div class="stats-number">10K+</div>
                                <div class="stats-label">Khách hàng</div>
                            </div>
                        </div>
                        <div class="col-4 text-center">
                            <div class="stats-box">
                                <div class="stats-number">15+</div>
                                <div class="stats-label">Năm kinh nghiệm</div>
                            </div>
                        </div>
                        <div class="col-4 text-center">
                            <div class="stats-box">
                                <div class="stats-number">100%</div>
                                <div class="stats-label">Chính hãng</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 text-center">
                    <img src="<?php echo getHeroImage('Hinh1.jpg'); ?>" alt="Về chúng tôi" class="img-fluid rounded about-image">
                </div>
            </div>
        </div>
    </section>

    <!-- Categories Section -->
    <section id="categories" class="categories-section">
        <div class="container">
            <div class="row mb-5">
                <div class="col-12 text-center">
                    <h2 class="section-title">Danh Mục Sản Phẩm</h2>
                    <p class="lead text-muted">Khám phá các danh mục thuốc đa dạng</p>
                </div>
            </div>
            <div class="row">
                <?php if(!empty($categories)): ?>
                    <?php foreach(array_slice($categories, 0, 6) as $category): ?>
                    <div class="col-lg-4 col-md-6 mb-4 animate-fade-in">
                        <div class="category-card card h-100 text-center">
                            <div class="category-icon">
                                <i class="<?php echo $category['icon_class'] ?? 'fas fa-pills'; ?> fa-3x"></i>
                            </div>
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($category['category_name'] ?? $category['name'] ?? 'Danh mục'); ?></h5>
                                <p class="card-text text-muted"><?php echo htmlspecialchars($category['category_description'] ?? $category['description'] ?? 'Danh mục sản phẩm'); ?></p>
                                <a href="/PHARMAGO/public/category-detail?id=<?php echo $category['category_id'] ?? $category['id'] ?? ''; ?>" class="btn btn-outline-primary">Xem sản phẩm</a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12 text-center">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            Đang tải danh mục sản phẩm...
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="text-center mt-4">
                <a href="/PHARMAGO/public/categories" class="btn btn-primary btn-lg">
                    Xem tất cả danh mục <i class="fas fa-arrow-right ms-2"></i>
                </a>
            </div>
        </div>
    </section>

    <!-- Services Section -->
    <section id="services" class="services-section">
        <div class="container">
            <div class="row mb-5">
                <div class="col-12 text-center">
                    <h2 class="section-title">Dịch Vụ Của Chúng Tôi</h2>
                    <p class="lead text-muted">Cung cấp giải pháp toàn diện cho sức khỏe của bạn</p>
                </div>
            </div>
            <div class="row">
                <div class="col-md-4 mb-4 animate-fade-in">
                    <div class="card service-card h-100">
                        <div class="card-body text-center">
                            <div class="icon-box">
                                <i class="fas fa-prescription-bottle-alt text-white fa-2x"></i>
                            </div>
                            <h4 class="mb-3">Bán Thuốc</h4>
                            <p class="text-muted">Cung cấp đầy đủ các loại thuốc kê đơn và không kê đơn với chất lượng đảm bảo, nguồn gốc rõ ràng từ các hãng dược phẩm uy tín.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4 animate-fade-in">
                    <div class="card service-card h-100">
                        <div class="card-body text-center">
                            <div class="icon-box">
                                <i class="fas fa-user-md text-white fa-2x"></i>
                            </div>
                            <h4 class="mb-3">Tư Vấn Dược Lý</h4>
                            <p class="text-muted">Đội ngũ dược sĩ giàu kinh nghiệm sẵn sàng tư vấn về cách sử dụng thuốc, tương tác thuốc, tác dụng phụ và chăm sóc sức khỏe.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4 animate-fade-in">
                    <div class="card service-card h-100">
                        <div class="card-body text-center">
                            <div class="icon-box">
                                <i class="fas fa-shipping-fast text-white fa-2x"></i>
                            </div>
                            <h4 class="mb-3">Giao Hàng</h4>
                            <p class="text-muted">Dịch vụ giao thuốc nhanh chóng, đảm bảo chất lượng trong vòng 2 giờ tại nội thành và 24h toàn quốc. Hỗ trợ giao thuốc khẩn cấp 24/7.</p>
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
                    <h2 class="section-title">Liên Hệ</h2>
                    <p class="lead mb-5">Liên hệ với chúng tôi để được tư vấn</p>
                    <div class="row">
                        <div class="col-md-4 mb-4">
                            <div class="p-4 rounded" style="background: var(--light-color);">
                                <i class="fas fa-map-marker-alt fa-2x mb-3" style="color: var(--primary-color);"></i>
                                <h5>Địa chỉ</h5>
                                <p class="mb-0">123 Đường 2/9, Quận Hải Châu, TP. Đà Nẵng</p>
                            </div>
                        </div>
                        <div class="col-md-4 mb-4">
                            <div class="p-4 rounded" style="background: var(--light-color);">
                                <i class="fas fa-phone fa-2x mb-3" style="color: var(--primary-color);"></i>
                                <h5>Điện thoại</h5>
                                <p class="mb-0">(028) 123456789</p>
                            </div>
                        </div>
                        <div class="col-md-4 mb-4">
                            <div class="p-4 rounded" style="background: var(--light-color);">
                                <i class="fas fa-envelope fa-2x mb-3" style="color: var(--primary-color);"></i>
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
                        <?php if(!empty($categories)): ?>
                            <?php foreach(array_slice($categories, 0, 3) as $category): ?>
                            <li class="mb-2">
                                <a href="/PHARMAGO/public/category-detail?id=<?php echo $category['category_id'] ?? $category['id'] ?? ''; ?>">
                                    <?php echo htmlspecialchars($category['category_name'] ?? $category['name'] ?? 'Danh mục'); ?>
                                </a>
                            </li>
                            <?php endforeach; ?>
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

    <!-- Scripts - SỬA ĐƯỜNG DẪN ĐÚNG -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/PHARMAGO/public/assets/js/home.js"></script>
    <script src="/PHARMAGO/public/assets/js/script.js"></script>
</body>
</html>