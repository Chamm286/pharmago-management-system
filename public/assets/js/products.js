// public/assets/js/products.js
class ProductFilter {
    constructor() {
        this.products = [];
        this.filteredProducts = [];
        this.filters = {
            priceRange: [0, 500000],
            manufacturers: [],
            sortBy: 'name_asc',
            searchTerm: ''
        };
        
        this.init();
    }

    init() {
        this.loadProducts();
        this.bindEvents();
        this.initScrollTop();
        this.initQuickNav();
        this.initAddToCart();
    }

    loadProducts() {
        // Lấy sản phẩm từ các tab hiện có
        const allProducts = document.querySelectorAll('#all .product-card');
        this.products = Array.from(allProducts).map(card => {
            const productId = card.querySelector('.add-to-cart')?.getAttribute('data-product-id');
            const priceText = card.querySelector('.product-price')?.textContent;
            const price = this.parsePrice(priceText);
            const manufacturer = card.querySelector('.product-manufacturer small')?.textContent.replace('Nhà sản xuất: ', '').trim();
            const name = card.querySelector('.product-title')?.textContent.trim();
            
            return {
                element: card,
                id: productId,
                price: price,
                manufacturer: manufacturer,
                name: name,
                category: card.querySelector('.product-category small')?.textContent.replace('Thuốc: ', '').trim(),
                isNew: card.querySelector('.product-badge.new') !== null,
                isOnSale: card.querySelector('.product-badge.sale') !== null,
                isBestSeller: card.querySelector('.product-badge.best') !== null
            };
        }).filter(product => product.id); // Lọc sản phẩm hợp lệ

        this.filteredProducts = [...this.products];
    }

    parsePrice(priceText) {
        if (!priceText) return 0;
        // Chuyển "100.000đ" thành 100000
        const cleanPrice = priceText.replace(/[^\d]/g, '');
        return parseInt(cleanPrice) || 0;
    }

    bindEvents() {
        // Price range filter
        const priceRange = document.getElementById('priceRange');
        if (priceRange) {
            priceRange.addEventListener('input', (e) => {
                this.filters.priceRange[1] = parseInt(e.target.value);
                this.updatePriceDisplay();
                this.applyFilters();
            });
        }

        // Manufacturer filters
        const manufacturerCheckboxes = document.querySelectorAll('.manufacturer-filters input[type="checkbox"]');
        manufacturerCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', (e) => {
                this.updateManufacturerFilters();
                this.applyFilters();
            });
        });

        // Sort options
        const sortSelect = document.getElementById('sortOptions');
        if (sortSelect) {
            sortSelect.addEventListener('change', (e) => {
                this.filters.sortBy = e.target.value;
                this.applyFilters();
            });
        }

        // Search form
        const searchForm = document.querySelector('.search-box form');
        if (searchForm) {
            searchForm.addEventListener('submit', (e) => {
                e.preventDefault();
                const searchInput = searchForm.querySelector('input[name="search"]');
                this.filters.searchTerm = searchInput.value.trim().toLowerCase();
                this.applyFilters();
            });
        }

        // Clear filters
        const clearFiltersBtn = document.getElementById('clearFilters');
        if (clearFiltersBtn) {
            clearFiltersBtn.addEventListener('click', () => {
                this.clearFilters();
            });
        }
    }

    updatePriceDisplay() {
        const priceDisplay = document.querySelector('.price-value');
        if (priceDisplay) {
            priceDisplay.textContent = this.formatPrice(this.filters.priceRange[1]);
        }
    }

    updateManufacturerFilters() {
        const checkedBoxes = document.querySelectorAll('.manufacturer-filters input[type="checkbox"]:checked');
        this.filters.manufacturers = Array.from(checkedBoxes).map(cb => {
            return cb.nextElementSibling.textContent.trim();
        });
    }

    applyFilters() {
        this.filteredProducts = this.products.filter(product => {
            // Price filter
            if (product.price > this.filters.priceRange[1]) {
                return false;
            }

            // Manufacturer filter
            if (this.filters.manufacturers.length > 0 && !this.filters.manufacturers.includes(product.manufacturer)) {
                return false;
            }

            // Search filter
            if (this.filters.searchTerm && !product.name.toLowerCase().includes(this.filters.searchTerm)) {
                return false;
            }

            return true;
        });

        this.sortProducts();
        this.displayFilteredProducts();
        this.updateActiveFilters();
        this.updateProductCount();
    }

    sortProducts() {
        switch (this.filters.sortBy) {
            case 'price_asc':
                this.filteredProducts.sort((a, b) => a.price - b.price);
                break;
            case 'price_desc':
                this.filteredProducts.sort((a, b) => b.price - a.price);
                break;
            case 'name_asc':
                this.filteredProducts.sort((a, b) => a.name.localeCompare(b.name));
                break;
            case 'name_desc':
                this.filteredProducts.sort((a, b) => b.name.localeCompare(a.name));
                break;
            default:
                this.filteredProducts.sort((a, b) => a.name.localeCompare(b.name));
        }
    }

    displayFilteredProducts() {
        // Ẩn tất cả sản phẩm
        this.products.forEach(product => {
            product.element.style.display = 'none';
        });

        // Hiển thị sản phẩm đã lọc
        this.filteredProducts.forEach(product => {
            product.element.style.display = 'block';
        });

        // Hiển thị thông báo nếu không có sản phẩm
        this.showNoProductsMessage();
    }

    showNoProductsMessage() {
        const allTab = document.getElementById('all');
        let noProductsMsg = allTab.querySelector('.no-products-message');
        
        if (this.filteredProducts.length === 0) {
            if (!noProductsMsg) {
                noProductsMsg = document.createElement('div');
                noProductsMsg.className = 'no-products-message alert alert-info text-center';
                noProductsMsg.innerHTML = `
                    <i class="fas fa-info-circle me-2"></i>
                    Không tìm thấy sản phẩm nào phù hợp với bộ lọc hiện tại.
                    <button class="btn btn-sm btn-outline-primary ms-2" id="resetFilters">Xóa bộ lọc</button>
                `;
                allTab.appendChild(noProductsMsg);
                
                // Bind reset event
                document.getElementById('resetFilters').addEventListener('click', () => {
                    this.clearFilters();
                });
            }
        } else if (noProductsMsg) {
            noProductsMsg.remove();
        }
    }

    updateActiveFilters() {
        const activeFiltersContainer = document.querySelector('.active-filters');
        if (!activeFiltersContainer) return;

        activeFiltersContainer.innerHTML = '';

        // Price filter
        if (this.filters.priceRange[1] < 500000) {
            const priceFilter = this.createActiveFilterItem(
                `Giá: Dưới ${this.formatPrice(this.filters.priceRange[1])}`,
                () => {
                    document.getElementById('priceRange').value = 500000;
                    this.filters.priceRange[1] = 500000;
                    this.applyFilters();
                }
            );
            activeFiltersContainer.appendChild(priceFilter);
        }

        // Manufacturer filters
        this.filters.manufacturers.forEach(manufacturer => {
            const manufacturerFilter = this.createActiveFilterItem(
                `NSX: ${manufacturer}`,
                () => {
                    const checkbox = Array.from(document.querySelectorAll('.manufacturer-filters input[type="checkbox"]'))
                        .find(cb => cb.nextElementSibling.textContent.trim() === manufacturer);
                    if (checkbox) {
                        checkbox.checked = false;
                        this.updateManufacturerFilters();
                        this.applyFilters();
                    }
                }
            );
            activeFiltersContainer.appendChild(manufacturerFilter);
        });

        // Search term
        if (this.filters.searchTerm) {
            const searchFilter = this.createActiveFilterItem(
                `Tìm: "${this.filters.searchTerm}"`,
                () => {
                    const searchInput = document.querySelector('.search-box input[name="search"]');
                    if (searchInput) {
                        searchInput.value = '';
                        this.filters.searchTerm = '';
                        this.applyFilters();
                    }
                }
            );
            activeFiltersContainer.appendChild(searchFilter);
        }

        // Show/hide active filters container
        if (activeFiltersContainer.children.length > 0) {
            activeFiltersContainer.style.display = 'block';
        } else {
            activeFiltersContainer.style.display = 'none';
        }
    }

    createActiveFilterItem(text, removeCallback) {
        const item = document.createElement('span');
        item.className = 'active-filter-item';
        item.innerHTML = `
            ${text}
            <span class="remove-filter">&times;</span>
        `;
        
        item.querySelector('.remove-filter').addEventListener('click', removeCallback);
        return item;
    }

    updateProductCount() {
        const countElement = document.querySelector('.product-count');
        if (countElement) {
            countElement.textContent = this.filteredProducts.length;
        }

        // Update tab badges
        const allTabBadge = document.querySelector('#all-tab .badge');
        if (allTabBadge) {
            allTabBadge.textContent = this.filteredProducts.length;
        }
    }

    clearFilters() {
        // Reset price range
        const priceRange = document.getElementById('priceRange');
        if (priceRange) {
            priceRange.value = 500000;
            this.filters.priceRange[1] = 500000;
            this.updatePriceDisplay();
        }

        // Reset manufacturer checkboxes
        const manufacturerCheckboxes = document.querySelectorAll('.manufacturer-filters input[type="checkbox"]');
        manufacturerCheckboxes.forEach(checkbox => {
            checkbox.checked = false;
        });

        // Reset search
        const searchInput = document.querySelector('.search-box input[name="search"]');
        if (searchInput) {
            searchInput.value = '';
        }

        // Reset sort
        const sortSelect = document.getElementById('sortOptions');
        if (sortSelect) {
            sortSelect.value = 'name_asc';
        }

        // Reset filters
        this.filters = {
            priceRange: [0, 500000],
            manufacturers: [],
            sortBy: 'name_asc',
            searchTerm: ''
        };

        this.applyFilters();
    }

    formatPrice(price) {
        return new Intl.NumberFormat('vi-VN').format(price) + 'đ';
    }

    initScrollTop() {
        const scrollTopBtn = document.getElementById('scrollTop');
        
        window.addEventListener('scroll', () => {
            if (window.pageYOffset > 300) {
                scrollTopBtn.classList.add('active');
            } else {
                scrollTopBtn.classList.remove('active');
            }
        });
        
        scrollTopBtn.addEventListener('click', () => {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
    }

    initQuickNav() {
        const quickNavLinks = document.querySelectorAll('.quick-nav a[href^="#"]');
        quickNavLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const targetId = this.getAttribute('href');
                if (targetId === '#top') {
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                } else if (targetId === '#contact') {
                    document.querySelector('.footer').scrollIntoView({ 
                        behavior: 'smooth' 
                    });
                } else {
                    const targetElement = document.querySelector(targetId);
                    if (targetElement) {
                        targetElement.scrollIntoView({ behavior: 'smooth' });
                    }
                }
            });
        });
    }

    initAddToCart() {
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('add-to-cart') || e.target.closest('.add-to-cart')) {
                const button = e.target.classList.contains('add-to-cart') ? e.target : e.target.closest('.add-to-cart');
                this.handleAddToCart(button);
            }
        });
    }

    handleAddToCart(button) {
        if (button.disabled) return;

        const productId = button.getAttribute('data-product-id');
        const originalText = button.innerHTML;
        
        // Show loading state
        button.innerHTML = '<span class="loading"></span> Đang thêm...';
        button.disabled = true;

        // Simulate API call
        setTimeout(() => {
            this.showNotification('Đã thêm sản phẩm vào giỏ hàng!', 'success');
            
            // Reset button
            button.innerHTML = originalText;
            button.disabled = false;
            
            // Update cart count
            this.updateCartCount();
        }, 1000);
    }

    showNotification(message, type = 'info') {
        // Remove existing notifications
        const existingNotifications = document.querySelectorAll('.custom-notification');
        existingNotifications.forEach(notification => notification.remove());

        // Create notification element
        const notification = document.createElement('div');
        notification.className = `custom-notification alert alert-${type} alert-dismissible fade show`;
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1060;
            min-width: 300px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        `;
        
        notification.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        document.body.appendChild(notification);
        
        // Auto remove after 3 seconds
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 3000);
    }

    updateCartCount() {
        const cartCount = document.querySelector('.cart-count');
        if (cartCount) {
            const currentCount = parseInt(cartCount.textContent) || 0;
            cartCount.textContent = currentCount + 1;
            
            // Add animation
            cartCount.classList.add('pulse');
            setTimeout(() => {
                cartCount.classList.remove('pulse');
            }, 500);
        }
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Initialize product filter
    const productFilter = new ProductFilter();
    
    // Add sort options to HTML if not exists
    if (!document.getElementById('sortOptions')) {
        const sortSection = document.createElement('div');
        sortSection.className = 'sort-options';
        sortSection.innerHTML = `
            <div class="filter-title">
                <i class="fas fa-sort-amount-down"></i>
                Sắp xếp
            </div>
            <select id="sortOptions" class="form-select">
                <option value="name_asc">Tên A-Z</option>
                <option value="name_desc">Tên Z-A</option>
                <option value="price_asc">Giá thấp đến cao</option>
                <option value="price_desc">Giá cao đến thấp</option>
            </select>
        `;
        
        // Insert after manufacturer filters
        const manufacturerFilters = document.querySelector('.manufacturer-filters');
        if (manufacturerFilters) {
            manufacturerFilters.parentNode.insertBefore(sortSection, manufacturerFilters.nextSibling);
        }
    }
    
    // Add active filters container
    if (!document.querySelector('.active-filters')) {
        const activeFilters = document.createElement('div');
        activeFilters.className = 'active-filters';
        activeFilters.style.display = 'none';
        
        // Insert after stats section
        const statsSection = document.querySelector('.d-flex.justify-content-between.align-items-center.mb-4');
        if (statsSection) {
            statsSection.parentNode.insertBefore(activeFilters, statsSection.nextSibling);
        }
    }
    
    // Add price display
    const priceRange = document.getElementById('priceRange');
    if (priceRange && !document.querySelector('.price-display')) {
        const priceDisplay = document.createElement('div');
        priceDisplay.className = 'price-display';
        priceDisplay.innerHTML = `
            <span>Giá tối đa:</span>
            <span class="price-value">500.000đ</span>
        `;
        priceRange.parentNode.appendChild(priceDisplay);
    }
    
    // Add clear filters button
    if (!document.getElementById('clearFilters')) {
        const clearButton = document.createElement('button');
        clearButton.id = 'clearFilters';
        clearButton.className = 'btn btn-outline-secondary btn-sm w-100 mt-2';
        clearButton.innerHTML = '<i class="fas fa-times me-1"></i>Xóa bộ lọc';
        
        // Insert at the end of sidebar
        const sidebar = document.querySelector('.sidebar');
        if (sidebar) {
            sidebar.appendChild(clearButton);
        }
    }

    // Tab switching animation
    const productTabs = document.querySelectorAll('#productTabs .nav-link');
    productTabs.forEach(tab => {
        tab.addEventListener('click', function() {
            // Reset filters when switching tabs (except All tab)
            if (!this.id.includes('all-tab')) {
                productFilter.clearFilters();
            }
        });
    });

    // Product card hover effects
    const productCards = document.querySelectorAll('.product-card');
    productCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });

    // Image loading error handling
    const productImages = document.querySelectorAll('.product-img');
    productImages.forEach(img => {
        img.addEventListener('error', function() {
            this.src = '/PHARMAGO/public/assets/images/default-product.jpg';
            this.alt = 'Ảnh sản phẩm không khả dụng';
        });
    });

    console.log('Product filter system initialized successfully!');
});

// Add CSS for pulse animation
const style = document.createElement('style');
style.textContent = `
    .pulse {
        animation: pulse 0.5s ease-in-out;
    }
    
    @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.2); }
        100% { transform: scale(1); }
    }
    
    .custom-notification {
        animation: slideInRight 0.3s ease-out;
    }
    
    @keyframes slideInRight {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    .no-products-message {
        margin: 20px 0;
        padding: 20px;
    }
`;
document.head.appendChild(style);