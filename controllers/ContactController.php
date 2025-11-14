<?php
// controllers/ContactController.php

class ContactController {
    private $db;
    private $conn;
    
    public function __construct() {
        $this->initializeDatabase();
    }
    
    private function initializeDatabase() {
        try {
            // Include database config - SỬA LẠI ĐƯỜNG DẪN
            $config_file = __DIR__ . '/../config/database.php';
            
            if (file_exists($config_file)) {
                require_once $config_file;
                
                // Khởi tạo Database class
                $database = new Database();
                $this->conn = $database->getConnection();
                
                if ($this->conn) {
                    error_log("✅ Database connection established successfully in ContactController");
                } else {
                    throw new Exception("Cannot connect to database");
                }
            } else {
                throw new Exception("Database config file not found at: " . $config_file);
            }
        } catch (Exception $e) {
            error_log("❌ Database connection failed in ContactController: " . $e->getMessage());
            $this->conn = null;
        }
    }
    
    public function index() {
        // Kiểm tra kết nối database
        if (!$this->conn) {
            error_log("❌ No database connection in index()");
            $this->showErrorPage("Không thể kết nối database. Vui lòng kiểm tra cấu hình.");
            return;
        }

        try {
            // Lấy danh sách chi nhánh
            $branches = $this->getBranches();
            
            // Lấy cài đặt hệ thống
            $settings = $this->getSystemSettings();
            
            // Lấy thống kê
            $stats = $this->getStats();
            
            // Kiểm tra session messages
            $contact_success = isset($_SESSION['contact_success']) ? $_SESSION['contact_success'] : false;
            $contact_error = isset($_SESSION['contact_error']) ? $_SESSION['contact_error'] : '';
            $contact_errors = isset($_SESSION['contact_errors']) ? $_SESSION['contact_errors'] : [];
            $contact_old = isset($_SESSION['contact_old']) ? $_SESSION['contact_old'] : [];
            
            // Clear session messages sau khi lấy
            unset($_SESSION['contact_success']);
            unset($_SESSION['contact_error']);
            unset($_SESSION['contact_errors']);
            unset($_SESSION['contact_old']);
            
            // Include view
            $contact_view = __DIR__ . '/../views/frontend/contact.php';
            if (file_exists($contact_view)) {
                // Truyền dữ liệu vào view
                $data = [
                    'branches' => $branches,
                    'settings' => $settings,
                    'stats' => $stats,
                    'contact_success' => $contact_success,
                    'contact_error' => $contact_error,
                    'contact_errors' => $contact_errors,
                    'contact_old' => $contact_old,
                    'title' => 'Liên hệ - PharmaGo'
                ];
                
                // Extract data để sử dụng trong view
                extract($data);
                
                include $contact_view;
            } else {
                error_log("❌ Contact view not found: " . $contact_view);
                $this->showErrorPage("Không tìm thấy trang liên hệ");
            }
        } catch (Exception $e) {
            error_log("❌ Error in contact index: " . $e->getMessage());
            $this->showErrorPage("Có lỗi xảy ra khi tải trang liên hệ: " . $e->getMessage());
        }
    }
    
    public function sendMessage() {
        // Start session nếu chưa có
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Kiểm tra kết nối database
        if (!$this->conn) {
            error_log("❌ No database connection in sendMessage()");
            $_SESSION['contact_error'] = "Không thể kết nối database. Vui lòng thử lại sau.";
            header('Location: /PHARMAGO/public/contact');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $full_name = $this->sanitize($_POST['full_name'] ?? '');
            $email = $this->sanitize($_POST['email'] ?? '');
            $phone = $this->sanitize($_POST['phone'] ?? '');
            $subject = $this->sanitize($_POST['subject'] ?? '');
            $message = $this->sanitize($_POST['message'] ?? '');
            $branch_id = isset($_POST['branch_id']) ? intval($_POST['branch_id']) : null;
            
            // Validation
            $errors = [];
            
            if (empty($full_name)) {
                $errors[] = "Vui lòng nhập họ và tên";
            } elseif (strlen($full_name) < 2) {
                $errors[] = "Họ và tên phải có ít nhất 2 ký tự";
            }
            
            if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = "Email không hợp lệ";
            }
            
            if (!empty($phone) && !preg_match('/^[0-9]{10,11}$/', $phone)) {
                $errors[] = "Số điện thoại không hợp lệ (10-11 chữ số)";
            }
            
            if (empty($subject)) {
                $errors[] = "Vui lòng nhập tiêu đề";
            } elseif (strlen($subject) < 5) {
                $errors[] = "Tiêu đề phải có ít nhất 5 ký tự";
            }
            
            if (empty($message)) {
                $errors[] = "Vui lòng nhập nội dung tin nhắn";
            } elseif (strlen($message) < 10) {
                $errors[] = "Nội dung tin nhắn phải có ít nhất 10 ký tự";
            }
            
            if (empty($errors)) {
                try {
                    // Kiểm tra xem bảng contacts có tồn tại không
                    $checkTable = $this->conn->query("SHOW TABLES LIKE 'contacts'");
                    if ($checkTable->rowCount() == 0) {
                        // Tạo bảng contacts nếu chưa tồn tại
                        $this->createContactsTable();
                    }
                    
                    // Lưu vào database
                    $sql = "INSERT INTO contacts (full_name, email, phone, subject, message, branch_id, status, ip_address, created_at) 
                            VALUES (:full_name, :email, :phone, :subject, :message, :branch_id, 'new', :ip_address, NOW())";
                    
                    $stmt = $this->conn->prepare($sql);
                    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
                    
                    $stmt->bindParam(':full_name', $full_name);
                    $stmt->bindParam(':email', $email);
                    $stmt->bindParam(':phone', $phone);
                    $stmt->bindParam(':subject', $subject);
                    $stmt->bindParam(':message', $message);
                    $stmt->bindParam(':branch_id', $branch_id);
                    $stmt->bindParam(':ip_address', $ip_address);
                    
                    if ($stmt->execute()) {
                        $_SESSION['contact_success'] = "Gửi tin nhắn thành công! Chúng tôi sẽ liên hệ lại với bạn sớm nhất.";
                    } else {
                        $_SESSION['contact_error'] = "Có lỗi xảy ra khi gửi tin nhắn. Vui lòng thử lại.";
                    }
                } catch (Exception $e) {
                    error_log("❌ Database error in sendMessage: " . $e->getMessage());
                    $_SESSION['contact_error'] = "Lỗi hệ thống: " . $e->getMessage();
                }
            } else {
                $_SESSION['contact_errors'] = $errors;
                $_SESSION['contact_old'] = [
                    'full_name' => $full_name,
                    'email' => $email,
                    'phone' => $phone,
                    'subject' => $subject,
                    'message' => $message,
                    'branch_id' => $branch_id
                ];
            }
            
            // Redirect back to contact page
            header('Location: /PHARMAGO/public/contact');
            exit;
        } else {
            header('Location: /PHARMAGO/public/contact');
            exit;
        }
    }
    
    private function getBranches() {
        if (!$this->conn) {
            error_log("❌ No database connection in getBranches()");
            return $this->getSampleBranches();
        }

        try {
            // Kiểm tra xem bảng branches có tồn tại không
            $checkTable = $this->conn->query("SHOW TABLES LIKE 'branches'");
            if ($checkTable->rowCount() == 0) {
                error_log("❌ Table 'branches' does not exist");
                return $this->getSampleBranches();
            }
            
            $sql = "SELECT * FROM branches WHERE is_active = 1 ORDER BY display_order ASC, branch_name ASC";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            
            $branches = $stmt->fetchAll(PDO::FETCH_ASSOC);
            error_log("✅ Retrieved " . count($branches) . " branches from database");
            
            return $branches;
        } catch (Exception $e) {
            error_log("❌ Error getting branches: " . $e->getMessage());
            return $this->getSampleBranches();
        }
    }
    
    private function getSystemSettings() {
        if (!$this->conn) {
            error_log("❌ No database connection in getSystemSettings()");
            return $this->getDefaultSettings();
        }

        try {
            // Kiểm tra xem bảng settings có tồn tại không
            $checkTable = $this->conn->query("SHOW TABLES LIKE 'settings'");
            if ($checkTable->rowCount() == 0) {
                error_log("❌ Table 'settings' does not exist");
                return $this->getDefaultSettings();
            }
            
            $sql = "SELECT setting_key, setting_value FROM settings 
                   WHERE setting_key IN ('google_maps_api_key', 'contact_email', 'contact_phone', 'contact_address', 
                                        'contact_hotline', 'contact_zalo', 'emergency_delivery_time', 'normal_delivery_time')";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            
            $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
            error_log("✅ Retrieved " . count($settings) . " settings from database");
            
            // Merge với giá trị mặc định
            return array_merge($this->getDefaultSettings(), $settings);
        } catch (Exception $e) {
            error_log("❌ Error getting settings: " . $e->getMessage());
            return $this->getDefaultSettings();
        }
    }
    
    private function getStats() {
        if (!$this->conn) {
            error_log("❌ No database connection in getStats()");
            return $this->getDefaultStats();
        }

        try {
            $stats_query = "
                SELECT 
                    (SELECT COUNT(*) FROM users WHERE role = 'customer' AND is_active = 1) as total_customers,
                    (SELECT COUNT(*) FROM contacts WHERE status = 'replied') as consultations_done,
                    (SELECT COUNT(*) FROM branches WHERE is_active = 1) as total_branches,
                    (SELECT COUNT(*) FROM product_reviews WHERE is_approved = 1 AND rating >= 4) as happy_customers,
                    (SELECT COUNT(*) FROM orders WHERE order_status = 'delivered') as orders_delivered
            ";
            $stmt = $this->conn->prepare($stats_query);
            $stmt->execute();
            $stats_data = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Format số liệu thống kê
            return [
                'total_customers' => $stats_data['total_customers'] > 1000 ? 
                                    round($stats_data['total_customers'] / 1000, 1) . 'K+' : 
                                    $stats_data['total_customers'],
                'consultations_done' => $stats_data['consultations_done'] > 1000 ? 
                                       round($stats_data['consultations_done'] / 1000, 1) . 'K+' : 
                                       $stats_data['consultations_done'],
                'total_branches' => $stats_data['total_branches'] . '+',
                'happy_customers' => $stats_data['happy_customers'] > 1000 ? 
                                    round($stats_data['happy_customers'] / 1000, 1) . 'K+' : 
                                    $stats_data['happy_customers'],
                'orders_delivered' => $stats_data['orders_delivered'] > 1000 ? 
                                     round($stats_data['orders_delivered'] / 1000, 1) . 'K+' : 
                                     $stats_data['orders_delivered']
            ];
        } catch (Exception $e) {
            error_log("❌ Stats query error: " . $e->getMessage());
            return $this->getDefaultStats();
        }
    }
    
    private function createContactsTable() {
        try {
            $sql = "CREATE TABLE IF NOT EXISTS contacts (
                contact_id INT PRIMARY KEY AUTO_INCREMENT,
                full_name VARCHAR(255) NOT NULL,
                email VARCHAR(255) NOT NULL,
                phone VARCHAR(20),
                subject VARCHAR(500) NOT NULL,
                message TEXT NOT NULL,
                branch_id INT NULL,
                status ENUM('new', 'read', 'replied', 'closed') DEFAULT 'new',
                ip_address VARCHAR(45),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (branch_id) REFERENCES branches(branch_id) ON DELETE SET NULL
            )";
            
            $this->conn->exec($sql);
            error_log("✅ Contacts table created successfully");
        } catch (Exception $e) {
            error_log("❌ Error creating contacts table: " . $e->getMessage());
        }
    }
    
    private function getSampleBranches() {
        return [
            [
                'branch_id' => 1,
                'branch_name' => 'Trụ sở chính Đà Nẵng',
                'address' => '123 Đường 2/9, Quận Hải Châu, TP. Đà Nẵng',
                'phone' => '0236 1234 567',
                'email' => 'danang@pharmacy.com',
                'opening_hours' => '7:00 - 22:00 (Thứ 2 - Chủ nhật)',
                'latitude' => 16.0544,
                'longitude' => 108.2022
            ],
            [
                'branch_id' => 2,
                'branch_name' => 'Chi nhánh Hà Nội',
                'address' => '456 Trần Duy Hưng, Quận Cầu Giấy, Hà Nội',
                'phone' => '024 1234 568',
                'email' => 'hanoi@pharmacy.com',
                'opening_hours' => '7:00 - 22:00 (Thứ 2 - Chủ nhật)',
                'latitude' => 21.0278,
                'longitude' => 105.8342
            ]
        ];
    }
    
    private function getDefaultSettings() {
        return [
            'contact_email' => 'info@pharmacy.com',
            'contact_phone' => '0236 1234 567',
            'contact_address' => '123 Đường 2/9, Quận Hải Châu, TP. Đà Nẵng',
            'contact_hotline' => '1900 1234',
            'contact_zalo' => '0909123456',
            'emergency_delivery_time' => '60',
            'normal_delivery_time' => '120',
            'google_maps_api_key' => 'AIzaSyCP5r4l8pUMVQKMtU8tZHfko6RzXj7VQLw'
        ];
    }
    
    private function getDefaultStats() {
        return [
            'total_customers' => '15K+',
            'consultations_done' => '50K+',
            'total_branches' => '10+',
            'happy_customers' => '20K+',
            'orders_delivered' => '100K+'
        ];
    }
    
    private function sanitize($data) {
        return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
    }
    
    private function showErrorPage($message) {
        echo "
        <!DOCTYPE html>
        <html>
        <head>
            <title>Lỗi - PharmaGo</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 50px; text-align: center; }
                .error { background: #f8d7da; color: #721c24; padding: 20px; border-radius: 5px; margin: 20px 0; }
                .btn { display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; margin: 5px; }
            </style>
        </head>
        <body>
            <h1>⚠️ Lỗi Hệ Thống</h1>
            <div class='error'>
                <p><strong>$message</strong></p>
                <p>Vui lòng thử lại sau hoặc liên hệ quản trị viên.</p>
            </div>
            <a href='/PHARMAGO/public/' class='btn'>← Quay lại Trang chủ</a>
            <a href='/PHARMAGO/public/contact' class='btn'>Thử lại</a>
        </body>
        </html>
        ";
    }
}