<?php
session_start();

// Kiểm tra đăng nhập
if(!isset($_SESSION['user_id'])) {
    header("Location: /public/index.php?controller=auth&action=login");
    exit;
}

require_once '../../config/database.php';
require_once '../../models/Cart.php';
require_once '../../models/Product.php';

$database = new Database();
$db = $database->getConnection();

$cartModel = new Cart($db);
$productModel = new Product($db);

// Lấy giỏ hàng của user
$user_id = $_SESSION['user_id'];
$cart_items = $cartModel->getCartItems($user_id);

// Xử lý cập nhật giỏ hàng
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    if(isset($_POST['update_cart'])) {
        $product_id = $_POST['product_id'];
        $quantity = $_POST['quantity'];
        
        if($quantity <= 0) {
            $cartModel->removeFromCart($user_id, $product_id);
        } else {
            $cartModel->updateCartItem($user_id, $product_id, $quantity);
        }
        
        header("Location: /public/index.php?controller=cart");
        exit;
    }
    
    if(isset($_POST['remove_item'])) {
        $product_id = $_POST['product_id'];
        $cartModel->removeFromCart($user_id, $product_id);
        
        header("Location: /public/index.php?controller=cart");
        exit;
    }
    
    if(isset($_POST['checkout'])) {
        // Xử lý thanh toán
        require_once '../../models/Order.php';
        $orderModel = new Order($db);
        
        $order_id = $orderModel->createOrder($user_id, $_SESSION['full_name'], $_SESSION['email'], $_SESSION['phone'] ?? '', $_SESSION['address'] ?? '');
        
        if($order_id) {
            // Xóa giỏ hàng sau khi tạo đơn hàng thành công
            $cartModel->clearCart($user_id);
            
            header("Location: /public/index.php?controller=order&action=success&id=" . $order_id);
            exit;
        }
    }
}

// Tính tổng tiền
$subtotal = 0;
$discount = 0;
foreach($cart_items as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}

// Giảm giá 10% cho đơn hàng trên 200,000đ
if($subtotal > 200000) {
    $discount = $subtotal * 0.1;
}

$total = $subtotal - $discount;
?>

<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Giỏ hàng - Nhà thuốc</title>
  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/public/assets/css/cart.css">
</head>
<body>
  <!-- Header -->
  <header class="pharmacy-header">
    <div class="container">
      <div class="row align-items-center">
        <div class="col-md-6">
          <h1 class="mb-0"><i class="fas fa-prescription-bottle-alt me-2"></i>Pharmacy</h1>
        </div>
        <div class="col-md-6 text-end">
          <p class="mb-0"><i class="fas fa-phone-alt me-2"></i>Hotline: 1800 1234</p>
          <p class="mb-0"><i class="fas fa-map-marker-alt me-2"></i>123 Đường 2/9, Quận Hải Châu, TP.Đà Nẵng</p>
        </div>
      </div>
    </div>
  </header>

  <!-- Main content -->
  <div class="container py-3">
    <div class="row">
      <div class="col-lg-8">
        <div class="d-flex justify-content-between align-items-center mb-4">
          <h2 class="mb-0"><i class="fas fa-shopping-cart me-2"></i>Giỏ hàng của bạn</h2>
          <span class="badge bg-primary rounded-pill cart-badge"><?php echo count($cart_items); ?></span>
        </div>
        
        <div id="cart-items">
          <?php if(empty($cart_items)): ?>
            <!-- Empty cart message -->
            <div id="empty-cart-message" class="empty-cart card">
              <div class="card-body">
                <div class="empty-cart-icon">
                  <i class="fas fa-shopping-cart"></i>
                </div>
                <h4 class="text-muted mb-3">Giỏ hàng của bạn đang trống</h4>
                <p class="text-muted mb-4">Hãy lựa chọn sản phẩm để tiếp tục mua sắm</p>
                <a href="/public/index.php?controller=product" class="btn btn-continue btn-lg">
                  <i class="fas fa-arrow-left me-2"></i>Tiếp tục mua sắm
                </a>
              </div>
            </div>
          <?php else: ?>
            <?php foreach($cart_items as $item): ?>
            <div class="cart-item mb-3 p-3 border-bottom">
              <div class="row align-items-center">
                <div class="col-md-2">
                  <img src="/public/assets/<?php echo htmlspecialchars($item['image_url']); ?>" 
                       class="img-fluid rounded" 
                       alt="<?php echo htmlspecialchars($item['product_name']); ?>"
                       style="max-height: 80px; object-fit: contain; background-color: #f8f9fa; padding: 5px; border: 1px solid #eee;">
                </div>
                <div class="col-md-5">
                  <h6 class="product-name mb-1"><?php echo htmlspecialchars($item['product_name']); ?></h6>
                  <small class="text-muted"><?php echo htmlspecialchars($item['category_name'] ?? 'Nhà thuốc'); ?></small>
                  <div class="mt-2">
                    <form method="POST" style="display: inline;">
                      <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                      <button type="submit" name="remove_item" class="btn btn-sm btn-outline-danger">
                        <i class="fas fa-trash me-1"></i> Xóa
                      </button>
                    </form>
                  </div>
                </div>
                <div class="col-md-5">
                  <form method="POST" class="d-flex align-items-center justify-content-end">
                    <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                    <div class="d-flex align-items-center me-4">
                      <button type="button" class="btn btn-sm btn-outline-secondary btn-decrease qty-btn" 
                              style="width: 30px; height: 30px;" 
                              onclick="updateQuantity(<?php echo $item['product_id']; ?>, -1)">
                        <i class="fas fa-minus"></i>
                      </button>
                      <input type="number" class="quantity-input mx-2 text-center" 
                             name="quantity" 
                             id="quantity-<?php echo $item['product_id']; ?>"
                             value="<?php echo $item['quantity']; ?>" 
                             min="1" 
                             style="width: 60px; border: 1px solid #dee2e6; border-radius: 4px;"
                             onchange="updateQuantityDirect(<?php echo $item['product_id']; ?>)">
                      <button type="button" class="btn btn-sm btn-outline-secondary btn-increase qty-btn" 
                              style="width: 30px; height: 30px;"
                              onclick="updateQuantity(<?php echo $item['product_id']; ?>, 1)">
                        <i class="fas fa-plus"></i>
                      </button>
                    </div>
                    <div class="text-end">
                      <div class="product-price fw-bold">
                        <?php echo number_format($item['price'] * $item['quantity'], 0, ',', '.'); ?>đ
                      </div>
                      <?php if($item['original_price'] && $item['original_price'] > $item['price']): ?>
                        <small class="text-muted text-decoration-line-through">
                          <?php echo number_format($item['original_price'] * $item['quantity'], 0, ',', '.'); ?>đ
                        </small>
                      <?php endif; ?>
                    </div>
                  </form>
                </div>
              </div>
            </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
      
      <div class="col-lg-4">
        <div class="payment-card">
          <div class="card-header">
            <i class="fas fa-receipt me-2"></i>Thanh toán
          </div>
          <div class="card-body">
            <div class="mb-4">
              <h6 class="mb-3"><i class="fas fa-credit-card me-2"></i>Phương thức thanh toán</h6>
              
              <div class="payment-method active" data-method="qr" id="qr-method">
                <div class="d-flex align-items-center">
                  <i class="fas fa-qrcode payment-icon"></i>
                  <div>
                    <input type="radio" name="payment" id="qr-payment" checked class="me-2">
                    <label for="qr-payment" class="mb-0 fw-bold">QR Code</label>
                  </div>
                </div>
                <div id="qr-code" class="mt-3 text-center">
                  <div class="qr-container">
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=Pharmacy_<?php echo $user_id; ?>_<?php echo time(); ?>&color=2a9d8f" 
                         alt="QR Code" class="img-fluid">
                  </div>
                  <p class="small text-muted mt-2">Quét mã để thanh toán</p>
                </div>
              </div>
              
              <div class="payment-method" data-method="cash" id="cash-method">
                <div class="d-flex align-items-center">
                  <i class="fas fa-money-bill-wave payment-icon"></i>
                  <div>
                    <input type="radio" name="payment" id="cash-payment" class="me-2">
                    <label for="cash-payment" class="mb-0 fw-bold">Tiền mặt</label>
                  </div>
                </div>
                <div id="cash-payment-details" class="mt-3 d-none">
                  <div class="mb-3">
                    <label for="cash-received" class="form-label">Khách đưa (đồng):</label>
                    <input type="number" class="form-control" id="cash-received" placeholder="Nhập số tiền">
                  </div>
                  <div class="alert alert-info">
                    <strong>Tiền thừa:</strong> <span id="cash-change">0</span>đ
                  </div>
                </div>
              </div>
            </div>
            
            <hr>
            
            <div class="mb-4">
              <h6 class="mb-3"><i class="fas fa-file-invoice-dollar me-2"></i>Tóm tắt đơn hàng</h6>
              <div class="summary-item">
                <span>Tạm tính:</span>
                <span id="subtotal"><?php echo number_format($subtotal, 0, ',', '.'); ?>đ</span>
              </div>
              <div class="summary-item">
                <span>Giảm giá:</span>
                <span id="discount"><?php echo number_format($discount, 0, ',', '.'); ?>đ</span>
              </div>
              <div class="summary-item mt-2">
                <strong>Tổng cộng:</strong>
                <strong class="total-amount" id="total"><?php echo number_format($total, 0, ',', '.'); ?>đ</strong>
              </div>
            </div>
            
            <?php if(!empty($cart_items)): ?>
            <form method="POST">
              <input type="hidden" name="checkout" value="1">
              <button type="submit" class="btn btn-checkout w-100 py-3">
                <i class="fas fa-check-circle me-2"></i>Hoàn tất thanh toán
              </button>
            </form>
            <?php else: ?>
            <button class="btn btn-checkout w-100 py-3" disabled>
              <i class="fas fa-check-circle me-2"></i>Hoàn tất thanh toán
            </button>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Invoice Modal -->
  <div class="modal fade" id="invoiceModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title"><i class="fas fa-receipt me-2"></i>Hóa đơn thanh toán</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body" id="invoiceContent">
          <!-- Invoice content will be loaded here by JS -->
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
            <i class="fas fa-times me-2"></i>Đóng
          </button>
          <button type="button" class="btn btn-primary" id="printInvoice">
            <i class="fas fa-print me-2"></i>In hóa đơn
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- jQuery & Bootstrap JS -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

  <script>
    function updateQuantity(productId, change) {
        const quantityInput = document.getElementById('quantity-' + productId);
        let newQuantity = parseInt(quantityInput.value) + change;
        
        if(newQuantity < 1) newQuantity = 1;
        
        quantityInput.value = newQuantity;
        
        // Submit form to update cart
        updateCartItem(productId, newQuantity);
    }
    
    function updateQuantityDirect(productId) {
        const quantityInput = document.getElementById('quantity-' + productId);
        const newQuantity = parseInt(quantityInput.value);
        
        if(newQuantity < 1) {
            quantityInput.value = 1;
            updateCartItem(productId, 1);
        } else {
            updateCartItem(productId, newQuantity);
        }
    }
    
    function updateCartItem(productId, quantity) {
        const formData = new FormData();
        formData.append('update_cart', '1');
        formData.append('product_id', productId);
        formData.append('quantity', quantity);
        
        fetch('/public/index.php?controller=cart', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if(response.ok) {
                location.reload();
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Có lỗi xảy ra khi cập nhật giỏ hàng');
        });
    }
    
    // Payment method selection
    document.querySelectorAll('.payment-method').forEach(method => {
        method.addEventListener('click', function() {
            const methodType = this.dataset.method;
            
            document.querySelectorAll('.payment-method').forEach(m => {
                m.classList.remove('active');
            });
            this.classList.add('active');
            
            document.getElementById('qr-code').classList.toggle('d-none', methodType !== 'qr');
            document.getElementById('cash-payment-details').classList.toggle('d-none', methodType !== 'cash');
        });
    });
    
    // Calculate cash change
    document.getElementById('cash-received')?.addEventListener('input', function() {
        const total = parseFloat(document.getElementById('total').textContent.replace(/[^\d]/g, ''));
        const received = parseFloat(this.value) || 0;
        const change = received - total;
        
        document.getElementById('cash-change').textContent = 
            change >= 0 ? change.toLocaleString('vi-VN') + 'đ' : '0đ';
    });
  </script>
</body>
</html>