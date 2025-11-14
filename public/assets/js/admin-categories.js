// Admin Categories JavaScript
class CategoriesManager {
    constructor() {
        this.categories = [];
        this.selectedCategories = new Set();
        this.init();
    }

    init() {
        this.initEventListeners();
        this.initCategoryTree();
        this.initDragAndDrop();
        this.initCategoryForm();
        this.initIconPicker();
    }

    initEventListeners() {
        // Category selection
        document.querySelectorAll('.category-select').forEach(checkbox => {
            checkbox.addEventListener('change', (e) => {
                this.handleCategorySelection(e.target);
            });
        });

        // Category actions
        document.querySelectorAll('.category-action-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                this.handleCategoryAction(e.currentTarget);
            });
        });

        // Tree navigation
        document.querySelectorAll('.tree-toggle').forEach(toggle => {
            toggle.addEventListener('click', (e) => {
                this.toggleTreeBranch(e.currentTarget);
            });
        });

        // Search functionality
        document.querySelector('.search-box input')?.addEventListener('input', (e) => {
            this.handleCategorySearch(e.target.value);
        });
    }

    initCategoryTree() {
        this.loadCategories();
        this.renderCategoryTree();
        this.setupTreeInteractions();
    }

    initDragAndDrop() {
        this.setupDragAndDrop();
        this.setupDropZones();
    }

    initCategoryForm() {
        this.setupFormValidation();
        this.setupFormPreview();
        this.setupParentCategorySelector();
    }

    initIconPicker() {
        this.setupIconPicker();
        this.loadIconLibrary();
    }

    handleCategorySelection(checkbox) {
        const categoryId = checkbox.dataset.categoryId;
        
        if (checkbox.checked) {
            this.selectedCategories.add(categoryId);
        } else {
            this.selectedCategories.delete(categoryId);
        }
        
        this.updateBulkCategoryActionsState();
    }

    handleCategoryAction(button) {
        const action = button.dataset.action;
        const categoryId = button.dataset.categoryId;
        
        switch (action) {
            case 'view':
                this.viewCategory(categoryId);
                break;
            case 'edit':
                this.editCategory(categoryId);
                break;
            case 'delete':
                this.deleteCategory(categoryId);
                break;
            case 'add-child':
                this.addChildCategory(categoryId);
                break;
            case 'toggle-visibility':
                this.toggleCategoryVisibility(categoryId);
                break;
            case 'expand':
                this.expandCategory(categoryId);
                break;
            case 'collapse':
                this.collapseCategory(categoryId);
                break;
        }
    }

    toggleTreeBranch(toggle) {
        const branch = toggle.closest('.tree-item');
        const children = branch.querySelector('.tree-children');
        
        if (children) {
            children.classList.toggle('expanded');
            toggle.querySelector('i').classList.toggle('fa-chevron-down');
            toggle.querySelector('i').classList.toggle('fa-chevron-right');
        }
    }

    handleCategorySearch(query) {
        const searchTerm = query.toLowerCase();
        
        if (searchTerm.length === 0) {
            // Show all categories
            document.querySelectorAll('.tree-item').forEach(item => {
                item.style.display = '';
            });
            return;
        }
        
        // Hide all categories first
        document.querySelectorAll('.tree-item').forEach(item => {
            item.style.display = 'none';
        });
        
        // Show matching categories and their parents
        document.querySelectorAll('.tree-item').forEach(item => {
            const categoryName = item.querySelector('.tree-name')?.textContent.toLowerCase();
            if (categoryName && categoryName.includes(searchTerm)) {
                this.showCategoryAndParents(item);
            }
        });
    }

    showCategoryAndParents(categoryElement) {
        // Show this category
        categoryElement.style.display = '';
        
        // Show all parent categories
        let parent = categoryElement.parentElement.closest('.tree-item');
        while (parent) {
            parent.style.display = '';
            const children = parent.querySelector('.tree-children');
            if (children) {
                children.classList.add('expanded');
                const toggle = parent.querySelector('.tree-toggle i');
                if (toggle) {
                    toggle.className = 'fas fa-chevron-down';
                }
            }
            parent = parent.parentElement.closest('.tree-item');
        }
    }

    async loadCategories() {
        try {
            const response = await fetch('/api/admin/categories');
            this.categories = await response.json();
            this.organizeCategoriesHierarchy();
        } catch (error) {
            console.error('Error loading categories:', error);
            this.showNotification('Lỗi khi tải danh mục', 'error');
        }
    }

    organizeCategoriesHierarchy() {
        const categoryMap = new Map();
        const rootCategories = [];
        
        // Create map of categories
        this.categories.forEach(category => {
            categoryMap.set(category.category_id, { ...category, children: [] });
        });
        
        // Build hierarchy
        this.categories.forEach(category => {
            const categoryNode = categoryMap.get(category.category_id);
            
            if (category.parent_id && categoryMap.has(category.parent_id)) {
                const parent = categoryMap.get(category.parent_id);
                parent.children.push(categoryNode);
            } else {
                rootCategories.push(categoryNode);
            }
        });
        
        this.categoryHierarchy = rootCategories;
    }

    renderCategoryTree() {
        const treeContainer = document.querySelector('.tree-view');
        if (!treeContainer) return;
        
        treeContainer.innerHTML = this.renderCategoryNode(this.categoryHierarchy);
    }

    renderCategoryNode(categories, level = 0) {
        if (!categories || categories.length === 0) return '';
        
        return categories.map(category => `
            <li class="tree-item" data-category-id="${category.category_id}">
                <div class="tree-node">
                    <button class="tree-toggle">
                        <i class="fas fa-chevron-${category.children.length > 0 ? 'down' : 'right'}"></i>
                    </button>
                    <div class="tree-icon">
                        <i class="${category.icon_class || 'fas fa-folder'}"></i>
                    </div>
                    <div class="tree-content">
                        <div class="tree-name">${this.escapeHtml(category.category_name)}</div>
                        <div class="tree-description">${this.escapeHtml(category.category_description || '')}</div>
                    </div>
                    <div class="tree-actions">
                        <button class="btn btn-sm btn-outline-primary category-action-btn" 
                                data-action="view" data-category-id="${category.category_id}">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-warning category-action-btn" 
                                data-action="edit" data-category-id="${category.category_id}">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-success category-action-btn" 
                                data-action="add-child" data-category-id="${category.category_id}">
                            <i class="fas fa-plus"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger category-action-btn" 
                                data-action="delete" data-category-id="${category.category_id}">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
                ${category.children.length > 0 ? `
                    <ul class="tree-children expanded">
                        ${this.renderCategoryNode(category.children, level + 1)}
                    </ul>
                ` : ''}
            </li>
        `).join('');
    }

    setupTreeInteractions() {
        // Add event listeners to dynamically created tree elements
        document.querySelectorAll('.tree-toggle').forEach(toggle => {
            toggle.addEventListener('click', (e) => {
                this.toggleTreeBranch(e.currentTarget);
            });
        });
        
        document.querySelectorAll('.category-action-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                this.handleCategoryAction(e.currentTarget);
            });
        });
    }

    setupDragAndDrop() {
        const treeNodes = document.querySelectorAll('.tree-node');
        
        treeNodes.forEach(node => {
            node.setAttribute('draggable', 'true');
            
            node.addEventListener('dragstart', (e) => {
                this.handleDragStart(e, node);
            });
            
            node.addEventListener('dragover', (e) => {
                this.handleDragOver(e, node);
            });
            
            node.addEventListener('drop', (e) => {
                this.handleDrop(e, node);
            });
            
            node.addEventListener('dragend', (e) => {
                this.handleDragEnd(e, node);
            });
        });
    }

    handleDragStart(e, node) {
        const categoryId = node.closest('.tree-item').dataset.categoryId;
        e.dataTransfer.setData('text/plain', categoryId);
        e.dataTransfer.effectAllowed = 'move';
        node.classList.add('dragging');
    }

    handleDragOver(e, node) {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';
        node.classList.add('drop-target');
    }

    handleDrop(e, node) {
        e.preventDefault();
        const draggedCategoryId = e.dataTransfer.getData('text/plain');
        const targetCategoryId = node.closest('.tree-item').dataset.categoryId;
        
        this.moveCategory(draggedCategoryId, targetCategoryId);
        node.classList.remove('drop-target');
    }

    handleDragEnd(e, node) {
        node.classList.remove('dragging');
        document.querySelectorAll('.drop-target').forEach(el => {
            el.classList.remove('drop-target');
        });
    }

    setupDropZones() {
        // Create drop zones for reordering
        const treeContainer = document.querySelector('.tree-view');
        if (treeContainer) {
            const dropZone = document.createElement('div');
            dropZone.className = 'drop-zone';
            dropZone.innerHTML = '<i class="fas fa-arrow-down"></i><p>Kéo danh mục vào đây để di chuyển</p>';
            treeContainer.appendChild(dropZone);
        }
    }

    async moveCategory(categoryId, newParentId) {
        try {
            const response = await fetch(`/api/admin/categories/${categoryId}/move`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ parent_id: newParentId })
            });
            
            if (response.ok) {
                this.showNotification('Đã di chuyển danh mục', 'success');
                this.loadCategories(); // Reload the tree
            }
        } catch (error) {
            this.showNotification('Lỗi khi di chuyển danh mục', 'error');
        }
    }

    setupFormValidation() {
        const form = document.getElementById('categoryForm');
        if (form) {
            form.addEventListener('submit', (e) => {
                if (!this.validateCategoryForm()) {
                    e.preventDefault();
                }
            });
        }
    }

    validateCategoryForm() {
        const name = document.getElementById('categoryName').value.trim();
        const slug = document.getElementById('categorySlug').value.trim();
        
        if (!name) {
            this.showNotification('Vui lòng nhập tên danh mục', 'warning');
            return false;
        }
        
        if (!slug) {
            this.showNotification('Vui lòng nhập slug danh mục', 'warning');
            return false;
        }
        
        return true;
    }

    setupFormPreview() {
        const nameInput = document.getElementById('categoryName');
        const descriptionInput = document.getElementById('categoryDescription');
        const iconSelect = document.getElementById('categoryIcon');
        
        [nameInput, descriptionInput, iconSelect].forEach(input => {
            if (input) {
                input.addEventListener('input', () => {
                    this.updateFormPreview();
                });
            }
        });
    }

    updateFormPreview() {
        const name = document.getElementById('categoryName')?.value || 'Tên danh mục';
        const description = document.getElementById('categoryDescription')?.value || 'Mô tả danh mục';
        const icon = document.getElementById('categoryIcon')?.value || 'fas fa-folder';
        
        const preview = document.querySelector('.form-preview');
        if (preview) {
            preview.innerHTML = `
                <div class="preview-icon">
                    <i class="${icon}"></i>
                </div>
                <div class="preview-name">${this.escapeHtml(name)}</div>
                <div class="preview-description">${this.escapeHtml(description)}</div>
            `;
        }
    }

    setupParentCategorySelector() {
        const parentSelect = document.getElementById('parentCategory');
        if (parentSelect) {
            this.populateParentCategorySelector(parentSelect);
        }
    }

    populateParentCategorySelector(select) {
        const options = this.generateCategoryOptions(this.categoryHierarchy);
        select.innerHTML = '<option value="">-- Danh mục gốc --</option>' + options;
    }

    generateCategoryOptions(categories, level = 0) {
        let options = '';
        const indent = '&nbsp;&nbsp;'.repeat(level);
        
        categories.forEach(category => {
            options += `<option value="${category.category_id}">${indent}${this.escapeHtml(category.category_name)}</option>`;
            if (category.children && category.children.length > 0) {
                options += this.generateCategoryOptions(category.children, level + 1);
            }
        });
        
        return options;
    }

    setupIconPicker() {
        const iconOptions = document.querySelectorAll('.icon-option');
        iconOptions.forEach(option => {
            option.addEventListener('click', () => {
                this.selectIcon(option);
            });
        });
    }

    selectIcon(option) {
        // Remove selected class from all options
        document.querySelectorAll('.icon-option').forEach(opt => {
            opt.classList.remove('selected');
        });
        
        // Add selected class to clicked option
        option.classList.add('selected');
        
        // Update hidden input
        const iconInput = document.getElementById('categoryIcon');
        if (iconInput) {
            iconInput.value = option.dataset.icon;
        }
        
        // Update form preview
        this.updateFormPreview();
    }

    loadIconLibrary() {
        // This would load available icons from a library
        // For now, we're using the static HTML icons
    }

    viewCategory(categoryId) {
        window.location.href = `/admin/categories/${categoryId}`;
    }

    editCategory(categoryId) {
        window.location.href = `/admin/categories/${categoryId}/edit`;
    }

    async deleteCategory(categoryId) {
        if (!confirm('Bạn có chắc muốn xóa danh mục này? Các danh mục con cũng sẽ bị xóa.')) {
            return;
        }

        try {
            const response = await fetch(`/api/admin/categories/${categoryId}`, {
                method: 'DELETE'
            });
            
            if (response.ok) {
                this.showNotification('Đã xóa danh mục', 'success');
                this.loadCategories(); // Reload the tree
            }
        } catch (error) {
            this.showNotification('Lỗi khi xóa danh mục', 'error');
        }
    }

    addChildCategory(parentId) {
        window.location.href = `/admin/categories/new?parent=${parentId}`;
    }

    async toggleCategoryVisibility(categoryId) {
        try {
            const response = await fetch(`/api/admin/categories/${categoryId}/toggle-visibility`, {
                method: 'POST'
            });
            
            if (response.ok) {
                this.showNotification('Đã cập nhật hiển thị danh mục', 'success');
                this.loadCategories(); // Reload the tree
            }
        } catch (error) {
            this.showNotification('Lỗi khi cập nhật hiển thị', 'error');
        }
    }

    expandCategory(categoryId) {
        const categoryElement = document.querySelector(`[data-category-id="${categoryId}"]`);
        if (categoryElement) {
            const children = categoryElement.querySelector('.tree-children');
            const toggle = categoryElement.querySelector('.tree-toggle i');
            
            if (children) {
                children.classList.add('expanded');
                if (toggle) {
                    toggle.className = 'fas fa-chevron-down';
                }
            }
        }
    }

    collapseCategory(categoryId) {
        const categoryElement = document.querySelector(`[data-category-id="${categoryId}"]`);
        if (categoryElement) {
            const children = categoryElement.querySelector('.tree-children');
            const toggle = categoryElement.querySelector('.tree-toggle i');
            
            if (children) {
                children.classList.remove('expanded');
                if (toggle) {
                    toggle.className = 'fas fa-chevron-right';
                }
            }
        }
    }

    updateBulkCategoryActionsState() {
        const bulkActions = document.querySelector('.bulk-category-actions');
        const selectedCount = document.getElementById('selectedCategoriesCount');
        
        if (this.selectedCategories.size > 0) {
            bulkActions?.classList.add('has-selection');
            if (selectedCount) {
                selectedCount.textContent = this.selectedCategories.size;
            }
        } else {
            bulkActions?.classList.remove('has-selection');
        }
    }

    escapeHtml(unsafe) {
        return unsafe
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
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
    new CategoriesManager();
});