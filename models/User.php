<?php
class User {
    private $conn;
    private $table_name = "users";

    public $user_id;
    public $username;
    public $password_hash;
    public $email;
    public $full_name;
    public $phone;
    public $address;
    public $role;
    public $is_active;
    public $created_at;
    public $updated_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Lấy tất cả người dùng
    public function getAllUsers() {
        $query = "SELECT user_id, username, email, full_name, phone, address, role, is_active, created_at 
                  FROM " . $this->table_name . " 
                  ORDER BY created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Lấy tổng số người dùng
    public function getTotalUsers() {
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name;
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'] ?? 0;
    }

    // Lấy số lượng admin
    public function getAdminCount() {
        $query = "SELECT COUNT(*) as count FROM " . $this->table_name . " WHERE role = 'admin'";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] ?? 0;
    }

    // Lấy số lượng khách hàng
    public function getCustomerCount() {
        $query = "SELECT COUNT(*) as count FROM " . $this->table_name . " WHERE role = 'customer'";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] ?? 0;
    }

    // Lấy số lượng nhân viên
    public function getStaffCount() {
        $query = "SELECT COUNT(*) as count FROM " . $this->table_name . " WHERE role = 'staff'";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] ?? 0;
    }

    // Lấy thông tin người dùng theo ID
    public function getUserById($user_id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE user_id = ? LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $user_id);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->user_id = $row['user_id'];
            $this->username = $row['username'];
            $this->email = $row['email'];
            $this->full_name = $row['full_name'];
            $this->phone = $row['phone'];
            $this->address = $row['address'];
            $this->role = $row['role'];
            $this->is_active = $row['is_active'];
            $this->created_at = $row['created_at'];
            return true;
        }
        return false;
    }

    // Tạo người dùng mới
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET username=:username, password_hash=:password_hash, email=:email, 
                      full_name=:full_name, phone=:phone, address=:address, 
                      role=:role, is_active=:is_active";
        
        $stmt = $this->conn->prepare($query);
        
        // Clean data
        $this->username = htmlspecialchars(strip_tags($this->username));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->full_name = htmlspecialchars(strip_tags($this->full_name));
        $this->phone = htmlspecialchars(strip_tags($this->phone));
        $this->address = htmlspecialchars(strip_tags($this->address));
        $this->role = htmlspecialchars(strip_tags($this->role));
        
        $stmt->bindParam(":username", $this->username);
        $stmt->bindParam(":password_hash", $this->password_hash);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":full_name", $this->full_name);
        $stmt->bindParam(":phone", $this->phone);
        $stmt->bindParam(":address", $this->address);
        $stmt->bindParam(":role", $this->role);
        $stmt->bindParam(":is_active", $this->is_active);
        
        if ($stmt->execute()) {
            $this->user_id = $this->conn->lastInsertId();
            return true;
        }
        
        // Debug lỗi
        error_log("User creation error: " . implode(", ", $stmt->errorInfo()));
        return false;
    }

    // Cập nhật người dùng
    public function update() {
        $query = "UPDATE " . $this->table_name . " 
                  SET username=:username, email=:email, full_name=:full_name, 
                      phone=:phone, address=:address, role=:role, is_active=:is_active,
                      updated_at = CURRENT_TIMESTAMP
                  WHERE user_id = :user_id";
        
        $stmt = $this->conn->prepare($query);
        
        // Clean data
        $this->username = htmlspecialchars(strip_tags($this->username));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->full_name = htmlspecialchars(strip_tags($this->full_name));
        $this->phone = htmlspecialchars(strip_tags($this->phone));
        $this->address = htmlspecialchars(strip_tags($this->address));
        $this->role = htmlspecialchars(strip_tags($this->role));
        
        $stmt->bindParam(":username", $this->username);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":full_name", $this->full_name);
        $stmt->bindParam(":phone", $this->phone);
        $stmt->bindParam(":address", $this->address);
        $stmt->bindParam(":role", $this->role);
        $stmt->bindParam(":is_active", $this->is_active);
        $stmt->bindParam(":user_id", $this->user_id);
        
        return $stmt->execute();
    }

    // Cập nhật mật khẩu
    public function updatePassword($user_id, $new_password_hash) {
        $query = "UPDATE " . $this->table_name . " 
                  SET password_hash = :password_hash,
                      updated_at = CURRENT_TIMESTAMP
                  WHERE user_id = :user_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":password_hash", $new_password_hash);
        $stmt->bindParam(":user_id", $user_id);
        
        return $stmt->execute();
    }

    // Xóa người dùng
    public function delete($user_id) {
        $query = "DELETE FROM " . $this->table_name . " WHERE user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        
        return $stmt->execute();
    }

    // Tìm kiếm người dùng
    public function searchUsers($keyword) {
        $query = "SELECT user_id, username, email, full_name, phone, role, is_active, created_at 
                  FROM " . $this->table_name . " 
                  WHERE username LIKE :keyword 
                     OR email LIKE :keyword 
                     OR full_name LIKE :keyword 
                     OR phone LIKE :keyword
                  ORDER BY created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $keyword = "%$keyword%";
        $stmt->bindParam(':keyword', $keyword);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Lọc người dùng theo vai trò
    public function getUsersByRole($role) {
        $query = "SELECT user_id, username, email, full_name, phone, role, is_active, created_at 
                  FROM " . $this->table_name . " 
                  WHERE role = :role
                  ORDER BY created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':role', $role);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Lọc người dùng theo trạng thái
    public function getUsersByStatus($is_active) {
        $query = "SELECT user_id, username, email, full_name, phone, role, is_active, created_at 
                  FROM " . $this->table_name . " 
                  WHERE is_active = :is_active
                  ORDER BY created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':is_active', $is_active, PDO::PARAM_BOOL);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Kiểm tra username tồn tại
    public function usernameExists($username) {
        $query = "SELECT user_id FROM " . $this->table_name . " WHERE username = ? LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $username);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    // Kiểm tra email tồn tại
    public function emailExists($email) {
        $query = "SELECT user_id FROM " . $this->table_name . " WHERE email = ? LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $email);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    // Lấy thống kê người dùng
    public function getUserStats() {
        $query = "SELECT 
                    COUNT(*) as total_users,
                    COUNT(CASE WHEN role = 'admin' THEN 1 END) as admin_count,
                    COUNT(CASE WHEN role = 'staff' THEN 1 END) as staff_count,
                    COUNT(CASE WHEN role = 'customer' THEN 1 END) as customer_count,
                    COUNT(CASE WHEN is_active = 1 THEN 1 END) as active_users,
                    COUNT(CASE WHEN is_active = 0 THEN 1 END) as inactive_users,
                    COUNT(CASE WHEN DATE(created_at) = CURDATE() THEN 1 END) as new_today
                  FROM " . $this->table_name;
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Lấy người dùng mới nhất
    public function getRecentUsers($limit = 10) {
        $query = "SELECT user_id, username, email, full_name, role, created_at 
                  FROM " . $this->table_name . " 
                  ORDER BY created_at DESC 
                  LIMIT :limit";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Kích hoạt/khóa người dùng
    public function toggleStatus($user_id, $is_active) {
        $query = "UPDATE " . $this->table_name . " 
                  SET is_active = :is_active,
                      updated_at = CURRENT_TIMESTAMP
                  WHERE user_id = :user_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":is_active", $is_active, PDO::PARAM_BOOL);
        $stmt->bindParam(":user_id", $user_id);
        
        return $stmt->execute();
    }

    // Đếm số lượng người dùng mới trong tháng
    public function getNewUsersThisMonth() {
        $query = "SELECT COUNT(*) as count 
                  FROM " . $this->table_name . " 
                  WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) 
                  AND YEAR(created_at) = YEAR(CURRENT_DATE())";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] ?? 0;
    }

    // Lấy người dùng có nhiều đơn hàng nhất
    public function getTopUsersByOrders($limit = 5) {
        $query = "SELECT u.user_id, u.username, u.full_name, u.email, 
                         COUNT(o.order_id) as order_count
                  FROM " . $this->table_name . " u
                  LEFT JOIN orders o ON u.user_id = o.user_id
                  WHERE u.role = 'customer'
                  GROUP BY u.user_id
                  ORDER BY order_count DESC
                  LIMIT :limit";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Kiểm tra xem user có phải là admin không
    public function isAdmin($user_id) {
        $query = "SELECT role FROM " . $this->table_name . " WHERE user_id = ? AND role = 'admin'";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $user_id);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    // Lấy user bằng username
    public function getUserByUsername($username) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE username = ? LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $username);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
        return false;
    }

    // Lấy user bằng email
    public function getUserByEmail($email) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE email = ? LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $email);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
        return false;
    }

    // Xác thực đăng nhập
    public function authenticate($username, $password) {
        $query = "SELECT user_id, username, password_hash, role, is_active 
                  FROM " . $this->table_name . " 
                  WHERE username = ? OR email = ? 
                  LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $username);
        $stmt->bindParam(2, $username);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if (password_verify($password, $user['password_hash'])) {
                return $user;
            }
        }
        return false;
    }
}
?>