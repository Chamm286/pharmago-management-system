<?php
// test_simple.php
echo "<h3>🔧 BƯỚC 1: TEST PASSWORD ĐƠN GIẢN</h3>";

$password = 'password123';
$hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';

echo "Password: <strong>$password</strong><br>";
echo "Hash: <code>$hash</code><br>";

$result = password_verify($password, $hash);
echo "<h4 style='color: " . ($result ? "green" : "red") . ";'>";
echo $result ? "✅ PASSWORD VERIFY THÀNH CÔNG" : "❌ PASSWORD VERIFY THẤT BẠI";
echo "</h4>";

if (!$result) {
    echo "<p>🚨 VẤN ĐỀ: Password 'password123' không khớp với hash trong database</p>";
    echo "<p>👉 Có thể password thực tế KHÔNG PHẢI là 'password123'</p>";
}
?>