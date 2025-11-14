class AuthManager {
    constructor() {
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.handleUrlParams();
        this.setupFormValidation();
    }

    setupEventListeners() {
        // Chuyển đổi giữa đăng nhập và đăng ký
        document.getElementById('show-register')?.addEventListener('click', (e) => {
            e.preventDefault();
            this.showRegister();
        });

        document.getElementById('show-login')?.addEventListener('click', (e) => {
            e.preventDefault();
            this.showLogin();
        });

        // Xử lý submit form
        document.getElementById('login-form')?.addEventListener('submit', (e) => this.handleLogin(e));
        document.getElementById('register-form')?.addEventListener('submit', (e) => this.handleRegister(e));

        // Real-time validation
        this.setupRealTimeValidation();
    }

    handleUrlParams() {
        const urlParams = new URLSearchParams(window.location.search);
        const action = urlParams.get('action');
        
        if (action === 'register') {
            this.showRegister();
        } else {
            this.showLogin();
        }
    }

    showRegister() {
        this.hideAllSections();
        document.getElementById('register-section').classList.remove('form-hidden');
        document.getElementById('register-section').classList.add('form-visible');
        this.updateUrl('register');
        this.updateHeroContent('register');
    }

    showLogin() {
        this.hideAllSections();
        document.getElementById('login-section').classList.remove('form-hidden');
        document.getElementById('login-section').classList.add('form-visible');
        this.updateUrl('login');
        this.updateHeroContent('login');
    }

    hideAllSections() {
        document.querySelectorAll('.form-section').forEach(section => {
            section.classList.remove('form-visible');
            section.classList.add('form-hidden');
        });
    }

    updateUrl(action) {
        const url = new URL(window.location);
        url.searchParams.set('action', action);
        window.history.replaceState({}, '', url);
    }

    updateHeroContent(type) {
        const heroIcon = document.querySelector('.hero-icon');
        const heroTitle = document.querySelector('.hero-content h2');
        const heroText = document.querySelector('.hero-content p');

        if (type === 'register') {
            heroIcon.className = 'hero-icon fas fa-user-plus';
            heroTitle.textContent = 'Tham Gia Cùng Chúng Tôi';
            heroText.textContent = 'Đăng ký tài khoản để nhận nhiều ưu đãi và trải nghiệm dịch vụ tốt nhất từ Pharmacy.';
        } else {
            heroIcon.className = 'hero-icon fas fa-user-md';
            heroTitle.textContent = 'Chào Mừng Trở Lại';
            heroText.textContent = 'Đăng nhập để tiếp tục trải nghiệm dịch vụ chăm sóc sức khỏe tốt nhất từ Pharmacy.';
        }
    }

    setupFormValidation() {
        // Password strength indicator
        const passwordInput = document.getElementById('register-password');
        if (passwordInput) {
            passwordInput.addEventListener('input', (e) => this.checkPasswordStrength(e.target.value));
        }

        // Confirm password validation
        const confirmPassword = document.getElementById('register-confirm-password');
        if (confirmPassword) {
            confirmPassword.addEventListener('input', () => this.validatePasswordMatch());
        }
    }

    setupRealTimeValidation() {
        // Email validation
        const emailInput = document.getElementById('register-email');
        if (emailInput) {
            emailInput.addEventListener('blur', (e) => this.validateEmail(e.target.value));
        }

        // Phone validation
        const phoneInput = document.getElementById('register-phone');
        if (phoneInput) {
            phoneInput.addEventListener('blur', (e) => this.validatePhone(e.target.value));
        }
    }

    checkPasswordStrength(password) {
        const strengthIndicator = document.getElementById('password-strength');
        if (!strengthIndicator) return;

        let strength = 0;
        let feedback = '';

        if (password.length >= 6) strength++;
        if (password.match(/[a-z]+/)) strength++;
        if (password.match(/[A-Z]+/)) strength++;
        if (password.match(/[0-9]+/)) strength++;
        if (password.match(/[!@#$%^&*(),.?":{}|<>]+/)) strength++;

        switch (strength) {
            case 0:
            case 1:
                feedback = 'Rất yếu';
                strengthIndicator.style.color = '#dc3545';
                break;
            case 2:
                feedback = 'Yếu';
                strengthIndicator.style.color = '#fd7e14';
                break;
            case 3:
                feedback = 'Trung bình';
                strengthIndicator.style.color = '#ffc107';
                break;
            case 4:
                feedback = 'Mạnh';
                strengthIndicator.style.color = '#20c997';
                break;
            case 5:
                feedback = 'Rất mạnh';
                strengthIndicator.style.color = '#198754';
                break;
        }

        strengthIndicator.textContent = `Độ mạnh mật khẩu: ${feedback}`;
    }

    validatePasswordMatch() {
        const password = document.getElementById('register-password')?.value;
        const confirmPassword = document.getElementById('register-confirm-password')?.value;
        const confirmInput = document.getElementById('register-confirm-password');

        if (!confirmInput) return;

        if (password && confirmPassword && password !== confirmPassword) {
            confirmInput.style.borderColor = '#dc3545';
            this.showFieldError('register-confirm-password', 'Mật khẩu xác nhận không khớp');
        } else {
            confirmInput.style.borderColor = '#198754';
            this.hideFieldError('register-confirm-password');
        }
    }

    validateEmail(email) {
        const emailInput = document.getElementById('register-email');
        if (!emailInput) return;

        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        
        if (email && !emailRegex.test(email)) {
            emailInput.style.borderColor = '#dc3545';
            this.showFieldError('register-email', 'Email không hợp lệ');
            return false;
        } else {
            emailInput.style.borderColor = '#198754';
            this.hideFieldError('register-email');
            return true;
        }
    }

    validatePhone(phone) {
        const phoneInput = document.getElementById('register-phone');
        if (!phoneInput) return;

        const phoneRegex = /^(0|\+84)[3|5|7|8|9][0-9]{8}$/;
        
        if (phone && !phoneRegex.test(phone)) {
            phoneInput.style.borderColor = '#dc3545';
            this.showFieldError('register-phone', 'Số điện thoại không hợp lệ');
            return false;
        } else {
            phoneInput.style.borderColor = '#198754';
            this.hideFieldError('register-phone');
            return true;
        }
    }

    showFieldError(fieldId, message) {
        let errorElement = document.getElementById(`${fieldId}-error`);
        if (!errorElement) {
            const input = document.getElementById(fieldId);
            errorElement = document.createElement('div');
            errorElement.id = `${fieldId}-error`;
            errorElement.className = 'field-error text-danger small mt-1';
            input.parentNode.appendChild(errorElement);
        }
        errorElement.textContent = message;
    }

    hideFieldError(fieldId) {
        const errorElement = document.getElementById(`${fieldId}-error`);
        if (errorElement) {
            errorElement.remove();
        }
    }

    async handleLogin(e) {
        e.preventDefault();
        
        const formData = new FormData(e.target);
        const username = formData.get('username');
        const password = formData.get('password');

        if (!this.validateLoginForm(username, password)) {
            return;
        }

        const button = e.target.querySelector('button[type="submit"]');
        this.setLoadingState(button, true);

        try {
            // Form sẽ được submit bình thường qua PHP
            // Đây chỉ là validation phía client
            e.target.submit();
        } catch (error) {
            this.showToast('Có lỗi xảy ra khi đăng nhập', 'error');
            console.error('Login error:', error);
            this.setLoadingState(button, false);
        }
    }

    async handleRegister(e) {
        e.preventDefault();
        
        const formData = new FormData(e.target);
        const userData = {
            full_name: formData.get('full_name'),
            username: formData.get('username'),
            email: formData.get('email'),
            phone: formData.get('phone'),
            password: formData.get('password'),
            confirm_password: formData.get('confirm_password')
        };

        if (!this.validateRegisterForm(userData)) {
            return;
        }

        const button = e.target.querySelector('button[type="submit"]');
        this.setLoadingState(button, true);

        try {
            // Form sẽ được submit bình thường qua PHP
            // Đây chỉ là validation phía client
            e.target.submit();
        } catch (error) {
            this.showToast('Có lỗi xảy ra khi đăng ký', 'error');
            console.error('Register error:', error);
            this.setLoadingState(button, false);
        }
    }

    validateLoginForm(username, password) {
        if (!username.trim()) {
            this.showToast('Vui lòng nhập tên đăng nhập hoặc email', 'error');
            return false;
        }

        if (!password.trim()) {
            this.showToast('Vui lòng nhập mật khẩu', 'error');
            return false;
        }

        return true;
    }

    validateRegisterForm(userData) {
        const { full_name, username, email, phone, password, confirm_password } = userData;

        if (!full_name.trim()) {
            this.showToast('Vui lòng nhập họ và tên', 'error');
            return false;
        }

        if (!username.trim()) {
            this.showToast('Vui lòng nhập tên đăng nhập', 'error');
            return false;
        }

        if (!this.validateEmail(email)) {
            this.showToast('Email không hợp lệ', 'error');
            return false;
        }

        if (!this.validatePhone(phone)) {
            this.showToast('Số điện thoại không hợp lệ', 'error');
            return false;
        }

        if (password.length < 6) {
            this.showToast('Mật khẩu phải có ít nhất 6 ký tự', 'error');
            return false;
        }

        if (password !== confirm_password) {
            this.showToast('Mật khẩu xác nhận không khớp', 'error');
            return false;
        }

        return true;
    }

    setLoadingState(button, isLoading) {
        if (isLoading) {
            button.classList.add('loading');
            button.disabled = true;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang xử lý...';
        } else {
            button.classList.remove('loading');
            button.disabled = false;
            const originalText = button.closest('form').id === 'login-form' 
                ? '<i class="fas fa-sign-in-alt"></i> ĐĂNG NHẬP'
                : '<i class="fas fa-user-plus"></i> ĐĂNG KÝ';
            button.innerHTML = originalText;
        }
    }

    showToast(message, type = 'info') {
        // Tạo toast container nếu chưa có
        let container = document.getElementById('toast-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'toast-container';
            container.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 9999;
                max-width: 400px;
            `;
            document.body.appendChild(container);
        }

        // Tạo toast
        const toast = document.createElement('div');
        toast.className = `alert alert-${type === 'success' ? 'success' : 'danger'} alert-dismissible fade show`;
        toast.innerHTML = `
            <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-triangle'} me-2"></i>
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;

        container.appendChild(toast);

        // Tự động xóa sau 5 giây
        setTimeout(() => {
            if (toast.parentNode) {
                toast.remove();
            }
        }, 5000);
    }
}

// Utility functions
function formatPhoneNumber(input) {
    let value = input.value.replace(/\D/g, '');
    if (value.startsWith('84')) {
        value = '0' + value.slice(2);
    }
    input.value = value;
}

// Khởi tạo khi DOM loaded
document.addEventListener('DOMContentLoaded', () => {
    new AuthManager();
});