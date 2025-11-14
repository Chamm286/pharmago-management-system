<?php
// admin_logout.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Xóa tất cả session admin
unset($_SESSION['admin_logged_in']);
unset($_SESSION['admin_id']);
unset($_SESSION['admin_username']);
unset($_SESSION['admin_name']);
unset($_SESSION['admin_role']);
unset($_SESSION['admin_email']);

// Chuyển hướng về trang login
header('Location: admin_login.php');
exit;
?>