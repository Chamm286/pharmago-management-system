// Xử lý hiển thị thông tin danh mục dựa trên tham số URL
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const category = urlParams.get('category');
            
            if (category) {
                updateCategoryInfo(category);
            }
        });
        
        function updateCategoryInfo(category) {
            const categoryData = {
                'khang-sinh': {
                    title: 'Kháng sinh',
                    description: 'Điều trị các bệnh nhiễm khuẩn theo chỉ định của bác sĩ',
                    infoTitle: 'Kháng sinh',
                    infoText: 'Kháng sinh là những chất có khả năng tiêu diệt vi khuẩn hay kìm hãm sự phát triển của vi khuẩn một cách đặc hiệu. Chúng tôi cung cấp đa dạng các loại kháng sinh từ các thương hiệu uy tín, đảm bảo chất lượng và hiệu quả điều trị.',
                    image: 'https://images.unsplash.com/photo-1587854692152-cbe660dbde88?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=500&q=80'
                },
                'giam-dau': {
                    title: 'Giảm đau, hạ sốt',
                    description: 'Thuốc giảm đau, kháng viêm, hạ sốt thông dụng',
                    infoTitle: 'Giảm đau, hạ sốt',
                    infoText: 'Các loại thuốc giảm đau, hạ sốt giúp làm giảm các triệu chứng đau nhức, sốt do nhiều nguyên nhân khác nhau. Chúng tôi cung cấp đầy đủ các loại thuốc giảm đau từ thông dụng đến đặc trị.',
                    image: 'https://images.unsplash.com/photo-1584308666744-24d5c474f2ae?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=500&q=80'
                },
                'tieu-hoa': {
                    title: 'Tiêu hóa',
                    description: 'Thuốc trị đau dạ dày, men tiêu hóa, nhuận tràng',
                    infoTitle: 'Tiêu hóa',
                    infoText: 'Các sản phẩm hỗ trợ hệ tiêu hóa, điều trị các bệnh lý về dạ dày, đường ruột. Giúp cải thiện chức năng tiêu hóa và hấp thu dinh dưỡng tốt hơn.',
                    image: 'https://images.unsplash.com/photo-1558645836-e44122a743ee?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=500&q=80'
                },
                'vitamin': {
                    title: 'Vitamin & Bổ sung',
                    description: 'Các loại vitamin và khoáng chất thiết yếu',
                    infoTitle: 'Vitamin & Bổ sung',
                    infoText: 'Cung cấp đầy đủ các loại vitamin và khoáng chất cần thiết cho cơ thể, giúp tăng cường sức đề kháng, bổ sung dinh dưỡng cho người thiếu hụt.',
                    image: 'https://images.unsplash.com/photo-1635070041078-e363dbe005cb?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=500&q=80'
                },
                'da-lieu': {
                    title: 'Da liễu',
                    description: 'Thuốc trị nấm, mẩn ngứa, kem bôi ngoài da',
                    infoTitle: 'Da liễu',
                    infoText: 'Các sản phẩm chăm sóc và điều trị các bệnh về da như nấm, mẩn ngứa, viêm da. Giúp làn da khỏe mạnh và sạch bệnh.',
                    image: 'https://images.unsplash.com/photo-1607619056574-7b8d3ee536b2?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=500&q=80'
                },
                'tim-mach': {
                    title: 'Tim mạch',
                    description: 'Thuốc điều trị huyết áp, tim mạch',
                    infoTitle: 'Tim mạch',
                    infoText: 'Các loại thuốc hỗ trợ và điều trị các bệnh lý về tim mạch, huyết áp. Giúp ổn định huyết áp và cải thiện chức năng tim mạch.',
                    image: 'https://images.unsplash.com/photo-1587854692152-cbe660dbde88?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=500&q=80'
                }
            };
            
            if (categoryData[category]) {
                document.getElementById('categoryTitle').textContent = categoryData[category].title;
                document.getElementById('categoryDescription').textContent = categoryData[category].description;
                document.getElementById('categoryInfoTitle').textContent = categoryData[category].infoTitle;
                document.getElementById('categoryInfoText').textContent = categoryData[category].infoText;
                document.querySelector('.category-info img').src = categoryData[category].image;
            }
        }

        // Thêm hiệu ứng tuần tự cho sản phẩm
document.querySelectorAll('.product-card').forEach((card, index) => {
    card.style.setProperty('--order', index);
    card.style.animationDelay = `${index * 0.1}s`;
});

// Hàm định dạng số với dấu phẩy
function formatNumber(num) {
    return num.toString().replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1,');
}

// Hàm tạo hiệu ứng đếm số
function animateCounter(element, target) {
    let current = 0;
    const increment = target / 50; // Tốc độ đếm
    const timer = setInterval(() => {
        current += increment;
        if (current >= target) {
            clearInterval(timer);
            current = target;
        }
        element.textContent = formatNumber(Math.floor(current));
    }, 20);
}

// Hàm cập nhật số liệu ngẫu nhiên
function updateRandomData() {
    document.querySelectorAll('.counter').forEach(counter => {
        const currentValue = parseInt(counter.textContent.replace(/,/g, ''));
        const randomChange = Math.floor(Math.random() * 100) - 30; // -30 đến +70
        const newTarget = Math.max(500, currentValue + randomChange); // Đảm bảo không nhỏ hơn 500
        
        // Cập nhật progress bar tương ứng
        const progressBar = counter.closest('.stat-item').querySelector('.progress-bar');
        const newWidth = (newTarget / 1500) * 100; // 1500 là giá trị max giả định
        progressBar.style.width = `${newWidth}%`;
        progressBar.setAttribute('aria-valuenow', newWidth);
        
        // Cập nhật số liệu
        counter.setAttribute('data-target', newTarget);
        animateCounter(counter, newTarget);
    });
}

// Khởi tạo lần đầu
document.addEventListener('DOMContentLoaded', () => {
    // Đếm số ban đầu
    document.querySelectorAll('.counter').forEach(counter => {
        const target = parseInt(counter.getAttribute('data-target'));
        animateCounter(counter, target);
    });
    
    // Tự động cập nhật mỗi 5 giây
    setInterval(updateRandomData, 5000);
});

// Kích hoạt AOS animation (nếu bạn dùng)
if (typeof AOS !== 'undefined') {
    AOS.init();
}

// Khởi tạo AOS animation
    AOS.init({
        duration: 800,
        easing: 'ease-in-out',
        once: true
    });

    // Counter animation
    const counters = document.querySelectorAll('.counter');
    const speed = 200;
    
    counters.forEach(counter => {
        const updateCount = () => {
            const target = +counter.getAttribute('data-target');
            const count = +counter.innerText;
            const increment = target / speed;
            
            if (count < target) {
                counter.innerText = Math.ceil(count + increment);
                setTimeout(updateCount, 1);
            } else {
                counter.innerText = target.toLocaleString();
            }
        };
        
        // Kích hoạt khi scroll đến phần thống kê
        const observer = new IntersectionObserver((entries) => {
            if (entries[0].isIntersecting) {
                updateCount();
            }
        });
        
        observer.observe(counter.parentElement);
    });

    // Review carousel navigation (đơn giản)
    document.querySelectorAll('.review-section .btn').forEach(btn => {
        btn.addEventListener('click', () => {
            // Logic chuyển đổi review ở đây
            // Có thể kết hợp với thư viện như Owl Carousel hoặc Glide.js
            alert('Chức năng xem thêm đánh giá sẽ được cập nhật!');
        });
    });