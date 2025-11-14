
USE web_cuoi_ky_2025;

-- 1. Bảng người dùng
CREATE TABLE users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    phone VARCHAR(15),
    address TEXT,
    role ENUM('customer', 'admin', 'staff') DEFAULT 'customer',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 2. Bảng danh mục sản phẩm
CREATE TABLE categories (
    category_id INT PRIMARY KEY AUTO_INCREMENT,
    category_name VARCHAR(100) NOT NULL,
    category_description TEXT,
    image_url VARCHAR(500),
    parent_id INT NULL,
    icon_class VARCHAR(50),
    display_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES categories(category_id) ON DELETE SET NULL
);

-- 3. Bảng nhà sản xuất
CREATE TABLE manufacturers (
    manufacturer_id INT PRIMARY KEY AUTO_INCREMENT,
    manufacturer_name VARCHAR(100) NOT NULL,
    description TEXT,
    contact_info TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 4. Bảng sản phẩm
CREATE TABLE products (
    product_id INT PRIMARY KEY AUTO_INCREMENT,
    product_name VARCHAR(255) NOT NULL,
    product_description TEXT,
    short_description VARCHAR(500),
    category_id INT NOT NULL,
    manufacturer_id INT NOT NULL,
    sku VARCHAR(100) UNIQUE NOT NULL,
    price DECIMAL(15,2) NOT NULL,
    original_price DECIMAL(15,2),
    cost_price DECIMAL(15,2),
    stock_quantity INT DEFAULT 0,
    min_stock_level INT DEFAULT 10,
    max_stock_level INT DEFAULT 100,
    image_url VARCHAR(500),
    prescription_required BOOLEAN DEFAULT FALSE,
    dosage_form VARCHAR(100),
    active_ingredient TEXT,
    usage_instruction TEXT,
    side_effects TEXT,
    contraindications TEXT,
    storage_condition TEXT,
    expiry_duration INT,
    weight_grams DECIMAL(8,2),
    dimensions VARCHAR(100),
    is_featured BOOLEAN DEFAULT FALSE,
    is_new BOOLEAN DEFAULT FALSE,
    is_best_seller BOOLEAN DEFAULT FALSE,
    is_on_sale BOOLEAN DEFAULT FALSE,
    discount_percent DECIMAL(5,2) DEFAULT 0,
    view_count INT DEFAULT 0,
    sold_count INT DEFAULT 0,
    average_rating DECIMAL(3,2) DEFAULT 0,
    review_count INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(category_id),
    FOREIGN KEY (manufacturer_id) REFERENCES manufacturers(manufacturer_id)
);

-- 5. Bảng hình ảnh sản phẩm
CREATE TABLE product_images (
    image_id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT NOT NULL,
    image_url VARCHAR(500) NOT NULL,
    image_alt VARCHAR(255),
    display_order INT DEFAULT 0,
    is_primary BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE
);

-- 6. Bảng giỏ hàng
CREATE TABLE cart (
    cart_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (product_id) REFERENCES products(product_id),
    UNIQUE KEY unique_cart_item (user_id, product_id)
);

-- 7. Bảng đơn hàng
CREATE TABLE orders (
    order_id INT PRIMARY KEY AUTO_INCREMENT,
    order_code VARCHAR(20) UNIQUE NOT NULL,
    user_id INT NOT NULL,
    customer_name VARCHAR(100) NOT NULL,
    customer_email VARCHAR(100) NOT NULL,
    customer_phone VARCHAR(15) NOT NULL,
    customer_address TEXT NOT NULL,
    order_notes TEXT,
    subtotal_amount DECIMAL(15,2) NOT NULL,
    discount_amount DECIMAL(15,2) DEFAULT 0,
    shipping_fee DECIMAL(15,2) DEFAULT 0,
    total_amount DECIMAL(15,2) NOT NULL,
    payment_method ENUM('cod', 'bank_transfer', 'credit_card', 'e_wallet') DEFAULT 'cod',
    payment_status ENUM('pending', 'paid', 'failed', 'refunded') DEFAULT 'pending',
    order_status ENUM('pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
    prescription_image_url VARCHAR(500),
    confirmed_by INT,
    shipped_at TIMESTAMP NULL,
    delivered_at TIMESTAMP NULL,
    cancelled_at TIMESTAMP NULL,
    cancellation_reason TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (confirmed_by) REFERENCES users(user_id)
);

-- 8. Bảng chi tiết đơn hàng
CREATE TABLE order_items (
    order_item_id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    product_name VARCHAR(255) NOT NULL,
    product_price DECIMAL(15,2) NOT NULL,
    quantity INT NOT NULL,
    discount_percent DECIMAL(5,2) DEFAULT 0,
    total_price DECIMAL(15,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(product_id)
);

-- 9. Bảng đánh giá sản phẩm
CREATE TABLE product_reviews (
    review_id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT NOT NULL,
    user_id INT NOT NULL,
    order_id INT NOT NULL,
    rating TINYINT NOT NULL,
    review_title VARCHAR(255),
    review_content TEXT,
    is_approved BOOLEAN DEFAULT FALSE,
    helpful_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(product_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (order_id) REFERENCES orders(order_id),
    UNIQUE KEY unique_product_user_review (product_id, user_id, order_id),
    CONSTRAINT chk_rating_range CHECK (rating >= 1 AND rating <= 5)
);

-- 10. Bảng tin tức/bài viết
CREATE TABLE articles (
    article_id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(300) UNIQUE NOT NULL,
    summary TEXT,
    content LONGTEXT NOT NULL,
    author_id INT NOT NULL,
    image_url VARCHAR(500),
    view_count INT DEFAULT 0,
    is_published BOOLEAN DEFAULT FALSE,
    published_at TIMESTAMP NULL,
    meta_title VARCHAR(255),
    meta_description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (author_id) REFERENCES users(user_id)
);

-- 11. Bảng liên hệ
CREATE TABLE contacts (
    contact_id INT PRIMARY KEY AUTO_INCREMENT,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(15),
    subject VARCHAR(255),
    message TEXT NOT NULL,
    status ENUM('new', 'read', 'replied', 'closed') DEFAULT 'new',
    replied_by INT,
    reply_message TEXT,
    replied_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (replied_by) REFERENCES users(user_id)
);

-- 12. Bảng cài đặt hệ thống
CREATE TABLE settings (
    setting_id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    setting_description VARCHAR(255),
    setting_type ENUM('text', 'number', 'boolean', 'json') DEFAULT 'text',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 13. Bảng lịch sử giá
CREATE TABLE price_history (
    price_history_id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT NOT NULL,
    old_price DECIMAL(15,2),
    new_price DECIMAL(15,2) NOT NULL,
    change_reason VARCHAR(255),
    changed_by INT NOT NULL,
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(product_id),
    FOREIGN KEY (changed_by) REFERENCES users(user_id)
);

-- 14. Bảng nhập kho
CREATE TABLE inventory_logs (
    log_id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT NOT NULL,
    change_type ENUM('import', 'export', 'adjustment', 'sale', 'return') NOT NULL,
    old_quantity INT NOT NULL,
    change_quantity INT NOT NULL,
    new_quantity INT NOT NULL,
    reference_id INT,
    reference_type ENUM('order', 'import', 'adjustment'),
    notes TEXT,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(product_id),
    FOREIGN KEY (created_by) REFERENCES users(user_id)
);

-- 15. Bảng newsletter
CREATE TABLE newsletter_subscriptions (
    subscription_id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(100) UNIQUE NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    subscribed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    unsubscribed_at TIMESTAMP NULL
);

-- 16. Bảng chat sessions
CREATE TABLE chat_sessions (
    session_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- 17. Bảng chat messages
CREATE TABLE chat_messages (
    message_id INT PRIMARY KEY AUTO_INCREMENT,
    session_id INT NOT NULL,
    user_id INT NOT NULL,
    message_text TEXT NOT NULL,
    message_type ENUM('user', 'bot') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (session_id) REFERENCES chat_sessions(session_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);

-- ====================================================================
-- CREATE INDEXES FOR PERFORMANCE
-- ====================================================================

CREATE INDEX idx_products_category ON products(category_id);
CREATE INDEX idx_products_manufacturer ON products(manufacturer_id);
CREATE INDEX idx_products_price ON products(price);
CREATE INDEX idx_products_active ON products(is_active);
CREATE INDEX idx_products_featured ON products(is_featured);
CREATE INDEX idx_products_best_seller ON products(is_best_seller);

CREATE INDEX idx_orders_user ON orders(user_id);
CREATE INDEX idx_orders_status ON orders(order_status);
CREATE INDEX idx_orders_created_at ON orders(created_at);

CREATE INDEX idx_order_items_order ON order_items(order_id);
CREATE INDEX idx_order_items_product ON order_items(product_id);

CREATE INDEX idx_cart_user ON cart(user_id);
CREATE INDEX idx_cart_product ON cart(product_id);

CREATE INDEX idx_reviews_product ON product_reviews(product_id);
CREATE INDEX idx_reviews_rating ON product_reviews(rating);

CREATE INDEX idx_chat_sessions_user ON chat_sessions(user_id);
CREATE INDEX idx_chat_messages_session ON chat_messages(session_id);
CREATE INDEX idx_chat_messages_created ON chat_messages(created_at);

-- ====================================================================
-- INSERT DỮ LIỆU MẪU
-- ====================================================================

-- Insert users (password: password)
INSERT INTO users (username, password_hash, email, full_name, phone, address, role) VALUES
('BichTram', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'tramntb.24it@vku.udn.vn', 'Nguyễn Thị Bích Trâm', '0934984665', '123 Đường 2/9, Quận Hải Châu, Đà Nẵng', 'admin'),
('MinhHoang', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'staff@pharmacy.com', 'Minh Hoàng', '0987654321', '456 Lê Lợi, Quận Hải Châu, Đà Nẵng', 'staff'),
('VanAnh', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Anh@gmail.com', 'Nguyễn Văn Anh', '0909123456', '789 Nguyễn Văn Linh, Quận Hải Châu, Đà Nẵng', 'customer'),
('ThiBinh', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Binh@gmail.com', 'Trần Thị Bình', '0918123456', '321 Trần Phú, Quận Thanh Khê, Đà Nẵng', 'customer');

-- Insert categories
INSERT INTO categories (category_name, category_description, image_url, icon_class, display_order) VALUES
('Thuốc kháng sinh', 'Các loại thuốc kháng sinh điều trị nhiễm khuẩn', 'images/Hinh80.webp', 'fas fa-bacteria', 1),
('Thuốc giảm đau, hạ sốt', 'Thuốc giảm đau, kháng viêm, hạ sốt thông dụng', 'images/Hinh81.png', 'fas fa-head-side-virus', 2),
('Thuốc tiêu hóa', 'Thuốc trị đau dạ dày, men tiêu hóa, nhuận tràng', 'images/Hinh82.jpg', 'fas fa-stomach', 3),
('Vitamin & Bổ sung', 'Các loại vitamin và khoáng chất thiết yếu', 'images/Hinh83.jpg', 'fas fa-capsules', 4),
('Thuốc da liễu', 'Thuốc trị nấm, mẩn ngứa, kem bôi ngoài da', 'images/Hinh84.jpg', 'fas fa-spa', 5),
('Thuốc tim mạch', 'Thuốc điều trị huyết áp, tim mạch', 'images/Hinh86.webp', 'fas fa-heartbeat', 6),
('Thuốc thần kinh', 'Thuốc điều trị bệnh thần kinh', 'images/Hinh85.jpg', 'fas fa-brain', 7),
('Thực phẩm chức năng', 'Thực phẩm bổ sung dinh dưỡng', 'images/Hinh87.webp', 'fas fa-apple-alt', 8);

-- Insert manufacturers
INSERT INTO manufacturers (manufacturer_name, description, contact_info) VALUES
('Dược Hậu Giang', 'Công ty cổ phần Dược Hậu Giang', 'DHG Pharma, 288 Bis Nguyễn Văn Cừ, An Hòa, Ninh Kiều, Cần Thơ'),
('Traphaco', 'Công ty cổ phần Traphaco', '75 Yên Ninh, Ba Đình, Hà Nội'),
('Sanofi Vietnam', 'Tập đoàn Dược phẩm Sanofi', 'Lô L1, Khu Công Nghệ Cao, Quận 9, TP.HCM'),
('OPC Pharma', 'Công ty cổ phần Dược phẩm OPC', 'Lô A1, A2, Khu Công Nghệ Cao, Quận 9, TP.HCM'),
('Imexpharm', 'Công ty cổ phần Dược phẩm Imexpharm', 'Lô A.3, Đường D4, Khu Công Nghệ Cao, Quận 9, TP.HCM'),
('Pharvina', 'Công ty cổ phần Dược phẩn Pharvina', 'Số 8, Đường 2A, Khu Công Nghệ Cao, Quận 9, TP.HCM'),
('Domesco', 'Công ty cổ phần Domesco', '10 Phạm Hùng, Phường 4, Quận 8, TP.HCM');

-- Insert products
INSERT INTO products (product_name, product_description, short_description, category_id, manufacturer_id, sku, price, original_price, stock_quantity, image_url, prescription_required, is_featured, is_new, is_best_seller, is_on_sale, discount_percent, sold_count, average_rating, review_count) VALUES
-- Kháng sinh
('Amoxicillin 500mg', 'Kháng sinh phổ rộng, điều trị các bệnh nhiễm khuẩn đường hô hấp, tiết niệu, da và mô mềm', 'Hộp 10 viên', 1, 1, 'AMOX500-001', 85000, 95000, 150, 'images/Hinh90.jpg', TRUE, TRUE, FALSE, TRUE, TRUE, 10.00, 1200, 4.5, 24),
('Cephalexin 500mg', 'Kháng sinh nhóm cephalosporin thế hệ 1, điều trị nhiễm khuẩn đường hô hấp, da, xương', 'Hộp 12 viên', 1, 2, 'CEPH500-001', 120000, NULL, 80, 'images/Hinh91.webp', TRUE, FALSE, FALSE, FALSE, FALSE, 0.00, 780, 4.0, 18),
('Azithromycin 250mg', 'Kháng sinh nhóm macrolid, điều trị nhiễm khuẩn đường hô hấp, sinh dục', 'Hộp 5 viên', 1, 3, 'AZI250-001', 150000, 180000, 60, 'images/Hinh92.jpg', TRUE, TRUE, TRUE, FALSE, TRUE, 16.67, 950, 5.0, 32),
('Ciprofloxacin 500mg', 'Kháng sinh nhóm quinolon, điều trị nhiễm khuẩn đường tiêu hóa, tiết niệu', 'Hộp 10 viên', 1, 4, 'CIPRO500-001', 95000, NULL, 45, 'images/Hinh93.webp', TRUE, FALSE, FALSE, FALSE, FALSE, 0.00, 450, 3.5, 12),

-- Giảm đau, hạ sốt
('Paracetamol 500mg', 'Thuốc hạ sốt, giảm đau hiệu quả cho các trường hợp đau đầu, đau cơ, cảm sốt', 'Hộp 20 viên', 2, 1, 'PARA500-001', 25000, NULL, 300, 'images/Hinh8.jpg', FALSE, TRUE, FALSE, TRUE, FALSE, 0.00, 2500, 4.8, 156),
('Ibuprofen 400mg', 'Thuốc kháng viêm không steroid, giảm đau, hạ sốt, kháng viêm', 'Hộp 10 viên', 2, 2, 'IBU400-001', 45000, 50000, 120, 'images/Hinh95.jpg', FALSE, FALSE, FALSE, FALSE, TRUE, 10.00, 890, 4.3, 67),
('Aspirin 500mg', 'Thuốc giảm đau, hạ sốt, kháng viêm, chống kết tập tiểu cầu', 'Hộp 10 viên', 2, 3, 'ASP500-001', 35000, NULL, 95, 'images/Hinh96.jpg', FALSE, FALSE, TRUE, FALSE, FALSE, 0.00, 670, 4.1, 45),

-- Tiêu hóa
('Omeprazole 20mg', 'Thuốc ức chế bơm proton, điều trị viêm loét dạ dày, trào ngược dạ dày thực quản', 'Hộp 14 viên', 3, 4, 'OME20-001', 120000, NULL, 85, 'images/Hinh10.jpg', TRUE, TRUE, FALSE, TRUE, FALSE, 0.00, 980, 4.6, 78),
('Domperidone 10mg', 'Thuốc chống nôn, tăng nhu động dạ dày', 'Hộp 30 viên', 3, 1, 'DOMP10-001', 75000, NULL, 110, 'images/Hinh97.jpg', FALSE, FALSE, FALSE, FALSE, FALSE, 0.00, 540, 4.2, 34),

-- Vitamin
('Vitamin C 500mg', 'Bổ sung Vitamin C, tăng cường sức đề kháng, chống oxy hóa', 'Hộp 100 viên', 4, 2, 'VITC500-001', 85000, NULL, 200, 'images/Hinh11.jpg', FALSE, FALSE, TRUE, FALSE, FALSE, 0.00, 1200, 4.7, 89),
('Vitamin D3 1000IU', 'Bổ sung Vitamin D3, hỗ trợ hấp thu canxi, tốt cho xương', 'Lọ 100 viên', 4, 3, 'VITD1000-001', 95000, 110000, 150, 'images/Hinh94.jpg', FALSE, TRUE, FALSE, FALSE, TRUE, 13.64, 780, 4.4, 56),
('Vitamin B Complex', 'Bổ sung vitamin nhóm B, tốt cho hệ thần kinh và chuyển hóa năng lượng', 'Hộp 50 viên', 4, 4, 'VITB-COMP-001', 65000, NULL, 180, 'images/Hinh12.jpg', FALSE, FALSE, FALSE, TRUE, FALSE, 0.00, 950, 4.5, 67);

-- Insert product images
INSERT INTO product_images (product_id, image_url, image_alt, is_primary) VALUES
(1, 'images/Hinh90.jpg', 'Amoxicillin 500mg', TRUE),
(2, 'images/Hinh91.webp', 'Cephalexin 500mg', TRUE),
(3, 'images/Hinh92.jpg', 'Azithromycin 250mg', TRUE),
(4, 'images/Hinh93.webp', 'Ciprofloxacin 500mg', TRUE),
(5, 'images/Hinh8.jpg', 'Paracetamol 500mg', TRUE),
(6, 'images/Hinh95.jpg', 'Ibuprofen 400mg', TRUE),
(7, 'images/Hinh96.jpg', 'Aspirin 500mg', TRUE),
(8, 'images/Hinh10.jpg', 'Omeprazole 20mg', TRUE),
(9, 'images/Hinh97.jpg', 'Domperidone 10mg', TRUE),
(10, 'images/Hinh11.jpg', 'Vitamin C 500mg', TRUE),
(11, 'images/Hinh94.jpg', 'Vitamin D3 1000IU', TRUE),
(12, 'images/Hinh12.jpg', 'Vitamin B Complex', TRUE);

-- Insert settings
INSERT INTO settings (setting_key, setting_value, setting_description, setting_type) VALUES
('store_name', 'PharmaGo', 'Tên cửa hàng', 'text'),
('store_address', '123 Đường 2/9, Quận Hải Châu, TP. Đà Nẵng', 'Địa chỉ cửa hàng', 'text'),
('store_phone', '0236 1234 567', 'Số điện thoại cửa hàng', 'text'),
('store_email', 'info@pharmago.com', 'Email cửa hàng', 'text'),
('store_hours', '7:00 - 22:00 hàng ngày', 'Giờ mở cửa', 'text'),
('shipping_fee', '15000', 'Phí vận chuyển', 'number'),
('min_order_free_ship', '300000', 'Đơn tối thiểu để miễn phí ship', 'number'),
('store_description', 'Nhà thuốc trực tuyến uy tín hàng đầu Việt Nam', 'Mô tả cửa hàng', 'text');

-- Insert sample orders
INSERT INTO orders (order_code, user_id, customer_name, customer_email, customer_phone, customer_address, subtotal_amount, shipping_fee, total_amount, payment_method, payment_status, order_status) VALUES
('PHARM2025001', 3, 'Nguyễn Văn Anh', 'Anh@gmail.com', '0909123456', '789 Nguyễn Văn Linh, Quận Hải Châu, Đà Nẵng', 205000, 15000, 220000, 'cod', 'paid', 'delivered'),
('PHARM2025002', 4, 'Trần Thị Bình', 'Binh@gmail.com', '0918123456', '321 Trần Phú, Quận Thanh Khê, Đà Nẵng', 320000, 0, 320000, 'bank_transfer', 'paid', 'shipped');

-- Insert order items
INSERT INTO order_items (order_id, product_id, product_name, product_price, quantity, total_price) VALUES
(1, 1, 'Amoxicillin 500mg', 85000, 2, 170000),
(1, 5, 'Paracetamol 500mg', 25000, 1, 25000),
(1, 10, 'Vitamin C 500mg', 85000, 1, 85000),
(2, 3, 'Azithromycin 250mg', 150000, 2, 300000);

-- Insert product reviews
INSERT INTO product_reviews (product_id, user_id, order_id, rating, review_title, review_content, is_approved) VALUES
(1, 3, 1, 5, 'Thuốc hiệu quả', 'Thuốc hiệu quả, uống 3 ngày là đỡ hẳn viêm họng. Sẽ mua lại khi cần.', TRUE),
(5, 3, 1, 4, 'Hạ sốt nhanh', 'Thuốc hạ sốt nhanh, giá cả hợp lý. Phù hợp cho gia đình.', TRUE),
(10, 3, 1, 5, 'Vitamin chất lượng', 'Vitamin chính hãng, sử dụng tốt. Tăng sức đề kháng rõ rệt.', TRUE),
(3, 4, 2, 4, 'Hiệu quả tốt', 'Thuốc hiệu quả tốt, nhưng hơi đắng. Dược sĩ tư vấn nhiệt tình.', TRUE);

-- Insert articles
INSERT INTO articles (title, slug, summary, content, author_id, image_url, is_published, published_at, view_count) VALUES
('Cách sử dụng kháng sinh an toàn và hiệu quả', 'cach-su-dung-khang-sinh-an-toan-va-hieu-qua', 'Hướng dẫn sử dụng kháng sinh đúng cách để tránh kháng thuốc và đảm bảo hiệu quả điều trị', 'Nội dung chi tiết về cách sử dụng kháng sinh...', 1, 'images/article1.jpg', TRUE, NOW(), 156),
('Vitamin C và vai trò tăng cường miễn dịch', 'vitamin-c-va-vai-tro-tang-cuong-mien-dich', 'Tìm hiểu về vai trò của Vitamin C trong việc tăng cường hệ miễn dịch và sức khỏe tổng thể', 'Nội dung chi tiết về Vitamin C...', 1, 'images/article2.jpg', TRUE, NOW(), 89);

-- Insert newsletter subscriptions
INSERT INTO newsletter_subscriptions (email) VALUES
('customer@example.com'),
('customer2@example.com'),
('subscriber@example.com');

-- Insert some cart items
INSERT INTO cart (user_id, product_id, quantity) VALUES
(3, 1, 2),
(3, 5, 1),
(4, 3, 1),
(4, 10, 3);

-- Insert some contacts
INSERT INTO contacts (full_name, email, phone, subject, message) VALUES
('Lê Văn C', 'levenc@example.com', '0937123456', 'Hỏi về thuốc kháng sinh', 'Tôi muốn hỏi về thuốc Amoxicillin...'),
('Phạm Thị D', 'phamthid@example.com', '0948123456', 'Tư vấn vitamin', 'Xin tư vấn về vitamin cho người lớn tuổi...');

-- Insert price history
INSERT INTO price_history (product_id, old_price, new_price, change_reason, changed_by) VALUES
(1, 95000, 85000, 'Khuyến mãi đặc biệt', 1),
(3, 180000, 150000, 'Điều chỉnh giá theo thị trường', 1);

-- Insert inventory logs
INSERT INTO inventory_logs (product_id, change_type, old_quantity, change_quantity, new_quantity, notes, created_by) VALUES
(1, 'import', 0, 200, 200, 'Nhập hàng lần đầu', 1),
(1, 'sale', 200, -50, 150, 'Bán hàng', 1);

-- Insert sample chat sessions
INSERT INTO chat_sessions (user_id, title) VALUES
(3, 'Tư vấn thuốc cảm cúm'),
(4, 'Hỏi về vitamin');

-- Insert sample chat messages
INSERT INTO chat_messages (session_id, user_id, message_text, message_type) VALUES
(1, 3, 'Tôi bị cảm nên uống thuốc gì?', 'user'),
(1, 3, 'Bạn có thể dùng Paracetamol để hạ sốt và giảm đau. Nghỉ ngơi nhiều và uống đủ nước.', 'bot'),
(2, 4, 'Tôi muốn bổ sung vitamin', 'user'),
(2, 4, 'Chúng tôi có Vitamin C, Vitamin D3 và Vitamin B Complex. Bạn cần loại nào?', 'bot');

-- Thêm bảng branches vào database của bạn
CREATE TABLE branches (
    branch_id INT PRIMARY KEY AUTO_INCREMENT,
    branch_name VARCHAR(100) NOT NULL,
    address TEXT NOT NULL,
    phone VARCHAR(15),
    email VARCHAR(100),
    manager_name VARCHAR(100),
    latitude DECIMAL(10, 8),
    longitude DECIMAL(11, 8),
    opening_hours TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    display_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Thêm dữ liệu mẫu cho branches
INSERT INTO branches (branch_name, address, phone, email, manager_name, latitude, longitude, opening_hours, display_order) VALUES
('Trụ sở chính Đà Nẵng', '123 Đường 2/9, Quận Hải Châu, TP. Đà Nẵng', '0236 1234 567', 'danang@pharmago.com', 'Nguyễn Thị Bích Trâm', 16.0544, 108.2022, '7:00 - 22:00 (Thứ 2 - Chủ nhật)', 1),
('Chi nhánh Hà Nội', '456 Trần Duy Hưng, Quận Cầu Giấy, Hà Nội', '024 1234 568', 'hanoi@pharmago.com', 'Minh Hoàng', 21.0278, 105.8342, '7:00 - 22:00 (Thứ 2 - Chủ nhật)', 2),
('Chi nhánh Hồ Chí Minh', '789 Nguyễn Văn Linh, Quận 7, TP.HCM', '028 1234 569', 'hcm@pharmago.com', 'Lê Văn C', 10.7321, 106.7220, '7:00 - 22:00 (Thứ 2 - Chủ nhật)', 3),
('Chi nhánh Cần Thơ', '321 Nguyễn Văn Cừ, Ninh Kiều, Cần Thơ', '0292 1234 570', 'cantho@pharmago.com', 'Phạm Thị D', 10.0452, 105.7469, '7:00 - 22:00 (Thứ 2 - Chủ nhật)', 4);

-- Thêm các setting cần thiết
INSERT INTO settings (setting_key, setting_value, setting_description, setting_type) VALUES
('google_maps_api_key', 'AIzaSyCP5r4l8pUMVQKMtU8tZHfko6RzXj7VQLw', 'Google Maps API Key', 'text'),
('contact_hotline', '1900 1234', 'Hotline liên hệ', 'text'),
('contact_zalo', '0909123456', 'Số Zalo hỗ trợ', 'text'),
('contact_facebook', 'https://facebook.com/pharmago', 'Facebook page', 'text'),
('contact_youtube', 'https://youtube.com/pharmago', 'YouTube channel', 'text'),
('emergency_delivery_time', '60', 'Thời gian giao hàng khẩn cấp (phút)', 'number'),
('normal_delivery_time', '120', 'Thời gian giao hàng thường (phút)', 'number');

-- ====================================================================
-- CREATE USEFUL VIEWS
-- ====================================================================

-- View for product sales statistics
CREATE VIEW product_sales_stats AS
SELECT 
    p.product_id,
    p.product_name,
    p.sku,
    p.price,
    p.stock_quantity,
    COALESCE(SUM(oi.quantity), 0) as total_sold,
    COALESCE(SUM(oi.total_price), 0) as total_revenue,
    COUNT(DISTINCT oi.order_id) as total_orders,
    p.average_rating,
    p.review_count
FROM products p
LEFT JOIN order_items oi ON p.product_id = oi.product_id
LEFT JOIN orders o ON oi.order_id = o.order_id AND o.order_status = 'delivered'
GROUP BY p.product_id, p.product_name, p.sku, p.price, p.stock_quantity, p.average_rating, p.review_count;

-- View for category statistics
CREATE VIEW category_stats AS
SELECT 
    c.category_id,
    c.category_name,
    COUNT(p.product_id) as total_products,
    SUM(p.sold_count) as total_sold,
    AVG(p.average_rating) as avg_rating
FROM categories c
LEFT JOIN products p ON c.category_id = p.category_id AND p.is_active = 1
GROUP BY c.category_id, c.category_name;

-- View for chat session with last message
CREATE VIEW chat_session_overview AS
SELECT 
    cs.session_id,
    cs.user_id,
    cs.title,
    cs.created_at,
    cs.updated_at,
    u.full_name,
    u.username,
    (SELECT message_text FROM chat_messages WHERE session_id = cs.session_id ORDER BY created_at DESC LIMIT 1) as last_message,
    (SELECT created_at FROM chat_messages WHERE session_id = cs.session_id ORDER BY created_at DESC LIMIT 1) as last_message_time
FROM chat_sessions cs
JOIN users u ON cs.user_id = u.user_id;

-- ====================================================================
-- CREATE STORED PROCEDURES
-- ====================================================================

DELIMITER //

-- Procedure to update product stock
CREATE PROCEDURE UpdateProductStock(
    IN p_product_id INT,
    IN p_quantity_change INT,
    IN p_change_type VARCHAR(20),
    IN p_notes TEXT,
    IN p_created_by INT
)
BEGIN
    DECLARE current_stock INT;
    DECLARE new_stock INT;
    
    SELECT stock_quantity INTO current_stock 
    FROM products 
    WHERE product_id = p_product_id;
    
    SET new_stock = current_stock + p_quantity_change;
    
    IF new_stock < 0 THEN
        SET new_stock = 0;
    END IF;
    
    UPDATE products 
    SET stock_quantity = new_stock,
        updated_at = CURRENT_TIMESTAMP
    WHERE product_id = p_product_id;
    
    INSERT INTO inventory_logs (
        product_id, 
        change_type, 
        old_quantity, 
        change_quantity, 
        new_quantity, 
        notes, 
        created_by
    ) VALUES (
        p_product_id,
        p_change_type,
        current_stock,
        p_quantity_change,
        new_stock,
        p_notes,
        p_created_by
    );
END//

-- Procedure to calculate product rating
CREATE PROCEDURE UpdateProductRating(IN p_product_id INT)
BEGIN
    DECLARE avg_rating DECIMAL(3,2);
    DECLARE review_count INT;
    
    SELECT AVG(rating), COUNT(*) 
    INTO avg_rating, review_count
    FROM product_reviews 
    WHERE product_id = p_product_id AND is_approved = TRUE;
    
    UPDATE products 
    SET average_rating = COALESCE(avg_rating, 0),
        review_count = COALESCE(review_count, 0),
        updated_at = CURRENT_TIMESTAMP
    WHERE product_id = p_product_id;
END//

-- Procedure to create new chat session
CREATE PROCEDURE CreateChatSession(
    IN p_user_id INT,
    IN p_title VARCHAR(255),
    OUT p_session_id INT
)
BEGIN
    INSERT INTO chat_sessions (user_id, title) VALUES (p_user_id, p_title);
    SET p_session_id = LAST_INSERT_ID();
END//

DELIMITER ;

SELECT 'Database setup completed successfully!' as message;