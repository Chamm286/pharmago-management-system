// PharmaGo Contact Page JavaScript với tính năng chỉ đường
class ContactPage {
    constructor() {
        this.map = null;
        this.markers = [];
        this.userLocationMarker = null;
        this.directionsService = null;
        this.directionsRenderer = null;
        this.branches = window.branchesData || [];
        this.defaultLocation = window.defaultLocation || { lat: 16.0544, lng: 108.2022 };
        
        this.init();
    }

    init() {
        console.log("📍 PharmaGo Contact page initialized");
        this.initScrollToTop();
        this.initFormValidation();
        this.initCharacterCounter();
        this.initSmoothScroll();
    }

    // Các hàm cơ bản
    initScrollToTop() {
        const scrollTopBtn = document.getElementById('scrollTop');
        if (!scrollTopBtn) return;
        
        window.addEventListener('scroll', () => {
            scrollTopBtn.classList.toggle('show', window.pageYOffset > 300);
        });

        scrollTopBtn.addEventListener('click', () => {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }

    initFormValidation() {
        const form = document.getElementById('contactForm');
        if (!form) return;
        
        form.addEventListener('submit', (event) => {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        });

        const inputs = form.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            input.addEventListener('input', () => {
                if (input.checkValidity()) {
                    input.classList.remove('is-invalid');
                    input.classList.add('is-valid');
                } else {
                    input.classList.remove('is-valid');
                    input.classList.add('is-invalid');
                }
            });
        });
    }

    initCharacterCounter() {
        const messageTextarea = document.getElementById('message');
        const charCount = document.getElementById('charCount');
        if (messageTextarea && charCount) {
            messageTextarea.addEventListener('input', () => {
                const count = messageTextarea.value.length;
                charCount.textContent = count;
                charCount.style.color = count > 1000 ? '#dc3545' : count > 800 ? '#fd7e14' : '#28a745';
            });
        }
    }

    initSmoothScroll() {
        const links = document.querySelectorAll('a[href^="#"]');
        links.forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const targetId = link.getAttribute('href');
                if (targetId === '#') return;
                const targetElement = document.querySelector(targetId);
                if (targetElement) {
                    const offsetTop = targetElement.offsetTop - 80;
                    window.scrollTo({ top: offsetTop, behavior: 'smooth' });
                }
            });
        });
    }

    // Google Maps với tính năng chỉ đường
    initializeGoogleMaps() {
        console.log("🗺️ Initializing Google Maps with Directions...");
        
        const mapElement = document.getElementById('googleMap');
        if (!mapElement) {
            console.error('❌ Map element not found');
            return;
        }

        try {
            // Ẩn loading indicator
            const loadingElement = mapElement.querySelector('.map-loading');
            if (loadingElement) {
                loadingElement.style.display = 'none';
            }

            // Đảm bảo map element có kích thước
            mapElement.style.height = '500px';
            mapElement.style.width = '100%';

            // Tạo map
            this.map = new google.maps.Map(mapElement, {
                center: this.defaultLocation,
                zoom: 12,
                mapTypeControl: false,
                streetViewControl: true,
                fullscreenControl: true,
                zoomControl: true
            });

            // Khởi tạo Directions Service
            this.directionsService = new google.maps.DirectionsService();
            this.directionsRenderer = new google.maps.DirectionsRenderer({
                map: this.map,
                suppressMarkers: true,
                polylineOptions: {
                    strokeColor: '#28a745',
                    strokeOpacity: 0.8,
                    strokeWeight: 6
                }
            });

            // Thêm markers sau khi map ready
            google.maps.event.addListenerOnce(this.map, 'idle', () => {
                console.log("🗺️ Map is ready, adding markers...");
                this.addBranchMarkers();
                this.showMapNotification('🗺️ Bản đồ đã sẵn sàng! Nhấp "Chỉ đường" để tìm đường đến nhà thuốc.');
            });

            console.log("✅ Google Maps with Directions initialized successfully");

        } catch (error) {
            console.error('❌ Error initializing Google Maps:', error);
            this.showMapFallback();
        }
    }

    addBranchMarkers() {
        if (!this.map || !this.branches.length) return;

        this.branches.forEach((branch, index) => {
            const position = {
                lat: parseFloat(branch.latitude),
                lng: parseFloat(branch.longitude)
            };

            // Tạo custom marker
            const markerIcon = {
                url: 'data:image/svg+xml;base64,' + btoa(`
                    <svg width="40" height="50" viewBox="0 0 40 50" xmlns="http://www.w3.org/2000/svg">
                        <path fill="#28a745" d="M20 0C9 0 0 9 0 20c0 15 20 30 20 30s20-15 20-30C40 9 31 0 20 0z"/>
                        <circle cx="20" cy="20" r="10" fill="white"/>
                        <path fill="#28a745" d="M20 15a5 5 0 1 1 0 10 5 5 0 0 1 0-10z"/>
                        <text x="20" y="26" text-anchor="middle" fill="#28a745" font-size="12" font-weight="bold">${index + 1}</text>
                    </svg>
                `),
                scaledSize: new google.maps.Size(40, 50),
                anchor: new google.maps.Point(20, 50)
            };

            const marker = new google.maps.Marker({
                position: position,
                map: this.map,
                title: branch.branch_name,
                icon: markerIcon,
                animation: google.maps.Animation.DROP
            });

            const infoWindow = new google.maps.InfoWindow({
                content: `
                    <div style="min-width: 280px; padding: 15px;">
                        <h6 style="margin: 0 0 10px 0; color: #28a745; font-size: 16px;">
                            <i class="fas fa-map-marker-alt me-1"></i>${branch.branch_name}
                        </h6>
                        <p style="margin: 8px 0; font-size: 14px; color: #666;">
                            <i class="fas fa-location-dot me-1"></i>${branch.address}
                        </p>
                        <p style="margin: 8px 0; font-size: 14px; color: #666;">
                            <i class="fas fa-phone me-1"></i>${branch.phone}
                        </p>
                        <p style="margin: 8px 0; font-size: 14px; color: #666;">
                            <i class="fas fa-clock me-1"></i>${branch.opening_hours}
                        </p>
                        <div style="margin-top: 15px; display: flex; gap: 8px;">
                            <button onclick="showDirections(${branch.latitude}, ${branch.longitude}, '${branch.branch_name.replace(/'/g, "\\'")}', '${branch.address.replace(/'/g, "\\'")}')" 
                                    style="background: #28a745; color: white; border: none; padding: 8px 16px; border-radius: 6px; font-size: 13px; cursor: pointer; flex: 1;">
                                <i class="fas fa-route me-1"></i>Chỉ đường
                            </button>
                            <a href="tel:${branch.phone}" 
                               style="background: #17a2b8; color: white; text-decoration: none; padding: 8px 16px; border-radius: 6px; font-size: 13px; text-align: center; flex: 1;">
                                <i class="fas fa-phone me-1"></i>Gọi ngay
                            </a>
                        </div>
                    </div>
                `
            });

            marker.addListener('click', () => {
                infoWindow.open(this.map, marker);
            });

            this.markers.push(marker);
        });

        console.log(`📍 Added ${this.markers.length} branch markers with directions`);
    }

    // TÍNH NĂNG CHỈ ĐƯỜNG - QUAN TRỌNG
    calculateAndDisplayRoute(branchLat, branchLng, mode) {
        if (!navigator.geolocation) {
            alert('Trình duyệt không hỗ trợ định vị.');
            return;
        }

        // Hiển thị loading
        this.showMapNotification('🔄 Đang tính toán tuyến đường...', 'warning');

        navigator.geolocation.getCurrentPosition(
            (position) => {
                const userLocation = {
                    lat: position.coords.latitude,
                    lng: position.coords.longitude
                };

                const destination = {
                    lat: parseFloat(branchLat),
                    lng: parseFloat(branchLng)
                };

                const request = {
                    origin: userLocation,
                    destination: destination,
                    travelMode: google.maps.TravelMode[mode],
                    provideRouteAlternatives: false
                };

                this.directionsService.route(request, (result, status) => {
                    if (status === 'OK') {
                        this.directionsRenderer.setDirections(result);
                        this.showRouteInfo(result, mode);
                        this.showMapNotification('✅ Đã tìm thấy tuyến đường!', 'success');
                    } else {
                        this.showMapNotification('❌ Không thể tìm tuyến đường', 'error');
                        console.error('Directions request failed:', status);
                    }
                });

            },
            (error) => {
                this.showMapNotification('❌ Không thể xác định vị trí của bạn', 'error');
            }
        );
    }

    showRouteInfo(result, mode) {
        const route = result.routes[0];
        const leg = route.legs[0];
        
        // Hiển thị thông tin tuyến đường
        const routeInfo = document.getElementById('routeInfo');
        const routeSummary = document.getElementById('routeSummary');
        const routeSteps = document.getElementById('routeSteps');
        
        const modeIcons = {
            'DRIVING': '🚗',
            'WALKING': '🚶',
            'BICYCLING': '🚴',
            'TRANSIT': '🚌'
        };
        
        routeSummary.innerHTML = `
            <div class="route-main-info">
                <div class="route-mode">${modeIcons[mode]} ${this.getTravelModeText(mode)}</div>
                <div class="route-distance">📏 ${leg.distance.text}</div>
                <div class="route-duration">⏱️ ${leg.duration.text}</div>
            </div>
        `;
        
        // Hiển thị các bước đi
        routeSteps.innerHTML = '<h6>Hướng dẫn đường đi:</h6>';
        leg.steps.forEach((step, index) => {
            routeSteps.innerHTML += `
                <div class="route-step">
                    <span class="step-number">${index + 1}</span>
                    <span class="step-text">${step.instructions.replace(/<[^>]*>/g, '')}</span>
                    <span class="step-distance">${step.distance.text}</span>
                </div>
            `;
        });
        
        routeInfo.style.display = 'block';
    }

    getTravelModeText(mode) {
        const modes = {
            'DRIVING': 'Ô tô/Xe máy',
            'WALKING': 'Đi bộ',
            'BICYCLING': 'Xe đạp',
            'TRANSIT': 'Xe buýt'
        };
        return modes[mode] || mode;
    }

    showMapFallback() {
        const mapElement = document.getElementById('googleMap');
        if (mapElement) {
            mapElement.innerHTML = `
                <div class="text-center py-5 bg-light">
                    <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
                    <h5 class="text-dark mb-2">Không thể tải bản đồ</h5>
                    <p class="text-muted mb-3">Vui lòng kiểm tra kết nối internet.</p>
                    <a href="https://maps.google.com" target="_blank" class="btn btn-success">
                        <i class="fas fa-external-link-alt me-2"></i>Mở Google Maps
                    </a>
                </div>
            `;
        }
    }

    showMapNotification(message, type = 'success') {
        let notification = document.getElementById('mapNotification');
        if (!notification) {
            notification = document.createElement('div');
            notification.id = 'mapNotification';
            notification.style.cssText = `
                position: absolute;
                top: 20px;
                left: 50%;
                transform: translateX(-50%);
                background: rgba(40, 167, 69, 0.95);
                color: white;
                padding: 12px 24px;
                border-radius: 25px;
                font-size: 14px;
                font-weight: 500;
                z-index: 1000;
                box-shadow: 0 4px 15px rgba(0,0,0,0.2);
                text-align: center;
                max-width: 80%;
            `;
            document.getElementById('googleMap').appendChild(notification);
        }
        
        if (type === 'error') {
            notification.style.background = 'rgba(220, 53, 69, 0.95)';
        } else if (type === 'warning') {
            notification.style.background = 'rgba(255, 193, 7, 0.95)';
            notification.style.color = '#212529';
        } else {
            notification.style.background = 'rgba(40, 167, 69, 0.95)';
        }
        
        notification.textContent = message;
        notification.style.display = 'block';
        
        setTimeout(() => {
            notification.style.display = 'none';
        }, 5000);
    }
}

// Hàm toàn cục
function hideRouteInfo() {
    document.getElementById('routeInfo').style.display = 'none';
    if (window.contactPage && window.contactPage.directionsRenderer) {
        window.contactPage.directionsRenderer.setMap(null);
    }
}

// Khởi tạo ứng dụng
document.addEventListener('DOMContentLoaded', function() {
    console.log("📄 DOM fully loaded, initializing contact page...");
    window.contactPage = new ContactPage();
});