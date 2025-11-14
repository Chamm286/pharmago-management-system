<?php
// public/index.php - Complete Router with Fixed Paths
session_start();

// Bật hiển thị lỗi
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// ==================== AUTOLOAD MODELS & CONTROLLERS ====================
spl_autoload_register(function ($class_name) {
    // Thử load từ models
    $models_path = __DIR__ . '/../models/' . $class_name . '.php';
    if (file_exists($models_path)) {
        require_once $models_path;
        error_log("✅ Model loaded: " . $class_name);
        return;
    }
    
    // Thử load từ controllers
    $controllers_path = __DIR__ . '/../controllers/' . $class_name . '.php';
    if (file_exists($controllers_path)) {
        require_once $controllers_path;
        error_log("✅ Controller loaded: " . $class_name);
        return;
    }
    
    error_log("❌ Class not found: " . $class_name);
});

// ==================== DATABASE CONFIG ====================
$database_config = __DIR__ . '/../config/database.php';
if (file_exists($database_config)) {
    require_once $database_config;
    error_log("✅ Database config loaded");
} else {
    error_log("⚠️ Database config not found, continuing without DB");
}

$request_uri = $_SERVER['REQUEST_URI'];
$path = parse_url($request_uri, PHP_URL_PATH);

// Debug
error_log("=== REQUEST START ===");
error_log("Full URI: " . $request_uri);
error_log("Path: " . $path);

// Remove base directory if exists
$base_dir = '/PHARMAGO/public';
if (strpos($path, $base_dir) === 0) {
    $path = substr($path, strlen($base_dir));
    error_log("Path after base removal: " . $path);
}

// ==================== STATIC FILES HANDLING ====================
if (preg_match('/\.(css|js|jpg|jpeg|png|gif|ico|svg|woff|woff2|ttf|eot|map)$/', $path)) {
    $static_file = __DIR__ . $path;
    
    error_log("🔍 STATIC FILE DEBUG:");
    error_log("Requested path: " . $path);
    error_log("Looking for: " . $static_file);
    error_log("File exists: " . (file_exists($static_file) ? 'YES' : 'NO'));
    
    // Nếu không tìm thấy, thử các đường dẫn khác
    if (!file_exists($static_file)) {
        // Thử từ thư mục gốc project
        $static_file = __DIR__ . '/..' . $path;
        error_log("Trying alternative 1: " . $static_file);
    }
    
    if (!file_exists($static_file) && strpos($path, '/assets/') === 0) {
        // Thử đường dẫn assets trực tiếp
        $static_file = __DIR__ . '/../assets' . substr($path, 7);
        error_log("Trying alternative 2: " . $static_file);
    }
    
    if (!file_exists($static_file) && strpos($path, '/images/') === 0) {
        // Thử đường dẫn images trực tiếp
        $static_file = __DIR__ . '/../assets/images' . substr($path, 7);
        error_log("Trying alternative 3: " . $static_file);
    }
    
    if (file_exists($static_file)) {
        error_log("✅ Static file FOUND: " . $static_file);
        $mime_types = [
            'css' => 'text/css',
            'js' => 'application/javascript',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'ico' => 'image/x-icon',
            'svg' => 'image/svg+xml',
            'woff' => 'font/woff',
            'woff2' => 'font/woff2',
            'ttf' => 'font/ttf',
            'eot' => 'application/vnd.ms-fontobject',
            'map' => 'application/json'
        ];
        
        $ext = strtolower(pathinfo($static_file, PATHINFO_EXTENSION));
        if (isset($mime_types[$ext])) {
            header('Content-Type: ' . $mime_types[$ext]);
        }
        
        // Cache control for static files
        header('Cache-Control: public, max-age=86400'); // 1 day cache
        readfile($static_file);
        exit;
    } else {
        error_log("❌ Static file NOT FOUND for: " . $path);
        http_response_code(404);
        exit;
    }
}

// ==================== ROUTE HANDLING ====================
try {
    error_log("Routing path: " . $path);
    
    switch ($path) {
        // ==================== FRONTEND ROUTES ====================
        case '/':
        case '/index.php':
        case '':
            error_log("Routing to HOME");
            $home_file = __DIR__ . '/../views/frontend/home.php';
            if (file_exists($home_file)) {
                error_log("✅ Home file found: " . $home_file);
                include $home_file;
            } else {
                error_log("❌ Home file not found, redirecting to admin login");
                header('Location: /PHARMAGO/public/admin_login.php');
            }
            break;
            
        case '/home':
            error_log("Routing to HOME explicit");
            $home_file = __DIR__ . '/../views/frontend/home.php';
            if (file_exists($home_file)) {
                include $home_file;
            } else {
                header('Location: /PHARMAGO/public/');
            }
            break;
            
        case '/products':
            error_log("Routing to PRODUCTS");
            $products_file = __DIR__ . '/../views/frontend/products.php';
            if (file_exists($products_file)) {
                include $products_file;
            } else {
                header('Location: /PHARMAGO/public/');
            }
            break;
            
        case '/categories':
            error_log("Routing to CATEGORIES");
            $categories_file = __DIR__ . '/../views/frontend/categories.php';
            if (file_exists($categories_file)) {
                include $categories_file;
            } else {
                header('Location: /PHARMAGO/public/');
            }
            break;
            
        case '/services':
            error_log("Routing to SERVICES");
            $services_file = __DIR__ . '/../views/frontend/services.php';
            if (file_exists($services_file)) {
                include $services_file;
            } else {
                header('Location: /PHARMAGO/public/');
            }
            break;
            
        // ==================== CONTACT ROUTE WITH CONTROLLER ====================
        case '/contact':
            error_log("Routing to CONTACT");
            
            // Include Contact Controller
            $contact_controller = __DIR__ . '/../controllers/ContactController.php';
            if (file_exists($contact_controller)) {
                require_once $contact_controller;
                
                // Khởi tạo controller
                $contact = new ContactController();
                
                // Xác định action dựa trên method
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    error_log("📨 Processing contact form submission");
                    $contact->sendMessage();
                } else {
                    error_log("📄 Displaying contact page");
                    $contact->index();
                }
            } else {
                error_log("❌ ContactController not found, using fallback");
                // Fallback to static contact page
                $contact_file = __DIR__ . '/../views/frontend/contact.php';
                if (file_exists($contact_file)) {
                    include $contact_file;
                } else {
                    error_log("❌ Contact files not found");
                    header('Location: /PHARMAGO/public/');
                }
            }
            break;
            
        case '/about':
            error_log("Routing to ABOUT");
            $about_file = __DIR__ . '/../views/frontend/about.php';
            if (file_exists($about_file)) {
                include $about_file;
            } else {
                header('Location: /PHARMAGO/public/');
            }
            break;
            
        case '/login':
            error_log("Routing to LOGIN");
            $login_file = __DIR__ . '/../views/frontend/login.php';
            if (file_exists($login_file)) {
                include $login_file;
            } else {
                header('Location: /PHARMAGO/public/admin_login.php');
            }
            break;
            
        case '/register':
            error_log("Routing to REGISTER");
            $register_file = __DIR__ . '/../views/frontend/register.php';
            if (file_exists($register_file)) {
                include $register_file;
            } else {
                header('Location: /PHARMAGO/public/');
            }
            break;
            
        case '/cart':
            error_log("Routing to CART");
            $cart_file = __DIR__ . '/../views/frontend/cart.php';
            if (file_exists($cart_file)) {
                include $cart_file;
            } else {
                header('Location: /PHARMAGO/public/');
            }
            break;

        case '/product-detail':
            error_log("Routing to PRODUCT DETAIL");
            $product_detail_file = __DIR__ . '/../views/frontend/product-detail.php';
            if (file_exists($product_detail_file)) {
                include $product_detail_file;
            } else {
                header('Location: /PHARMAGO/public/products');
            }
            break;

        case '/category-detail':
            error_log("Routing to CATEGORY DETAIL");
            $category_detail_file = __DIR__ . '/../views/frontend/category-detail.php';
            if (file_exists($category_detail_file)) {
                include $category_detail_file;
            } else {
                header('Location: /PHARMAGO/public/categories');
            }
            break;

        case '/chat-tu-van-thuoc':
            error_log("Routing to CHAT TƯ VẤN THUỐC");
            $chat_file = __DIR__ . '/../views/frontend/chat-tu-van-thuoc.php';
            if (file_exists($chat_file)) {
                include $chat_file;
            } else {
                header('Location: /PHARMAGO/public/services');
            }
            break;

        case '/chat':
            error_log("Routing to CHAT PAGE");
            $chat_page_file = __DIR__ . '/../views/frontend/chat.php';
            if (file_exists($chat_page_file)) {
                include $chat_page_file;
            } else {
                // Fallback to about page with AI chat
                header('Location: /PHARMAGO/public/about#ai-assistant');
            }
            break;
            
        case '/logout':
            error_log("Routing to LOGOUT");
            session_destroy();
            header('Location: /PHARMAGO/public/');
            exit;

        // ==================== AUTH ROUTES ====================
        case '/auth/login':
            error_log("Routing to AUTH LOGIN");
            $auth_login_file = __DIR__ . '/../controllers/AuthController.php';
            if (file_exists($auth_login_file)) {
                require_once $auth_login_file;
                $auth = new AuthController();
                $auth->login();
            } else {
                header('Location: /PHARMAGO/public/login');
            }
            break;
            
        case '/auth/register':
            error_log("Routing to AUTH REGISTER");
            $auth_register_file = __DIR__ . '/../controllers/AuthController.php';
            if (file_exists($auth_register_file)) {
                require_once $auth_register_file;
                $auth = new AuthController();
                $auth->register();
            } else {
                header('Location: /PHARMAGO/public/register');
            }
            break;
            
        case '/auth/logout':
            error_log("Routing to AUTH LOGOUT");
            session_destroy();
            header('Location: /PHARMAGO/public/');
            exit;

        // ==================== ADMIN ROUTES ====================
        case '/admin':
            error_log("Routing to ADMIN");
            // Check admin authentication
            if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
                header('Location: /PHARMAGO/public/admin_login.php');
                exit;
            }
            
            $admin_file = __DIR__ . '/admin.php';
            if (file_exists($admin_file)) {
                include $admin_file;
            } else {
                header('Location: /PHARMAGO/public/admin_login.php');
            }
            break;
            
        case '/admin/login':
        case '/admin_login.php':
            error_log("Routing to ADMIN LOGIN");
            $admin_login_file = __DIR__ . '/admin_login.php';
            if (file_exists($admin_login_file)) {
                include $admin_login_file;
            } else {
                header('Location: /PHARMAGO/public/');
            }
            break;
            
        case '/admin/products':
        case '/admin_products.php':
            error_log("Routing to ADMIN PRODUCTS");
            if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
                header('Location: /PHARMAGO/public/admin_login.php');
                exit;
            }
            
            $admin_products_file = __DIR__ . '/admin_products.php';
            if (file_exists($admin_products_file)) {
                include $admin_products_file;
            } else {
                header('Location: /PHARMAGO/public/admin.php');
            }
            break;
            
        case '/admin/categories':
        case '/admin_categories.php':
            error_log("Routing to ADMIN CATEGORIES");
            if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
                header('Location: /PHARMAGO/public/admin_login.php');
                exit;
            }
            
            $admin_categories_file = __DIR__ . '/admin_categories.php';
            if (file_exists($admin_categories_file)) {
                include $admin_categories_file;
            } else {
                header('Location: /PHARMAGO/public/admin.php');
            }
            break;
            
        case '/admin/users':
        case '/admin_users.php':
            error_log("Routing to ADMIN USERS");
            if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
                header('Location: /PHARMAGO/public/admin_login.php');
                exit;
            }
            
            $admin_users_file = __DIR__ . '/admin_users.php';
            if (file_exists($admin_users_file)) {
                include $admin_users_file;
            } else {
                header('Location: /PHARMAGO/public/admin.php');
            }
            break;
            
        case '/admin/orders':
        case '/admin_orders.php':
            error_log("Routing to ADMIN ORDERS");
            if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
                header('Location: /PHARMAGO/public/admin_login.php');
                exit;
            }
            
            $admin_orders_file = __DIR__ . '/admin_orders.php';
            if (file_exists($admin_orders_file)) {
                include $admin_orders_file;
            } else {
                header('Location: /PHARMAGO/public/admin.php');
            }
            break;
            
        case '/admin/logout':
            error_log("Routing to ADMIN LOGOUT");
            session_destroy();
            header('Location: /PHARMAGO/public/admin_login.php');
            exit;

        // ==================== API ROUTES ====================
        case '/api/chat_message':
            error_log("Routing to API CHAT MESSAGE");
            $api_chat_file = __DIR__ . '/../api/chat_message.php';
            if (file_exists($api_chat_file)) {
                include $api_chat_file;
            } else {
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Chat API not available']);
            }
            break;
            
        case '/api/chat_sessions':
            error_log("Routing to API CHAT SESSIONS");
            $api_chat_sessions_file = __DIR__ . '/../api/chat_sessions.php';
            if (file_exists($api_chat_sessions_file)) {
                include $api_chat_sessions_file;
            } else {
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Chat sessions API not available']);
            }
            break;
            
        case '/api/branches':
            error_log("Routing to API BRANCHES");
            header('Content-Type: application/json');
            
            try {
                $contact_controller = __DIR__ . '/../controllers/ContactController.php';
                if (file_exists($contact_controller)) {
                    require_once $contact_controller;
                    $contact = new ContactController();
                    $branches = $contact->getBranchesAPI();
                    echo json_encode($branches);
                } else {
                    echo json_encode(['error' => 'Contact controller not available']);
                }
            } catch (Exception $e) {
                echo json_encode(['error' => $e->getMessage()]);
            }
            break;

        // ==================== AI CHAT ROUTES ====================
        case '/ai-chat':
            error_log("Routing to AI CHAT API");
            $ai_chat_controller = __DIR__ . '/../controllers/AIChatController.php';
            if (file_exists($ai_chat_controller)) {
                include $ai_chat_controller;
            } else {
                // Fallback to simple AI response
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false, 
                    'response' => 'AI Chat service is temporarily unavailable. Please try again later.'
                ]);
            }
            break;

        // ==================== CONTACT API ROUTES ====================
        case '/api/contact/send':
            error_log("Routing to CONTACT API SEND");
            header('Content-Type: application/json');
            
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                try {
                    $contact_controller = __DIR__ . '/../controllers/ContactController.php';
                    if (file_exists($contact_controller)) {
                        require_once $contact_controller;
                        $contact = new ContactController();
                        
                        // Get JSON input
                        $input = json_decode(file_get_contents('php://input'), true);
                        $result = $contact->sendMessageAPI($input);
                        echo json_encode($result);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Contact controller not available']);
                    }
                } catch (Exception $e) {
                    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            }
            break;

        // ==================== TEST ROUTES ====================
        case '/test_db':
        case '/test_db.php':
            error_log("Routing to TEST DB");
            $test_db_file = __DIR__ . '/test_db.php';
            if (file_exists($test_db_file)) {
                include $test_db_file;
            } else {
                echo "Test DB file not found";
            }
            break;
            
        case '/test_mysqli':
        case '/test_mysqli.php':
            error_log("Routing to TEST MYSQLI");
            $test_mysqli_file = __DIR__ . '/test_mysqli.php';
            if (file_exists($test_mysqli_file)) {
                include $test_mysqli_file;
            } else {
                echo "Test MySQLi file not found";
            }
            break;
            
        case '/test_contact':
            error_log("Routing to TEST CONTACT");
            $test_contact_file = __DIR__ . '/test_contact.php';
            if (file_exists($test_contact_file)) {
                include $test_contact_file;
            } else {
                echo "Test Contact file not found";
            }
            break;

        // ==================== HEALTH CHECK ====================
        case '/health':
        case '/status':
            error_log("Routing to HEALTH CHECK");
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'ok',
                'timestamp' => date('Y-m-d H:i:s'),
                'service' => 'PharmaGo Router',
                'version' => '1.0.0'
            ]);
            break;

        default:
            error_log("Routing DEFAULT for: " . $path);
            
            // Try to find the file in views/frontend
            $views_file = __DIR__ . '/../views/frontend' . $path . '.php';
            if (file_exists($views_file)) {
                error_log("✅ Found in views: " . $views_file);
                include $views_file;
                break;
            }
            
            // Try direct PHP file in public
            $public_file = __DIR__ . $path . '.php';
            if (file_exists($public_file)) {
                error_log("✅ Found in public: " . $public_file);
                include $public_file;
                break;
            }
            
            // Try direct file (without .php)
            $direct_file = __DIR__ . $path;
            if (file_exists($direct_file) && is_file($direct_file)) {
                error_log("✅ Found direct: " . $direct_file);
                include $direct_file;
                break;
            }
            
            // Try API routes pattern
            if (strpos($path, '/api/') === 0) {
                error_log("❌ API route not found: " . $path);
                header('Content-Type: application/json');
                http_response_code(404);
                echo json_encode(['error' => 'API endpoint not found']);
                break;
            }
            
            error_log("❌❌❌ 404 - File not found for: " . $path);
            http_response_code(404);
            display404Page($path);
            break;
    }
    
    error_log("=== REQUEST COMPLETED ===");
    
} catch (Exception $e) {
    error_log("❌❌❌ ROUTING ERROR: " . $e->getMessage());
    http_response_code(500);
    displayErrorPage($e->getMessage());
}

// ==================== HELPER FUNCTIONS ====================

function display404Page($path) {
    ?>
    <!DOCTYPE html>
    <html lang="vi">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>404 - Page Not Found</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
        <style>
            body { 
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                min-height: 100vh;
                display: flex;
                align-items: center;
            }
            .error-container {
                background: white;
                border-radius: 15px;
                padding: 40px;
                box-shadow: 0 15px 35px rgba(0,0,0,0.1);
                text-align: center;
                max-width: 500px;
                margin: 0 auto;
            }
            .error-icon {
                font-size: 4rem;
                color: #dc3545;
                margin-bottom: 20px;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="error-container">
                <div class="error-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <h1 class="text-danger mb-3">404 - Page Not Found</h1>
                <p class="text-muted mb-4">The page you are looking for doesn't exist or has been moved.</p>
                <p class="small text-muted mb-4">Requested path: <code><?= htmlspecialchars($path) ?></code></p>
                <div class="d-grid gap-2 d-md-block">
                    <a href="/PHARMAGO/public/" class="btn btn-primary">
                        <i class="fas fa-home me-2"></i>Back to Home
                    </a>
                    <a href="/PHARMAGO/public/contact" class="btn btn-outline-primary">
                        <i class="fas fa-phone me-2"></i>Contact Support
                    </a>
                </div>
            </div>
        </div>
    </body>
    </html>
    <?php
}

function displayErrorPage($message) {
    ?>
    <!DOCTYPE html>
    <html lang="vi">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>System Error</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
        <style>
            body { 
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
                min-height: 100vh;
                display: flex;
                align-items: center;
            }
            .error-container {
                background: white;
                border-radius: 15px;
                padding: 40px;
                box-shadow: 0 15px 35px rgba(0,0,0,0.1);
                text-align: center;
                max-width: 600px;
                margin: 0 auto;
            }
            .error-icon {
                font-size: 4rem;
                color: #dc3545;
                margin-bottom: 20px;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="error-container">
                <div class="error-icon">
                    <i class="fas fa-bug"></i>
                </div>
                <h1 class="text-danger mb-3">System Error</h1>
                <div class="alert alert-danger">
                    <p class="mb-0"><strong>Error Details:</strong> <?= htmlspecialchars($message) ?></p>
                </div>
                <p class="text-muted mb-4">Please try again later or contact the administrator if the problem persists.</p>
                <div class="d-grid gap-2 d-md-block">
                    <a href="/PHARMAGO/public/" class="btn btn-primary">
                        <i class="fas fa-home me-2"></i>Back to Home
                    </a>
                    <button onclick="window.location.reload()" class="btn btn-outline-primary">
                        <i class="fas fa-redo me-2"></i>Try Again
                    </button>
                </div>
            </div>
        </div>
    </body>
    </html>
    <?php
}
?>