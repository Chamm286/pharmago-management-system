<?php
// test_db.php
echo "<h3>🔧 BƯỚC 3: TEST VỚI DATABASE</h3>";

try {
    require_once '../config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    
    echo "✅ Kết nối database thành công<br>";
    
    // Lấy user BichTram
    $query = "SELECT user_id, username, password_hash FROM users WHERE username = 'BichTram'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "User: <strong>" . $user['username'] . "</strong><br>";
    echo "Hash: <code>" . $user['password_hash'] . "</code><br>";
    
    // Test password
    $passwords_to_test = ['password123', '123456', 'admin', 'password', 'pharmacy'];
    
    foreach ($passwords_to_test as $test_pass) {
        $result = password_verify($test_pass, $user['password_hash']);
        echo "Password '$test_pass': " . ($result ? "✅ ĐÚNG" : "❌ SAI") . "<br>";
        if ($result) break;
    }
    
} catch (Exception $e) {
    echo "❌ Lỗi: " . $e->getMessage();
}
?>