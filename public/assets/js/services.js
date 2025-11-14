// services.js
document.addEventListener('DOMContentLoaded', function() {
    // Scroll to top functionality
    const scrollTopBtn = document.getElementById('scrollTop');
    
    window.addEventListener('scroll', function() {
        if (window.pageYOffset > 300) {
            scrollTopBtn.style.display = 'block';
        } else {
            scrollTopBtn.style.display = 'none';
        }
    });
    
    scrollTopBtn.addEventListener('click', function() {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });

    // Animation on scroll
    const animateOnScroll = function() {
        const elements = document.querySelectorAll('.animate-fade-in');
        
        elements.forEach(element => {
            const elementTop = element.getBoundingClientRect().top;
            const elementVisible = 150;
            
            if (elementTop < window.innerHeight - elementVisible) {
                element.classList.add('active');
            }
        });
    };
    
    window.addEventListener('scroll', animateOnScroll);
    animateOnScroll(); // Initial check
});

// Service modal functions
function showServiceModal(serviceId) {
    // In a real application, you would fetch service details via AJAX
    alert('Chi tiết dịch vụ ID: ' + serviceId + '\nTính năng này đang được phát triển.');
}

function bookService(serviceId, serviceName) {
    document.getElementById('selectedServiceId').value = serviceId;
    document.getElementById('selectedPackageId').value = '';
    document.getElementById('selectedServiceName').value = serviceName;
    
    const bookingModal = new bootstrap.Modal(document.getElementById('bookingModal'));
    bookingModal.show();
}

function bookPackage(packageId, packageName) {
    document.getElementById('selectedServiceId').value = '';
    document.getElementById('selectedPackageId').value = packageId;
    document.getElementById('selectedServiceName').value = packageName;
    
    const bookingModal = new bootstrap.Modal(document.getElementById('bookingModal'));
    bookingModal.show();
}

function contactForPackage(packageId, packageName) {
    alert('Liên hệ tư vấn gói: ' + packageName + '\nGọi ngay: 0901 234 567');
}

function contactNow() {
    alert('Liên hệ ngay với chúng tôi:\nHotline: 0901 234 567\nEmail: info@pharmacy.com');
}

function showBookingModal() {
    document.getElementById('selectedServiceId').value = '';
    document.getElementById('selectedPackageId').value = '';
    document.getElementById('selectedServiceName').value = '';
    
    const bookingModal = new bootstrap.Modal(document.getElementById('bookingModal'));
    bookingModal.show();
}

function submitBooking() {
    const form = document.getElementById('bookingForm');
    const formData = new FormData(form);
    
    // Basic validation
    const fullName = formData.get('full_name');
    const phone = formData.get('phone');
    const appointmentDate = formData.get('appointment_date');
    
    if (!fullName || !phone || !appointmentDate) {
        alert('Vui lòng điền đầy đủ thông tin bắt buộc.');
        return;
    }
    
    // In a real application, you would send this data to the server via AJAX
    const serviceName = document.getElementById('selectedServiceName').value;
    
    alert('Đặt lịch thành công!\n\nDịch vụ: ' + serviceName + 
          '\nHọ tên: ' + fullName + 
          '\nSĐT: ' + phone + 
          '\nNgày hẹn: ' + appointmentDate +
          '\n\nChúng tôi sẽ liên hệ xác nhận trong thời gian sớm nhất.');
    
    // Close modal
    const bookingModal = bootstrap.Modal.getInstance(document.getElementById('bookingModal'));
    bookingModal.hide();
    
    // Reset form
    form.reset();
}