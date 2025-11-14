// assets/js/admin_login.js
document.addEventListener('DOMContentLoaded', function() {
    console.log('✅ Admin Login JS loaded successfully!');
    
    const loginForm = document.getElementById('loginForm');
    const loginBtn = document.getElementById('loginBtn');
    const loginSpinner = document.getElementById('loginSpinner');
    const btnText = document.querySelector('.btn-text');
    const togglePassword = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('password');

    // Remove any problematic service worker code
    if ('serviceWorker' in navigator) {
        // Only register if sw.js exists
        fetch('/sw.js')
            .then(response => {
                if (response.ok) {
                    return navigator.serviceWorker.register('/sw.js');
                }
                return Promise.reject('Service Worker file not found');
            })
            .then(registration => {
                console.log('✅ Service Worker Registered');
            })
            .catch(error => {
                // Silently fail - not critical for login
                console.log('ℹ️ Service Worker not registered (not required)');
            });
    }

    // Toggle password visibility
    if (togglePassword && passwordInput) {
        togglePassword.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.querySelector('i').classList.toggle('fa-eye');
            this.querySelector('i').classList.toggle('fa-eye-slash');
        });
    }

    // Form validation
    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value.trim();

            if (!username || !password) {
                e.preventDefault();
                showError('Vui lòng nhập đầy đủ thông tin đăng nhập!');
                return;
            }

            // Show loading state
            if (loginBtn && loginSpinner && btnText) {
                loginBtn.disabled = true;
                loginSpinner.style.display = 'inline-block';
                btnText.textContent = 'Đang đăng nhập...';
            }
        });
    }

    // Real-time validation
    const usernameInput = document.getElementById('username');
    const passwordInputField = document.getElementById('password');

    if (usernameInput) {
        usernameInput.addEventListener('input', function() {
            validateUsername(this.value.trim());
        });
    }

    if (passwordInputField) {
        passwordInputField.addEventListener('input', function() {
            validatePassword(this.value.trim());
        });
    }

    // Enter key to submit
    document.addEventListener('keypress', function(e) {
        if (e.key === 'Enter' && loginForm) {
            const focused = document.activeElement;
            if (focused && (focused.type === 'text' || focused.type === 'password')) {
                loginForm.requestSubmit();
            }
        }
    });

    console.log('✅ Login form initialized successfully');
});

function validateUsername(username) {
    const helpText = document.getElementById('usernameHelp');
    if (!helpText) return;

    if (username.length === 0) {
        helpText.textContent = '';
        helpText.className = 'form-text text-muted';
    } else if (username.length < 3) {
        helpText.textContent = 'Tên đăng nhập phải có ít nhất 3 ký tự';
        helpText.className = 'form-text text-danger';
    } else {
        helpText.textContent = '✓ Tên đăng nhập hợp lệ';
        helpText.className = 'form-text text-success';
    }
}

function validatePassword(password) {
    const helpText = document.getElementById('passwordHelp');
    if (!helpText) return;

    if (password.length === 0) {
        helpText.textContent = '';
        helpText.className = 'form-text text-muted';
    } else if (password.length < 6) {
        helpText.textContent = 'Mật khẩu phải có ít nhất 6 ký tự';
        helpText.className = 'form-text text-danger';
    } else {
        helpText.textContent = '✓ Mật khẩu hợp lệ';
        helpText.className = 'form-text text-success';
    }
}

function showError(message) {
    // Remove existing alerts
    const existingAlert = document.querySelector('.alert');
    if (existingAlert) {
        existingAlert.remove();
    }

    // Create new alert
    const alertDiv = document.createElement('div');
    alertDiv.className = 'alert alert-danger alert-dismissible fade show';
    alertDiv.innerHTML = `
        <i class="fas fa-exclamation-triangle me-2"></i>
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;

    // Insert after the login header
    const loginBody = document.querySelector('.login-body');
    if (loginBody) {
        loginBody.insertBefore(alertDiv, loginBody.firstChild);
    }

    // Auto remove after 5 seconds
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 5000);
}

// Fix for negative page load time (harmless but annoying)
window.addEventListener('load', function() {
    const loadTime = performance.now();
    console.log(`✅ Page fully loaded in ${Math.round(loadTime)}ms`);
});