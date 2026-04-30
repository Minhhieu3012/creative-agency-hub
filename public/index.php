<?php
/**
 * NEXUS AGENCY HUB - CENTRAL ENTRY POINT (FIXED PATH)
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Cấu hình hiển thị lỗi
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 2. Định nghĩa hằng số đường dẫn
define('BASE_PATH', dirname(__DIR__));
// ĐỊNH NGHĨA CHUẨN: Không có dấu gạch chéo ở cuối
define('APP_URL', '/creative-agency-hub/public'); 

// 3. Nạp Autoload và Môi trường
require_once BASE_PATH . '/vendor/autoload.php';
if (file_exists(BASE_PATH . '/.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(BASE_PATH);
    $dotenv->load();
}

// 4. CORS Headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, user_id');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

use App\Middleware\AuthMiddleware;
use App\Middleware\RoleMiddleware;

// 5. Phân tích URI
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

// Lấy path thuần túy sau khi loại bỏ APP_URL
$path = (strpos($uri, APP_URL) === 0) ? substr($uri, strlen(APP_URL)) : $uri;
$path = '/' . trim($path, '/');

// 6. Xử lý Routes
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

            $controller = new $controllerClass();
            call_user_func_array([$controller, $action], $matches);
            exit;
        }
    }

    // 7. VIEW RESOLVER
    $viewPath = BASE_PATH . '/app/View' . $path;
    
    if (file_exists($viewPath) && is_file($viewPath)) {
        header('Content-Type: text/html; charset=utf-8');
        return require_once $viewPath;
    }

    http_response_code(404);
    echo "<h1>404 Not Found</h1><p>Path: $path</p>";

} catch (\Throwable $e) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}