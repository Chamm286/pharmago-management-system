// Admin Users JavaScript
class UsersManager {
    constructor() {
        this.selectedUsers = new Set();
        this.baseUrl = window.location.origin + '/PHARMAGO/public';
        this.init();
    }

    init() {
        this.initEventListeners();
        this.initUserTable();
        this.initRoleManagement();
        this.initPermissionSystem();
        this.initUserSearch();
        this.initBulkActions();
    }

    initEventListeners() {
        // User selection
        document.addEventListener('change', (e) => {
            if (e.target.classList.contains('user-select')) {
                this.handleUserSelection(e.target);
            }
        });

        // User actions
        document.addEventListener('click', (e) => {
            if (e.target.closest('.user-action-btn')) {
                this.handleUserAction(e.target.closest('.user-action-btn'));
            }
        });

        // Role changes
        document.addEventListener('change', (e) => {
            if (e.target.classList.contains('role-select')) {
                this.handleRoleChange(e.target);
            }
        });

        // Status toggles
        document.addEventListener('click', (e) => {
            if (e.target.closest('.status-toggle')) {
                this.toggleUserStatus(e.target.closest('.status-toggle'));
            }
        });

        // Bulk actions
        document.addEventListener('click', (e) => {
            if (e.target.closest('.bulk-action-btn')) {
                this.handleBulkAction(e.target.closest('.bulk-action-btn'));
            }
        });
    }

    initUserTable() {
        this.setupUserTableSorting();
        this.setupUserTableFilters();
    }

    initRoleManagement() {
        this.loadRoleTemplates();
        this.setupRolePermissions();
    }

    initPermissionSystem() {
        this.setupPermissionToggles();
        this.setupBulkPermissions();
    }

    initUserSearch() {
        const searchInput = document.querySelector('.search-box input');
        if (searchInput) {
            searchInput.addEventListener('input', (e) => {
                this.handleUserSearch(e.target.value);
            });
        }
    }

    initBulkActions() {
        // Select all checkbox
        const selectAll = document.getElementById('selectAllUsers');
        if (selectAll) {
            selectAll.addEventListener('change', (e) => {
                this.toggleSelectAllUsers(e.target.checked);
            });
        }
    }

    handleUserSelection(checkbox) {
        const userId = checkbox.dataset.userId;
        
        if (checkbox.checked) {
            this.selectedUsers.add(userId);
        } else {
            this.selectedUsers.delete(userId);
        }
        
        this.updateBulkUserActionsState();
        this.updateSelectAllCheckbox();
    }

    toggleSelectAllUsers(checked) {
        const checkboxes = document.querySelectorAll('.user-select');
        checkboxes.forEach(checkbox => {
            checkbox.checked = checked;
            this.handleUserSelection(checkbox);
        });
    }

    updateSelectAllCheckbox() {
        const selectAll = document.getElementById('selectAllUsers');
        if (selectAll) {
            const checkboxes = document.querySelectorAll('.user-select');
            const checkedCount = document.querySelectorAll('.user-select:checked').length;
            
            if (checkedCount === 0) {
                selectAll.checked = false;
                selectAll.indeterminate = false;
            } else if (checkedCount === checkboxes.length) {
                selectAll.checked = true;
                selectAll.indeterminate = false;
            } else {
                selectAll.checked = false;
                selectAll.indeterminate = true;
            }
        }
    }

    handleUserAction(button) {
        const action = button.dataset.action;
        const userId = button.dataset.userId;
        
        switch (action) {
            case 'view':
                this.viewUserProfile(userId);
                break;
            case 'edit':
                this.editUser(userId);
                break;
            case 'delete':
                this.deleteUser(userId);
                break;
            case 'impersonate':
                this.impersonateUser(userId);
                break;
            case 'reset-password':
                this.resetUserPassword(userId);
                break;
            case 'send-email':
                this.sendUserEmail(userId);
                break;
            case 'activity':
                this.viewUserActivity(userId);
                break;
        }
    }

    handleBulkAction(button) {
        const action = button.dataset.action;
        
        if (this.selectedUsers.size === 0) {
            this.showNotification('Vui lòng chọn ít nhất một người dùng', 'warning');
            return;
        }

        switch (action) {
            case 'bulk-delete':
                this.bulkDeleteUsers();
                break;
            case 'bulk-activate':
                this.bulkUpdateStatus(true);
                break;
            case 'bulk-deactivate':
                this.bulkUpdateStatus(false);
                break;
            case 'bulk-export':
                this.bulkExportUsers();
                break;
            case 'bulk-role':
                this.bulkChangeRole();
                break;
        }
    }

    handleRoleChange(select) {
        const userId = select.dataset.userId;
        const newRole = select.value;
        
        if (confirm(`Thay đổi vai trò của người dùng thành ${this.getRoleText(newRole)}?`)) {
            this.updateUserRole(userId, newRole);
        } else {
            // Reset to original value
            select.value = select.dataset.originalValue;
        }
    }

    async toggleUserStatus(button) {
        const userId = button.dataset.userId;
        const currentStatus = button.dataset.status === 'active';
        const newStatus = !currentStatus;
        
        if (!confirm(`Bạn có chắc muốn ${newStatus ? 'kích hoạt' : 'khóa'} người dùng này?`)) {
            return;
        }

        try {
            const formData = new FormData();
            formData.append('user_id', userId);
            formData.append('current_status', currentStatus ? '1' : '0');
            formData.append('toggle_status', '1');

            const response = await fetch('admin_users.php', {
                method: 'POST',
                body: formData
            });
            
            if (response.ok) {
                this.updateUserStatusUI(button, newStatus);
                this.showNotification(
                    `Đã ${newStatus ? 'kích hoạt' : 'khóa'} người dùng thành công`,
                    'success'
                );
                
                // Reload page after 1 second to reflect changes
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            }
        } catch (error) {
            console.error('Error:', error);
            this.showNotification('Lỗi khi cập nhật trạng thái', 'error');
        }
    }

    async deleteUser(userId) {
        if (!confirm('Bạn có chắc muốn xóa người dùng này? Hành động này không thể hoàn tác.')) {
            return;
        }

        try {
            const formData = new FormData();
            formData.append('user_id', userId);
            formData.append('delete_user', '1');

            const response = await fetch('admin_users.php', {
                method: 'POST',
                body: formData
            });
            
            if (response.ok) {
                this.showNotification('Đã xóa người dùng thành công', 'success');
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            }
        } catch (error) {
            this.showNotification('Lỗi khi xóa người dùng', 'error');
        }
    }

    handleUserSearch(query) {
        const rows = document.querySelectorAll('.user-table tbody tr');
        const searchTerm = query.toLowerCase().trim();
        
        if (!searchTerm) {
            rows.forEach(row => row.style.display = '');
            return;
        }

        rows.forEach(row => {
            const userName = row.querySelector('.user-name')?.textContent.toLowerCase() || '';
            const userEmail = row.querySelector('.user-email')?.textContent.toLowerCase() || '';
            const userPhone = row.querySelector('.user-phone')?.textContent.toLowerCase() || '';
            const userUsername = row.querySelector('.user-username')?.textContent.toLowerCase() || '';
            
            const searchableText = `${userName} ${userEmail} ${userPhone} ${userUsername}`;
            
            if (searchableText.includes(searchTerm)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }

    async updateUserRole(userId, newRole) {
        try {
            const formData = new FormData();
            formData.append('user_id', userId);
            formData.append('new_role', newRole);
            formData.append('update_role', '1');

            const response = await fetch('admin_users.php', {
                method: 'POST',
                body: formData
            });
            
            if (response.ok) {
                this.updateUserRoleUI(userId, newRole);
                this.showNotification('Đã cập nhật vai trò người dùng', 'success');
                
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            }
        } catch (error) {
            this.showNotification('Lỗi khi cập nhật vai trò', 'error');
        }
    }

    updateUserStatusUI(button, isActive) {
        const newStatus = isActive ? 'active' : 'inactive';
        const newIcon = isActive ? 'unlock' : 'lock';
        const newClass = isActive ? 'success' : 'danger';
        const newTitle = isActive ? 'Khóa' : 'Mở khóa';
        
        button.dataset.status = newStatus;
        button.className = `btn btn-sm btn-outline-${newClass} status-toggle`;
        button.innerHTML = `<i class="fas fa-${newIcon}"></i>`;
        button.title = newTitle;
    }

    updateUserRoleUI(userId, newRole) {
        const row = document.querySelector(`tr[data-user-id="${userId}"]`);
        if (row) {
            const roleBadge = row.querySelector('.user-role');
            if (roleBadge) {
                const roleConfig = this.getRoleConfig(newRole);
                roleBadge.className = `badge bg-${roleConfig.class}`;
                roleBadge.textContent = roleConfig.text;
            }
        }
    }

    getRoleConfig(role) {
        const configs = {
            admin: { class: 'danger', text: 'Quản trị viên' },
            staff: { class: 'warning', text: 'Nhân viên' },
            customer: { class: 'success', text: 'Khách hàng' }
        };
        
        return configs[role] || { class: 'secondary', text: role };
    }

    getRoleText(role) {
        const configs = {
            admin: 'Quản trị viên',
            staff: 'Nhân viên',
            customer: 'Khách hàng'
        };
        return configs[role] || role;
    }

    viewUserProfile(userId) {
        window.location.href = `admin_user_details.php?id=${userId}`;
    }

    editUser(userId) {
        window.location.href = `admin_user_edit.php?id=${userId}`;
    }

    async impersonateUser(userId) {
        if (!confirm('Bạn có chắc muốn đăng nhập với tư cách người dùng này?')) {
            return;
        }

        try {
            const response = await fetch(`admin_users.php?action=impersonate&id=${userId}`, {
                method: 'POST'
            });
            
            if (response.ok) {
                window.location.href = '../index.php';
            }
        } catch (error) {
            this.showNotification('Lỗi khi đăng nhập thay thế', 'error');
        }
    }

    async resetUserPassword(userId) {
        const newPassword = prompt('Nhập mật khẩu mới (để trống để tạo tự động):');
        
        try {
            const formData = new FormData();
            formData.append('user_id', userId);
            formData.append('new_password', newPassword);
            formData.append('reset_password', '1');

            const response = await fetch('admin_users.php', {
                method: 'POST',
                body: formData
            });
            
            if (response.ok) {
                const result = await response.text();
                if (newPassword === '') {
                    alert(`Mật khẩu mới: ${result}`);
                }
                this.showNotification('Đã đặt lại mật khẩu', 'success');
            }
        } catch (error) {
            this.showNotification('Lỗi khi đặt lại mật khẩu', 'error');
        }
    }

    async sendUserEmail(userId) {
        const subject = prompt('Tiêu đề email:');
        if (!subject) return;
        
        const message = prompt('Nội dung email:');
        if (!message) return;

        try {
            const formData = new FormData();
            formData.append('user_id', userId);
            formData.append('subject', subject);
            formData.append('message', message);
            formData.append('send_email', '1');

            const response = await fetch('admin_users.php', {
                method: 'POST',
                body: formData
            });
            
            if (response.ok) {
                this.showNotification('Đã gửi email', 'success');
            }
        } catch (error) {
            this.showNotification('Lỗi khi gửi email', 'error');
        }
    }

    viewUserActivity(userId) {
        window.location.href = `admin_user_activity.php?id=${userId}`;
    }

    async bulkDeleteUsers() {
        if (!confirm(`Bạn có chắc muốn xóa ${this.selectedUsers.size} người dùng đã chọn?`)) {
            return;
        }

        try {
            const formData = new FormData();
            formData.append('bulk_action', 'delete');
            this.selectedUsers.forEach(userId => {
                formData.append('user_ids[]', userId);
            });

            const response = await fetch('admin_users.php', {
                method: 'POST',
                body: formData
            });
            
            if (response.ok) {
                this.showNotification(`Đã xóa ${this.selectedUsers.size} người dùng`, 'success');
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            }
        } catch (error) {
            this.showNotification('Lỗi khi xóa người dùng', 'error');
        }
    }

    async bulkUpdateStatus(isActive) {
        const action = isActive ? 'kích hoạt' : 'khóa';
        if (!confirm(`Bạn có chắc muốn ${action} ${this.selectedUsers.size} người dùng đã chọn?`)) {
            return;
        }

        try {
            const formData = new FormData();
            formData.append('bulk_action', 'update_status');
            formData.append('new_status', isActive ? '1' : '0');
            this.selectedUsers.forEach(userId => {
                formData.append('user_ids[]', userId);
            });

            const response = await fetch('admin_users.php', {
                method: 'POST',
                body: formData
            });
            
            if (response.ok) {
                this.showNotification(`Đã ${action} ${this.selectedUsers.size} người dùng`, 'success');
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            }
        } catch (error) {
            this.showNotification(`Lỗi khi ${action} người dùng`, 'error');
        }
    }

    bulkExportUsers() {
        const userIds = Array.from(this.selectedUsers).join(',');
        window.location.href = `admin_users_export.php?user_ids=${userIds}`;
    }

    bulkChangeRole() {
        const newRole = prompt('Nhập vai trò mới (admin/staff/customer):');
        if (!newRole || !['admin', 'staff', 'customer'].includes(newRole)) {
            this.showNotification('Vai trò không hợp lệ', 'error');
            return;
        }

        if (!confirm(`Thay đổi vai trò của ${this.selectedUsers.size} người dùng thành ${this.getRoleText(newRole)}?`)) {
            return;
        }

        this.bulkUpdateRole(newRole);
    }

    async bulkUpdateRole(newRole) {
        try {
            const formData = new FormData();
            formData.append('bulk_action', 'update_role');
            formData.append('new_role', newRole);
            this.selectedUsers.forEach(userId => {
                formData.append('user_ids[]', userId);
            });

            const response = await fetch('admin_users.php', {
                method: 'POST',
                body: formData
            });
            
            if (response.ok) {
                this.showNotification(`Đã cập nhật vai trò cho ${this.selectedUsers.size} người dùng`, 'success');
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            }
        } catch (error) {
            this.showNotification('Lỗi khi cập nhật vai trò', 'error');
        }
    }

    updateBulkUserActionsState() {
        const bulkActions = document.querySelector('.bulk-actions');
        const selectedCount = document.getElementById('selectedUsersCount');
        
        if (this.selectedUsers.size > 0) {
            bulkActions?.classList.remove('d-none');
            if (selectedCount) {
                selectedCount.textContent = this.selectedUsers.size;
            }
        } else {
            bulkActions?.classList.add('d-none');
        }
    }

    setupUserTableSorting() {
        const headers = document.querySelectorAll('.user-table th[data-sort]');
        headers.forEach(header => {
            header.style.cursor = 'pointer';
            header.addEventListener('click', () => {
                this.sortUserTable(header.dataset.sort, header.dataset.sortDirection || 'asc');
            });
        });
    }

    setupUserTableFilters() {
        const filters = document.querySelectorAll('#userFilters select, #userFilters input');
        filters.forEach(filter => {
            filter.addEventListener('change', () => {
                this.applyUserFilters();
            });
        });
    }

    loadRoleTemplates() {
        this.roleTemplates = {
            admin: ['all'],
            staff: ['products.read', 'products.edit', 'orders.read', 'orders.edit'],
            customer: ['profile.read', 'orders.own']
        };
    }

    setupRolePermissions() {
        // Implementation for role-based permissions
    }

    setupPermissionToggles() {
        // Implementation for permission toggles
    }

    setupBulkPermissions() {
        // Implementation for bulk permissions
    }

    applyUserFilters() {
        const roleFilter = document.getElementById('roleFilter')?.value;
        const statusFilter = document.getElementById('statusFilter')?.value;
        const searchFilter = document.querySelector('.search-box input')?.value.toLowerCase() || '';
        
        const rows = document.querySelectorAll('.user-table tbody tr');
        
        rows.forEach(row => {
            let shouldShow = true;
            
            // Role filter
            if (roleFilter && roleFilter !== 'all') {
                const userRole = row.dataset.role;
                if (userRole !== roleFilter) {
                    shouldShow = false;
                }
            }
            
            // Status filter
            if (statusFilter && statusFilter !== 'all') {
                const userStatus = row.dataset.status;
                if (userStatus !== statusFilter) {
                    shouldShow = false;
                }
            }
            
            // Search filter
            if (searchFilter) {
                const text = row.textContent.toLowerCase();
                if (!text.includes(searchFilter)) {
                    shouldShow = false;
                }
            }
            
            row.style.display = shouldShow ? '' : 'none';
        });
    }

    showNotification(message, type = 'info') {
        // Create toast notification
        const toast = document.createElement('div');
        toast.className = `alert alert-${type} alert-dismissible fade show`;
        toast.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            min-width: 300px;
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

    sortUserTable(column, direction) {
        // Implementation for table sorting
        console.log(`Sorting by ${column} in ${direction} order`);
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.usersManager = new UsersManager();
});

// Utility functions
const UserUtils = {
    formatPhoneNumber(phone) {
        if (!phone) return '';
        return phone.replace(/(\d{3})(\d{3})(\d{4})/, '$1 $2 $3');
    },

    formatDate(dateString) {
        return new Date(dateString).toLocaleDateString('vi-VN');
    },

    getStatusBadge(isActive) {
        return isActive ? 
            '<span class="badge bg-success">Hoạt động</span>' : 
            '<span class="badge bg-danger">Đã khóa</span>';
    },

    getRoleBadge(role) {
        const configs = {
            admin: { class: 'danger', text: 'Quản trị viên' },
            staff: { class: 'warning', text: 'Nhân viên' },
            customer: { class: 'success', text: 'Khách hàng' }
        };
        
        const config = configs[role] || { class: 'secondary', text: role };
        return `<span class="badge bg-${config.class}">${config.text}</span>`;
    }
};