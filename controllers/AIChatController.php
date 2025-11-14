<?php
class AIChatController {
    private $geminiApiKey = 'AIzaSyCP5r4l8pUMVQKMtU8tZHfko6RzXj7VQLw'; // Thay bằng key thực của bạn
    private $geminiUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent';
    
    public function __construct() {
        // Set headers for API response
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type');
        
        // Handle preflight request
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            exit(0);
        }
    }
    
    public function handleRequest() {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Method not allowed');
            }
            
            // Get input data
            $input = $this->getInputData();
            $message = $input['message'] ?? '';
            $type = $input['type'] ?? 'text';
            
            if (empty($message)) {
                throw new Exception('Message cannot be empty');
            }
            
            // Process based on type
            if ($type === 'image' && isset($_FILES['image'])) {
                $response = $this->handleImageAnalysis($_FILES['image'], $message);
            } else {
                $response = $this->handleTextMessage($message);
            }
            
            echo json_encode($response);
            
        } catch (Exception $e) {
            error_log("AI Chat Controller Error: " . $e->getMessage());
            echo json_encode([
                'success' => false, 
                'response' => 'Lỗi hệ thống: ' . $e->getMessage()
            ]);
        }
    }
    
    private function getInputData() {
        if (!empty($_POST)) {
            return $_POST;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON input');
        }
        
        return $input ?? [];
    }
    
    private function handleTextMessage($message) {
        $prompt = $this->buildPrompt($message);
        $response = $this->callGeminiAPI($prompt);
        
        return [
            'success' => true, 
            'response' => $response
        ];
    }
    
    private function handleImageAnalysis($imageFile, $question = "") {
        // For now, return a message about image analysis
        // You can implement actual image analysis with Gemini Pro Vision later
        return [
            'success' => true, 
            'response' => "Tính năng phân tích ảnh đang được phát triển. Vui lòng mô tả triệu chứng bằng văn bản để được tư vấn chi tiết hơn.\n\nCâu hỏi của bạn: " . $question
        ];
    }
    
    private function buildPrompt($message) {
        return "Bạn là một trợ lý AI chuyên về tư vấn sức khỏe và thuốc men cho nhà thuốc Pharmacy. 

HÃY LUÔN GHI NHỚ:
1. Bạn là AI tư vấn sức khỏe, KHÔNG PHẢI bác sĩ
2. Tư vấn của bạn chỉ mang tính chất THAM KHẢO
3. LUÔN nhắc người dùng tham khảo ý kiến bác sĩ/dược sĩ trước khi dùng thuốc
4. Với các triệu chứng nghiêm trọng, YÊU CẦU người dùng đến cơ sở y tế ngay lập tức
5. Trả lời bằng tiếng Việt, thân thiện, chuyên nghiệp và dễ hiểu

Câu hỏi từ người dùng: " . $message . "

Hãy cung cấp thông tin hữu ích nhưng luôn kèm theo cảnh báo về việc tham khảo ý kiến chuyên môn.";
    }
    
    private function callGeminiAPI($prompt) {
        $data = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => 0.7,
                'topK' => 40,
                'topP' => 0.95,
                'maxOutputTokens' => 1024,
            ],
            'safetySettings' => [
                [
                    'category' => 'HARM_CATEGORY_HARASSMENT',
                    'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
                ],
                [
                    'category' => 'HARM_CATEGORY_HATE_SPEECH', 
                    'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
                ],
                [
                    'category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT',
                    'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
                ],
                [
                    'category' => 'HARM_CATEGORY_DANGEROUS_CONTENT',
                    'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
                ]
            ]
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->geminiUrl . '?key=' . $this->geminiApiKey);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        if ($curlError) {
            throw new Exception('CURL Error: ' . $curlError);
        }
        
        if ($httpCode !== 200) {
            error_log("Gemini API HTTP Error: " . $httpCode . " - Response: " . $response);
            throw new Exception('API request failed with code: ' . $httpCode);
        }
        
        $result = json_decode($response, true);
        
        if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
            return $result['candidates'][0]['content']['parts'][0]['text'];
        } elseif (isset($result['promptFeedback']['blockReason'])) {
            throw new Exception('Content blocked for safety: ' . $result['promptFeedback']['blockReason']);
        } else {
            error_log("Gemini API Invalid Response: " . $response);
            throw new Exception('Invalid response format from Gemini API');
        }
    }
}

// Instantiate and handle the request
$controller = new AIChatController();
$controller->handleRequest();
?>