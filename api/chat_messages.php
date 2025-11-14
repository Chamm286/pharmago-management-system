<?php
header('Content-Type: application/json');
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $sessionId = $input['session_id'] ?? null;
    $userId = $input['user_id'] ?? null;
    $message = $input['message'] ?? '';
    
    if (!$sessionId || !$userId || empty($message)) {
        echo json_encode(['success' => false, 'error' => 'Thiếu thông tin']);
        exit;
    }
    
    try {
        // Lưu tin nhắn người dùng
        $stmt = $pdo->prepare("INSERT INTO chat_messages (session_id, user_id, message_text, message_type) VALUES (?, ?, ?, 'user')");
        $stmt->execute([$sessionId, $userId, $message]);
        
        // Xử lý tin nhắn và tạo phản hồi
        $botResponse = generateBotResponse($message, $pdo);
        
        // Lưu phản hồi của bot
        $stmt = $pdo->prepare("INSERT INTO chat_messages (session_id, user_id, message_text, message_type) VALUES (?, ?, ?, 'bot')");
        $stmt->execute([$sessionId, $userId, $botResponse]);
        
        echo json_encode(['success' => true, 'bot_response' => $botResponse]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $sessionId = $_GET['session_id'] ?? null;
    
    if (!$sessionId) {
        echo json_encode([]);
        exit;
    }
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM chat_messages WHERE session_id = ? ORDER BY created_at ASC");
        $stmt->execute([$sessionId]);
        $messages = $stmt->fetchAll();
        
        echo json_encode($messages);
    } catch (PDOException $e) {
        echo json_encode([]);
    }
}

function generateBotResponse($message, $pdo) {
    $message = strtolower(trim($message));
    
    // Kiểm tra các từ khóa và tạo phản hồi phù hợp
    if (strpos($message, 'đau đầu') !== false || strpos($message, 'nhức đầu') !== false) {
        return getPainRelievers($pdo);
    } elseif (strpos($message, 'paracetamol') !== false) {
        return getParacetamolInfo($pdo);
    } elseif (strpos($message, 'kháng sinh') !== false) {
        return getAntibioticInfo($pdo);
    } elseif (strpos($message, 'dị ứng') !== false) {
        return getAllergyInfo($pdo);
    } elseif (strpos($message, 'vitamin') !== false) {
        return getVitaminInfo($pdo);
    } elseif (strpos($message, 'cảm') !== false || strpos($message, 'sốt') !== false) {
        return getColdFeverInfo($pdo);
    } elseif (strpos($message, 'dạ dày') !== false || strpos($message, 'tiêu hóa') !== false) {
        return getStomachInfo($pdo);
    } else {
        return "Tôi hiểu bạn đang hỏi về: \"$message\". Để tư vấn chính xác hơn, bạn có thể mô tả rõ hơn về triệu chứng hoặc loại thuốc bạn quan tâm không?";
    }
}

function getPainRelievers($pdo) {
    try {
        $stmt = $pdo->prepare("
            SELECT product_name, short_description, price, image_url 
            FROM products 
            WHERE category_id = 2 AND is_active = 1 
            LIMIT 3
        ");
        $stmt->execute();
        $products = $stmt->fetchAll();
        
        if (empty($products)) {
            return "Đối với triệu chứng đau đầu, bạn có thể tham khảo các thuốc giảm đau thông dụng như Paracetamol. Tuy nhiên, nếu đau đầu kéo dài, bạn nên đi khám bác sĩ để được chẩn đoán chính xác.";
        }
        
        $response = "Dựa trên triệu chứng đau đầu của bạn, tôi gợi ý một số thuốc:\n\n";
        foreach ($products as $product) {
            $response .= "💊 <strong>{$product['product_name']}</strong>\n";
            $response .= "📝 {$product['short_description']}\n";
            $response .= "💰 " . number_format($product['price']) . " VNĐ\n\n";
        }
        $response .= "⚠️ <em>Lưu ý: Đây chỉ là gợi ý tham khảo. Vui lòng tham khảo ý kiến dược sĩ trước khi sử dụng.</em>";
        
        return $response;
    } catch (PDOException $e) {
        return "Tôi khuyên bạn nên dùng Paracetamol 500mg cho triệu chứng đau đầu thông thường. Liều dùng: 1-2 viên mỗi 4-6 giờ khi cần. Không vượt quá 4g/ngày.";
    }
}

function getParacetamolInfo($pdo) {
    try {
        $stmt = $pdo->prepare("
            SELECT product_name, short_description, price, usage_instruction 
            FROM products 
            WHERE product_name LIKE '%paracetamol%' AND is_active = 1
            LIMIT 1
        ");
        $stmt->execute();
        $product = $stmt->fetch();
        
        if ($product) {
            $response = "Thông tin về <strong>{$product['product_name']}</strong>:\n\n";
            $response .= "📝 {$product['short_description']}\n";
            $response .= "💰 " . number_format($product['price']) . " VNĐ\n\n";
            $response .= "💊 <strong>Công dụng:</strong> Giảm đau, hạ sốt\n";
            $response .= "📋 <strong>Liều dùng:</strong> Người lớn 1-2 viên 500mg mỗi 4-6 giờ\n";
            $response .= "🚫 <strong>Không vượt quá:</strong> 4g (8 viên)/ngày\n";
            $response .= "⚠️ <strong>Thận trọng:</strong> Người bệnh gan, nghiện rượu\n\n";
            $response .= "<em>Tham khảo ý kiến dược sĩ để được tư vấn cụ thể</em>";
            
            return $response;
        }
        
        return "Thông tin về Paracetamol:\n\n" .
               "• Công dụng: Giảm đau, hạ sốt\n" .
               "• Liều dùng: Người lớn 1-2 viên 500mg mỗi 4-6 giờ\n" .
               "• Không vượt quá 4g (8 viên)/ngày\n" .
               "• Thận trọng: Người bệnh gan, nghiện rượu\n" .
               "• Tác dụng phụ: Hiếm gặp, có thể gây phát ban\n\n" .
               "⚠️ <em>Tham khảo ý kiến dược sĩ để được tư vấn cụ thể</em>";
    } catch (PDOException $e) {
        return "Thông tin về Paracetamol:\n\n" .
               "• Công dụng: Giảm đau, hạ sốt\n" .
               "• Liều dùng: Người lớn 1-2 viên 500mg mỗi 4-6 giờ\n" .
               "• Không vượt quá 4g (8 viên)/ngày\n" .
               "• Thận trọng: Người bệnh gan, nghiện rượu\n\n" .
               "⚠️ <em>Tham khảo ý kiến dược sĩ để được tư vấn cụ thể</em>";
    }
}

function getAntibioticInfo($pdo) {
    try {
        $stmt = $pdo->prepare("
            SELECT product_name, short_description, price, prescription_required 
            FROM products 
            WHERE category_id = 1 AND is_active = 1 
            LIMIT 2
        ");
        $stmt->execute();
        $antibiotics = $stmt->fetchAll();
        
        $response = "🔴 <strong>THÔNG TIN QUAN TRỌNG VỀ THUỐC KHÁNG SINH</strong>\n\n";
        $response .= "🚫 <strong>KHÔNG TỰ Ý SỬ DỤNG KHÁNG SINH</strong>\n";
        $response .= "• Chỉ dùng khi có chỉ định của bác sĩ\n";
        $response .= "• Uống đủ liều, đủ thời gian\n";
        $response .= "• Không ngưng thuốc giữa chừng\n";
        $response .= "• Tuân thủ hướng dẫn về thời gian uống\n\n";
        
        if (!empty($antibiotics)) {
            $response .= "Một số kháng sinh phổ biến:\n";
            foreach ($antibiotics as $ab) {
                $prescription = $ab['prescription_required'] ? '🟢 Cần đơn thuốc' : '🔵 Không cần đơn';
                $response .= "💊 {$ab['product_name']} - " . number_format($ab['price']) . " VNĐ - $prescription\n";
            }
        }
        
        $response .= "\n⚠️ <em>Lạm dụng kháng sinh dẫn đến kháng thuốc nguy hiểm</em>";
        
        return $response;
    } catch (PDOException $e) {
        return "🔴 <strong>THÔNG TIN QUAN TRỌNG VỀ THUỐC KHÁNG SINH</strong>\n\n" .
               "🚫 KHÔNG TỰ Ý SỬ DỤNG KHÁNG SINH\n" .
               "• Chỉ dùng khi có chỉ định của bác sĩ\n" .
               "• Uống đủ liều, đủ thời gian\n" .
               "• Không ngưng thuốc giữa chừng\n" .
               "• Tuân thủ hướng dẫn về thời gian uống\n\n" .
               "⚠️ <em>Lạm dụng kháng sinh dẫn đến kháng thuốc nguy hiểm</em>";
    }
}

function getAllergyInfo($pdo) {
    return "Triệu chứng dị ứng thời tiết thường gặp:\n\n" .
           "• Hắt hơi, sổ mũi, ngứa mũi\n" .
           "• Ngứa mắt, chảy nước mắt\n" .
           "• Phát ban, mề đay\n" .
           "• Khó thở (cần đi khám ngay)\n\n" .
           "Điều trị:\n" .
           "• Tránh tiếp xúc dị nguyên\n" .
           "• Thuốc kháng histamine\n" .
           "• Thuốc xịt mũi corticosteroid\n" .
           "• Thuốc bôi ngoài da\n\n" .
           "⚠️ <em>Nên đi khám để được chẩn đoán và điều trị phù hợp</em>";
}

function getVitaminInfo($pdo) {
    try {
        $stmt = $pdo->prepare("
            SELECT product_name, short_description, price 
            FROM products 
            WHERE category_id = 4 AND is_active = 1 
            LIMIT 3
        ");
        $stmt->execute();
        $vitamins = $stmt->fetchAll();
        
        $response = "🌿 <strong>Các loại vitamin và thực phẩm chức năng phổ biến:</strong>\n\n";
        foreach ($vitamins as $vitamin) {
            $response .= "💊 <strong>{$vitamin['product_name']}</strong>\n";
            $response .= "📝 {$vitamin['short_description']}\n";
            $response .= "💰 " . number_format($vitamin['price']) . " VNĐ\n\n";
        }
        $response .= "💡 <em>Nên bổ sung vitamin theo nhu cầu cơ thể và tư vấn của chuyên gia</em>";
        
        return $response;
    } catch (PDOException $e) {
        return "🌿 <strong>Vitamin giúp bổ sung dưỡng chất thiết yếu:</strong>\n\n" .
               "• Vitamin C: Tăng sức đề kháng\n" .
               "• Vitamin D3: Tốt cho xương\n" .
               "• Vitamin B Complex: Hỗ trợ thần kinh\n\n" .
               "💡 <em>Nên bổ sung vitamin theo nhu cầu cơ thể</em>";
    }
}

function getColdFeverInfo($pdo) {
    try {
        $stmt = $pdo->prepare("
            SELECT product_name, short_description, price 
            FROM products 
            WHERE (category_id = 2 OR product_name LIKE '%paracetamol%') AND is_active = 1 
            LIMIT 2
        ");
        $stmt->execute();
        $products = $stmt->fetchAll();
        
        $response = "🤒 <strong>Đối với triệu chứng cảm, sốt:</strong>\n\n";
        $response .= "• Nghỉ ngơi nhiều, uống đủ nước\n";
        $response .= "• Có thể sử dụng thuốc hạ sốt khi sốt trên 38.5°C\n";
        $response .= "• Theo dõi nhiệt độ thường xuyên\n\n";
        
        if (!empty($products)) {
            $response .= "Thuốc có thể tham khảo:\n";
            foreach ($products as $product) {
                $response .= "💊 {$product['product_name']} - " . number_format($product['price']) . " VNĐ\n";
            }
        }
        
        $response .= "\n⚠️ <em>Nếu sốt cao kéo dài, nên đi khám bác sĩ</em>";
        
        return $response;
    } catch (PDOException $e) {
        return "🤒 <strong>Đối với triệu chứng cảm, sốt:</strong>\n\n" .
               "• Nghỉ ngơi nhiều, uống đủ nước\n" .
               "• Có thể dùng Paracetamol khi sốt trên 38.5°C\n" .
               "• Liều Paracetamol: 1-2 viên 500mg mỗi 4-6 giờ\n" .
               "• Không vượt quá 4g/ngày\n\n" .
               "⚠️ <em>Nếu sốt cao kéo dài, nên đi khám bác sĩ</em>";
    }
}

function getStomachInfo($pdo) {
    try {
        $stmt = $pdo->prepare("
            SELECT product_name, short_description, price 
            FROM products 
            WHERE category_id = 3 AND is_active = 1 
            LIMIT 2
        ");
        $stmt->execute();
        $products = $stmt->fetchAll();
        
        $response = "🤢 <strong>Đối với vấn đề dạ dày, tiêu hóa:</strong>\n\n";
        $response .= "• Ăn uống điều độ, tránh thức ăn cay nóng\n";
        $response .= "• Hạn chế rượu bia, cà phê\n";
        $response .= "• Ăn chín uống sôi\n\n";
        
        if (!empty($products)) {
            $response .= "Thuốc hỗ trợ tiêu hóa:\n";
            foreach ($products as $product) {
                $response .= "💊 {$product['product_name']} - " . number_format($product['price']) . " VNĐ\n";
            }
        }
        
        $response .= "\n⚠️ <em>Nếu đau dạ dày kéo dài, nên nội soi để chẩn đoán chính xác</em>";
        
        return $response;
    } catch (PDOException $e) {
        return "🤢 <strong>Đối với vấn đề dạ dày, tiêu hóa:</strong>\n\n" .
               "• Ăn uống điều độ, tránh thức ăn cay nóng\n" .
               "• Hạn chế rượu bia, cà phê\n" .
               "• Có thể dùng Omeprazole cho viêm loét dạ dày\n" .
               "• Domperidone hỗ trợ tiêu hóa, chống nôn\n\n" .
               "⚠️ <em>Nếu đau dạ dày kéo dài, nên nội soi để chẩn đoán chính xác</em>";
    }
}
?>