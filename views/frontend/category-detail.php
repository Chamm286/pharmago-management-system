<?php
// views/frontend/category-detail.php

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
    $product_model_path = $base_dir . 'models/Product.php';
    
    if (!file_exists($category_model_path)) {
        throw new Exception('Category model file not found at: ' . $category_model_path);
    }
    require_once $category_model_path;
    
    if (!file_exists($product_model_path)) {
        throw new Exception('Product model file not found at: ' . $product_model_path);
    }
    require_once $product_model_path;

    // Khởi tạo database và models
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception('Cannot connect to database. Check database configuration.');
    }

    $categoryModel = new Category($db);
    $productModel = new Product($db);

    // Lấy category_id từ URL parameter
    $category_id = isset($_GET['id']) ? intval($_GET['id']) : 1;

    // Lấy thông tin category
    $category = $categoryModel->getCategoryById($category_id);
    if (!$category) {
        header("HTTP/1.0 404 Not Found");
        include $base_dir . 'views/errors/404.php';
        exit;
    }

    // Lấy sản phẩm theo danh mục - SỬA LẠI: Sử dụng phương thức đúng
    $products_result = $productModel->readByCategory($category_id);
    
    // Chuyển PDOStatement thành array
    if ($products_result instanceof PDOStatement) {
        $products = $products_result->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $products = is_array($products_result) ? $products_result : [];
    }

    // Lấy sản phẩm bán chạy - SỬA LẠI: Sử dụng phương thức đúng
    $topProducts_result = $productModel->getBestSellerProducts(3);
    
    if ($topProducts_result instanceof PDOStatement) {
        $topProducts = $topProducts_result->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $topProducts = is_array($topProducts_result) ? $topProducts_result : [];
    }

    // Lấy tất cả categories cho footer
    $allCategories_result = $categoryModel->getAllCategories();
    if ($allCategories_result instanceof PDOStatement) {
        $allCategories = $allCategories_result->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $allCategories = is_array($allCategories_result) ? $allCategories_result : [];
    }

} catch (Exception $e) {
    error_log("Category detail page error: " . $e->getMessage());
    $category = ['category_name' => 'Danh mục', 'category_description' => 'Mô tả danh mục'];
    $products = [];
    $topProducts = [];
    $allCategories = [];
    $error_message = "Hệ thống đang bảo trì. Vui lòng quay lại sau.";
    $debug_error = $e->getMessage();
}

// Hàm helper để lấy ảnh sản phẩm
function getProductImage($product) {
    $default_image = 'https://images.unsplash.com/photo-1584308666744-24d5c474f2ae?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=80';
    
    // Kiểm tra các key có thể có
    if (isset($product['image_url']) && !empty($product['image_url'])) {
        return $product['image_url'];
    } elseif (isset($product['image']) && !empty($product['image'])) {
        return $product['image'];
    } elseif (isset($product['product_image']) && !empty($product['product_image'])) {
        return $product['product_image'];
    }
    
    return $default_image;
}

// Hàm helper để lấy ảnh danh mục
function getCategoryImage($category) {
    $default_image = 'https://images.unsplash.com/photo-1559757148-5c350d0d3c56?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1000&q=80';
    
    if (isset($category['image_url']) && !empty($category['image_url'])) {
        return $category['image_url'];
    } elseif (isset($category['image']) && !empty($category['image'])) {
        return $category['image'];
    }
    
    return $default_image;
}

// Hàm format giá
function formatPrice($price) {
    return number_format($price, 0, ',', '.') . 'đ';
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($category['category_name'] ?? 'Danh mục'); ?> - Pharmacy</title>
    <link rel="icon" type="image/x-icon" href="/PHARMAGO/public/assets/images/favicon.ico">
    
    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link rel="stylesheet" href="/PHARMAGO/public/assets/css/category-detail.css">
    <link rel="stylesheet" href="/PHARMAGO/public/assets/css/style.css">
</head>
<body>
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

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container text-center">
            <h1 class="display-4 fw-bold mb-4 animate__animated animate__fadeInDown"><?php echo htmlspecialchars($category['category_name']); ?></h1>
            <p class="lead mb-5 animate__animated animate__fadeInUp"><?php echo htmlspecialchars($category['category_description']); ?></p>
        </div>
    </section>

    <!-- Main Content -->
    <section class="py-5">
        <div class="container">
            <!-- Category Info -->
            <div class="category-info mb-5">
                <div class="row align-items-center">
                    <div class="col-lg-8">
                        <h2><?php echo htmlspecialchars($category['category_name']); ?></h2>
                        <p class="text-muted"><?php echo htmlspecialchars($category['category_description']); ?></p>
                        <p class="text-warning fw-bold"><i class="fas fa-exclamation-triangle me-2"></i>Lưu ý: <?php echo htmlspecialchars($category['category_name']); ?> chỉ sử dụng khi có chỉ định của bác sĩ. Không tự ý mua và sử dụng để tránh tình trạng kháng thuốc.</p>
                    </div>
                    <div class="col-lg-4 text-center">
                        <img src="<?php echo getCategoryImage($category); ?>" class="img-fluid rounded shadow" alt="<?php echo htmlspecialchars($category['category_name']); ?>">
                    </div>
                </div>
            </div>

            <!-- Product Grid -->
            <div class="row mb-5">
                <div class="col-12">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h3 class="mb-0">Sản phẩm trong danh mục</h3>
                        <span class="badge bg-primary fs-6"><?php echo count($products); ?> sản phẩm</span>
                    </div>
                </div>
            </div>

            <div class="row" id="productContainer">
                <?php if(!empty($products)): ?>
                    <?php foreach($products as $product): ?>
                    <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                        <div class="card product-card h-100">
                            <div class="product-img-container position-relative">
                                <img src="<?php echo getProductImage($product); ?>" class="product-img" alt="<?php echo htmlspecialchars($product['product_name'] ?? $product['name'] ?? 'Sản phẩm'); ?>">
                                <?php if($product['is_best_seller'] ?? false): ?>
                                    <span class="product-badge">Bán chạy</span>
                                <?php elseif($product['is_new'] ?? false): ?>
                                    <span class="product-badge">Mới</span>
                                <?php elseif($product['is_on_sale'] ?? false): ?>
                                    <span class="product-badge">Giảm giá</span>
                                <?php endif; ?>
                            </div>
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title"><?php echo htmlspecialchars($product['product_name'] ?? $product['name'] ?? 'Sản phẩm'); ?></h5>
                                <p class="card-text text-muted flex-grow-1"><?php echo htmlspecialchars($product['short_description'] ?? $product['description'] ?? 'Sản phẩm chất lượng cao'); ?></p>
                                <div class="product-rating mb-2">
                                    <?php
                                    $rating = $product['average_rating'] ?? 4.5;
                                    for($i = 1; $i <= 5; $i++): 
                                        if($i <= floor($rating)): ?>
                                            <i class="fas fa-star text-warning"></i>
                                        <?php elseif($i == ceil($rating) && $rating != floor($rating)): ?>
                                            <i class="fas fa-star-half-alt text-warning"></i>
                                        <?php else: ?>
                                            <i class="far fa-star text-muted"></i>
                                        <?php endif;
                                    endfor; ?>
                                    <span class="ms-1 text-muted">(<?php echo $product['review_count'] ?? 0; ?>)</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div>
                                        <span class="product-price fw-bold"><?php echo formatPrice($product['price'] ?? 0); ?></span>
                                        <?php if(isset($product['original_price']) && $product['original_price'] > $product['price']): ?>
                                            <span class="product-old-price ms-2 text-muted text-decoration-line-through">
                                                <?php echo formatPrice($product['original_price']); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="d-grid gap-2 mt-auto">
                                    <a href="/PHARMAGO/public/product-detail?id=<?php echo $product['product_id'] ?? $product['id'] ?? ''; ?>" 
                                       class="btn btn-primary">
                                        <i class="fas fa-eye me-2"></i>Xem chi tiết
                                    </a>
                                    <?php if(isset($_SESSION['user_id'])): ?>
                                        <button class="btn btn-outline-primary add-to-cart" 
                                                data-product-id="<?php echo $product['product_id'] ?? $product['id'] ?? ''; ?>"
                                                data-product-name="<?php echo htmlspecialchars($product['product_name'] ?? $product['name'] ?? 'Sản phẩm'); ?>"
                                                data-product-price="<?php echo $product['price'] ?? 0; ?>">
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
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12 text-center">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            Không có sản phẩm nào trong danh mục này.
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Stats Section - Chỉ hiển thị nếu có top products -->
            <?php if(!empty($topProducts)): ?>
            <section class="stats-section py-5">
                <div class="container">
                    <h2 class="text-center mb-5">Sản phẩm bán chạy <i class="fas fa-chart-line ms-2"></i></h2>
                    <div class="row justify-content-center">
                        <?php foreach($topProducts as $index => $topProduct): ?>
                        <div class="col-md-4 mb-4">
                            <div class="stat-item text-center p-4 rounded-3 shadow-sm">
                                <div class="stat-icon mb-3">
                                    <i class="fas fa-capsules fa-3x"></i>
                                </div>
                                <div class="stat-number fw-bold fs-3">
                                    <?php echo number_format($topProduct['sold_count'] ?? rand(800, 1500), 0, ',', '.'); ?>
                                </div>
                                <h3 class="stat-title h5 mt-2"><?php echo htmlspecialchars($topProduct['product_name'] ?? $topProduct['name'] ?? 'Sản phẩm'); ?></h3>
                                <p class="mb-0">Hộp bán ra tháng này</p>
                                <div class="progress mt-3" style="height: 8px;">
                                    <?php $progress = min((($topProduct['sold_count'] ?? rand(800, 1500)) / 1500) * 100, 100); ?>
                                    <div class="progress-bar" role="progressbar" 
                                         style="width: <?php echo $progress; ?>%;" 
                                         aria-valuenow="<?php echo $progress; ?>" 
                                         aria-valuemin="0" aria-valuemax="100">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>
            <?php endif; ?>

            <!-- Review Section -->
            <section class="review-section py-5">
                <div class="container">
                    <div class="text-center mb-5">
                        <h2>Đánh giá của khách hàng</h2>
                        <p class="lead text-muted">Hơn 1,000+ đánh giá tích cực từ khách hàng</p>
                    </div>
                    
                    <div class="row g-4">
                        <?php
                        // Reviews mẫu
                        $reviews = [
                            ['rating' => 5, 'content' => 'Thuốc chất lượng tốt, hiệu quả nhanh. Nhân viên tư vấn nhiệt tình, giao hàng đúng hẹn. Sẽ ủng hộ dài lâu!', 'name' => 'Nguyễn Thị A', 'type' => 'Khách hàng thân thiết', 'avatar' => 'https://randomuser.me/api/portraits/women/32.jpg', 'date' => '2 ngày trước'],
                            ['rating' => 4.5, 'content' => 'Giá cả hợp lý hơn so với hiệu thuốc gần nhà. Thuốc đúng hãng, còn hạn dài. Giao hàng nhanh, đóng gói cẩn thận.', 'name' => 'Trần Văn B', 'type' => 'Khách hàng mới', 'avatar' => 'https://randomuser.me/api/portraits/men/45.jpg', 'date' => '1 tuần trước'],
                            ['rating' => 4, 'content' => 'Dược sĩ tư vấn rất kỹ về cách dùng thuốc. Thuốc hiệu quả sau 3 ngày sử dụng. Chỉ mong có thêm nhiều chương trình khuyến mãi.', 'name' => 'Lê Thị C', 'type' => 'Khách hàng thường xuyên', 'avatar' => 'https://randomuser.me/api/portraits/women/68.jpg', 'date' => '3 tuần trước']
                        ];
                        ?>
                        
                        <?php foreach($reviews as $review): ?>
                        <div class="col-md-4">
                            <div class="review-card p-4 rounded-3 h-100">
                                <div class="review-header d-flex justify-content-between mb-3">
                                    <div class="review-rating text-warning">
                                        <?php for($i = 1; $i <= 5; $i++): ?>
                                            <?php if($i <= floor($review['rating'])): ?>
                                                <i class="fas fa-star"></i>
                                            <?php elseif($i == ceil($review['rating']) && $review['rating'] != floor($review['rating'])): ?>
                                                <i class="fas fa-star-half-alt"></i>
                                            <?php else: ?>
                                                <i class="far fa-star"></i>
                                            <?php endif; ?>
                                        <?php endfor; ?>
                                    </div>
                                    <small class="text-muted"><?php echo $review['date']; ?></small>
                                </div>
                                <p class="review-text mb-4">"<?php echo htmlspecialchars($review['content']); ?>"</p>
                                <div class="review-author d-flex align-items-center">
                                    <img src="<?php echo $review['avatar']; ?>" class="rounded-circle me-3" width="50" height="50" alt="Customer">
                                    <div>
                                        <h6 class="mb-0"><?php echo htmlspecialchars($review['name']); ?></h6>
                                        <small class="text-muted"><?php echo $review['type']; ?></small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>

            <!-- FAQ Section -->
            <section class="faq-section py-5">
                <div class="container">
                    <h2 class="text-center mb-5">Câu hỏi thường gặp</h2>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="faq-item mb-4">
                                <h5><i class="fas fa-question-circle text-success me-2"></i> Làm sao để đặt hàng?</h5>
                                <p class="text-muted">Bạn có thể đặt hàng trực tiếp trên website bằng cách thêm sản phẩm vào giỏ hàng và tiến hành thanh toán. Hoặc gọi điện đến hotline 1800.xxxx để được hỗ trợ đặt hàng.</p>
                            </div>
                            <div class="faq-item mb-4">
                                <h5><i class="fas fa-question-circle text-success me-2"></i> Thời gian giao hàng bao lâu?</h5>
                                <p class="text-muted">Đối với khu vực nội thành: giao trong ngày nếu đặt trước 17h. Ngoại thành: 1-2 ngày làm việc. Các tỉnh thành khác: 2-5 ngày tùy địa chỉ.</p>
                            </div>
                            <div class="faq-item mb-4">
                                <h5><i class="fas fa-question-circle text-success me-2"></i> Có được kiểm tra hàng trước khi nhận không?</h5>
                                <p class="text-muted">Bạn hoàn toàn có quyền kiểm tra hàng hóa trước khi thanh toán. Chỉ thanh toán khi hài lòng với sản phẩm.</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="faq-item mb-4">
                                <h5><i class="fas fa-question-circle text-success me-2"></i> Chính sách đổi trả như thế nào?</h5>
                                <p class="text-muted">Chúng tôi chấp nhận đổi trả trong vòng 7 ngày nếu sản phẩm còn nguyên seal, hộp, chưa qua sử dụng và có hóa đơn mua hàng.</p>
                            </div>
                            <div class="faq-item mb-4">
                                <h5><i class="fas fa-question-circle text-success me-2"></i> Có cần đơn thuốc để mua thuốc kê đơn?</h5>
                                <p class="text-muted">Đối với thuốc kê đơn, bạn cần cung cấp đơn thuốc từ bác sĩ. Chúng tôi sẽ kiểm tra đơn thuốc trước khi bán để đảm bảo an toàn cho bạn.</p>
                            </div>
                            <div class="faq-item mb-4">
                                <h5><i class="fas fa-question-circle text-success me-2"></i> Có tư vấn sử dụng thuốc không?</h5>
                                <p class="text-muted">Đội ngũ dược sĩ của chúng tôi luôn sẵn sàng tư vấn cách sử dụng thuốc an toàn, hiệu quả qua hotline hoặc chat trực tuyến.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
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
                    <h5>Danh mục</h5>
                    <ul class="list-unstyled">
                        <?php if(!empty($allCategories)): ?>
                            <?php foreach(array_slice($allCategories, 0, 4) as $cat): ?>
                            <li class="mb-2">
                                <a href="/PHARMAGO/public/category-detail?id=<?php echo $cat['category_id'] ?? $cat['id'] ?? ''; ?>">
                                    <?php echo htmlspecialchars($cat['category_name'] ?? $cat['name'] ?? 'Danh mục'); ?>
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
    <script src="/PHARMAGO/public/assets/js/category-detail.js"></script>
</body>
</html>