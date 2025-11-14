<?php
// debug_detailed.php
session_start();
echo "<h3>🔧 DEBUG CHI TIẾT ĐĂNG NHẬP</h3>";

// Test 1: Session
echo "<h4>1. Session Test</h4>";
echo "Session ID: " . session_id() . "<br>";
echo "Session Data: ";
print_r($_SESSION);
echo "<br>";

// Test 2: Database Connection
echo "<h4>2. Database Test</h4>";
try {
    require_once '../config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    echo "✅ Database connected<br>";
    
    // Test user query
    $query = "SELECT user_id, username, password_hash, role FROM users WHERE username = 'BichTram'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "User found: " . ($user ? "✅" : "❌") . "<br>";
    if ($user) {
        echo "Username: " . $user['username'] . "<br>";
        echo "Role: " . $user['role'] . "<br>";
        echo "Password hash: " . $user['password_hash'] . "<br>";
        
        // Test password
        $passwords = ['password', 'password123', 'admin', '123456'];
        foreach ($passwords as $pwd) {
            $result = password_verify($pwd, $user['password_hash']);
            echo "Password '$pwd': " . ($result ? "✅ MATCH" : "❌ NO MATCH") . "<br>";
        }
    }
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "<br>";
}

// Test 3: Form Simulation
echo "<h4>3. Form Simulation Test</h4>";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    echo "Form submitted - Username: $username, Password: $password<br>";
    
    // Simulate login
    if ($username === 'BichTram' && $password === 'password') {
        $_SESSION['admin_logged_in'] = true;
        echo "✅ LOGIN SUCCESS - Session created<br>";
        echo "Session data now: ";
        print_r($_SESSION);
    } else {
        echo "❌ LOGIN FAILED<br>";
    }
}
?>

<form method="POST">
    <input type="text" name="username" placeholder="Username" value="BichTram">
    <input type="password" name="password" placeholder="Password" value="password">
    <button type="submit">Test Login</button>
</form>