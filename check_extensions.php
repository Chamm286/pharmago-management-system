<?php
echo "<h2>🔧 Re-checking PHP Extensions</h2>";

// Kiểm tra PDO MySQL
if (extension_loaded('pdo_mysql')) {
    echo "✅ PDO MySQL extension is NOW loaded<br>";
    
    // Test PDO connection
    try {
        $pdo = new PDO("mysql:host=localhost;dbname=pharmaGo", "pharmacy_admin", "");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        echo "✅ PDO Connection SUCCESSFUL!<br>";
    } catch (PDOException $e) {
        echo "❌ PDO Connection failed: " . $e->getMessage() . "<br>";
    }
} else {
    echo "❌ PDO MySQL extension is still NOT loaded<br>";
}

// Kiểm tra MySQLi
if (extension_loaded('mysqli')) {
    echo "✅ MySQLi extension is loaded<br>";
} else {
    echo "❌ MySQLi extension is NOT loaded<br>";
}

// Hiển thị PDO drivers
echo "<h3>PDO Drivers:</h3>";
print_r(PDO::getAvailableDrivers());
?>