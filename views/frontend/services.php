<?php
// Kiểm tra session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Kết nối database
try {
    $base_dir = dirname(__DIR__, 2) . '/';
    $config_path = $base_dir . 'config/database.php';
    
    if (!file_exists($config_path)) {
        throw new Exception('Database config file not found');
    }
    require_once $config_path;
    
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception('Cannot connect to database');
    }

    // Lấy dữ liệu dịch vụ từ database
    $services_query = "
        SELECT s.service_id, s.service_name, s.description, s.icon_class, 
               s.color, s.is_active, s.created_at,
               GROUP_CONCAT(sf.feature_name) as features
        FROM services s
        LEFT JOIN service_features sf ON s.service_id = sf.service_id
        WHERE s.is_active = 1
        GROUP BY s.service_id
        ORDER BY s.display_order
    ";
    
    $services_stmt = $db->prepare($services_query);
    $services_stmt->execute();
    $services = $services_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Lấy dữ liệu gói sức khỏe từ database
    $packages_query = "
        SELECT package_id, package_name, price, period, is_popular, created_at
        FROM health_packages 
        WHERE is_active = 1
        ORDER BY display_order
    ";
    
    $packages_stmt = $db->prepare($packages_query);
    $packages_stmt->execute();
    $health_packages = $packages_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Lấy features cho từng gói
    foreach ($health_packages as &$package) {
        $features_query = "
            SELECT feature_name 
            FROM package_features 
            WHERE package_id = :package_id
            ORDER BY display_order
        ";
        
        $features_stmt = $db->prepare($features_query);
        $features_stmt->bindParam(':package_id', $package['package_id']);
        $features_stmt->execute();
        $package_features = $features_stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $package['features'] = $package_features;
        $package['popular'] = (bool)$package['is_popular'];
    }

} catch (Exception $e) {
    error_log("Services page error: " . $e->getMessage());
    $services = [];
    $health_packages = [];
}

// Hàm helper để format giá
function formatPrice($price) {
    if ($price === null || $price === '') {
        return 'Liên hệ';
    }
    return number_format($price, 0, ',', '.') . 'đ';
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dịch vụ - Pharmacy</title>
    
    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link rel="stylesheet" href="/PHARMAGO/public/assets/css/services.css">
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
                        <a class="nav-link" href="/PHARMAGO/public/about">
                            <i class="fas fa-info-circle me-1"></i>Giới thiệu
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="/PHARMAGO/public/services">
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

    <!-- Services Hero Section -->
    <section class="services-hero" id="home">
        <div class="container">
            <div class="row align-items-center min-vh-80">
                <div class="col-lg-6">
                    <h1 class="display-4 fw-bold text-white mb-4 animate__animated animate__fadeInLeft">
                        Dịch Vụ Chăm Sóc Sức Khỏe Toàn Diện
                    </h1>
                    <p class="lead text-white mb-4 animate__animated animate__fadeInLeft">
                        Từ dược phẩm chất lượng đến dịch vụ y tế chuyên nghiệp, chúng tôi đồng hành cùng sức khỏe của bạn và gia đình
                    </p>
                    <div class="hero-stats animate__animated animate__fadeInUp">
                        <div class="stat">
                            <span class="stat-number"><?php echo count($services); ?>+</span>
                            <span class="stat-label">Dịch vụ chuyên nghiệp</span>
                        </div>
                        <div class="stat">
                            <span class="stat-number">99%</span>
                            <span class="stat-label">Hài lòng dịch vụ</span>
                        </div>
                        <div class="stat">
                            <span class="stat-number">24/7</span>
                            <span class="stat-label">Hỗ trợ tư vấn</span>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 text-center animate__animated animate__fadeInRight">
                    <div class="services-visual">
                        <img src="https://images.unsplash.com/photo-1576091160399-112ba8d25d1f?ixlib=rb-4.0.3&auto=format&fit=crop&w=1000&q=80" 
                             alt="Dịch vụ y tế" class="img-fluid rounded-3">
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Main Services Section -->
    <section class="main-services-section">
        <div class="container">
            <div class="row mb-5">
                <div class="col-12 text-center">
                    <h2 class="section-title">Dịch Vụ Chính</h2>
                    <p class="lead text-muted">Các dịch vụ chăm sóc sức khỏe toàn diện được cung cấp bởi đội ngũ chuyên gia</p>
                </div>
            </div>
            
            <div class="row">
                <?php if(!empty($services)): ?>
                    <?php foreach($services as $index => $service): ?>
                    <?php 
                    $features = !empty($service['features']) ? explode(',', $service['features']) : [];
                    ?>
                    <div class="col-lg-4 col-md-6 mb-4 animate-fade-in">
                        <div class="service-card card h-100">
                            <div class="card-body">
                                <div class="service-icon bg-<?php echo $service['color'] ?? 'primary'; ?>">
                                    <i class="<?php echo $service['icon_class'] ?? 'fas fa-concierge-bell'; ?>"></i>
                                </div>
                                <h4 class="service-title"><?php echo htmlspecialchars($service['service_name']); ?></h4>
                                <p class="service-description"><?php echo htmlspecialchars($service['description']); ?></p>
                                
                                <?php if(!empty($features)): ?>
                                <div class="service-features">
                                    <?php foreach($features as $feature): ?>
                                    <div class="feature-item">
                                        <i class="fas fa-check-circle text-success me-2"></i>
                                        <span><?php echo htmlspecialchars(trim($feature)); ?></span>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <?php endif; ?>
                                
                                <div class="service-actions mt-3">
                                    <button class="btn btn-outline-primary btn-sm" onclick="showServiceModal(<?php echo $service['service_id']; ?>)">
                                        <i class="fas fa-info-circle me-1"></i>Chi tiết
                                    </button>
                                    <button class="btn btn-primary btn-sm" onclick="bookService('<?php echo $service['service_id']; ?>', '<?php echo htmlspecialchars($service['service_name']); ?>')">
                                        <i class="fas fa-calendar-check me-1"></i>Đặt lịch
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12 text-center py-5">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            Hiện chưa có dịch vụ nào. Vui lòng quay lại sau.
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Health Packages Section -->
    <section class="packages-section">
        <div class="container">
            <div class="row mb-5">
                <div class="col-12 text-center">
                    <h2 class="section-title">Gói Sức Khỏe</h2>
                    <p class="lead text-muted">Lựa chọn gói chăm sóc sức khỏe phù hợp với nhu cầu của bạn</p>
                </div>
            </div>
            
            <div class="row justify-content-center">
                <?php if(!empty($health_packages)): ?>
                    <?php foreach($health_packages as $package): ?>
                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="package-card card h-100 <?php echo $package['popular'] ? 'popular' : ''; ?>">
                            <?php if($package['popular']): ?>
                            <div class="popular-badge">Phổ biến</div>
                            <?php endif; ?>
                            
                            <div class="card-body text-center">
                                <h4 class="package-name"><?php echo htmlspecialchars($package['package_name']); ?></h4>
                                <div class="package-price">
                                    <span class="price"><?php echo formatPrice($package['price']); ?></span>
                                </div>
                                <div class="package-period"><?php echo htmlspecialchars($package['period']); ?></div>
                                
                                <?php if(!empty($package['features'])): ?>
                                <div class="package-features">
                                    <?php foreach($package['features'] as $feature): ?>
                                    <div class="feature-item">
                                        <i class="fas fa-check text-success me-2"></i>
                                        <span><?php echo htmlspecialchars($feature); ?></span>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <?php endif; ?>
                                
                                <div class="package-actions mt-4">
                                    <?php if($package['price'] === null || $package['price'] === ''): ?>
                                    <button class="btn btn-primary w-100" onclick="contactForPackage('<?php echo $package['package_id']; ?>', '<?php echo htmlspecialchars($package['package_name']); ?>')">
                                        <i class="fas fa-phone me-1"></i>Liên hệ tư vấn
                                    </button>
                                    <?php else: ?>
                                    <button class="btn btn-primary w-100" onclick="bookPackage('<?php echo $package['package_id']; ?>', '<?php echo htmlspecialchars($package['package_name']); ?>')">
                                        <i class="fas fa-shopping-cart me-1"></i>Đăng ký ngay
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12 text-center py-5">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            Hiện chưa có gói sức khỏe nào. Vui lòng quay lại sau.
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section" id="contact">
        <div class="container">
            <div class="row">
                <div class="col-lg-8 mx-auto text-center">
                    <h2 class="text-white mb-4">Sẵn sàng chăm sóc sức khỏe của bạn?</h2>
                    <p class="lead text-white mb-4">Liên hệ ngay để được tư vấn miễn phí và lựa chọn dịch vụ phù hợp nhất</p>
                    <div class="d-flex flex-wrap justify-content-center gap-3">
                        <button class="btn btn-light btn-lg" onclick="contactNow()">
                            <i class="fas fa-phone me-2"></i>Liên hệ ngay
                        </button>
                        <button class="btn btn-outline-light btn-lg" onclick="showBookingModal()">
                            <i class="fas fa-calendar me-2"></i>Đặt lịch trực tuyến
                        </button>
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
                    <p>Địa chỉ tin cậy cho sức khỏe của bạn và gia đình. Cam kết chất lượng và dịch vụ tốt nhất.</p>
                </div>
                <div class="col-lg-2 col-md-6 mb-4">
                    <h5>Liên kết</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="/PHARMAGO/public/">Trang chủ</a></li>
                        <li class="mb-2"><a href="/PHARMAGO/public/categories">Danh mục</a></li>
                        <li class="mb-2"><a href="/PHARMAGO/public/products">Sản phẩm</a></li>
                        <li class="mb-2"><a href="/PHARMAGO/public/services">Dịch vụ</a></li>
                        <li class="mb-2"><a href="/PHARMAGO/public/contact">Liên hệ</a></li>
                    </ul>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <h5>Dịch vụ</h5>
                    <ul class="list-unstyled">
                        <?php if(!empty($services)): ?>
                            <?php foreach(array_slice($services, 0, 4) as $service): ?>
                            <li class="mb-2"><a href="/PHARMAGO/public/services#service-<?php echo $service['service_id']; ?>"><?php echo htmlspecialchars($service['service_name']); ?></a></li>
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
            <hr>
            <div class="text-center">
                <p class="mb-0">&copy; <?php echo date('Y'); ?> Pharmacy. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Booking Modal -->
    <div class="modal fade" id="bookingModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Đặt lịch dịch vụ</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="bookingForm">
                        <input type="hidden" id="selectedServiceId" name="service_id">
                        <input type="hidden" id="selectedPackageId" name="package_id">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Họ tên *</label>
                                <input type="text" class="form-control" name="full_name" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Số điện thoại *</label>
                                <input type="tel" class="form-control" name="phone" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Dịch vụ/Gói đã chọn</label>
                            <input type="text" class="form-control" id="selectedServiceName" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Ngày hẹn *</label>
                            <input type="date" class="form-control" name="appointment_date" required min="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Ghi chú</label>
                            <textarea class="form-control" name="notes" rows="3" placeholder="Mô tả tình trạng sức khỏe hoặc yêu cầu đặc biệt..."></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="button" class="btn btn-primary" onclick="submitBooking()">Đặt lịch ngay</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/PHARMAGO/public/assets/js/services.js"></script>
</body>
</html>