<?php
header('Content-Type: application/json');
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $userId = $input['user_id'] ?? null;
    $title = $input['title'] ?? 'Tư vấn thuốc';
    
    if (!$userId) {
        echo json_encode(['success' => false, 'error' => 'Thiếu user_id']);
        exit;
    }
    
    try {
        $stmt = $pdo->prepare("INSERT INTO chat_sessions (user_id, title) VALUES (?, ?)");
        $stmt->execute([$userId, $title]);
        $sessionId = $pdo->lastInsertId();
        
        echo json_encode(['success' => true, 'session_id' => $sessionId]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $userId = $_GET['user_id'] ?? null;
    
    if (!$userId) {
        echo json_encode([]);
        exit;
    }
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM chat_sessions WHERE user_id = ? ORDER BY created_at DESC");
        $stmt->execute([$userId]);
        $sessions = $stmt->fetchAll();
        
        echo json_encode($sessions);
    } catch (PDOException $e) {
        echo json_encode([]);
    }
}
?>