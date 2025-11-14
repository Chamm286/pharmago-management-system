// Contact Form Validation and Interaction
document.addEventListener('DOMContentLoaded', function() {
    console.log('Contact page initialized successfully!');

    // Form Validation
    const forms = document.querySelectorAll('.needs-validation');
    
    Array.from(forms).forEach(form => {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            
            form.classList.add('was-validated');
            
            // If form is valid, show loading state
            if (form.checkValidity()) {
                const submitBtn = form.querySelector('button[type="submit"]');
                const originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Đang gửi...';
                submitBtn.disabled = true;
                
                // Simulate API call
                setTimeout(() => {
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                    
                    // Show success message
                    showNotification('Tin nhắn đã được gửi thành công! Chúng tôi sẽ liên hệ với bạn sớm.', 'success');
                }, 2000);
            }
        }, false);
    });

    // Character counter for message
    const messageTextarea = document.getElementById('message');
    if (messageTextarea) {
        const counter = messageTextarea.parentNode.querySelector('.form-text');
        
        messageTextarea.addEventListener('input', function() {
            const count = this.value.length;
            const charCount = document.getElementById('charCount');
            if (charCount) {
                charCount.textContent = count;
                
                if (count > 1000) {
                    charCount.style.color = '#dc3545';
                } else if (count > 800) {
                    charCount.style.color = '#ffc107';
                } else {
                    charCount.style.color = '';
                }
            }
        });
    }

    // Phone number formatting
    const phoneInput = document.getElementById('phone');
    if (phoneInput) {
        phoneInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 0) {
                value = value.match(/.{1,4}/g).join(' ');
            }
            e.target.value = value;
        });
    }

    // Subject change effect
    const subjectSelect = document.getElementById('subject');
    if (subjectSelect) {
        subjectSelect.addEventListener('change', function() {
            if (this.value === 'Hoạt động cộng đồng') {
                showCommunityMessage();
            }
        });
    }

    function showCommunityMessage() {
        // Check if message already exists
        if (!document.getElementById('community-message')) {
            const message = document.createElement('div');
            message.id = 'community-message';
            message.className = 'alert alert-info mt-3';
            message.innerHTML = `
                <i class="fas fa-info-circle me-2"></i>
                Cảm ơn bạn quan tâm đến hoạt động cộng đồng! Chúng tôi sẽ liên hệ để cung cấp thông tin chi tiết về các chương trình sắp tới.
            `;
            subjectSelect.parentNode.appendChild(message);
            
            // Auto remove after 5 seconds
            setTimeout(() => {
                message.remove();
            }, 5000);
        }
    }

    // Interactive Map
    const interactiveMap = document.getElementById('interactiveMap');
    if (interactiveMap) {
        interactiveMap.addEventListener('click', function() {
            this.innerHTML = `
                <div class="map-content">
                    <i class="fas fa-map-marked-alt fa-3x mb-3"></i>
                    <h5>Tính năng bản đồ</h5>
                    <p class="mb-3">Đang phát triển...</p>
                    <button class="btn btn-outline-light btn-sm" onclick="openGoogleMaps()">
                        <i class="fas fa-external-link-alt me-1"></i>
                        Xem trên Google Maps
                    </button>
                </div>
            `;
        });
    }

    // Impact cards counter animation
    const impactNumbers = document.querySelectorAll('.impact-number');
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const target = entry.target;
                const finalValue = parseInt(target.textContent.replace(/,/g, ''));
                animateCounter(target, finalValue);
                observer.unobserve(target);
            }
        });
    }, { threshold: 0.5 });

    impactNumbers.forEach(number => {
        observer.observe(number);
    });

    function animateCounter(element, finalValue) {
        const duration = 2000;
        const step = finalValue / (duration / 16);
        let current = 0;
        
        const timer = setInterval(() => {
            current += step;
            if (current >= finalValue) {
                current = finalValue;
                clearInterval(timer);
            }
            element.textContent = Math.floor(current).toLocaleString() + '+';
        }, 16);
    }

    // Form auto-save (localStorage)
    const form = document.querySelector('form');
    if (form) {
        const formFields = form.querySelectorAll('input, select, textarea');
        
        // Load saved data
        formFields.forEach(field => {
            const savedValue = localStorage.getItem(`contact_${field.name}`);
            if (savedValue) {
                if (field.type === 'checkbox') {
                    field.checked = savedValue === 'true';
                } else {
                    field.value = savedValue;
                }
            }
        });
        
        // Save on input
        formFields.forEach(field => {
            field.addEventListener('input', function() {
                if (this.type === 'checkbox') {
                    localStorage.setItem(`contact_${this.name}`, this.checked);
                } else {
                    localStorage.setItem(`contact_${this.name}`, this.value);
                }
            });
        });
        
        // Clear saved data on successful submit
        form.addEventListener('submit', function() {
            if (this.checkValidity()) {
                formFields.forEach(field => {
                    localStorage.removeItem(`contact_${field.name}`);
                });
            }
        });
    }

    // Activity cards hover effect
    const activityItems = document.querySelectorAll('.activity-item');
    activityItems.forEach(item => {
        item.addEventListener('mouseenter', function() {
            this.style.transform = 'translateX(10px)';
            this.style.transition = 'transform 0.3s ease';
        });
        
        item.addEventListener('mouseleave', function() {
            this.style.transform = 'translateX(0)';
        });
    });

    // Smooth scrolling for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
});

// Global functions
function showCommunityInterest() {
    const subjectSelect = document.getElementById('subject');
    if (subjectSelect) {
        subjectSelect.value = 'Hoạt động cộng đồng';
        subjectSelect.dispatchEvent(new Event('change'));
        
        // Scroll to form
        document.querySelector('.contact-form-card').scrollIntoView({
            behavior: 'smooth'
        });
    }
}

function openGoogleMaps() {
    const address = "123 Đường 2/9, Quận Hải Châu, TP. Đà Nẵng";
    const url = `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(address)}`;
    window.open(url, '_blank');
}

function showNotification(message, type = 'info') {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    notification.style.cssText = `
        top: 20px;
        right: 20px;
        z-index: 9999;
        min-width: 300px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.3);
    `;
    
    notification.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : 'info-circle'} me-2"></i>
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(notification);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        if (notification.parentNode) {
            notification.remove();
        }
    }, 5000);
}

// Utility functions
function validatePhoneNumber(phone) {
    const phoneRegex = /^(?:\+84|0)(?:\d){9,10}$/;
    return phoneRegex.test(phone.replace(/\s/g, ''));
}

function validateEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}