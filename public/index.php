<?php
/**
 * NEXUS AGENCY HUB - CENTRAL ENTRY POINT (ULTIMATE SESSION & ROLE FIX)
 * Kết hợp hoàn hảo logic Routing và cơ chế chống nhảy Role bằng Cookie.
 */

use App\Seeders\AdminSeeder;
use App\Middleware\AuthMiddleware;
use App\Middleware\RoleMiddleware;
use Core\JwtHandler;

// 1. Khởi động Session nghiêm ngặt
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Cấu hình hiển thị lỗi
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 3. Định nghĩa hằng số đường dẫn
define('BASE_PATH', dirname(__DIR__));
define('APP_URL', '/creative-agency-hub/public'); 

// 4. Nạp Autoload và Môi trường
require_once BASE_PATH . '/vendor/autoload.php';
if (file_exists(BASE_PATH . '/.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(BASE_PATH);
    $dotenv->load();
}

// 5. --- LOGIC PHỤC HỒI ROLE TỪ COOKIE (CHỐNG NHẢY ROLE TUYỆT ĐỐI) ---
// Đặt tại đây để cả API và VIEW đều nhận được Role đúng trước khi xử lý
$token = $_COOKIE['cah_token'] ?? null;
if ($token) {
    try {
        $jwt = new JwtHandler();
        $decoded = $jwt->decode($token);
        if ($decoded) {
            // Ép buộc Session phải đi theo dữ liệu chính xác từ Token trong Cookie
            $_SESSION['user_id'] = $decoded['id'];
            $_SESSION['user_role'] = strtolower($decoded['role']);
        }
    } catch (\Exception $e) {
        // Token không hợp lệ hoặc hết hạn -> Xóa session để đảm bảo an toàn
        unset($_SESSION['user_id']);
        unset($_SESSION['user_role']);
    }
}

// 6. CORS Headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, user_id');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// 7. Phân tích URI
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

// Lấy path thuần túy sau khi loại bỏ APP_URL
$path = (strpos($uri, APP_URL) === 0) ? substr($uri, strlen(APP_URL)) : $uri;
$path = '/' . trim($path, '/');

// 8. Xử lý Routes
$routes = require BASE_PATH . '/routes/api.php';

try {
    foreach ($routes as $route) {
        [$rMethod, $rPath, $handler, $roles] = $route;
        $rPath = '/' . trim($rPath, '/');

        $pattern = preg_replace('#:(\w+)#', '(\d+)', $rPath);
        $pattern = '#^' . $pattern . '$#';

        if ($method === $rMethod && preg_match($pattern, $path, $matches)) {
            array_shift($matches);

            if (strpos($path, '/api/') === 0) {
                header('Content-Type: application/json; charset=utf-8');
            }

            if (is_callable($handler)) {
                return $handler();
            }

            [$controllerName, $action] = explode('@', $handler);

            $subFolders = ['', 'Auth\\', 'HRM\\', 'Task\\', 'Project\\', 'Payroll\\', 'Core\\'];
            $controllerClass = null;
            foreach ($subFolders as $folder) {
                $checkClass = "App\\Controllers\\" . $folder . $controllerName;
                if (class_exists($checkClass)) {
                    $controllerClass = $checkClass;
                    break;
                }
            }

            if (!$controllerClass) throw new Exception("Controller not found: $controllerName");

            $authUser = null;
            if ($roles !== null) {
                $authUser = AuthMiddleware::check();
                RoleMiddleware::handle($authUser, $roles);
            }

            $controller = new $controllerClass($authUser);
            call_user_func_array([$controller, $action], $matches);
            exit;
        }
    }

    // 9. VIEW RESOLVER (Đã được bảo vệ bởi logic Cookie ở mục 5)
    $viewPath = BASE_PATH . '/app/View' . $path;
    
    if (file_exists($viewPath) && is_file($viewPath)) {
        header('Content-Type: text/html; charset=utf-8');
        require_once $viewPath;
        exit;
    }

    // Tự động chạy Seeder nếu cần
    AdminSeeder::run();

    http_response_code(404);
    echo "<h1>404 Not Found</h1><p>Path: $path</p>";

} catch (\Throwable $e) {
    // Xử lý lỗi hệ thống
    if (strpos($path, '/api/') === 0) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    } else {
        http_response_code(500);
        echo "<h1>Internal Server Error</h1><p>" . $e->getMessage() . "</p>";
    }
}