// Admin Orders JavaScript
class OrdersManager {
    constructor() {
        this.selectedOrders = new Set();
        this.init();
    }

    init() {
        this.initEventListeners();
        this.initOrderFilters();
        this.initOrderActions();
        this.initStatusTracking();
        this.initCharts();
    }

    initEventListeners() {
        // Order selection
        document.querySelectorAll('.order-select').forEach(checkbox => {
            checkbox.addEventListener('change', (e) => {
                this.handleOrderSelection(e.target);
            });
        });

        // Quick actions
        document.querySelectorAll('.order-action-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                this.handleOrderAction(e.currentTarget);
            });
        });

        // Search functionality
        document.querySelector('.search-box input')?.addEventListener('input', (e) => {
            this.handleSearch(e.target.value);
        });

        // Filter form
        document.getElementById('filterForm')?.addEventListener('submit', (e) => {
            e.preventDefault();
            this.applyFilters();
        });

        // Reset filters
        document.querySelector('.btn-reset-filters')?.addEventListener('click', () => {
            this.resetFilters();
        });
    }

    initOrderFilters() {
        // Initialize date pickers
        this.initDatePickers();
        
        // Initialize status filters
        this.initStatusFilters();
        
        // Initialize payment filters
        this.initPaymentFilters();
    }

    initOrderActions() {
        // Bulk order actions
        document.getElementById('bulkOrderAction')?.addEventListener('change', (e) => {
            this.handleBulkOrderAction(e.target.value);
        });

        // Print orders
        document.querySelector('.btn-print-orders')?.addEventListener('click', () => {
            this.printOrders();
        });

        // Export orders
        document.querySelector('.btn-export-orders')?.addEventListener('click', () => {
            this.exportOrders();
        });
    }

    initStatusTracking() {
        // Real-time status updates
        this.setupStatusWebSocket();
        
        // Auto-refresh for pending orders
        this.setupAutoRefresh();
    }

    initCharts() {
        // Order statistics charts
        this.initOrderStatsChart();
        this.initRevenueChart();
        this.initStatusDistributionChart();
    }

    handleOrderSelection(checkbox) {
        const orderId = checkbox.dataset.orderId;
        
        if (checkbox.checked) {
            this.selectedOrders.add(orderId);
        } else {
            this.selectedOrders.delete(orderId);
        }
        
        this.updateBulkOrderActionsState();
    }

    handleOrderAction(button) {
        const action = button.dataset.action;
        const orderId = button.dataset.orderId;
        
        switch (action) {
            case 'view':
                this.viewOrderDetails(orderId);
                break;
            case 'edit':
                this.editOrder(orderId);
                break;
            case 'confirm':
                this.confirmOrder(orderId);
                break;
            case 'cancel':
                this.cancelOrder(orderId);
                break;
            case 'process':
                this.processOrder(orderId);
                break;
            case 'ship':
                this.shipOrder(orderId);
                break;
            case 'complete':
                this.completeOrder(orderId);
                break;
            case 'print':
                this.printOrder(orderId);
                break;
        }
    }

    handleBulkOrderAction(action) {
        if (this.selectedOrders.size === 0) {
            this.showNotification('Vui lòng chọn ít nhất một đơn hàng', 'warning');
            return;
        }

        const orderIds = Array.from(this.selectedOrders);
        
        switch (action) {
            case 'confirm':
                this.bulkConfirmOrders(orderIds);
                break;
            case 'process':
                this.bulkProcessOrders(orderIds);
                break;
            case 'ship':
                this.bulkShipOrders(orderIds);
                break;
            case 'complete':
                this.bulkCompleteOrders(orderIds);
                break;
            case 'cancel':
                this.bulkCancelOrders(orderIds);
                break;
            case 'print':
                this.bulkPrintOrders(orderIds);
                break;
            case 'export':
                this.bulkExportOrders(orderIds);
                break;
        }
    }

    handleSearch(query) {
        const rows = document.querySelectorAll('.table tbody tr');
        const searchTerm = query.toLowerCase();
        
        rows.forEach(row => {
            const orderCode = row.querySelector('.order-code')?.textContent.toLowerCase();
            const customerName = row.querySelector('.customer-name')?.textContent.toLowerCase();
            const customerPhone = row.querySelector('.customer-phone')?.textContent.toLowerCase();
            
            const searchableText = `${orderCode} ${customerName} ${customerPhone}`;
            
            if (searchableText.includes(searchTerm)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }

    applyFilters() {
        const formData = new FormData(document.getElementById('filterForm'));
        const filters = Object.fromEntries(formData);
        
        this.filterOrders(filters);
    }

    resetFilters() {
        document.getElementById('filterForm').reset();
        this.applyFilters();
    }

    filterOrders(filters) {
        const rows = document.querySelectorAll('.table tbody tr');
        
        rows.forEach(row => {
            let shouldShow = true;
            
            // Status filter
            if (filters.status && filters.status !== 'all') {
                const rowStatus = row.dataset.status;
                if (rowStatus !== filters.status) {
                    shouldShow = false;
                }
            }
            
            // Payment status filter
            if (filters.paymentStatus && filters.paymentStatus !== 'all') {
                const rowPaymentStatus = row.dataset.paymentStatus;
                if (rowPaymentStatus !== filters.paymentStatus) {
                    shouldShow = false;
                }
            }
            
            // Date range filter
            if (filters.startDate || filters.endDate) {
                const orderDate = new Date(row.dataset.orderDate);
                
                if (filters.startDate) {
                    const startDate = new Date(filters.startDate);
                    if (orderDate < startDate) {
                        shouldShow = false;
                    }
                }
                
                if (filters.endDate) {
                    const endDate = new Date(filters.endDate);
                    endDate.setHours(23, 59, 59, 999);
                    if (orderDate > endDate) {
                        shouldShow = false;
                    }
                }
            }
            
            row.style.display = shouldShow ? '' : 'none';
        });
    }

    async confirmOrder(orderId) {
        try {
            const response = await fetch(`/api/admin/orders/${orderId}/confirm`, {
                method: 'POST'
            });
            
            if (response.ok) {
                this.showNotification('Đã xác nhận đơn hàng', 'success');
                this.updateOrderStatus(orderId, 'confirmed');
            }
        } catch (error) {
            this.showNotification('Lỗi khi xác nhận đơn hàng', 'error');
        }
    }

    async processOrder(orderId) {
        try {
            const response = await fetch(`/api/admin/orders/${orderId}/process`, {
                method: 'POST'
            });
            
            if (response.ok) {
                this.showNotification('Đã chuyển sang xử lý', 'success');
                this.updateOrderStatus(orderId, 'processing');
            }
        } catch (error) {
            this.showNotification('Lỗi khi xử lý đơn hàng', 'error');
        }
    }

    async shipOrder(orderId) {
        const trackingNumber = prompt('Nhập mã vận đơn:');
        if (!trackingNumber) return;

        try {
            const response = await fetch(`/api/admin/orders/${orderId}/ship`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ trackingNumber })
            });
            
            if (response.ok) {
                this.showNotification('Đã chuyển sang trạng thái đang giao', 'success');
                this.updateOrderStatus(orderId, 'shipped');
            }
        } catch (error) {
            this.showNotification('Lỗi khi cập nhật trạng thái giao hàng', 'error');
        }
    }

    async completeOrder(orderId) {
        try {
            const response = await fetch(`/api/admin/orders/${orderId}/complete`, {
                method: 'POST'
            });
            
            if (response.ok) {
                this.showNotification('Đã hoàn thành đơn hàng', 'success');
                this.updateOrderStatus(orderId, 'completed');
            }
        } catch (error) {
            this.showNotification('Lỗi khi hoàn thành đơn hàng', 'error');
        }
    }

    async cancelOrder(orderId) {
        const reason = prompt('Lý do hủy đơn hàng:');
        if (!reason) return;

        try {
            const response = await fetch(`/api/admin/orders/${orderId}/cancel`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ reason })
            });
            
            if (response.ok) {
                this.showNotification('Đã hủy đơn hàng', 'success');
                this.updateOrderStatus(orderId, 'cancelled');
            }
        } catch (error) {
            this.showNotification('Lỗi khi hủy đơn hàng', 'error');
        }
    }

    viewOrderDetails(orderId) {
        window.location.href = `/admin/orders/${orderId}`;
    }

    editOrder(orderId) {
        window.location.href = `/admin/orders/${orderId}/edit`;
    }

    printOrder(orderId) {
        window.open(`/admin/orders/${orderId}/print`, '_blank');
    }

    async bulkConfirmOrders(orderIds) {
        try {
            const response = await fetch('/api/admin/orders/bulk-confirm', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ orderIds })
            });
            
            if (response.ok) {
                this.showNotification(`Đã xác nhận ${orderIds.length} đơn hàng`, 'success');
                orderIds.forEach(id => this.updateOrderStatus(id, 'confirmed'));
            }
        } catch (error) {
            this.showNotification('Lỗi khi xác nhận đơn hàng', 'error');
        }
    }

    // Similar methods for other bulk actions...

    printOrders() {
        const selectedOrders = this.selectedOrders.size > 0 ? 
            Array.from(this.selectedOrders) : null;
        
        const queryString = selectedOrders ? `?ids=${selectedOrders.join(',')}` : '';
        window.open(`/admin/orders/print${queryString}`, '_blank');
    }

    exportOrders() {
        const selectedOrders = this.selectedOrders.size > 0 ? 
            Array.from(this.selectedOrders) : null;
        
        const queryString = selectedOrders ? `?ids=${selectedOrders.join(',')}` : '';
        window.open(`/api/admin/orders/export${queryString}`, '_blank');
    }

    updateOrderStatus(orderId, newStatus) {
        const row = document.querySelector(`tr[data-order-id="${orderId}"]`);
        if (row) {
            const statusBadge = row.querySelector('.order-status');
            if (statusBadge) {
                // Update badge class and text
                const statusConfig = this.getStatusConfig(newStatus);
                statusBadge.className = `badge badge-${statusConfig.class}`;
                statusBadge.textContent = statusConfig.text;
                
                // Update data attribute
                row.dataset.status = newStatus;
            }
        }
    }

    getStatusConfig(status) {
        const configs = {
            pending: { class: 'warning', text: 'Chờ xác nhận' },
            confirmed: { class: 'info', text: 'Đã xác nhận' },
            processing: { class: 'primary', text: 'Đang xử lý' },
            shipped: { class: 'info', text: 'Đang giao' },
            completed: { class: 'success', text: 'Đã giao' },
            cancelled: { class: 'danger', text: 'Đã hủy' }
        };
        
        return configs[status] || { class: 'secondary', text: status };
    }

    updateBulkOrderActionsState() {
        const bulkActions = document.querySelector('.bulk-actions-bar');
        const selectedCount = document.getElementById('selectedOrdersCount');
        
        if (this.selectedOrders.size > 0) {
            bulkActions?.classList.add('has-selection');
            if (selectedCount) {
                selectedCount.textContent = this.selectedOrders.size;
            }
        } else {
            bulkActions?.classList.remove('has-selection');
        }
    }

    initDatePickers() {
        // Initialize flatpickr or native date inputs
        const dateInputs = document.querySelectorAll('input[type="date"]');
        dateInputs.forEach(input => {
            input.addEventListener('change', () => {
                this.applyFilters();
            });
        });
    }

    initStatusFilters() {
        const statusSelect = document.getElementById('statusFilter');
        if (statusSelect) {
            statusSelect.addEventListener('change', () => {
                this.applyFilters();
            });
        }
    }

    initPaymentFilters() {
        const paymentSelect = document.getElementById('paymentFilter');
        if (paymentSelect) {
            paymentSelect.addEventListener('change', () => {
                this.applyFilters();
            });
        }
    }

    setupStatusWebSocket() {
        // WebSocket implementation for real-time updates
        // This would connect to your WebSocket server
    }

    setupAutoRefresh() {
        // Auto-refresh for pending orders every 30 seconds
        setInterval(() => {
            this.refreshPendingOrders();
        }, 30000);
    }

    async refreshPendingOrders() {
        try {
            const response = await fetch('/api/admin/orders/pending-count');
            const data = await response.json();
            
            this.updatePendingCount(data.count);
        } catch (error) {
            console.error('Error refreshing pending orders:', error);
        }
    }

    updatePendingCount(count) {
        const badge = document.querySelector('.menu-badge');
        if (badge) {
            badge.textContent = count;
        }
    }

    initOrderStatsChart() {
        // Implementation for order statistics chart
        const ctx = document.getElementById('orderStatsChart');
        if (ctx) {
            // Chart.js implementation
        }
    }

    initRevenueChart() {
        // Implementation for revenue chart
        const ctx = document.getElementById('revenueChart');
        if (ctx) {
            // Chart.js implementation
        }
    }

    initStatusDistributionChart() {
        // Implementation for status distribution chart
        const ctx = document.getElementById('statusDistributionChart');
        if (ctx) {
            // Chart.js implementation
        }
    }

    showNotification(message, type = 'info') {
        if (window.AdminUtils) {
            AdminUtils.showNotification(message, type);
        } else {
            alert(message);
        }
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new OrdersManager();
});