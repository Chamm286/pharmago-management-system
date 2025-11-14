<?php
// manual_test.php
session_start();
require_once '../config/database.php';

echo "<h3>🔧 TEST ĐĂNG NHẬP THỦ CÔNG</h3>";

// Manual login test
$username = 'BichTram';
$password = 'password';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT user_id, username, password_hash, full_name, role, email 
              FROM users 
              WHERE username = :username";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':username', $username);
    $stmt->execute();
    
    if ($stmt->rowCount() === 1) {
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "✅ User found: " . $user['username'] . "<br>";
        
        $password_valid = password_verify($password, $user['password_hash']);
        echo "Password verify: " . ($password_valid ? "✅ VALID" : "❌ INVALID") . "<br>";
        
        if ($password_valid) {
            // Create session manually
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_id'] = $user['user_id'];
            $_SESSION['admin_username'] = $user['username'];
            $_SESSION['admin_name'] = $user['full_name'];
            $_SESSION['admin_role'] = $user['role'];
            $_SESSION['admin_email'] = $user['email'];
            
            echo "✅ SESSION CREATED SUCCESSFULLY<br>";
            echo "Session data: <pre>";
            print_r($_SESSION);
            echo "</pre>";
            
            // Test redirect
            echo "<script>setTimeout(() => { window.location.href = 'admin.php'; }, 2000);</script>";
            echo "⏳ Redirecting to admin.php in 2 seconds...";
        }
    } else {
        echo "❌ User not found<br>";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}
?>