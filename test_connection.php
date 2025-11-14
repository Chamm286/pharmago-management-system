<?php
// Hiển thị lỗi rõ ràng
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>🔍 Testing Database Connection</h2>";

// Include file database
require_once 'config/database.php';

// Tạo instance và test kết nối
$database = new Database();
$db = $database->getConnection();

if ($db) {
    echo "<p style='color: green; font-weight: bold;'>✅ Kết nối database THÀNH CÔNG!</p>";
    
    // Hiển thị thông tin database
    echo "<h3>📊 Database Info:</h3>";
    echo "<ul>";
    echo "<li>Host: 127.0.0.1</li>";
    echo "<li>Database: pharmacy_db</li>";
    echo "<li>Username: root</li>";
    echo "</ul>";
    
    // Kiểm tra tables
    try {
        $stmt = $db->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        echo "<h3>🗃️ Tables trong database:</h3>";
        if (count($tables) > 0) {
            echo "<ul>";
            foreach ($tables as $table) {
                echo "<li>" . htmlspecialchars($table) . "</li>";
            }
            echo "</ul>";
        } else {
            echo "<p style='color: orange;'>⚠️ Database trống, chưa có tables!</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Lỗi khi lấy danh sách tables: " . $e->getMessage() . "</p>";
    }
    
} else {
    echo "<p style='color: red; font-weight: bold;'>❌ Kết nối database THẤT BẠI!</p>";
    echo "<p>Kiểm tra:</p>";
    echo "<ul>";
    echo "<li>MySQL có đang chạy trong XAMPP không?</li>";
    echo "<li>Database 'pharmacy_db' đã được tạo chưa?</li>";
    echo "<li>Username/password có đúng không?</li>";
    echo "</ul>";
}

echo "<hr>";
echo "<p><a href='../'>← Quay lại</a></p>";
?>