// Hiệu ứng chữ xuất hiện từ từ
    document.addEventListener('DOMContentLoaded', function() {
        const aboutSection = document.querySelector('.about-section');
        const aboutContent = aboutSection.querySelector('.about-content');
        
        // Hiệu ứng khi scroll đến phần giới thiệu
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    aboutContent.style.opacity = '1';
                    aboutContent.style.transform = 'translateY(0)';
                }
            });
        }, { threshold: 0.1 });
        
        observer.observe(aboutSection);
        
        // Khởi tạo trạng thái ban đầu
        aboutContent.style.opacity = '0';
        aboutContent.style.transform = 'translateY(20px)';
        aboutContent.style.transition = 'all 0.6s ease 0.2s';
    });

    // Xử lý click cho FAQ
    document.addEventListener('DOMContentLoaded', function() {
        const faqItems = document.querySelectorAll('.faq-item');
        
        faqItems.forEach(item => {
            const heading = item.querySelector('h4');
            
            heading.addEventListener('click', () => {
                // Đóng tất cả các item khác
                faqItems.forEach(otherItem => {
                    if (otherItem !== item) {
                        otherItem.classList.remove('active');
                    }
                });
                
                // Mở/đóng item hiện tại
                item.classList.toggle('active');
            });
        });
    });