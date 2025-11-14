// Admin Products JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // Initialize all functionality
    initBulkActions();
    initSearch();
    initFilters();
    initQuickActions();
    initTableInteractions();
});

// Bulk Actions Functionality
function initBulkActions() {
    const selectAll = document.getElementById('selectAll');
    const productSelects = document.querySelectorAll('.product-select');
    const bulkActionsBar = document.getElementById('bulkActionsBar');
    const selectedCount = document.getElementById('selectedCount');
    const clearSelection = document.getElementById('clearSelection');
    const bulkAction = document.getElementById('bulkAction');

    // Select All functionality
    if (selectAll) {
        selectAll.addEventListener('change', function() {
            const isChecked = this.checked;
            productSelects.forEach(checkbox => {
                checkbox.checked = isChecked;
            });
            updateBulkActionsBar();
        });
    }

    // Individual product selection
    productSelects.forEach(checkbox => {
        checkbox.addEventListener('change', updateBulkActionsBar);
    });

    // Update bulk actions bar
    function updateBulkActionsBar() {
        const selectedProducts = document.querySelectorAll('.product-select:checked');
        const count = selectedProducts.length;
        
        if (count > 0) {
            bulkActionsBar.style.display = 'flex';
            selectedCount.textContent = count;
        } else {
            bulkActionsBar.style.display = 'none';
        }

        // Update select all checkbox
        if (selectAll) {
            selectAll.checked = count === productSelects.length;
            selectAll.indeterminate = count > 0 && count < productSelects.length;
        }
    }

    // Clear selection
    if (clearSelection) {
        clearSelection.addEventListener('click', function() {
            productSelects.forEach(checkbox => {
                checkbox.checked = false;
            });
            if (selectAll) selectAll.checked = false;
            bulkActionsBar.style.display = 'none';
        });
    }

    // Bulk action execution
    if (bulkAction) {
        bulkAction.addEventListener('change', function() {
            const action = this.value;
            if (!action) return;

            const selectedProducts = Array.from(document.querySelectorAll('.product-select:checked'))
                .map(checkbox => checkbox.dataset.productId);

            if (selectedProducts.length === 0) {
                showAlert('Vui lòng chọn ít nhất một sản phẩm', 'warning');
                this.value = '';
                return;
            }

            executeBulkAction(action, selectedProducts);
            this.value = '';
        });
    }
}

// Search Functionality
function initSearch() {
    const searchInput = document.getElementById('productSearch');
    if (!searchInput) return;

    let searchTimeout;
    
    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            performSearch(this.value.trim());
        }, 300);
    });

    function performSearch(query) {
        const rows = document.querySelectorAll('#productsTable tbody tr');
        let hasResults = false;

        rows.forEach(row => {
            const productName = row.querySelector('.product-name').textContent.toLowerCase();
            const sku = row.querySelector('small.text-muted').textContent.toLowerCase();
            const searchText = query.toLowerCase();

            const matches = productName.includes(searchText) || sku.includes(searchText);
            row.style.display = matches ? '' : 'none';
            
            if (matches) hasResults = true;
        });

        // Show no results message if needed
        showNoResultsMessage(!hasResults && query.length > 0);
    }

    function showNoResultsMessage(show) {
        let noResultsRow = document.getElementById('noResultsMessage');
        
        if (show && !noResultsRow) {
            noResultsRow = document.createElement('tr');
            noResultsRow.id = 'noResultsMessage';
            noResultsRow.innerHTML = `
                <td colspan="8" class="text-center py-4 text-muted">
                    <i class="fas fa-search fa-2x mb-3 d-block"></i>
                    Không tìm thấy sản phẩm phù hợp
                </td>
            `;
            document.querySelector('#productsTable tbody').appendChild(noResultsRow);
        } else if (!show && noResultsRow) {
            noResultsRow.remove();
        }
    }
}

// Filter Functionality
function initFilters() {
    const categoryFilter = document.getElementById('categoryFilter');
    const statusFilter = document.getElementById('statusFilter');
    const stockFilter = document.getElementById('stockFilter');
    const applyFilters = document.getElementById('applyFilters');

    if (applyFilters) {
        applyFilters.addEventListener('click', applyAllFilters);
    }

    // Auto-apply filters when select changes
    [categoryFilter, statusFilter, stockFilter].forEach(filter => {
        if (filter) {
            filter.addEventListener('change', applyAllFilters);
        }
    });

    function applyAllFilters() {
        const categoryValue = categoryFilter ? categoryFilter.value : '';
        const statusValue = statusFilter ? statusFilter.value : '';
        const stockValue = stockFilter ? stockFilter.value : '';

        const rows = document.querySelectorAll('#productsTable tbody tr');
        let visibleCount = 0;

        rows.forEach(row => {
            if (row.id === 'noResultsMessage') return;

            const categoryMatch = !categoryValue || row.dataset.category === categoryValue;
            const statusMatch = !statusValue || row.dataset.status === statusValue;
            const stockMatch = !stockValue || row.dataset.stock === stockValue;

            const shouldShow = categoryMatch && statusMatch && stockMatch;
            row.style.display = shouldShow ? '' : 'none';
            
            if (shouldShow) visibleCount++;
        });

        // Show message if no results
        if (visibleCount === 0) {
            showNoFilterResultsMessage();
        } else {
            hideNoFilterResultsMessage();
        }
    }

    function showNoFilterResultsMessage() {
        let message = document.getElementById('noFilterResults');
        if (!message) {
            message = document.createElement('tr');
            message.id = 'noFilterResults';
            message.innerHTML = `
                <td colspan="8" class="text-center py-4 text-muted">
                    <i class="fas fa-filter fa-2x mb-3 d-block"></i>
                    Không có sản phẩm nào phù hợp với bộ lọc
                    <br>
                    <button class="btn btn-sm btn-outline-primary mt-2" onclick="resetFilters()">
                        <i class="fas fa-times me-1"></i>Xóa bộ lọc
                    </button>
                </td>
            `;
            document.querySelector('#productsTable tbody').appendChild(message);
        }
    }

    function hideNoFilterResultsMessage() {
        const message = document.getElementById('noFilterResults');
        if (message) message.remove();
    }
}

// Quick Actions Functionality
function initQuickActions() {
    const quickActionBtns = document.querySelectorAll('.quick-action-btn');
    
    quickActionBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const action = this.dataset.action;
            const productId = this.dataset.productId;
            
            switch (action) {
                case 'edit':
                    editProduct(productId);
                    break;
                case 'view':
                    viewProduct(productId);
                    break;
                case 'toggle-status':
                    toggleProductStatus(productId, this);
                    break;
                case 'delete':
                    confirmDeleteProduct(productId);
                    break;
            }
        });
    });
}

// Table Interactions
function initTableInteractions() {
    const table = document.getElementById('productsTable');
    if (!table) return;

    // Make rows clickable (except when clicking action buttons)
    table.addEventListener('click', function(e) {
        const row = e.target.closest('tr');
        const isActionButton = e.target.closest('.quick-action-btn') || 
                              e.target.closest('.product-select') ||
                              e.target.closest('th');
        
        if (row && !isActionButton && !row.querySelector('td:first-child input[type="checkbox"]').contains(e.target)) {
            const productId = row.dataset.productId;
            viewProduct(productId);
        }
    });

    // Add hover effects
    table.addEventListener('mouseover', function(e) {
        const row = e.target.closest('tr');
        if (row && !row.classList.contains('table-hover')) {
            row.style.cursor = 'pointer';
        }
    });
}

// Product Actions
function editProduct(productId) {
    showAlert(`Chuyển đến trang chỉnh sửa sản phẩm #${productId}`, 'info');
    // window.location.href = `admin_products_edit.php?id=${productId}`;
}

function viewProduct(productId) {
    showAlert(`Xem chi tiết sản phẩm #${productId}`, 'info');
    // window.location.href = `admin_products_view.php?id=${productId}`;
}

function toggleProductStatus(productId, button) {
    const isActive = button.closest('tr').querySelector('.status-badge').classList.contains('bg-success');
    const newStatus = !isActive;
    
    // Simulate API call
    showAlert(`Đang ${newStatus ? 'kích hoạt' : 'vô hiệu hóa'} sản phẩm...`, 'info');
    
    setTimeout(() => {
        const statusBadge = button.closest('tr').querySelector('.status-badge');
        const badgeClass = newStatus ? 'bg-success' : 'bg-danger';
        const badgeText = newStatus ? 'Đang bán' : 'Ngừng bán';
        const buttonTitle = newStatus ? 'Ngừng bán' : 'Kích hoạt';
        
        statusBadge.className = `badge ${badgeClass} status-badge`;
        statusBadge.textContent = badgeText;
        button.title = buttonTitle;
        
        showAlert(`Đã ${newStatus ? 'kích hoạt' : 'vô hiệu hóa'} sản phẩm thành công`, 'success');
    }, 1000);
}

function confirmDeleteProduct(productId) {
    const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
    const confirmDeleteBtn = document.getElementById('confirmDelete');
    
    // Set up confirmation
    confirmDeleteBtn.onclick = function() {
        deleteProduct(productId);
        deleteModal.hide();
    };
    
    deleteModal.show();
}

function deleteProduct(productId) {
    // Simulate API call
    showAlert('Đang xóa sản phẩm...', 'info');
    
    setTimeout(() => {
        const row = document.querySelector(`tr[data-product-id="${productId}"]`);
        if (row) {
            row.style.opacity = '0';
            setTimeout(() => {
                row.remove();
                showAlert('Đã xóa sản phẩm thành công', 'success');
                updateProductCounts();
            }, 300);
        }
    }, 1000);
}

// Bulk Actions Execution
function executeBulkAction(action, productIds) {
    switch (action) {
        case 'activate':
            bulkUpdateStatus(productIds, true);
            break;
        case 'deactivate':
            bulkUpdateStatus(productIds, false);
            break;
        case 'delete':
            bulkDeleteProducts(productIds);
            break;
        case 'export':
            exportProducts(productIds);
            break;
    }
}

function bulkUpdateStatus(productIds, activate) {
    showAlert(`Đang ${activate ? 'kích hoạt' : 'vô hiệu hóa'} ${productIds.length} sản phẩm...`, 'info');
    
    setTimeout(() => {
        productIds.forEach(productId => {
            const row = document.querySelector(`tr[data-product-id="${productId}"]`);
            if (row) {
                const statusBadge = row.querySelector('.status-badge');
                const button = row.querySelector('[data-action="toggle-status"]');
                
                if (statusBadge && button) {
                    const badgeClass = activate ? 'bg-success' : 'bg-danger';
                    const badgeText = activate ? 'Đang bán' : 'Ngừng bán';
                    const buttonTitle = activate ? 'Ngừng bán' : 'Kích hoạt';
                    
                    statusBadge.className = `badge ${badgeClass} status-badge`;
                    statusBadge.textContent = badgeText;
                    button.title = buttonTitle;
                }
            }
        });
        
        showAlert(`Đã ${activate ? 'kích hoạt' : 'vô hiệu hóa'} ${productIds.length} sản phẩm thành công`, 'success');
        clearSelection();
    }, 1500);
}

function bulkDeleteProducts(productIds) {
    if (!confirm(`Bạn có chắc chắn muốn xóa ${productIds.length} sản phẩm?`)) {
        return;
    }
    
    showAlert(`Đang xóa ${productIds.length} sản phẩm...`, 'info');
    
    setTimeout(() => {
        productIds.forEach(productId => {
            const row = document.querySelector(`tr[data-product-id="${productId}"]`);
            if (row) row.remove();
        });
        
        showAlert(`Đã xóa ${productIds.length} sản phẩm thành công`, 'success');
        updateProductCounts();
        clearSelection();
    }, 2000);
}

function exportProducts(productIds) {
    showAlert(`Đang xuất ${productIds.length} sản phẩm...`, 'info');
    
    // Simulate export process
    setTimeout(() => {
        const data = productIds.map(id => `Product #${id}`).join(', ');
        showAlert(`Đã xuất dữ liệu: ${data}`, 'success');
    }, 1000);
}

// Utility Functions
function showAlert(message, type = 'info') {
    // Remove existing alerts
    const existingAlert = document.querySelector('.custom-alert');
    if (existingAlert) existingAlert.remove();

    // Create new alert
    const alert = document.createElement('div');
    alert.className = `custom-alert alert alert-${type} alert-dismissible fade show`;
    alert.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
        min-width: 300px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    `;
    alert.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;

    document.body.appendChild(alert);

    // Auto remove after 5 seconds
    setTimeout(() => {
        if (alert.parentNode) {
            alert.remove();
        }
    }, 5000);
}

function clearSelection() {
    document.querySelectorAll('.product-select').forEach(checkbox => {
        checkbox.checked = false;
    });
    document.getElementById('selectAll').checked = false;
    document.getElementById('bulkActionsBar').style.display = 'none';
}

function updateProductCounts() {
    // Update total products count
    const remainingProducts = document.querySelectorAll('#productsTable tbody tr:not([style*="display: none"])').length;
    const totalBadge = document.querySelector('.menu-badge');
    if (totalBadge) {
        totalBadge.textContent = remainingProducts;
    }
}

function resetFilters() {
    document.getElementById('categoryFilter').value = '';
    document.getElementById('statusFilter').value = '';
    document.getElementById('stockFilter').value = '';
    applyAllFilters();
}

// Global functions for inline event handlers
window.resetFilters = resetFilters;

// Export functionality
document.getElementById('exportProducts')?.addEventListener('click', function() {
    const allProductIds = Array.from(document.querySelectorAll('.product-select'))
        .map(checkbox => checkbox.dataset.productId);
    exportProducts(allProductIds);
});

// Initialize tooltips
document.addEventListener('DOMContentLoaded', function() {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[title]'));
    tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});