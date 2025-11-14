<?php
// debug_login.php
session_start();
require_once '../config/database.php';

echo "<h3>DEBUG LOGIN SYSTEM</h3>";

// Test database connection
try {
    $database = new Database();
    $db = $database->getConnection();
    
    if ($db) {
        echo "<p style='color: green'>✓ Database connected successfully</p>";
        
        // Test query users
        $query = "SELECT user_id, username, password_hash, full_name, role, email 
                  FROM users 
                  WHERE role IN ('admin', 'staff')";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<p>Found " . count($users) . " admin/staff users:</p>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Username</th><th>Email</th><th>Role</th><th>Password Hash</th></tr>";
        foreach ($users as $user) {
            echo "<tr>";
            echo "<td>{$user['user_id']}</td>";
            echo "<td>{$user['username']}</td>";
            echo "<td>{$user['email']}</td>";
            echo "<td>{$user['role']}</td>";
            echo "<td style='font-size: 10px;'>{$user['password_hash']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Test password verification
        echo "<h4>Password Test:</h4>";
        $test_password = "password123";
        foreach ($users as $user) {
            $is_valid = password_verify($test_password, $user['password_hash']);
            echo "<p>User {$user['username']}: " . ($is_valid ? "✓ Password match" : "✗ Password NOT match") . "</p>";
        }
        
    } else {
        echo "<p style='color: red'>✗ Database connection failed</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red'>✗ Database error: " . $e->getMessage() . "</p>";
}

// Test session
echo "<h4>Session Test:</h4>";
echo "<pre>Session ID: " . session_id() . "</pre>";
echo "<pre>Session Data: ";
print_r($_SESSION);
echo "</pre>";
?>