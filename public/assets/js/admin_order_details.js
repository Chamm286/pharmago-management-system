// Admin Order Details JavaScript
document.addEventListener('DOMContentLoaded', function() {
    initializeOrderDetails();
});

function initializeOrderDetails() {
    // Initialize tooltips
    initializeTooltips();
    
    // Auto-dismiss alerts
    autoDismissAlerts();
    
    // Form validation
    setupFormValidation();
    
    // Status change confirmation
    setupStatusChangeConfirmation();
    
    // Keyboard shortcuts
    setupKeyboardShortcuts();
    
    // Animate timeline
    animateTimeline();
}

function initializeTooltips() {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
}

function autoDismissAlerts() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            if (alert.parentNode) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }
        }, 5000);
    });
}

function setupFormValidation() {
    const updateForm = document.getElementById('statusUpdateForm');
    if (updateForm) {
        updateForm.addEventListener('submit', function(e) {
            const orderStatus = document.getElementById('orderStatus');
            const paymentStatus = document.getElementById('paymentStatus');
            
            if (!orderStatus.value || !paymentStatus.value) {
                e.preventDefault();
                showToast('Vui lòng chọn đầy đủ trạng thái!', 'error');
                return false;
            }
            
            // Show loading state
            const submitBtn = document.getElementById('updateBtn');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Đang cập nhật...';
            submitBtn.disabled = true;
            
            // Re-enable after 3 seconds in case form submission fails
            setTimeout(() => {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }, 3000);
        });
    }
}

function setupStatusChangeConfirmation() {
    const orderStatusSelect = document.getElementById('orderStatus');
    if (orderStatusSelect) {
        // Store initial value
        orderStatusSelect.dataset.previousValue = orderStatusSelect.value;
        
        orderStatusSelect.addEventListener('change', function() {
            if (this.value === 'cancelled') {
                if (!confirm('Bạn có chắc chắn muốn hủy đơn hàng này? Hành động này không thể hoàn tác.')) {
                    this.value = this.dataset.previousValue;
                    return;
                }
            }
            
            // Show animation for status change
            const card = this.closest('.status-update-card');
            if (card) {
                card.classList.add('status-change-animation');
                setTimeout(() => {
                    card.classList.remove('status-change-animation');
                }, 500);
            }
            
            this.dataset.previousValue = this.value;
        });
    }
}

function setupKeyboardShortcuts() {
    document.addEventListener('keydown', function(e) {
        // Ctrl + P for print
        if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
            e.preventDefault();
            window.print();
        }
        
        // Escape key to go back
        if (e.key === 'Escape') {
            const backBtn = document.querySelector('a[href="admin_orders.php"]');
            if (backBtn) {
                backBtn.click();
            }
        }
        
        // Ctrl + S to save (submit form)
        if ((e.ctrlKey || e.metaKey) && e.key === 's') {
            e.preventDefault();
            const updateBtn = document.getElementById('updateBtn');
            if (updateBtn) {
                updateBtn.click();
            }
        }
    });
}

function animateTimeline() {
    const timelineItems = document.querySelectorAll('.timeline-item');
    timelineItems.forEach((item, index) => {
        item.style.opacity = '0';
        item.style.transform = 'translateX(-20px)';
        
        setTimeout(() => {
            item.style.transition = 'all 0.5s ease';
            item.style.opacity = '1';
            item.style.transform = 'translateX(0)';
        }, index * 200);
    });
}

// Show toast notification
function showToast(message, type = 'info') {
    // Remove existing toasts
    const existingToasts = document.querySelectorAll('.custom-toast');
    existingToasts.forEach(toast => toast.remove());
    
    const toast = document.createElement('div');
    toast.className = `custom-toast alert alert-${type} alert-dismissible fade show`;
    
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

// Export order data
function exportOrderData() {
    try {
        const orderData = {
            orderCode: document.querySelector('.card-header .card-title')?.textContent?.trim() || '',
            customerName: document.querySelector('.card-body strong:first-child')?.nextSibling?.textContent?.trim() || '',
            totalAmount: document.querySelector('tfoot .text-success')?.textContent?.trim() || '',
            orderStatus: document.getElementById('orderStatus')?.value || '',
            paymentStatus: document.getElementById('paymentStatus')?.value || '',
            orderDate: document.querySelector('.card-body strong:contains("Ngày đặt")')?.nextSibling?.textContent?.trim() || '',
            exportDate: new Date().toLocaleString('vi-VN')
        };
        
        const dataStr = JSON.stringify(orderData, null, 2);
        const dataBlob = new Blob([dataStr], { type: 'application/json' });
        
        const url = URL.createObjectURL(dataBlob);
        const link = document.createElement('a');
        link.href = url;
        link.download = `order-${orderData.orderCode.replace('#', '')}-${new Date().getTime()}.json`;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        URL.revokeObjectURL(url);
        
        showToast('Đã xuất dữ liệu đơn hàng!', 'success');
    } catch (error) {
        console.error('Export error:', error);
        showToast('Có lỗi xảy ra khi xuất dữ liệu!', 'error');
    }
}

// Quick status update functions
function quickUpdateStatus(status) {
    const orderStatusSelect = document.getElementById('orderStatus');
    if (orderStatusSelect) {
        orderStatusSelect.value = status;
        document.getElementById('updateBtn').click();
    }
}

function quickUpdatePayment(status) {
    const paymentStatusSelect = document.getElementById('paymentStatus');
    if (paymentStatusSelect) {
        paymentStatusSelect.value = status;
        document.getElementById('updateBtn').click();
    }
}

// Print enhancement
function enhancePrint() {
    // Add print-specific classes before printing
    document.body.classList.add('printing');
    
    window.addEventListener('afterprint', function() {
        document.body.classList.remove('printing');
    });
}

// Initialize print enhancement
window.addEventListener('beforeprint', enhancePrint);