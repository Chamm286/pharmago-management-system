<?php
// views/frontend/products.php

// Bật hiển thị lỗi để debug
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start session nếu chưa có
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    // Kết nối database
    $base_dir = dirname(__DIR__, 2) . '/';
    
    $config_path = $base_dir . 'config/database.php';
    $product_model_path = $base_dir . 'models/Product.php';
    $category_model_path = $base_dir . 'models/Category.php';
    
    // Kiểm tra và require các file
    if (!file_exists($config_path)) {
        throw new Exception('Database config file not found');
    }
    require_once $config_path;
    
    if (!file_exists($product_model_path)) {
        throw new Exception('Product model file not found');
    }
    require_once $product_model_path;
    
    if (!file_exists($category_model_path)) {
        throw new Exception('Category model file not found');
    }
    require_once $category_model_path;

    // Khởi tạo database và models
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception('Cannot connect to database');
    }

    $productModel = new Product($db);
    $categoryModel = new Category($db);

    // Lấy tham số từ URL
    $category_id = $_GET['category'] ?? null;
    $search = $_GET['search'] ?? '';

    // Lấy dữ liệu từ database
    try {
        // Lấy tất cả danh mục
        $categories_stmt = $categoryModel->read();
        $categories = $categories_stmt->fetchAll(PDO::FETCH_ASSOC);

        // Lấy sản phẩm theo filter
        if (!empty($category_id)) {
            // Lấy sản phẩm theo danh mục
            $all_products_stmt = $productModel->readByCategory($category_id);
        } elseif (!empty($search)) {
            // Tìm kiếm sản phẩm
            $all_products_stmt = $productModel->search($search);
        } else {
            // Lấy tất cả sản phẩm
            $all_products_stmt = $productModel->read();
        }
        $all_products = $all_products_stmt->fetchAll(PDO::FETCH_ASSOC);

        // Lấy sản phẩm mới (sử dụng logic lọc từ tất cả sản phẩm)
        $new_products = array_filter($all_products, function($product) {
            return $product['is_new'] == 1;
        });

        // Lấy sản phẩm giảm giá
        $sale_products = array_filter($all_products, function($product) {
            return $product['is_on_sale'] == 1;
        });

        // Lấy sản phẩm bán chạy
        $best_seller_products = array_filter($all_products, function($product) {
            return $product['is_best_seller'] == 1;
        });

        // Lấy tên danh mục nếu có filter
        $current_category = null;
        if ($category_id && !empty($categories)) {
            foreach ($categories as $cat) {
                if ($cat['category_id'] == $category_id) {
                    $current_category = $cat;
                    break;
                }
            }
        }

    } catch (Exception $e) {
        error_log("Error getting products data: " . $e->getMessage());
        $all_products = [];
        $new_products = [];
        $sale_products = [];
        $best_seller_products = [];
        $categories = [];
        $error_message = "Hệ thống đang bảo trì. Vui lòng quay lại sau.";
    }

} catch (Exception $e) {
    error_log("Products page error: " . $e->getMessage());
    $all_products = [];
    $new_products = [];
    $sale_products = [];
    $best_seller_products = [];
    $categories = [];
    $error_message = "Hệ thống đang bảo trì. Vui lòng quay lại sau.";
}

// Hàm helper để lấy ảnh sản phẩm
function getProductImage($product) {
    $default_image = '/PHARMAGO/public/assets/images/default-product.jpg';
    
    if (isset($product['image_url']) && !empty($product['image_url'])) {
        $image_path = $_SERVER['DOCUMENT_ROOT'] . '/PHARMAGO/public/assets/' . $product['image_url'];
        if (file_exists($image_path)) {
            return '/PHARMAGO/public/assets/' . $product['image_url'];
        }
    }
    
    return $default_image;
}

// Hàm helper để format giá
function formatPrice($price) {
    return number_format($price, 0, ',', '.') . 'đ';
}

// Hàm helper để tạo rating stars
function getRatingStars($rating) {
    $stars = '';
    for ($i = 1; $i <= 5; $i++) {
        $stars .= '<i class="fas fa-star ' . ($i <= $rating ? 'text-warning' : 'text-muted') . '"></i>';
    }
    return $stars;
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nhà Thuốc Pharmacy - Danh Mục Sản Phẩm Thuốc & Dược Phẩm</title>
    <meta name="description" content="Khám phá danh mục thuốc đa dạng tại Pharmacy - Thuốc kháng sinh, giảm đau, vitamin, thực phẩm chức năng chính hãng, giá tốt">
    <link rel="icon" type="image/x-icon" href="/PHARMAGO/public/assets/images/favicon.ico">
    
    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    
    <!-- CSS riêng -->
    <link rel="stylesheet" href="/PHARMAGO/public/assets/css/products.css">
    
</head>
<body>
    <div class="main-wrapper">
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
                            <a class="nav-link active" href="/PHARMAGO/public/products">
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

        <!-- Quick Navigation -->
        <div class="quick-nav">
            <a href="#top" title="Lên đầu trang"><i class="fas fa-arrow-up"></i></a>
            <a href="/PHARMAGO/public/products" title="Sản phẩm"><i class="fas fa-pills"></i></a>
            <a href="/PHARMAGO/public/categories" title="Danh mục"><i class="fas fa-list"></i></a>
            <a href="#contact" title="Liên hệ"><i class="fas fa-phone"></i></a>
        </div>

        <!-- Scroll to Top Button -->
        <button class="scroll-top" id="scrollTop">
            <i class="fas fa-chevron-up"></i>
        </button>

        <!-- Breadcrumb -->
        <section class="breadcrumb-section py-3">
            <div class="container">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="/PHARMAGO/public/"><i class="fas fa-home me-1"></i>Trang chủ</a></li>
                        <li class="breadcrumb-item active"><i class="fas fa-pills me-1"></i>Sản phẩm</li>
                        <?php if(isset($current_category)): ?>
                            <li class="breadcrumb-item active"><?php echo htmlspecialchars($current_category['category_name']); ?></li>
                        <?php endif; ?>
                    </ol>
                </nav>
            </div>
        </section>

        <!-- Product Section -->
        <section class="products-section py-5">
            <div class="container">
                <!-- Header Section -->
                <div class="row mb-5">
                    <div class="col-12 text-center">
                        <h1 class="display-5 fw-bold text-primary mb-3">
                            <?php if(isset($current_category)): ?>
                                <i class="<?php echo $current_category['icon_class'] ?? 'fas fa-pills'; ?> me-3"></i>
                                <?php echo htmlspecialchars($current_category['category_name']); ?>
                            <?php else: ?>
                                <i class="fas fa-boxes me-3"></i>
                                Tất Cả Sản Phẩm
                            <?php endif; ?>
                        </h1>
                        <p class="lead text-muted">
                            <?php if(isset($current_category)): ?>
                                <?php echo htmlspecialchars($current_category['category_description'] ?? 'Khám phá các sản phẩm thuốc chất lượng cao'); ?>
                            <?php else: ?>
                                Khám phá danh mục thuốc đa dạng - Chất lượng đảm bảo - Giá cả hợp lý
                            <?php endif; ?>
                        </p>
                    </div>
                </div>

                <div class="row">
                    <!-- Sidebar with categories - CỐ ĐỊNH -->
<div class="col-lg-3 mb-4">
    <div class="sidebar">
        <!-- Search Box -->
        <div class="search-box mb-4">
            <form action="/PHARMAGO/public/products" method="GET" id="searchForm">
                <div class="input-group">
                    <input type="text" class="form-control" name="search" placeholder="Tìm kiếm thuốc..." 
                           value="<?php echo htmlspecialchars($search ?? ''); ?>" id="searchInput">
                    <button class="btn btn-primary" type="submit">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </form>
        </div>

        <!-- Active Filters -->
        <div class="active-filters mb-3" style="display: none;"></div>

        <h5 class="sidebar-title"><i class="fas fa-list me-2"></i>Danh Mục Thuốc</h5>
        <ul class="category-list">
            <li>
                <a href="/PHARMAGO/public/products" class="<?php echo !isset($category_id) && empty($search) ? 'active' : ''; ?>">
                    <i class="fas fa-boxes me-2"></i>Tất cả sản phẩm
                    <span class="badge bg-primary float-end product-count"><?php echo count($all_products); ?></span>
                </a>
            </li>
            <?php if(isset($categories) && !empty($categories)): ?>
                <?php foreach($categories as $category): ?>
                <li>
                    <a href="/PHARMAGO/public/products?category=<?php echo $category['category_id']; ?>" 
                       class="<?php echo (isset($category_id) && $category_id == $category['category_id']) ? 'active' : ''; ?>">
                        <i class="<?php echo $category['icon_class'] ?? 'fas fa-pills'; ?> me-2"></i>
                        <?php echo htmlspecialchars($category['category_name']); ?>
                        <span class="badge bg-light text-dark float-end">
                            <?php 
                                // Đếm số sản phẩm trong danh mục
                                $count = 0;
                                foreach($all_products as $product) {
                                    if ($product['category_id'] == $category['category_id']) {
                                        $count++;
                                    }
                                }
                                echo $count;
                            ?>
                        </span>
                    </a>
                </li>
                <?php endforeach; ?>
            <?php else: ?>
                <li><a href="#"><i class="fas fa-pills me-2"></i>Đang tải danh mục...</a></li>
            <?php endif; ?>
        </ul>

        <!-- Sort Options -->
        <div class="filter-section">
            <h5 class="sidebar-title"><i class="fas fa-sort-amount-down me-2"></i>Sắp xếp</h5>
            <select id="sortOptions" class="form-select">
                <option value="name_asc">Tên A-Z</option>
                <option value="name_desc">Tên Z-A</option>
                <option value="price_asc">Giá thấp đến cao</option>
                <option value="price_desc">Giá cao đến thấp</option>
                <option value="newest">Mới nhất</option>
                <option value="bestseller">Bán chạy nhất</option>
            </select>
        </div>

        <!-- Price Filter -->
        <div class="filter-section">
            <h5 class="sidebar-title"><i class="fas fa-filter me-2"></i>Lọc Theo Giá</h5>
            <div class="range-slider mt-3">
                <input type="range" class="form-range" min="0" max="500000" step="10000" id="priceRange" value="500000">
                <div class="price-display d-flex justify-content-between mt-2">
                    <small>0đ</small>
                    <small class="price-value">500.000đ</small>
                </div>
            </div>
        </div>

        <!-- Manufacturer Filter -->
        <div class="filter-section">
            <h5 class="sidebar-title"><i class="fas fa-industry me-2"></i>Nhà Sản Xuất</h5>
            <div class="manufacturer-filters">
                <?php
                // Lấy danh sách nhà sản xuất từ sản phẩm
                $manufacturers = [];
                foreach($all_products as $product) {
                    if (!empty($product['manufacturer_name'])) {
                        $manufacturers[$product['manufacturer_name']] = true;
                    }
                }
                $manufacturer_list = array_keys($manufacturers);
                sort($manufacturer_list);
                
                // Hiển thị tối đa 8 nhà sản xuất
                $display_manufacturers = array_slice($manufacturer_list, 0, 8);
                $i = 1;
                foreach($display_manufacturers as $manufacturer): 
                ?>
                <div class="form-check mt-2">
                    <input class="form-check-input manufacturer-checkbox" type="checkbox" 
                           id="manufacturer<?php echo $i; ?>" value="<?php echo htmlspecialchars($manufacturer); ?>">
                    <label class="form-check-label" for="manufacturer<?php echo $i; ?>">
                        <?php echo htmlspecialchars($manufacturer); ?>
                    </label>
                </div>
                <?php $i++; endforeach; ?>
                
                <?php if(count($manufacturer_list) > 8): ?>
                <div class="more-manufacturers" style="display: none;">
                    <?php for($j = 8; $j < count($manufacturer_list) && $j < 15; $j++): ?>
                    <div class="form-check mt-2">
                        <input class="form-check-input manufacturer-checkbox" type="checkbox" 
                               id="manufacturer<?php echo $j + 1; ?>" value="<?php echo htmlspecialchars($manufacturer_list[$j]); ?>">
                        <label class="form-check-label" for="manufacturer<?php echo $j + 1; ?>">
                            <?php echo htmlspecialchars($manufacturer_list[$j]); ?>
                        </label>
                    </div>
                    <?php endfor; ?>
                </div>
                <button type="button" class="btn btn-sm btn-outline-secondary mt-2 w-100" id="showMoreManufacturers">
                    <i class="fas fa-chevron-down me-1"></i>Xem thêm
                </button>
                <?php endif; ?>
            </div>
        </div>

        <!-- Stock Status Filter -->
        <div class="filter-section">
            <h5 class="sidebar-title"><i class="fas fa-box me-2"></i>Tình Trạng Kho</h5>
            <div class="form-check mt-2">
                <input class="form-check-input" type="checkbox" id="inStockOnly">
                <label class="form-check-label" for="inStockOnly">
                    Chỉ hiện sản phẩm còn hàng
                </label>
            </div>
        </div>

        <!-- Special Offers -->
        <div class="special-offers mt-4 p-3 rounded">
            <h6 class="fw-bold"><i class="fas fa-gift me-2"></i>Ưu Đãi Đặc Biệt</h6>
            <p class="small mb-2">Giảm đến 20% cho đơn hàng đầu tiên</p>
            <p class="small mb-0">Miễn phí vận chuyển đơn từ 300K</p>
        </div>

        <!-- Clear Filters Button -->
        <button class="btn btn-outline-primary w-100 mt-3" id="clearFilters">
            <i class="fas fa-times me-2"></i>Xóa Bộ Lọc
        </button>

        <!-- Health Tips -->
        <div class="health-tips mt-4 p-3 rounded">
            <h6 class="fw-bold text-dark"><i class="fas fa-heartbeat me-2"></i>Lời Khuyên Sức Khỏe</h6>
            <p class="small text-muted mb-0">"Sử dụng thuốc theo chỉ định của bác sĩ. Đọc kỹ hướng dẫn trước khi dùng."</p>
        </div>
    </div>
</div>
                    <!-- Main content - SCROLL ĐỘC LẬP -->
                    <div class="col-lg-9">
                        <div class="main-content">
                            <div class="content-scrollable">
                                <!-- Stats & Sort Section -->
                                <div class="d-flex justify-content-between align-items-center mb-4 p-3 rounded" style="background: #f8f9fa;">
                                    <div>
                                        <span class="text-muted">
                                            Hiển thị <strong><?php echo count($all_products); ?></strong> sản phẩm
                                            <?php if(isset($current_category)): ?>
                                                trong <strong><?php echo htmlspecialchars($current_category['category_name']); ?></strong>
                                            <?php endif; ?>
                                            <?php if(!empty($search)): ?>
                                                cho từ khóa "<strong><?php echo htmlspecialchars($search); ?></strong>"
                                            <?php endif; ?>
                                        </span>
                                    </div>
                                </div>

                                <!-- Product tabs -->
                                <ul class="nav nav-tabs mb-4" id="productTabs" role="tablist">
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link active" id="all-tab" data-bs-toggle="tab" data-bs-target="#all"
                                            type="button">
                                            <i class="fas fa-boxes me-1"></i>Tất Cả
                                            <span class="badge bg-secondary ms-1"><?php echo count($all_products); ?></span>
                                        </button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="new-tab" data-bs-toggle="tab" data-bs-target="#new"
                                            type="button">
                                            <i class="fas fa-star me-1"></i>Mới Về
                                            <span class="badge bg-success ms-1"><?php echo count($new_products); ?></span>
                                        </button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="sale-tab" data-bs-toggle="tab" data-bs-target="#sale"
                                            type="button">
                                            <i class="fas fa-tag me-1"></i>Đang Giảm Giá
                                            <span class="badge bg-danger ms-1"><?php echo count($sale_products); ?></span>
                                        </button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="best-tab" data-bs-toggle="tab" data-bs-target="#best"
                                            type="button">
                                            <i class="fas fa-fire me-1"></i>Bán Chạy
                                            <span class="badge bg-warning ms-1"><?php echo count($best_seller_products); ?></span>
                                        </button>
                                    </li>
                                </ul>

                                <div class="tab-content" id="productTabsContent">
                                    <!-- Tab Tất cả sản phẩm -->
                                    <div class="tab-pane fade show active" id="all" role="tabpanel">
                                        <?php if(isset($error_message)): ?>
                                            <div class="alert alert-warning text-center">
                                                <i class="fas fa-exclamation-triangle me-2"></i>
                                                <?php echo $error_message; ?>
                                            </div>
                                        <?php elseif(!empty($all_products)): ?>
                                            <div class="row">
                                                <?php foreach($all_products as $product): ?>
                                                <div class="col-lg-4 col-md-6 mb-4 animate-fade-in">
                                                    <div class="card product-card h-100">
                                                        <div class="product-img-container">
                                                            <img src="<?php echo getProductImage($product); ?>" 
                                                                 class="product-img" 
                                                                 alt="<?php echo htmlspecialchars($product['product_name']); ?>"
                                                                 loading="lazy">
                                                            <div class="product-badges">
                                                                <?php if($product['is_on_sale']): ?>
                                                                    <span class="product-badge sale">-<?php echo $product['discount_percent']; ?>%</span>
                                                                <?php endif; ?>
                                                                <?php if($product['is_new']): ?>
                                                                    <span class="product-badge new">Mới</span>
                                                                <?php endif; ?>
                                                                <?php if($product['is_best_seller']): ?>
                                                                    <span class="product-badge best">Bán chạy</span>
                                                                <?php endif; ?>
                                                                <?php if($product['prescription_required']): ?>
                                                                    <span class="product-badge prescription">Kê đơn</span>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                        <div class="card-body d-flex flex-column">
                                                            <div class="product-category mb-2">
                                                                <small class="text-muted">
                                                                    <i class="fas fa-tag me-1"></i>
                                                                    <?php echo htmlspecialchars($product['category_name'] ?? 'Thuốc'); ?>
                                                                </small>
                                                            </div>
                                                            <h5 class="card-title product-title"><?php echo htmlspecialchars($product['product_name']); ?></h5>
                                                            <p class="card-text text-muted flex-grow-1 product-description">
                                                                <?php echo htmlspecialchars($product['short_description'] ?? $product['product_description'] ?? 'Sản phẩm chất lượng cao'); ?>
                                                            </p>
                                                            
                                                            <div class="product-manufacturer mb-2">
                                                                <small class="text-primary">
                                                                    <i class="fas fa-industry me-1"></i>
                                                                    <?php echo htmlspecialchars($product['manufacturer_name'] ?? 'Nhà sản xuất'); ?>
                                                                </small>
                                                            </div>

                                                            <div class="product-rating mb-2">
                                                                <?php echo getRatingStars($product['average_rating'] ?? 0); ?>
                                                                <span class="ms-1 text-muted small">(<?php echo $product['review_count'] ?? 0; ?> đánh giá)</span>
                                                            </div>

                                                            <div class="product-sales mb-2">
                                                                <small class="text-muted">
                                                                    <i class="fas fa-shopping-cart me-1"></i>
                                                                    Đã bán: <?php echo $product['sold_count'] ?? 0; ?>
                                                                </small>
                                                            </div>

                                                            <div class="mt-auto">
                                                                <div class="d-flex justify-content-between align-items-center mb-3">
                                                                    <div>
                                                                        <span class="product-price fw-bold text-primary">
                                                                            <?php echo formatPrice($product['price']); ?>
                                                                        </span>
                                                                        <?php if(isset($product['original_price']) && $product['original_price'] > $product['price']): ?>
                                                                            <span class="product-old-price text-muted text-decoration-line-through ms-2">
                                                                                <?php echo formatPrice($product['original_price']); ?>
                                                                            </span>
                                                                        <?php endif; ?>
                                                                    </div>
                                                                    <?php if($product['stock_quantity'] > 0): ?>
                                                                        <span class="badge bg-success">Còn hàng</span>
                                                                    <?php else: ?>
                                                                        <span class="badge bg-danger">Hết hàng</span>
                                                                    <?php endif; ?>
                                                                </div>
                                                                <div class="d-grid gap-2">
                                                                    <a href="/PHARMAGO/public/product/<?php echo $product['product_id']; ?>" class="btn btn-primary btn-detail">
                                                                        <i class="fas fa-info-circle me-2"></i>Chi tiết
                                                                    </a>
                                                                    <?php if(isset($_SESSION['user_id'])): ?>
                                                                        <button class="btn btn-outline-primary add-to-cart" 
                                                                                data-product-id="<?php echo $product['product_id']; ?>"
                                                                                <?php echo ($product['stock_quantity'] <= 0) ? 'disabled' : ''; ?>>
                                                                            <i class="fas fa-cart-plus me-2"></i>
                                                                            <?php echo ($product['stock_quantity'] > 0) ? 'Thêm vào giỏ' : 'Hết hàng'; ?>
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
                                                    <?php if(!empty($search)): ?>
                                                        Không tìm thấy sản phẩm nào phù hợp với từ khóa "<?php echo htmlspecialchars($search); ?>"
                                                    <?php else: ?>
                                                        Không có sản phẩm nào trong danh mục này.
                                                    <?php endif; ?>
                                                </div>
                                                <a href="/PHARMAGO/public/products" class="btn btn-primary mt-3">
                                                    <i class="fas fa-redo me-2"></i>Xem tất cả sản phẩm
                                                </a>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Tab Sản phẩm mới -->
                                    <div class="tab-pane fade" id="new" role="tabpanel">
                                        <div class="row">
                                            <?php if(!empty($new_products)): ?>
                                                <?php foreach($new_products as $product): ?>
                                                <div class="col-lg-4 col-md-6 mb-4">
                                                    <div class="card product-card h-100">
                                                        <div class="product-img-container">
                                                            <img src="<?php echo getProductImage($product); ?>" 
                                                                 class="product-img" 
                                                                 alt="<?php echo htmlspecialchars($product['product_name']); ?>">
                                                            <span class="product-badge new">Mới</span>
                                                        </div>
                                                        <div class="card-body d-flex flex-column">
                                                            <h5 class="card-title"><?php echo htmlspecialchars($product['product_name']); ?></h5>
                                                            <p class="card-text text-muted flex-grow-1">
                                                                <?php echo htmlspecialchars($product['short_description'] ?? 'Sản phẩm mới'); ?>
                                                            </p>
                                                            <div class="mt-auto">
                                                                <div class="d-flex justify-content-between align-items-center mb-3">
                                                                    <span class="product-price fw-bold">
                                                                        <?php echo formatPrice($product['price']); ?>
                                                                    </span>
                                                                </div>
                                                                <div class="d-grid gap-2">
                                                                    <a href="/PHARMAGO/public/product/<?php echo $product['product_id']; ?>" class="btn btn-primary">
                                                                        <i class="fas fa-eye me-2"></i>Xem chi tiết
                                                                    </a>
                                                                    <?php if(isset($_SESSION['user_id'])): ?>
                                                                        <button class="btn btn-outline-primary add-to-cart" 
                                                                                data-product-id="<?php echo $product['product_id']; ?>">
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
                                            <?php else: ?>
                                                <div class="col-12 text-center py-5">
                                                    <div class="alert alert-info">
                                                        <i class="fas fa-info-circle me-2"></i>
                                                        Không có sản phẩm mới.
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <!-- Tab Đang giảm giá -->
                                    <div class="tab-pane fade" id="sale" role="tabpanel">
                                        <div class="row">
                                            <?php if(!empty($sale_products)): ?>
                                                <?php foreach($sale_products as $product): ?>
                                                <div class="col-lg-4 col-md-6 mb-4">
                                                    <div class="card product-card h-100">
                                                        <div class="product-img-container">
                                                            <img src="<?php echo getProductImage($product); ?>" 
                                                                 class="product-img" 
                                                                 alt="<?php echo htmlspecialchars($product['product_name']); ?>">
                                                            <span class="product-badge sale">-<?php echo $product['discount_percent']; ?>%</span>
                                                        </div>
                                                        <div class="card-body d-flex flex-column">
                                                            <h5 class="card-title"><?php echo htmlspecialchars($product['product_name']); ?></h5>
                                                            <p class="card-text text-muted flex-grow-1">
                                                                <?php echo htmlspecialchars($product['short_description'] ?? 'Sản phẩm đang giảm giá'); ?>
                                                            </p>
                                                            <div class="mt-auto">
                                                                <div class="d-flex justify-content-between align-items-center mb-3">
                                                                    <span class="product-price fw-bold">
                                                                        <?php echo formatPrice($product['price']); ?>
                                                                    </span>
                                                                    <?php if(isset($product['original_price']) && $product['original_price'] > $product['price']): ?>
                                                                        <span class="product-old-price text-muted text-decoration-line-through">
                                                                            <?php echo formatPrice($product['original_price']); ?>
                                                                        </span>
                                                                    <?php endif; ?>
                                                                </div>
                                                                <div class="d-grid gap-2">
                                                                    <a href="/PHARMAGO/public/product/<?php echo $product['product_id']; ?>" class="btn btn-primary">
                                                                        <i class="fas fa-eye me-2"></i>Xem chi tiết
                                                                    </a>
                                                                    <?php if(isset($_SESSION['user_id'])): ?>
                                                                        <button class="btn btn-outline-primary add-to-cart" 
                                                                                data-product-id="<?php echo $product['product_id']; ?>">
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
                                            <?php else: ?>
                                                <div class="col-12 text-center py-5">
                                                    <div class="alert alert-info">
                                                        <i class="fas fa-info-circle me-2"></i>
                                                        Không có sản phẩm đang giảm giá.
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <!-- Tab Bán chạy -->
                                    <div class="tab-pane fade" id="best" role="tabpanel">
                                        <div class="row">
                                            <?php if(!empty($best_seller_products)): ?>
                                                <?php foreach($best_seller_products as $product): ?>
                                                <div class="col-lg-4 col-md-6 mb-4">
                                                    <div class="card product-card h-100">
                                                        <div class="product-img-container">
                                                            <img src="<?php echo getProductImage($product); ?>" 
                                                                 class="product-img" 
                                                                 alt="<?php echo htmlspecialchars($product['product_name']); ?>">
                                                            <span class="product-badge best">Bán chạy</span>
                                                        </div>
                                                        <div class="card-body d-flex flex-column">
                                                            <h5 class="card-title"><?php echo htmlspecialchars($product['product_name']); ?></h5>
                                                            <p class="card-text text-muted flex-grow-1">
                                                                <?php echo htmlspecialchars($product['short_description'] ?? 'Sản phẩm bán chạy'); ?>
                                                            </p>
                                                            <div class="mt-auto">
                                                                <div class="d-flex justify-content-between align-items-center mb-3">
                                                                    <span class="product-price fw-bold">
                                                                        <?php echo formatPrice($product['price']); ?>
                                                                    </span>
                                                                    <?php if(isset($product['original_price']) && $product['original_price'] > $product['price']): ?>
                                                                        <span class="product-old-price text-muted text-decoration-line-through">
                                                                            <?php echo formatPrice($product['original_price']); ?>
                                                                        </span>
                                                                    <?php endif; ?>
                                                                </div>
                                                                <div class="d-grid gap-2">
                                                                    <a href="/PHARMAGO/public/product/<?php echo $product['product_id']; ?>" class="btn btn-primary">
                                                                        <i class="fas fa-eye me-2"></i>Xem chi tiết
                                                                    </a>
                                                                    <?php if(isset($_SESSION['user_id'])): ?>
                                                                        <button class="btn btn-outline-primary add-to-cart" 
                                                                                data-product-id="<?php echo $product['product_id']; ?>">
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
                                            <?php else: ?>
                                                <div class="col-12 text-center py-5">
                                                    <div class="alert alert-info">
                                                        <i class="fas fa-info-circle me-2"></i>
                                                        Không có sản phẩm bán chạy.
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
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
                        <p class="mb-3">Địa chỉ tin cậy cho sức khỏe của bạn và gia đình. Cam kết chất lượng và dịch vụ tốt nhất.</p>
                        <div class="contact-info">
                            <p class="mb-1"><i class="fas fa-map-marker-alt me-2"></i>123 Đường 2/9, Đà Nẵng</p>
                            <p class="mb-1"><i class="fas fa-phone me-2"></i>(0236) 1234 567</p>
                            <p class="mb-0"><i class="fas fa-envelope me-2"></i>info@pharmacy.com</p>
                        </div>
                    </div>
                    <div class="col-lg-2 col-md-6 mb-4">
                        <h5>Liên kết</h5>
                        <ul class="list-unstyled">
                            <li class="mb-2"><a href="/PHARMAGO/public/"><i class="fas fa-home me-1"></i>Trang chủ</a></li>
                            <li class="mb-2"><a href="/PHARMAGO/public/categories"><i class="fas fa-list me-1"></i>Danh mục</a></li>
                            <li class="mb-2"><a href="/PHARMAGO/public/products"><i class="fas fa-pills me-1"></i>Sản phẩm</a></li>
                            <li class="mb-2"><a href="/PHARMAGO/public/contact"><i class="fas fa-phone me-1"></i>Liên hệ</a></li>
                        </ul>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-4">
                        <h5>Danh mục nổi bật</h5>
                        <ul class="list-unstyled">
                            <?php if(isset($categories) && !empty($categories)): ?>
                                <?php foreach(array_slice($categories, 0, 4) as $category): ?>
                                <li class="mb-2">
                                    <a href="/PHARMAGO/public/products?category=<?php echo $category['category_id']; ?>">
                                        <i class="<?php echo $category['icon_class'] ?? 'fas fa-pills'; ?> me-1"></i>
                                        <?php echo htmlspecialchars($category['category_name']); ?>
                                    </a>
                                </li>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </ul>
                    </div>
                    <div class="col-lg-3 mb-4">
                        <h5>Theo dõi chúng tôi</h5>
                        <div class="social-icons mb-3">
                            <a href="#" class="facebook"><i class="fab fa-facebook-f"></i></a>
                            <a href="#" class="twitter"><i class="fab fa-twitter"></i></a>
                            <a href="#" class="instagram"><i class="fab fa-instagram"></i></a>
                            <a href="#" class="youtube"><i class="fab fa-youtube"></i></a>
                        </div>
                    </div>
                </div>
                <hr>
                <div class="text-center">
                    <p class="mb-0">&copy; <?php echo date('Y'); ?> Pharmacy. All rights reserved.</p>
                </div>
            </div>
        </footer>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/PHARMAGO/public/assets/js/products.js"></script>
    <script src="/PHARMAGO/public/assets/js/script.js"></script>
</body>
</html>