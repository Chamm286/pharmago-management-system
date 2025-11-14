// Admin User Add JavaScript
document.addEventListener('DOMContentLoaded', function() {
    initializeUserAddPage();
});

function initializeUserAddPage() {
    // Password strength indicator
    setupPasswordStrength();
    
    // Form validation
    setupFormValidation();
    
    // Real-time username availability check
    setupUsernameCheck();
    
    // Auto format phone number
    setupPhoneFormatting();
}

function setupPasswordStrength() {
    const passwordInput = document.getElementById('password');
    const strengthBar = document.getElementById('passwordStrength');
    
    if (passwordInput && strengthBar) {
        passwordInput.addEventListener('input', function() {
            const password = this.value;
            const strength = calculatePasswordStrength(password);
            
            // Reset classes
            strengthBar.className = 'password-strength';
            
            // Add strength class
            if (password.length === 0) {
                strengthBar.style.width = '0%';
            } else {
                switch (strength) {
                    case 'weak':
                        strengthBar.classList.add('password-weak');
                        break;
                    case 'medium':
                        strengthBar.classList.add('password-medium');
                        break;
                    case 'strong':
                        strengthBar.classList.add('password-strong');
                        break;
                    case 'very-strong':
                        strengthBar.classList.add('password-very-strong');
                        break;
                }
            }
        });
    }
}

function calculatePasswordStrength(password) {
    let score = 0;
    
    if (!password) return 'weak';
    
    // Length check
    if (password.length >= 6) score++;
    if (password.length >= 8) score++;
    if (password.length >= 12) score++;
    
    // Character variety
    if (/[a-z]/.test(password)) score++;
    if (/[A-Z]/.test(password)) score++;
    if (/[0-9]/.test(password)) score++;
    if (/[^a-zA-Z0-9]/.test(password)) score++;
    
    if (score >= 6) return 'very-strong';
    if (score >= 4) return 'strong';
    if (score >= 3) return 'medium';
    return 'weak';
}

function setupFormValidation() {
    const form = document.getElementById('userForm');
    const password = document.getElementById('password');
    const confirmPassword = document.getElementById('confirm_password');
    
    if (form) {
        form.addEventListener('submit', function(e) {
            let isValid = true;
            
            // Check password match
            if (password && confirmPassword && password.value !== confirmPassword.value) {
                showFieldError(confirmPassword, 'Mật khẩu xác nhận không khớp');
                isValid = false;
            } else {
                clearFieldError(confirmPassword);
            }
            
            // Check password strength
            if (password && calculatePasswordStrength(password.value) === 'weak' && password.value.length > 0) {
                showFieldError(password, 'Mật khẩu quá yếu. Vui lòng sử dụng mật khẩu mạnh hơn.');
                isValid = false;
            }
            
            if (!isValid) {
                e.preventDefault();
                showToast('Vui lòng kiểm tra lại thông tin!', 'error');
            } else {
                // Show loading state
                const submitBtn = form.querySelector('button[type="submit"]');
                const originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Đang xử lý...';
                submitBtn.disabled = true;
                
                // Re-enable after 5 seconds in case form submission fails
                setTimeout(() => {
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                }, 5000);
            }
        });
    }
}

function setupUsernameCheck() {
    const usernameInput = document.getElementById('username');
    
    if (usernameInput) {
        let checkTimeout;
        
        usernameInput.addEventListener('input', function() {
            clearTimeout(checkTimeout);
            
            const username = this.value.trim();
            if (username.length >= 3) {
                checkTimeout = setTimeout(() => {
                    checkUsernameAvailability(username);
                }, 500);
            }
        });
    }
}

function checkUsernameAvailability(username) {
    // In a real application, this would make an AJAX call to check username availability
    // For now, we'll just simulate it with common usernames
    const takenUsernames = ['admin', 'user', 'test', 'demo', 'guest'];
    
    if (takenUsernames.includes(username.toLowerCase())) {
        showFieldError(document.getElementById('username'), 'Tên đăng nhập đã được sử dụng');
    } else {
        clearFieldError(document.getElementById('username'));
        showFieldSuccess(document.getElementById('username'), 'Tên đăng nhập có thể sử dụng');
    }
}

function setupPhoneFormatting() {
    const phoneInput = document.getElementById('phone');
    
    if (phoneInput) {
        phoneInput.addEventListener('input', function() {
            let value = this.value.replace(/\D/g, '');
            
            if (value.length > 0) {
                if (value.length <= 3) {
                    value = value;
                } else if (value.length <= 6) {
                    value = value.replace(/(\d{3})(\d{0,3})/, '$1 $2');
                } else if (value.length <= 10) {
                    value = value.replace(/(\d{3})(\d{3})(\d{0,4})/, '$1 $2 $3');
                } else {
                    value = value.substring(0, 10);
                    value = value.replace(/(\d{3})(\d{3})(\d{4})/, '$1 $2 $3');
                }
            }
            
            this.value = value;
        });
    }
}

function showFieldError(field, message) {
    if (!field) return;
    
    field.classList.add('is-invalid');
    field.classList.remove('is-valid');
    
    let feedback = field.nextElementSibling;
    if (!feedback || !feedback.classList.contains('invalid-feedback')) {
        feedback = document.createElement('div');
        feedback.className = 'invalid-feedback';
        field.parentNode.insertBefore(feedback, field.nextSibling);
    }
    
    feedback.textContent = message;
}

function showFieldSuccess(field, message) {
    if (!field) return;
    
    field.classList.add('is-valid');
    field.classList.remove('is-invalid');
    
    let feedback = field.nextElementSibling;
    if (!feedback || !feedback.classList.contains('valid-feedback')) {
        feedback = document.createElement('div');
        feedback.className = 'valid-feedback';
        field.parentNode.insertBefore(feedback, field.nextSibling);
    }
    
    feedback.textContent = message;
}

function clearFieldError(field) {
    if (!field) return;
    
    field.classList.remove('is-invalid');
    field.classList.remove('is-valid');
    
    let feedback = field.nextElementSibling;
    if (feedback && (feedback.classList.contains('invalid-feedback') || feedback.classList.contains('valid-feedback'))) {
        feedback.remove();
    }
}

function showToast(message, type = 'info') {
    // Remove existing toasts
    const existingToasts = document.querySelectorAll('.custom-toast');
    existingToasts.forEach(toast => toast.remove());
    
    const toast = document.createElement('div');
    toast.className = `custom-toast alert alert-${type} alert-dismissible fade show`;
    toast.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
        min-width: 300px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    `;
    
    toast.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(toast);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        if (toast.parentNode) {
            toast.remove();
        }
    }, 5000);
}

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    // Ctrl + S to save
    if ((e.ctrlKey || e.metaKey) && e.key === 's') {
        e.preventDefault();
        const submitBtn = document.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.click();
        }
    }
    
    // Escape to go back
    if (e.key === 'Escape') {
        const backBtn = document.querySelector('a[href="admin_users.php"]');
        if (backBtn) {
            backBtn.click();
        }
    }
});

// Generate random password
function generatePassword() {
    const length = 12;
    const charset = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*";
    let password = "";
    
    for (let i = 0; i < length; i++) {
        password += charset.charAt(Math.floor(Math.random() * charset.length));
    }
    
    const passwordInput = document.getElementById('password');
    const confirmInput = document.getElementById('confirm_password');
    
    if (passwordInput) passwordInput.value = password;
    if (confirmInput) confirmInput.value = password;
    
    // Trigger password strength calculation
    if (passwordInput) {
        passwordInput.dispatchEvent(new Event('input'));
    }
    
    showToast('Đã tạo mật khẩu ngẫu nhiên!', 'success');
}

// Add generate password button (optional)
function addGeneratePasswordButton() {
    const passwordGroup = document.querySelector('.mb-3:has(#password)');
    if (passwordGroup) {
        const generateBtn = document.createElement('button');
        generateBtn.type = 'button';
        generateBtn.className = 'btn btn-outline-secondary btn-sm mt-2';
        generateBtn.innerHTML = '<i class="fas fa-dice me-1"></i> Tạo mật khẩu';
        generateBtn.onclick = generatePassword;
        
        passwordGroup.appendChild(generateBtn);
    }
}

// Initialize generate password button
addGeneratePasswordButton();