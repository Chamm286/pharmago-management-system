// about.js - JavaScript cho trang About với chat AI

class PharmacyAIChat {
    constructor() {
        this.isTyping = false;
        this.init();
    }

    init() {
        this.bindEvents();
        this.initScrollTop();
        this.initQuickNav();
        this.initTimeline();
    }

    bindEvents() {
        // Send message
        const sendButton = document.getElementById('sendAiMessage');
        const messageInput = document.getElementById('aiMessageInput');

        sendButton.addEventListener('click', () => this.sendMessage());
        messageInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                this.sendMessage();
            }
        });

        // Auto-resize textarea
        messageInput.addEventListener('input', this.autoResizeTextarea);

        // Voice recognition
        window.startVoiceRecognition = () => this.startVoiceRecognition();
        window.handleImageUpload = (file) => this.handleImageUpload(file);
        window.clearChat = () => this.clearChat();
    }

    autoResizeTextarea(e) {
        const textarea = e.target;
        textarea.style.height = 'auto';
        textarea.style.height = Math.min(textarea.scrollHeight, 120) + 'px';
    }

    sendMessage() {
        const messageInput = document.getElementById('aiMessageInput');
        const message = messageInput.value.trim();

        if (message && !this.isTyping) {
            this.addMessage(message, true);
            messageInput.value = '';
            messageInput.style.height = 'auto';

            // Simulate AI response
            setTimeout(() => {
                this.showTypingIndicator();
                setTimeout(() => {
                    this.hideTypingIndicator();
                    this.addMessage(this.generateAIResponse(message), false);
                }, 1500 + Math.random() * 1000);
            }, 500);
        }
    }

    addMessage(content, isUser = false) {
        const chatMessages = document.getElementById('aiChatMessages');
        const messageDiv = document.createElement('div');
        messageDiv.className = `message ${isUser ? 'user-message' : 'ai-message'}`;

        const messageContent = document.createElement('div');
        messageContent.className = 'message-content';
        messageContent.innerHTML = content;

        // Add timestamp
        const timestamp = document.createElement('div');
        timestamp.className = 'message-time';
        timestamp.textContent = this.getCurrentTime();

        messageDiv.appendChild(messageContent);
        messageDiv.appendChild(timestamp);
        chatMessages.appendChild(messageDiv);

        this.scrollToBottom();
    }

    showTypingIndicator() {
        this.isTyping = true;
        const chatMessages = document.getElementById('aiChatMessages');
        const typingDiv = document.createElement('div');
        typingDiv.className = 'message ai-message';
        typingDiv.id = 'typing-indicator';

        const typingContent = document.createElement('div');
        typingContent.className = 'typing-indicator';

        typingContent.innerHTML = `
            <div class="typing-dots">
                <div class="typing-dot"></div>
                <div class="typing-dot"></div>
                <div class="typing-dot"></div>
            </div>
            <span>AI đang trả lời...</span>
        `;

        typingDiv.appendChild(typingContent);
        chatMessages.appendChild(typingDiv);
        this.scrollToBottom();
    }

    hideTypingIndicator() {
        this.isTyping = false;
        const typingIndicator = document.getElementById('typing-indicator');
        if (typingIndicator) {
            typingIndicator.remove();
        }
    }

    generateAIResponse(userMessage) {
        const responses = {
            greetings: [
                "Xin chào! Tôi có thể giúp gì cho sức khỏe của bạn? 😊",
                "Chào bạn! Hãy cho tôi biết vấn đề sức khỏe bạn đang gặp phải.",
                "Xin chào! Tôi là trợ lý AI của Pharmacy. Bạn cần tư vấn gì về sức khỏe?"
            ],
            medicine: [
                "Dựa trên triệu chứng bạn mô tả, tôi đề xuất nên tham khảo ý kiến bác sĩ. Tuy nhiên, đây là một số thuốc thông thường có thể hỗ trợ...",
                "Về vấn đề này, bạn có thể cân nhắc sử dụng thuốc không kê đơn như... Nhưng hãy nhớ đọc kỹ hướng dẫn sử dụng.",
                "Tôi hiểu tình trạng của bạn. Một số sản phẩm phù hợp có thể là..."
            ],
            symptoms: [
                "Các triệu chứng bạn mô tả có thể liên quan đến... Tuy nhiên, để chẩn đoán chính xác, bạn nên đến gặp bác sĩ.",
                "Dựa trên triệu chứng, đây có thể là dấu hiệu của... Tôi khuyên bạn nên nghỉ ngơi và theo dõi thêm.",
                "Triệu chứng này thường gặp trong các trường hợp... Bạn có thể thử các biện pháp hỗ trợ tại nhà như..."
            ],
            general: [
                "Cảm ơn bạn đã chia sẻ thông tin. Đây là một số khuyến nghị từ tôi...",
                "Tôi hiểu vấn đề của bạn. Dưới đây là một số gợi ý có thể hữu ích...",
                "Dựa trên thông tin bạn cung cấp, tôi có thể tư vấn như sau..."
            ]
        };

        const lowerMessage = userMessage.toLowerCase();

        if (lowerMessage.includes('xin chào') || lowerMessage.includes('hello') || lowerMessage.includes('hi')) {
            return this.getRandomResponse(responses.greetings);
        } else if (lowerMessage.includes('thuốc') || lowerMessage.includes('medic') || lowerMessage.includes('uống')) {
            return this.getRandomResponse(responses.medicine);
        } else if (lowerMessage.includes('đau') || lowerMessage.includes('sốt') || lowerMessage.includes('mệt') || lowerMessage.includes('triệu chứng')) {
            return this.getRandomResponse(responses.symptoms);
        } else {
            return this.getRandomResponse(responses.general);
        }
    }

    getRandomResponse(responses) {
        return responses[Math.floor(Math.random() * responses.length)];
    }

    getCurrentTime() {
        const now = new Date();
        return now.getHours().toString().padStart(2, '0') + ':' + 
               now.getMinutes().toString().padStart(2, '0');
    }

    scrollToBottom() {
        const chatMessages = document.getElementById('aiChatMessages');
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    startVoiceRecognition() {
        const voiceStatus = document.getElementById('voiceStatus');
        const voiceStatusText = document.getElementById('voiceStatusText');
        
        if ('webkitSpeechRecognition' in window) {
            const recognition = new webkitSpeechRecognition();
            recognition.continuous = false;
            recognition.interimResults = false;
            recognition.lang = 'vi-VN';
            
            voiceStatus.style.display = 'flex';
            voiceStatusText.textContent = 'Đang nghe...';
            
            recognition.start();
            
            recognition.onresult = (event) => {
                const transcript = event.results[0][0].transcript;
                document.getElementById('aiMessageInput').value = transcript;
                voiceStatusText.textContent = 'Đã nhận diện: ' + transcript;
                
                setTimeout(() => {
                    voiceStatus.style.display = 'none';
                }, 3000);
            };
            
            recognition.onerror = (event) => {
                voiceStatusText.textContent = 'Lỗi: ' + event.error;
                setTimeout(() => {
                    voiceStatus.style.display = 'none';
                }, 3000);
            };
            
            recognition.onend = () => {
                if (voiceStatusText.textContent === 'Đang nghe...') {
                    voiceStatus.style.display = 'none';
                }
            };
        } else {
            alert('Trình duyệt của bạn không hỗ trợ nhận diện giọng nói.');
        }
    }

    handleImageUpload(file) {
        if (file) {
            this.addMessage(`<i class="fas fa-image me-2"></i>Đã tải lên ảnh: ${file.name}`, true);
            
            setTimeout(() => {
                this.showTypingIndicator();
                setTimeout(() => {
                    this.hideTypingIndicator();
                    this.addMessage("Cảm ơn bạn đã tải lên ảnh. Dựa trên phân tích hình ảnh, tôi có thể thấy... Đây là phân tích từ AI Gemini về hình ảnh của bạn.", false);
                }, 2000);
            }, 500);
        }
    }

    clearChat() {
        const chatMessages = document.getElementById('aiChatMessages');
        const initialMessage = chatMessages.querySelector('.message.ai-message');
        
        chatMessages.innerHTML = '';
        if (initialMessage) {
            chatMessages.appendChild(initialMessage);
        }
        
        this.addMessage("Cuộc trò chuyện đã được làm mới. Tôi có thể giúp gì cho bạn?", false);
    }

    initScrollTop() {
        const scrollTopBtn = document.getElementById('scrollTop');
        
        window.addEventListener('scroll', () => {
            if (window.pageYOffset > 300) {
                scrollTopBtn.classList.add('show');
            } else {
                scrollTopBtn.classList.remove('show');
            }
        });
        
        scrollTopBtn.addEventListener('click', () => {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
    }

    initQuickNav() {
        const quickNavLinks = document.querySelectorAll('.quick-nav a');
        quickNavLinks.forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const targetId = link.getAttribute('href').substring(1);
                const targetElement = document.getElementById(targetId);
                
                if (targetElement) {
                    const offsetTop = targetElement.offsetTop - 80;
                    window.scrollTo({
                        top: offsetTop,
                        behavior: 'smooth'
                    });
                }
            });
        });
    }

    initTimeline() {
        const timelineItems = document.querySelectorAll('.timeline-item');
        const timelineProgress = document.querySelector('.timeline-progress-bar');
        
        function animateTimeline() {
            const windowHeight = window.innerHeight;
            const documentHeight = document.documentElement.scrollHeight;
            const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
            
            // Update progress bar
            const scrolled = (scrollTop / (documentHeight - windowHeight)) * 100;
            timelineProgress.style.height = Math.min(scrolled, 100) + '%';
            
            // Animate timeline items
            timelineItems.forEach(item => {
                const itemTop = item.getBoundingClientRect().top;
                const itemVisible = 150;
                
                if (itemTop < windowHeight - itemVisible) {
                    item.style.opacity = '1';
                    item.style.transform = 'translateY(0)';
                }
            });
        }
        
        // Initialize timeline items
        timelineItems.forEach(item => {
            item.style.opacity = '0';
            item.style.transform = 'translateY(30px)';
            item.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
        });
        
        window.addEventListener('scroll', animateTimeline);
        animateTimeline();
    }
}

// Initialize the chat when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new PharmacyAIChat();
});