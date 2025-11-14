<?php
// views/frontend/product-detail.php
$base_url = 'http://' . $_SERVER['HTTP_HOST'] . '/WEB%20CU%E1%BB%90I%20K%E1%BB%B2%202025/public';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['product_name'] ?? 'Chi tiết sản phẩm'); ?> - Pharmacy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #2e7d32;
            --secondary-color: #81c784;
            --light-color: #e8f5e9;
            --dark-color: #1b5e20;
        }
        
        .product-detail-section {
            padding: 80px 0;
            background: linear-gradient(rgba(248, 249, 250, 0.95), rgba(248, 249, 250, 0.95));
            min-height: 100vh;
        }
        
        .product-image {
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            max-height: 500px;
            object-fit: cover;
        }
        
        .price-section {
            background: var(--light-color);
            padding: 25px;
            border-radius: 15px;
            border-left: 4px solid var(--primary-color);
        }
        
        .back-button {
            margin-bottom: 30px;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-light sticky-top">
        <div class="container">
            <a class="navbar-brand fw-bold" href="<?php echo $base_url; ?>">
                <i class="fas fa-prescription-bottle-alt me-2"></i>Pharmacy
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="<?php echo $base_url; ?>"><i class="fas fa-home me-1"></i> Trang chủ</a>
                <a class="nav-link" href="<?php echo $base_url; ?>/products"><i class="fas fa-pills me-1"></i> Sản phẩm</a>
            </div>
        </div>
    </nav>

    <section class="product-detail-section">
        <div class="container">
            <div class="back-button">
                <a href="<?php echo $base_url; ?>" class="btn btn-outline-primary">
                    <i class="fas fa-arrow-left me-2"></i>Quay lại trang chủ
                </a>
            </div>
            
            <?php if(isset($product) && $product): ?>
                <div class="row">
                    <div class="col-md-6 mb-4">
                        <img src="<?php echo $base_url . htmlspecialchars($product['image_url'] ?? '/assets/images/default-product.jpg'); ?>" 
                             alt="<?php echo htmlspecialchars($product['product_name']); ?>" 
                             class="img-fluid product-image w-100"
                             onerror="this.src='https://via.placeholder.com/600x400/81c784/ffffff?text=Product+Image'">
                    </div>
                    <div class="col-md-6">
                        <h1 class="mb-3 fw-bold"><?php echo htmlspecialchars($product['product_name']); ?></h1>
                        
                        <div class="product-rating mb-3">
                            <?php $rating = $product['average_rating'] ?? 4.5; ?>
                            <?php for($i = 1; $i <= 5; $i++): ?>
                                <i class="fas fa-star <?php echo $i <= $rating ? 'text-warning' : 'text-muted'; ?>"></i>
                            <?php endfor; ?>
                            <span class="ms-2 text-muted">(<?php echo $product['review_count'] ?? 0; ?> đánh giá)</span>
                        </div>
                        
                        <p class="text-muted lead mb-4"><?php echo htmlspecialchars($product['short_description'] ?? 'Sản phẩm chất lượng cao'); ?></p>
                        
                        <div class="price-section mb-4">
                            <h2 class="text-primary mb-2"><?php echo number_format($product['price'], 0, ',', '.'); ?>đ</h2>
                            <?php if(isset($product['original_price']) && $product['original_price'] > $product['price']): ?>
                                <del class="text-muted h5"><?php echo number_format($product['original_price'], 0, ',', '.'); ?>đ</del>
                                <span class="badge bg-success ms-2 fs-6">Tiết kiệm <?php echo number_format($product['original_price'] - $product['price'], 0, ',', '.'); ?>đ</span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="product-info mb-4">
                            <p class="fs-5"><strong>Tình trạng:</strong> 
                                <span class="<?php echo ($product['stock_quantity'] ?? 0) > 0 ? 'text-success' : 'text-danger'; ?>">
                                    <?php echo ($product['stock_quantity'] ?? 0) > 0 ? '✓ Còn hàng' : '✗ Hết hàng'; ?>
                                </span>
                            </p>
                            <?php if(isset($product['category_name'])): ?>
                                <p class="fs-5"><strong>Danh mục:</strong> <?php echo htmlspecialchars($product['category_name']); ?></p>
                            <?php endif; ?>
                        </div>
                        
                        <div class="action-buttons mb-5">
                            <?php if(($product['stock_quantity'] ?? 0) > 0): ?>
                                <button class="btn btn-primary btn-lg me-3 add-to-cart" 
                                        data-product-id="<?php echo $product['product_id']; ?>">
                                    <i class="fas fa-cart-plus me-2"></i>Thêm vào giỏ hàng
                                </button>
                                <button class="btn btn-outline-primary btn-lg">
                                    <i class="fas fa-bolt me-2"></i>Mua ngay
                                </button>
                            <?php else: ?>
                                <button class="btn btn-secondary btn-lg me-3" disabled>
                                    <i class="fas fa-times me-2"></i>Hết hàng
                                </button>
                                <button class="btn btn-outline-secondary btn-lg" disabled>
                                    Liên hệ đặt trước
                                </button>
                            <?php endif; ?>
                        </div>
                        
                        <div class="delivery-info">
                            <div class="d-flex align-items-center mb-2">
                                <i class="fas fa-shipping-fast text-success me-2"></i>
                                <span>Giao hàng tận nơi trong 2 giờ</span>
                            </div>
                            <div class="d-flex align-items-center mb-2">
                                <i class="fas fa-shield-alt text-primary me-2"></i>
                                <span>Đảm bảo chính hãng 100%</span>
                            </div>
                            <div class="d-flex align-items-center">
                                <i class="fas fa-undo text-info me-2"></i>
                                <span>Đổi trả trong 7 ngày</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row mt-5">
                    <div class="col-12">
                        <h3 class="mb-4 border-bottom pb-2">Mô tả sản phẩm</h3>
                        <div class="card border-0 shadow-sm">
                            <div class="card-body">
                                <p class="card-text fs-5"><?php echo nl2br(htmlspecialchars($product['description'] ?? 'Sản phẩm chất lượng cao, an toàn cho sức khỏe. Được sản xuất và phân phối chính hãng, đảm bảo tiêu chuẩn chất lượng nghiêm ngặt.')); ?></p>
                                
                                <div class="mt-4">
                                    <h5 class="text-primary">Thông tin bổ sung:</h5>
                                    <ul class="list-unstyled">
                                        <li><i class="fas fa-check text-success me-2"></i> Xuất xứ: Chính hãng</li>
                                        <li><i class="fas fa-check text-success me-2"></i> Hạn sử dụng: Xem trên bao bì</li>
                                        <li><i class="fas fa-check text-success me-2"></i> Bảo quản: Nơi khô ráo, thoáng mát</li>
                                        <li><i class="fas fa-check text-success me-2"></i> Đối tượng sử dụng: Theo chỉ định</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-warning text-center py-5">
                    <i class="fas fa-exclamation-triangle fa-3x mb-3 text-warning"></i>
                    <h3 class="mb-3">Sản phẩm không tồn tại</h3>
                    <p class="mb-4">Sản phẩm bạn đang tìm kiếm không có trong hệ thống.</p>
                    <a href="<?php echo $base_url; ?>" class="btn btn-primary btn-lg">
                        <i class="fas fa-home me-2"></i>Quay về trang chủ
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <footer class="bg-dark text-white py-4 mt-5">
        <div class="container text-center">
            <p class="mb-0">&copy; <?php echo date('Y'); ?> Pharmacy. All rights reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const addToCartButtons = document.querySelectorAll('.add-to-cart');
            
            addToCartButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const productId = this.dataset.productId;
                    const productName = document.querySelector('h1').textContent;
                    
                    // Hiển thị thông báo
                    showNotification(`Đã thêm "${productName}" vào giỏ hàng!`, 'success');
                });
            });

            function showNotification(message, type = 'info') {
                const notification = document.createElement('div');
                notification.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
                notification.style.cssText = `
                    top: 20px;
                    right: 20px;
                    z-index: 9999;
                    min-width: 300px;
                    box-shadow: 0 5px 15px rgba(0,0,0,0.3);
                `;
                notification.innerHTML = `
                    <div class="d-flex align-items-center">
                        <i class="fas fa-${type === 'success' ? 'check-circle' : 'info-circle'} me-2"></i>
                        <span>${message}</span>
                        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
                    </div>
                `;
                
                document.body.appendChild(notification);
                
                // Tự động ẩn sau 3 giây
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.parentNode.removeChild(notification);
                    }
                }, 3000);
            }
        });
    </script>
</body>
</html>