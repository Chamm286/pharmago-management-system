<?php
// check_php.php
echo "<h3>🔧 BƯỚC 2: KIỂM TRA PHP</h3>";
echo "PHP Version: <strong>" . PHP_VERSION . "</strong><br>";
echo "Password Hash: " . (function_exists('password_verify') ? "✅ Có" : "❌ Không") . "<br>";

// Test tạo hash mới
$test_pass = 'test123';
$test_hash = password_hash($test_pass, PASSWORD_DEFAULT);
echo "Test hash mới: " . (password_verify($test_pass, $test_hash) ? "✅ Hoạt động" : "❌ Lỗi");
?>