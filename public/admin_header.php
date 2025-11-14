<?php
// public/admin_header.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Hàm helper để lấy chữ cái đầu cho avatar
function getInitials($name) {
    if (empty($name)) return 'A';
    $words = explode(' ', $name);
    $initials = '';
    foreach ($words as $word) {
        if (!empty($word)) {
            $initials .= strtoupper($word[0]);
        }
    }
    return substr($initials, 0, 2);
}

// Hàm helper để lấy role text
function getRoleText($role) {
    switch ($role) {
        case 'admin': return 'Quản trị viên';
        case 'staff': return 'Nhân viên';
        case 'customer': return 'Khách hàng';
        default: return 'Người dùng';
    }
}
?>

<!-- Admin Header Component -->
<header class="admin-header">
    <div class="header-left">
        <button class="toggle-sidebar">
            <i class="fas fa-bars"></i>
        </button>
        <h1 class="page-title">
            <?php 
            // Xác định tiêu đề trang dựa trên URL hiện tại
            $current_page = basename($_SERVER['PHP_SELF']);
            switch($current_page) {
                case 'admin.php':
                    echo 'Dashboard';
                    break;
                case 'admin_products.php':
                    echo 'Quản lý Sản phẩm';
                    break;
                case 'admin_orders.php':
                    echo 'Quản lý Đơn hàng';
                    break;
                case 'admin_users.php':
                    echo 'Quản lý Người dùng';
                    break;
                case 'admin_categories.php':
                    echo 'Quản lý Danh mục';
                    break;
                default:
                    echo 'Admin Panel';
            }
            ?>
        </h1>
    </div>
    
    <div class="header-right">
        <div class="search-box">
            <i class="fas fa-search"></i>
            <input type="text" placeholder="Tìm kiếm..." id="globalSearch">
        </div>
        
        <div class="header-actions">
            <button class="notification-btn">
                <i class="fas fa-bell"></i>
                <span class="notification-badge">3</span>
            </button>
            <button class="message-btn">
                <i class="fas fa-envelope"></i>
            </button>
        </div>
        
        <div class="user-profile">
            <div class="user-avatar">
                <?php echo getInitials($_SESSION['admin_name'] ?? 'Admin'); ?>
            </div>
            <div class="user-details">
                <div class="user-name"><?php echo $_SESSION['admin_name'] ?? 'Admin'; ?></div>
                <div class="user-role"><?php echo getRoleText($_SESSION['admin_role'] ?? 'staff'); ?></div>
            </div>
            <div class="user-dropdown">
                <form method="POST" action="admin_logout.php" class="logout-form">
                    <button type="submit" class="dropdown-item">
                        <i class="fas fa-sign-out-alt me-2"></i>Đăng xuất
                    </button>
                </form>
            </div>
        </div>
    </div>
</header>