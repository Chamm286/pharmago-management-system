<?php
// views/frontend/chat.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat với AI - Pharmacy</title>
    <link rel="icon" type="image/x-icon" href="/PHARMAGO/public/assets/images/favicon.ico">
    
    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/PHARMAGO/public/assets/css/about.css">
    <style>
        .chat-page {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .chat-container {
            height: 80vh;
            margin-top: 5vh;
        }
    </style>
</head>
<body class="chat-page">
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white">
        <div class="container">
            <a class="navbar-brand fw-bold" href="/PHARMAGO/public/">
                <i class="fas fa-prescription-bottle-alt me-2"></i>Pharmacy
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="/PHARMAGO/public/">
                    <i class="fas fa-home me-1"></i>Trang chủ
                </a>
                <a class="nav-link" href="/PHARMAGO/public/about">
                    <i class="fas fa-robot me-1"></i>Về AI
                </a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="text-center text-white mb-4">
                    <h1 class="display-5 fw-bold">Trợ Lý AI Tư Vấn Sức Khỏe</h1>
                    <p class="lead">Được hỗ trợ bởi Google Gemini AI</p>
                </div>
                
                <!-- AI Chat Container -->
                <div class="ai-chat-container">
                    <div class="ai-chat-header">
                        <div class="ai-avatar">
                            <i class="fas fa-robot"></i>
                        </div>
                        <div class="ai-info">
                            <h5 class="mb-1 text-white">Pharmacy AI Assistant</h5>
                            <span class="status online">Powered by Gemini AI</span>
                        </div>
                        <div class="ai-actions">
                            <button class="btn btn-sm btn-light me-2" onclick="startVoiceRecognition()" title="Nhận diện giọng nói">
                                <i class="fas fa-microphone"></i>
                            </button>
                            <button class="btn btn-sm btn-light" onclick="clearChat()" title="Xóa chat">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="ai-chat-messages" id="aiChatMessages">
                        <div class="message ai-message">
                            <div class="message-content">
                                <strong>Xin chào! 👋</strong><br><br>
                                Tôi là trợ lý AI của Pharmacy, được hỗ trợ bởi <strong>Google Gemini AI</strong>. 
                                Tôi có thể giúp bạn tư vấn về:
                                <br>• Thuốc và sức khỏe
                                <br>• Hướng dẫn sử dụng thuốc
                                <br>• Thông tin tác dụng phụ
                                <br>• Gợi ý sản phẩm phù hợp
                                <br><br>
                                Hãy cho tôi biết tình trạng sức khỏe của bạn!
                            </div>
                        </div>
                    </div>
                    
                    <div class="ai-chat-input-container">
                        <div class="ai-chat-input">
                            <textarea id="aiMessageInput" placeholder="Nhập câu hỏi về sức khỏe của bạn hoặc sử dụng giọng nói..." rows="2"></textarea>
                            <div class="chat-buttons">
                                <button class="btn btn-sm btn-outline-primary me-2" onclick="startVoiceRecognition()" title="Nhận diện giọng nói">
                                    <i class="fas fa-microphone"></i>
                                </button>
                                <button id="sendAiMessage" class="btn btn-primary">
                                    <i class="fas fa-paper-plane"></i>
                                </button>
                            </div>
                        </div>
                        <div class="voice-status" id="voiceStatus" style="display: none;">
                            <i class="fas fa-microphone text-primary me-2"></i>
                            <span id="voiceStatusText">Đang nghe...</span>
                        </div>
                    </div>
                    
                    <input type="file" id="imageInput" accept="image/*" style="display: none;" onchange="handleImageUpload(this.files[0])">
                    
                    <div class="ai-disclaimer">
                        <i class="fas fa-exclamation-triangle text-warning me-2"></i>
                        <small>Tư vấn AI chỉ mang tính tham khảo. Vui lòng tham khảo ý kiến bác sĩ cho các vấn đề nghiêm trọng.</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/PHARMAGO/public/assets/js/about.js"></script>
</body>
</html>