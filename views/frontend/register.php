<?php
session_start();
require_once 'config/database.php';

$errors = [];
$success = '';

// Xử lý form đăng ký
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Lấy dữ liệu từ form
    $email = trim($_POST['email'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $full_name = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    
    // Validate dữ liệu
    if (empty($email)) {
        $errors[] = "Email không được để trống";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Email không hợp lệ";
    }
    
    if (empty($username)) {
        $errors[] = "Tên đăng nhập không được để trống";
    } elseif (strlen($username) < 3) {
        $errors[] = "Tên đăng nhập phải có ít nhất 3 ký tự";
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $errors[] = "Tên đăng nhập chỉ được chứa chữ cái, số và dấu gạch dưới";
    }
    
    if (empty($password)) {
        $errors[] = "Mật khẩu không được để trống";
    } elseif (strlen($password) < 6) {
        $errors[] = "Mật khẩu phải có ít nhất 6 ký tự";
    }
    
    if ($password !== $confirm_password) {
        $errors[] = "Mật khẩu xác nhận không khớp";
    }
    
    if (empty($full_name)) {
        $errors[] = "Họ tên không được để trống";
    }
    
    // Kiểm tra email và username đã tồn tại chưa
    if (empty($errors)) {
        try {
            // Kiểm tra email tồn tại
            $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->rowCount() > 0) {
                $errors[] = "Email đã được sử dụng";
            }
            
            // Kiểm tra username tồn tại
            $stmt = $pdo->prepare("SELECT user_id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->rowCount() > 0) {
                $errors[] = "Tên đăng nhập đã được sử dụng";
            }
        } catch (PDOException $e) {
            $errors[] = "Lỗi hệ thống: " . $e->getMessage();
        }
    }
    
    // Nếu không có lỗi, thực hiện đăng ký
    if (empty($errors)) {
        try {
            // Mã hóa mật khẩu
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Chuẩn bị câu lệnh SQL
            $sql = "INSERT INTO users (username, password_hash, email, full_name, phone, address, role) 
                    VALUES (?, ?, ?, ?, ?, ?, 'customer')";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$username, $hashed_password, $email, $full_name, $phone, $address]);
            
            $success = "Đăng ký thành công! Bạn có thể đăng nhập ngay bây giờ.";
            
            // Reset form
            $email = $username = $full_name = $phone = $address = '';
            
        } catch (PDOException $e) {
            $errors[] = "Lỗi đăng ký: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>Đăng ký - Pharmacy</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <style>
    body {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      min-height: 100vh;
      display: flex;
      align-items: center;
    }
    .card {
      border: none;
      border-radius: 15px;
      overflow: hidden;
    }
    .card-header {
      background: linear-gradient(135deg, #28a745, #20c997);
      color: white;
      text-align: center;
      padding: 2rem 1rem;
      border-bottom: none;
    }
    .card-header h3 {
      margin: 0;
      font-weight: 600;
    }
    .card-header i {
      font-size: 3rem;
      margin-bottom: 1rem;
    }
    .form-control {
      border-radius: 10px;
      padding: 12px 15px;
      border: 2px solid #e9ecef;
      transition: all 0.3s;
    }
    .form-control:focus {
      border-color: #28a745;
      box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
    }
    .btn-success {
      background: linear-gradient(135deg, #28a745, #20c997);
      border: none;
      border-radius: 10px;
      padding: 12px;
      font-weight: 600;
      transition: all 0.3s;
    }
    .btn-success:hover {
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(40, 167, 69, 0.4);
    }
    .login-link {
      text-align: center;
      margin-top: 1rem;
    }
    .password-toggle {
      position: relative;
    }
    .password-toggle-icon {
      position: absolute;
      right: 15px;
      top: 50%;
      transform: translateY(-50%);
      cursor: pointer;
      color: #6c757d;
    }
  </style>
</head>
<body>

  <div class="container">
    <div class="row justify-content-center">
      <div class="col-md-6 col-lg-5">
        <div class="card shadow-lg">
          <div class="card-header">
            <i class="fas fa-user-plus"></i>
            <h3>Đăng ký tài khoản</h3>
            <p class="mb-0">Tham gia cùng Pharmacy ngay hôm nay</p>
          </div>
          
          <div class="card-body p-4">
            <?php if (!empty($errors)): ?>
              <div class="alert alert-danger">
                <?php foreach ($errors as $error): ?>
                  <p class="mb-1"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?></p>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
              <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
              </div>
            <?php endif; ?>

            <form method="POST" action="">
              <div class="row">
                <div class="col-md-6 mb-3">
                  <label class="form-label">Họ và tên <span class="text-danger">*</span></label>
                  <input type="text" class="form-control" name="full_name" 
                         value="<?php echo isset($full_name) ? htmlspecialchars($full_name) : ''; ?>" 
                         placeholder="Nhập họ và tên" required>
                </div>
                <div class="col-md-6 mb-3">
                  <label class="form-label">Số điện thoại</label>
                  <input type="tel" class="form-control" name="phone" 
                         value="<?php echo isset($phone) ? htmlspecialchars($phone) : ''; ?>" 
                         placeholder="Nhập số điện thoại">
                </div>
              </div>

              <div class="mb-3">
                <label class="form-label">Email <span class="text-danger">*</span></label>
                <input type="email" class="form-control" name="email" 
                       value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>" 
                       placeholder="Nhập email" required>
              </div>

              <div class="mb-3">
                <label class="form-label">Tên đăng nhập <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="username" 
                       value="<?php echo isset($username) ? htmlspecialchars($username) : ''; ?>" 
                       placeholder="Nhập tên đăng nhập" required>
                <div class="form-text">Tên đăng nhập phải có ít nhất 3 ký tự, chỉ chứa chữ cái, số và dấu gạch dưới.</div>
              </div>

              <div class="mb-3 password-toggle">
                <label class="form-label">Mật khẩu <span class="text-danger">*</span></label>
                <input type="password" class="form-control" name="password" id="password" 
                       placeholder="Nhập mật khẩu" required>
                <span class="password-toggle-icon" onclick="togglePassword('password')">
                  <i class="fas fa-eye"></i>
                </span>
                <div class="form-text">Mật khẩu phải có ít nhất 6 ký tự.</div>
              </div>

              <div class="mb-3 password-toggle">
                <label class="form-label">Xác nhận mật khẩu <span class="text-danger">*</span></label>
                <input type="password" class="form-control" name="confirm_password" id="confirm_password" 
                       placeholder="Nhập lại mật khẩu" required>
                <span class="password-toggle-icon" onclick="togglePassword('confirm_password')">
                  <i class="fas fa-eye"></i>
                </span>
              </div>

              <div class="mb-3">
                <label class="form-label">Địa chỉ</label>
                <textarea class="form-control" name="address" rows="2" 
                          placeholder="Nhập địa chỉ"><?php echo isset($address) ? htmlspecialchars($address) : ''; ?></textarea>
              </div>

              <div class="d-grid mb-3">
                <button type="submit" class="btn btn-success btn-lg">
                  <i class="fas fa-user-plus me-2"></i>Đăng ký
                </button>
              </div>
            </form>

            <div class="login-link">
              <p>Đã có tài khoản? <a href="login.php" class="text-success text-decoration-none fw-bold">Đăng nhập ngay</a></p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script>
    function togglePassword(fieldId) {
      const field = document.getElementById(fieldId);
      const icon = field.nextElementSibling.querySelector('i');
      
      if (field.type === 'password') {
        field.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
      } else {
        field.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
      }
    }

    // Real-time password confirmation check
    document.getElementById('confirm_password').addEventListener('input', function() {
      const password = document.getElementById('password').value;
      const confirmPassword = this.value;
      
      if (confirmPassword && password !== confirmPassword) {
        this.style.borderColor = '#dc3545';
      } else {
        this.style.borderColor = '#28a745';
      }
    });
  </script>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>