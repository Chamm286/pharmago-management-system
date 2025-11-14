<?php
session_start();
require_once '../config/database.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$currentUserId = $_SESSION['user_id'];
$currentUserName = $_SESSION['full_name'] ?? $_SESSION['username'];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hệ thống tư vấn thuốc - PharmaGo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #0d6efd;
            --secondary-color: #6c757d;
            --success-color: #198754;
            --light-color: #f8f9fa;
            --dark-color: #212529;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f7fb;
            color: #333;
        }
        
        .chat-container {
            max-width: 1200px;
            margin: 0 auto;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            border-radius: 10px;
            overflow: hidden;
            background: white;
        }
        
        .chat-header {
            background: linear-gradient(135deg, var(--primary-color), #0a58ca);
            color: white;
            padding: 15px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .chat-header .pharmacist-info {
            display: flex;
            align-items: center;
        }
        
        .chat-header .pharmacist-info img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 10px;
            border: 2px solid rgba(255, 255, 255, 0.5);
        }
        
        .chat-messages {
            height: 500px;
            overflow-y: auto;
            padding: 20px;
            background-color: #f8f9fa;
        }
        
        .message {
            margin-bottom: 15px;
            display: flex;
            align-items: flex-start;
        }
        
        .message.user {
            justify-content: flex-end;
        }
        
        .message.bot {
            justify-content: flex-start;
        }
        
        .message-content {
            max-width: 70%;
            padding: 12px 16px;
            border-radius: 18px;
            position: relative;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        
        .message.user .message-content {
            background-color: var(--primary-color);
            color: white;
            border-bottom-right-radius: 4px;
        }
        
        .message.bot .message-content {
            background-color: white;
            color: var(--dark-color);
            border-bottom-left-radius: 4px;
            border: 1px solid #e9ecef;
        }
        
        .message-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            margin: 0 10px;
            flex-shrink: 0;
        }
        
        .message-time {
            font-size: 0.75rem;
            color: var(--secondary-color);
            margin-top: 5px;
            text-align: right;
        }
        
        .message.bot .message-time {
            text-align: left;
        }
        
        .chat-input-container {
            padding: 15px;
            border-top: 1px solid #e9ecef;
            background-color: white;
        }
        
        .chat-input {
            border-radius: 25px;
            padding: 12px 20px;
            border: 1px solid #ced4da;
            resize: none;
        }
        
        .chat-input:focus {
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
            border-color: #86b7fe;
        }
        
        .send-button {
            border-radius: 50%;
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: var(--primary-color);
            color: white;
            border: none;
            margin-left: 10px;
            transition: all 0.3s;
        }
        
        .send-button:hover {
            background-color: #0b5ed7;
            transform: scale(1.05);
        }
        
        .send-button:disabled {
            background-color: var(--secondary-color);
            cursor: not-allowed;
            transform: none;
        }
        
        .typing-indicator {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
            opacity: 0;
            transition: opacity 0.3s;
        }
        
        .typing-indicator.active {
            opacity: 1;
        }
        
        .typing-indicator .message-avatar {
            margin-right: 10px;
        }
        
        .typing-dots {
            display: flex;
            padding: 10px 15px;
            background-color: white;
            border-radius: 18px;
            border-bottom-left-radius: 4px;
            border: 1px solid #e9ecef;
        }
        
        .typing-dots span {
            height: 8px;
            width: 8px;
            border-radius: 50%;
            background-color: #bbb;
            margin: 0 2px;
            animation: typing 1.5s infinite ease-in-out;
        }
        
        .typing-dots span:nth-child(1) {
            animation-delay: 0s;
        }
        
        .typing-dots span:nth-child(2) {
            animation-delay: 0.2s;
        }
        
        .typing-dots span:nth-child(3) {
            animation-delay: 0.4s;
        }
        
        @keyframes typing {
            0%, 60%, 100% {
                transform: translateY(0);
            }
            30% {
                transform: translateY(-5px);
            }
        }
        
        .suggested-questions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 15px;
        }
        
        .suggested-question {
            background-color: #e9ecef;
            border: none;
            border-radius: 20px;
            padding: 8px 15px;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .suggested-question:hover {
            background-color: #dee2e6;
        }
        
        .chat-history {
            max-height: 300px;
            overflow-y: auto;
            border: 1px solid #e9ecef;
            border-radius: 5px;
            margin-top: 20px;
            padding: 10px;
            background-color: white;
        }
        
        .history-item {
            padding: 8px 12px;
            border-bottom: 1px solid #e9ecef;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        
        .history-item:hover {
            background-color: #f8f9fa;
        }
        
        .history-item:last-child {
            border-bottom: none;
        }
        
        .history-date {
            font-size: 0.8rem;
            color: var(--secondary-color);
        }
        
        .history-preview {
            font-size: 0.9rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .faq-section {
            margin-top: 40px;
            padding: 30px 0;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.05);
        }
        
        .faq-item {
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 1px solid #e9ecef;
        }
        
        .faq-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        
        .faq-item h4 {
            color: var(--primary-color);
            font-size: 1.1rem;
            margin-bottom: 10px;
        }
        
        .faq-item p {
            color: var(--secondary-color);
            line-height: 1.6;
        }
        
        @media (max-width: 768px) {
            .chat-messages {
                height: 400px;
            }
            
            .message-content {
                max-width: 85%;
            }
        }

        .medicine-suggestion {
            background: #e7f3ff;
            border: 1px solid #b6d4fe;
            border-radius: 10px;
            padding: 15px;
            margin: 10px 0;
        }

        .medicine-card {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 12px;
            margin: 8px 0;
            background: white;
        }

        .medicine-name {
            font-weight: bold;
            color: #0d6efd;
        }

        .medicine-price {
            color: #198754;
            font-weight: bold;
        }

        .warning-message {
            background: #fff3cd;
            border: 1px solid #ffecb5;
            border-radius: 8px;
            padding: 12px;
            margin: 10px 0;
            color: #856404;
        }
    </style>
</head>
<body>
    <div class="container my-5">
        <div class="chat-container">
            <div class="chat-header">
                <div class="pharmacist-info">
                    <img src="https://randomuser.me/api/portraits/women/43.jpg" alt="Dược sĩ">
                    <div>
                        <h5 class="mb-0">Dược sĩ AI tư vấn</h5>
                        <small class="opacity-75">Xin chào, <?php echo htmlspecialchars($currentUserName); ?>!</small>
                    </div>
                </div>
                <div>
                    <button class="btn btn-sm btn-outline-light" id="historyToggle">
                        <i class="fas fa-history me-1"></i> Lịch sử
                    </button>
                    <button class="btn btn-sm btn-outline-light ms-2" id="newChatBtn">
                        <i class="fas fa-plus me-1"></i> Chat mới
                    </button>
                    <a href="index.php" class="btn btn-sm btn-outline-light ms-2">
                        <i class="fas fa-home me-1"></i> Trang chủ
                    </a>
                </div>
            </div>
            
            <div class="chat-messages" id="chatMessages">
                <div class="message bot">
                    <img src="https://randomuser.me/api/portraits/women/43.jpg" alt="Dược sĩ" class="message-avatar">
                    <div>
                        <div class="message-content">
                            <p class="mb-0">Xin chào <strong><?php echo htmlspecialchars($currentUserName); ?></strong>! Tôi là trợ lý AI tư vấn thuốc. Tôi có thể giúp bạn tìm hiểu thông tin về thuốc, cách sử dụng, tác dụng phụ từ cơ sở dữ liệu thực tế. Bạn cần hỗ trợ gì hôm nay?</p>
                        </div>
                        <div class="message-time" id="currentTime"></div>
                    </div>
                </div>
                
                <div class="typing-indicator" id="typingIndicator">
                    <img src="https://randomuser.me/api/portraits/women/43.jpg" alt="Dược sĩ" class="message-avatar">
                    <div class="typing-dots">
                        <span></span>
                        <span></span>
                        <span></span>
                    </div>
                </div>
            </div>
            
            <div class="chat-input-container">
                <div class="d-flex align-items-end">
                    <textarea class="form-control chat-input" id="messageInput" placeholder="Nhập câu hỏi của bạn về thuốc hoặc sức khỏe..." rows="1"></textarea>
                    <button class="send-button" id="sendButton" disabled>
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </div>
                
                <div class="suggested-questions">
                    <button class="suggested-question" data-question="Tôi bị đau đầu nên uống thuốc gì?">Tôi bị đau đầu nên uống thuốc gì?</button>
                    <button class="suggested-question" data-question="Cách sử dụng paracetamol đúng cách?">Cách sử dụng paracetamol</button>
                    <button class="suggested-question" data-question="Thuốc kháng sinh cần lưu ý gì?">Lưu ý thuốc kháng sinh</button>
                    <button class="suggested-question" data-question="Triệu chứng dị ứng thời tiết?">Triệu chứng dị ứng</button>
                </div>
                
                <div class="chat-history" id="chatHistory" style="display: none;">
                    <div class="text-center text-muted py-3" id="historyLoading">
                        <div class="spinner-border spinner-border-sm me-2"></div>
                        Đang tải lịch sử...
                    </div>
                    <div id="historyList"></div>
                </div>
            </div>
        </div>
        
        <section class="faq-section mt-5">
            <div class="container">
                <h2 class="text-center mb-5">Câu hỏi thường gặp</h2>
                <div class="row">
                    <div class="col-md-6">
                        <div class="faq-item">
                            <h4><i class="fas fa-question-circle text-success me-2"></i> Làm sao để đặt hàng?</h4>
                            <p>Bạn có thể đặt hàng trực tiếp trên website bằng cách thêm sản phẩm vào giỏ hàng và tiến hành thanh toán. Hoặc gọi điện đến hotline 1800.xxxx để được hỗ trợ đặt hàng.</p>
                        </div>
                        <div class="faq-item">
                            <h4><i class="fas fa-question-circle text-success me-2"></i> Thời gian giao hàng bao lâu?</h4>
                            <p>Đối với khu vực nội thành: giao trong ngày nếu đặt trước 17h. Ngoại thành: 1-2 ngày làm việc. Các tỉnh thành khác: 2-5 ngày tùy địa chỉ.</p>
                        </div>
                        <div class="faq-item">
                            <h4><i class="fas fa-question-circle text-success me-2"></i> Có được kiểm tra hàng trước khi nhận không?</h4>
                            <p>Bạn hoàn toàn có quyền kiểm tra hàng hóa trước khi thanh toán. Chỉ thanh toán khi hài lòng với sản phẩm.</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="faq-item">
                            <h4><i class="fas fa-question-circle text-success me-2"></i> Chính sách đổi trả như thế nào?</h4>
                            <p>Chúng tôi chấp nhận đổi trả trong vòng 7 ngày nếu sản phẩm còn nguyên seal, hộp, chưa qua sử dụng và có hóa đơn mua hàng.</p>
                        </div>
                        <div class="faq-item">
                            <h4><i class="fas fa-question-circle text-success me-2"></i> Có cần đơn thuốc để mua thuốc kê đơn?</h4>
                            <p>Đối với thuốc kê đơn, bạn cần cung cấp đơn thuốc từ bác sĩ. Chúng tôi sẽ kiểm tra đơn thuốc trước khi bán để đảm bảo an toàn cho bạn.</p>
                        </div>
                        <div class="faq-item">
                            <h4><i class="fas fa-question-circle text-success me-2"></i> Có tư vấn sử dụng thuốc không?</h4>
                            <p>Đội ngũ dược sĩ của chúng tôi luôn sẵn sàng tư vấn cách sử dụng thuốc an toàn, hiệu quả qua hotline hoặc chat trực tuyến.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Biến toàn cục
        let currentSessionId = null;
        let currentUserId = <?php echo $currentUserId; ?>;

        document.addEventListener('DOMContentLoaded', function() {
            const messageInput = document.getElementById('messageInput');
            const sendButton = document.getElementById('sendButton');
            const chatMessages = document.getElementById('chatMessages');
            const typingIndicator = document.getElementById('typingIndicator');
            const historyToggle = document.getElementById('historyToggle');
            const chatHistory = document.getElementById('chatHistory');
            const newChatBtn = document.getElementById('newChatBtn');
            const suggestedQuestions = document.querySelectorAll('.suggested-question');
            const currentTimeElement = document.getElementById('currentTime');
            
            // Hiển thị thời gian hiện tại
            const now = new Date();
            currentTimeElement.textContent = now.toLocaleTimeString('vi-VN', { 
                hour: '2-digit', 
                minute: '2-digit' 
            });
            
            // Khởi tạo chat session
            initializeChat();
            
            // Tự động điều chỉnh chiều cao của textarea
            messageInput.addEventListener('input', function() {
                this.style.height = 'auto';
                this.style.height = (this.scrollHeight) + 'px';
                sendButton.disabled = this.value.trim() === '';
            });
            
            // Gửi tin nhắn khi nhấn nút
            sendButton.addEventListener('click', sendMessage);
            
            // Gửi tin nhắn khi nhấn Enter
            messageInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    sendMessage();
                }
            });
            
            // Sử dụng câu hỏi gợi ý
            suggestedQuestions.forEach(button => {
                button.addEventListener('click', function() {
                    messageInput.value = this.getAttribute('data-question');
                    messageInput.dispatchEvent(new Event('input'));
                    sendMessage();
                });
            });
            
            // Hiển thị/ẩn lịch sử chat
            historyToggle.addEventListener('click', function() {
                if (chatHistory.style.display === 'none') {
                    chatHistory.style.display = 'block';
                    this.innerHTML = '<i class="fas fa-times me-1"></i> Đóng';
                    loadChatSessions();
                } else {
                    chatHistory.style.display = 'none';
                    this.innerHTML = '<i class="fas fa-history me-1"></i> Lịch sử';
                }
            });
            
            // Tạo chat mới
            newChatBtn.addEventListener('click', function() {
                createNewChatSession();
            });

            // Khởi tạo chat session
            async function initializeChat() {
                try {
                    const response = await fetch('../api/chat_sessions.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ 
                            user_id: currentUserId,
                            title: 'Tư vấn thuốc ' + new Date().toLocaleDateString('vi-VN')
                        })
                    });
                    
                    const data = await response.json();
                    if (data.success) {
                        currentSessionId = data.session_id;
                        console.log('Chat session created:', currentSessionId);
                    }
                } catch (error) {
                    console.error('Lỗi khởi tạo chat:', error);
                    // Fallback: tạo session ID tạm thời
                    currentSessionId = 'temp_' + Date.now();
                }
            }

            // Tạo chat session mới
            async function createNewChatSession() {
                try {
                    const response = await fetch('../api/chat_sessions.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ 
                            user_id: currentUserId,
                            title: 'Tư vấn thuốc ' + new Date().toLocaleDateString('vi-VN')
                        })
                    });
                    
                    const data = await response.json();
                    if (data.success) {
                        currentSessionId = data.session_id;
                        
                        // Xóa tin nhắn cũ, chỉ giữ tin nhắn chào mừng
                        const messages = chatMessages.querySelectorAll('.message');
                        messages.forEach((msg, index) => {
                            if (index > 0) msg.remove(); // Giữ lại tin nhắn đầu tiên (chào mừng)
                        });
                        
                        addMessage('Đã tạo cuộc trò chuyện mới. Bạn có câu hỏi gì về thuốc không?', 'bot');
                    }
                } catch (error) {
                    console.error('Lỗi tạo chat mới:', error);
                }
            }

            // Load danh sách chat sessions
            async function loadChatSessions() {
                try {
                    const response = await fetch(`../api/chat_sessions.php?user_id=${currentUserId}`);
                    const sessions = await response.json();
                    
                    const historyList = document.getElementById('historyList');
                    const historyLoading = document.getElementById('historyLoading');
                    
                    historyLoading.style.display = 'none';
                    
                    if (sessions.length === 0) {
                        historyList.innerHTML = '<div class="text-center text-muted py-3">Chưa có lịch sử chat</div>';
                        return;
                    }
                    
                    historyList.innerHTML = sessions.map(session => `
                        <div class="history-item" data-session-id="${session.id}">
                            <div class="history-date">${new Date(session.created_at).toLocaleDateString('vi-VN')}</div>
                            <div class="history-preview">${session.title}</div>
                        </div>
                    `).join('');
                    
                    // Thêm event listeners cho các item lịch sử
                    document.querySelectorAll('.history-item').forEach(item => {
                        item.addEventListener('click', function() {
                            const sessionId = this.getAttribute('data-session-id');
                            loadChatHistory(sessionId);
                        });
                    });
                    
                } catch (error) {
                    console.error('Lỗi tải lịch sử:', error);
                    document.getElementById('historyLoading').innerHTML = 'Lỗi tải lịch sử';
                }
            }

            // Load lịch sử chat của session cụ thể
            async function loadChatHistory(sessionId) {
                try {
                    const response = await fetch(`../api/chat_messages.php?session_id=${sessionId}`);
                    const messages = await response.json();
                    
                    // Xóa tất cả tin nhắn hiện tại
                    chatMessages.innerHTML = '';
                    
                    // Thêm tin nhắn chào mừng
                    const welcomeMsg = document.createElement('div');
                    welcomeMsg.className = 'message bot';
                    welcomeMsg.innerHTML = `
                        <img src="https://randomuser.me/api/portraits/women/43.jpg" alt="Dược sĩ" class="message-avatar">
                        <div>
                            <div class="message-content">
                                <p class="mb-0">Đang xem lịch sử chat</p>
                            </div>
                            <div class="message-time">${new Date().toLocaleTimeString('vi-VN')}</div>
                        </div>
                    `;
                    chatMessages.appendChild(welcomeMsg);
                    
                    // Thêm các tin nhắn từ lịch sử
                    messages.forEach(msg => {
                        addMessage(msg.message_text, msg.message_type, false);
                    });
                    
                    // Thêm typing indicator
                    chatMessages.appendChild(typingIndicator);
                    
                    currentSessionId = sessionId;
                    chatHistory.style.display = 'none';
                    historyToggle.innerHTML = '<i class="fas fa-history me-1"></i> Lịch sử';
                    
                    scrollToBottom();
                    
                } catch (error) {
                    console.error('Lỗi tải tin nhắn:', error);
                }
            }
            
            // Hàm gửi tin nhắn
            async function sendMessage() {
                const message = messageInput.value.trim();
                if (message === '' || !currentSessionId) return;
                
                // Thêm tin nhắn của người dùng
                addMessage(message, 'user');
                
                // Xóa nội dung input
                messageInput.value = '';
                messageInput.style.height = 'auto';
                sendButton.disabled = true;
                
                // Hiển thị indicator đang nhập
                showTypingIndicator();
                
                try {
                    const response = await fetch('../api/chat_messages.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            session_id: currentSessionId,
                            user_id: currentUserId,
                            message: message
                        })
                    });
                    
                    const data = await response.json();
                    hideTypingIndicator();
                    
                    if (data.success) {
                        addMessage(data.bot_response, 'bot');
                    } else {
                        addMessage('Xin lỗi, có lỗi xảy ra. Vui lòng thử lại.', 'bot');
                    }
                    
                } catch (error) {
                    hideTypingIndicator();
                    addMessage('Xin lỗi, có lỗi kết nối. Vui lòng thử lại.', 'bot');
                    console.error('Lỗi gửi tin nhắn:', error);
                }
                
                scrollToBottom();
            }
            
            // Thêm tin nhắn vào giao diện
            function addMessage(text, sender, scroll = true) {
                const messageDiv = document.createElement('div');
                messageDiv.className = `message ${sender}`;
                
                const time = new Date().toLocaleTimeString('vi-VN', { 
                    hour: '2-digit', 
                    minute: '2-digit' 
                });
                
                if (sender === 'user') {
                    messageDiv.innerHTML = `
                        <div>
                            <div class="message-content">
                                <p class="mb-0">${text}</p>
                            </div>
                            <div class="message-time">${time}</div>
                        </div>
                        <img src="https://randomuser.me/api/portraits/men/32.jpg" alt="Người dùng" class="message-avatar">
                    `;
                } else {
                    messageDiv.innerHTML = `
                        <img src="https://randomuser.me/api/portraits/women/43.jpg" alt="Dược sĩ" class="message-avatar">
                        <div>
                            <div class="message-content">
                                <p class="mb-0">${text}</p>
                            </div>
                            <div class="message-time">${time}</div>
                        </div>
                    `;
                }
                
                chatMessages.insertBefore(messageDiv, typingIndicator);
                if (scroll) scrollToBottom();
            }
            
            // Hiển thị indicator đang nhập
            function showTypingIndicator() {
                typingIndicator.classList.add('active');
                scrollToBottom();
            }
            
            // Ẩn indicator đang nhập
            function hideTypingIndicator() {
                typingIndicator.classList.remove('active');
            }
            
            // Cuộn xuống cuối chat
            function scrollToBottom() {
                chatMessages.scrollTop = chatMessages.scrollHeight;
            }
        });
    </script>
</body>
</html>