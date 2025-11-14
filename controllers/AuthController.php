<?php
session_start();
include_once '../config/database.php';
include_once '../models/User.php';

class AuthController {
    private $db;
    private $user;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->user = new User($this->db);
    }

    public function login($username, $password) {
        $this->user->username = $username;
        $this->user->password_hash = $password;

        if ($this->user->login()) {
            if ($this->user->role == 'admin' || $this->user->role == 'staff') {
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_id'] = $this->user->user_id;
                $_SESSION['admin_username'] = $this->user->username;
                $_SESSION['admin_role'] = $this->user->role;
                $_SESSION['admin_full_name'] = $this->user->full_name;
                return ['success' => true, 'role' => 'admin', 'redirect' => '/admin'];
            } else {
                $_SESSION['user_logged_in'] = true;
                $_SESSION['user_id'] = $this->user->user_id;
                $_SESSION['user_username'] = $this->user->username;
                $_SESSION['user_full_name'] = $this->user->full_name;
                $_SESSION['user_email'] = $this->user->email;
                $_SESSION['user_phone'] = $this->user->phone;
                $_SESSION['user_address'] = $this->user->address;
                return ['success' => true, 'role' => 'user', 'redirect' => '/'];
            }
        } else {
            return ['success' => false, 'message' => 'Tên đăng nhập hoặc mật khẩu không đúng'];
        }
    }

    public function logout() {
        session_destroy();
        return ['success' => true, 'redirect' => '/'];
    }

    public function register($user_data) {
        // Check if username already exists
        if ($this->checkUsernameExists($user_data['username'])) {
            return ['success' => false, 'message' => 'Tên đăng nhập đã tồn tại'];
        }

        // Check if email already exists
        if ($this->checkEmailExists($user_data['email'])) {
            return ['success' => false, 'message' => 'Email đã được sử dụng'];
        }

        $this->user->username = $user_data['username'];
        $this->user->email = $user_data['email'];
        $this->user->password_hash = $user_data['password'];
        $this->user->full_name = $user_data['full_name'];
        $this->user->phone = $user_data['phone'];
        $this->user->address = $user_data['address'];
        $this->user->role = 'customer';
        $this->user->is_active = true;

        if ($this->user->create()) {
            // Auto login after registration
            $_SESSION['user_logged_in'] = true;
            $_SESSION['user_id'] = $this->user->user_id;
            $_SESSION['user_username'] = $this->user->username;
            $_SESSION['user_full_name'] = $this->user->full_name;
            $_SESSION['user_email'] = $this->user->email;
            $_SESSION['user_phone'] = $this->user->phone;
            $_SESSION['user_address'] = $this->user->address;
            
            return ['success' => true, 'message' => 'Đăng ký thành công', 'redirect' => '/'];
        } else {
            return ['success' => false, 'message' => 'Đăng ký thất bại. Vui lòng thử lại.'];
        }
    }

    private function checkUsernameExists($username) {
        $query = "SELECT user_id FROM users WHERE username = ? LIMIT 1";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(1, $username);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    private function checkEmailExists($email) {
        $query = "SELECT user_id FROM users WHERE email = ? LIMIT 1";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(1, $email);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    public function updateProfile($user_id, $user_data) {
        $this->user->user_id = $user_id;
        $this->user->full_name = $user_data['full_name'];
        $this->user->phone = $user_data['phone'];
        $this->user->address = $user_data['address'];
        $this->user->email = $user_data['email'];

        if ($this->user->update()) {
            // Update session data
            $_SESSION['user_full_name'] = $this->user->full_name;
            $_SESSION['user_phone'] = $this->user->phone;
            $_SESSION['user_address'] = $this->user->address;
            $_SESSION['user_email'] = $this->user->email;
            
            return ['success' => true, 'message' => 'Cập nhật thông tin thành công'];
        } else {
            return ['success' => false, 'message' => 'Cập nhật thất bại'];
        }
    }

    public function changePassword($user_id, $current_password, $new_password) {
        // Get user current data
        $this->user->getUserById($user_id);
        
        // Verify current password
        if (!password_verify($current_password, $this->user->password_hash)) {
            return ['success' => false, 'message' => 'Mật khẩu hiện tại không đúng'];
        }

        // Update password
        $query = "UPDATE users SET password_hash = ? WHERE user_id = ?";
        $stmt = $this->db->prepare($query);
        $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt->bindParam(1, $new_password_hash);
        $stmt->bindParam(2, $user_id);

        if ($stmt->execute()) {
            return ['success' => true, 'message' => 'Đổi mật khẩu thành công'];
        } else {
            return ['success' => false, 'message' => 'Đổi mật khẩu thất bại'];
        }
    }

    public function isLoggedIn() {
        return isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true;
    }

    public function isAdminLoggedIn() {
        return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
    }

    public function requireAuth() {
        if (!$this->isLoggedIn()) {
            header('Location: /login');
            exit;
        }
    }

    public function requireAdminAuth() {
        if (!$this->isAdminLoggedIn()) {
            header('Location: /admin/login');
            exit;
        }
    }

    public function getCurrentUser() {
        if ($this->isLoggedIn()) {
            return [
                'user_id' => $_SESSION['user_id'],
                'username' => $_SESSION['user_username'],
                'full_name' => $_SESSION['user_full_name'],
                'email' => $_SESSION['user_email'],
                'phone' => $_SESSION['user_phone'],
                'address' => $_SESSION['user_address']
            ];
        }
        return null;
    }

    public function getCurrentAdmin() {
        if ($this->isAdminLoggedIn()) {
            return [
                'admin_id' => $_SESSION['admin_id'],
                'username' => $_SESSION['admin_username'],
                'full_name' => $_SESSION['admin_full_name'],
                'role' => $_SESSION['admin_role']
            ];
        }
        return null;
    }
}
?>