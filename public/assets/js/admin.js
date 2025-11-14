// Admin JavaScript
class PharmacyAdmin {
    constructor() {
        this.init();
    }

    init() {
        this.initSidebar();
        this.initCharts();
        this.initDataTables();
        this.initNotifications();
        this.bindEvents();
    }

    // Sidebar toggle
    initSidebar() {
        const toggleBtn = document.querySelector('.toggle-sidebar');
        const sidebar = document.querySelector('.sidebar');
        const mainContent = document.querySelector('.main-content');

        if (toggleBtn) {
            toggleBtn.addEventListener('click', () => {
                sidebar.classList.toggle('collapsed');
                mainContent.classList.toggle('expanded');
                
                // Update localStorage
                const isCollapsed = sidebar.classList.contains('collapsed');
                localStorage.setItem('sidebarCollapsed', isCollapsed);
            });
        }

        // Load sidebar state from localStorage
        const sidebarCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
        if (sidebarCollapsed) {
            sidebar.classList.add('collapsed');
            mainContent.classList.add('expanded');
        }
    }

    // Initialize charts
    initCharts() {
        this.initRevenueChart();
        this.initSalesChart();
        this.initInventoryChart();
    }

    initRevenueChart() {
        const ctx = document.getElementById('revenueChart');
        if (!ctx) return;

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['T2', 'T3', 'T4', 'T5', 'T6', 'T7', 'CN'],
                datasets: [{
                    label: 'Doanh thu',
                    data: [12000000, 19000000, 15000000, 22000000, 18000000, 25000000, 21000000],
                    borderColor: '#28a745',
                    backgroundColor: 'rgba(40, 167, 69, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return `Doanh thu: ${context.parsed.y.toLocaleString('vi-VN')}đ`;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return value.toLocaleString('vi-VN') + 'đ';
                            }
                        }
                    }
                }
            }
        });
    }

    initSalesChart() {
        const ctx = document.getElementById('salesChart');
        if (!ctx) return;

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Kháng sinh', 'Giảm đau', 'Vitamin', 'Tiêu hóa', 'Da liễu'],
                datasets: [{
                    label: 'Số lượng bán',
                    data: [45, 32, 28, 19, 15],
                    backgroundColor: [
                        'rgba(40, 167, 69, 0.8)',
                        'rgba(108, 117, 125, 0.8)',
                        'rgba(255, 193, 7, 0.8)',
                        'rgba(0, 123, 255, 0.8)',
                        'rgba(220, 53, 69, 0.8)'
                    ],
                    borderColor: [
                        '#28a745',
                        '#6c757d',
                        '#ffc107',
                        '#007bff',
                        '#dc3545'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    }

    initInventoryChart() {
        const ctx = document.getElementById('inventoryChart');
        if (!ctx) return;

        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Đủ hàng', 'Sắp hết', 'Hết hàng'],
                datasets: [{
                    data: [75, 15, 10],
                    backgroundColor: [
                        '#28a745',
                        '#ffc107',
                        '#dc3545'
                    ],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                cutout: '70%',
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    }

    // Initialize data tables
    initDataTables() {
        const tables = document.querySelectorAll('.data-table');
        tables.forEach(table => {
            // Simple sorting functionality
            const headers = table.querySelectorAll('th[data-sort]');
            headers.forEach(header => {
                header.style.cursor = 'pointer';
                header.addEventListener('click', () => {
                    this.sortTable(table, header.cellIndex, header.dataset.sort);
                });
            });
        });
    }

    sortTable(table, column, type) {
        const tbody = table.querySelector('tbody');
        const rows = Array.from(tbody.querySelectorAll('tr'));
        const isNumeric = type === 'numeric';
        const isDate = type === 'date';

        rows.sort((a, b) => {
            let aValue = a.cells[column].textContent.trim();
            let bValue = b.cells[column].textContent.trim();

            if (isNumeric) {
                aValue = parseFloat(aValue.replace(/[^\d.-]/g, ''));
                bValue = parseFloat(bValue.replace(/[^\d.-]/g, ''));
            } else if (isDate) {
                aValue = new Date(aValue);
                bValue = new Date(bValue);
            }

            if (aValue < bValue) return -1;
            if (aValue > bValue) return 1;
            return 0;
        });

        // Remove existing rows
        while (tbody.firstChild) {
            tbody.removeChild(tbody.firstChild);
        }

        // Add sorted rows
        rows.forEach(row => tbody.appendChild(row));
    }

    // Notifications
    initNotifications() {
        this.showNotification = (message, type = 'info') => {
            const notification = document.createElement('div');
            notification.className = `alert alert-${type} alert-dismissible fade show`;
            notification.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            // Add to page
            const container = document.querySelector('.content');
            container.insertBefore(notification, container.firstChild);
            
            // Auto remove after 5 seconds
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.remove();
                }
            }, 5000);
        };
    }

    // Bind events
    bindEvents() {
        // Logout confirmation
        const logoutBtn = document.querySelector('.btn-logout');
        if (logoutBtn) {
            logoutBtn.addEventListener('click', (e) => {
                if (!confirm('Bạn có chắc muốn đăng xuất?')) {
                    e.preventDefault();
                }
            });
        }

        // Auto update stats every 30 seconds
        setInterval(() => {
            this.updateStats();
        }, 30000);

        // Search functionality
        const searchInput = document.querySelector('.search-input');
        if (searchInput) {
            searchInput.addEventListener('input', (e) => {
                this.handleSearch(e.target.value);
            });
        }
    }

    // Update statistics
    async updateStats() {
        try {
            // In a real app, this would be an API call
            console.log('Updating statistics...');
            
            // Simulate API call
            // const response = await fetch('/api/admin/stats');
            // const data = await response.json();
            // this.updateStatsUI(data);
            
        } catch (error) {
            console.error('Error updating stats:', error);
        }
    }

    updateStatsUI(data) {
        // Update stat cards with new data
        const statCards = document.querySelectorAll('.stat-value');
        // Implementation would depend on your data structure
    }

    // Handle search
    handleSearch(query) {
        const tables = document.querySelectorAll('.data-table tbody');
        tables.forEach(table => {
            const rows = table.querySelectorAll('tr');
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                if (text.includes(query.toLowerCase())) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    }

    // Export data
    exportTable(tableId, filename) {
        const table = document.getElementById(tableId);
        let csv = [];
        const rows = table.querySelectorAll('tr');

        for (let i = 0; i < rows.length; i++) {
            const row = [], cols = rows[i].querySelectorAll('td, th');
            
            for (let j = 0; j < cols.length; j++) {
                row.push(cols[j].innerText);
            }
            
            csv.push(row.join(','));
        }

        // Download CSV file
        const csvFile = new Blob([csv.join('\n')], { type: 'text/csv' });
        const downloadLink = document.createElement('a');
        downloadLink.download = filename;
        downloadLink.href = window.URL.createObjectURL(csvFile);
        downloadLink.style.display = 'none';
        document.body.appendChild(downloadLink);
        downloadLink.click();
        document.body.removeChild(downloadLink);
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new PharmacyAdmin();
});

// Utility functions
const AdminUtils = {
    formatCurrency: (amount) => {
        return new Intl.NumberFormat('vi-VN', {
            style: 'currency',
            currency: 'VND'
        }).format(amount);
    },

    formatDate: (dateString) => {
        return new Date(dateString).toLocaleDateString('vi-VN');
    },

    debounce: (func, wait) => {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
};