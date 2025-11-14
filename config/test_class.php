<?php
require_once 'database.php';

$database = new Database();
$conn = $database->getConnection();

if ($conn) {
    echo "✅ Database class WORKS! Connection successful.\n";
    
    // Test query
    $stmt = $conn->query("SELECT DATABASE() as db");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "📊 Connected to: " . $result['db'] . "\n";
} else {
    echo "❌ Database class FAILED! Cannot connect.\n";
}
?>
