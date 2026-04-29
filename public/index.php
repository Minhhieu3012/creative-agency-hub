<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, user_id');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

define('BASE_PATH', dirname(__DIR__));

require_once BASE_PATH . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(BASE_PATH);
$dotenv->load();

if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

use App\Middleware\AuthMiddleware;
use App\Middleware\RoleMiddleware;
use App\Controllers\AuthController;

// Ghi chú: Nạp class TaskController của Huy (Không dùng namespace)
require_once __DIR__ . '/../app/Controllers/TaskController.php';
require_once __DIR__ . '/../app/Controllers/AuthController.php';

header('Content-Type: application/json; charset=utf-8');

require_once BASE_PATH . '/core/Router.php';
$router = new Router();

// load web.php router
require_once BASE_PATH . '/routes/web.php';
// load api.php routes
$apiRoutes = require __DIR__ . '/../routes/api.php';
foreach ($apiRoutes as $route) {

    if (!is_array($route)) {
        continue;
    }

    [$method, $uri, $controller, $action] = $route;

    $router->{strtolower($method)}(
        $uri,
        $controller . '@' . $action
    );
}

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

$base = '/creative-agency-hub/public';
$uri = str_replace($base, '', $uri);

// FIX 1: Định nghĩa biến $path để dùng cho các luồng if/else phía dưới
$path = $uri; 

// 4. BỘ ĐỊNH TUYẾN (ROUTER)
try {
    // ==========================================
    // NHÁNH: core-auth-security (HIẾU)
    // ==========================================
    if ($method === 'POST' && $path === '/api/auth/login') {
        $controller = new AuthController();
        $controller->login();
        exit; // Xử lý Edge Case: Chặn thực thi khối Router động bên dưới
    }
    elseif ($method === 'GET' && $path === '/api/auth/me') {
        $authUser   = AuthMiddleware::check();
        $controller = new AuthController($authUser);
        $controller->me();
        exit;
    }
    elseif ($method === 'POST' && $path === '/api/auth/register') {
        $controller = new AuthController();
        $controller->register();
        exit;
    }

    // ==========================================
    // NHÁNH: task-kanban-board (HUY)
    // ==========================================
    
    // UI: Tải giao diện Kanban (Ghi đè Header thành HTML)
    elseif ($method === 'GET' && $path === '/tasks/board') {
        header('Content-Type: text/html; charset=utf-8');
        $controller = new TaskController();
        $controller->showBoard();
        exit;
    }

    // API: Lấy danh sách Task
    elseif ($method === 'GET' && $path === '/api/tasks') {
        $controller = new TaskController();
        $controller->getTasksAPI();
        exit;
    }

    // API: Tạo Task mới
    elseif ($method === 'POST' && $path === '/api/tasks') {
        $controller = new TaskController();
        $controller->createTaskAPI();
        exit;
    }

    // API: Cập nhật trạng thái Task (Kéo thả)
    elseif ($method === 'PATCH' && preg_match('#^/api/tasks/(\d+)/status$#', $path, $matches)) {
        $controller = new TaskController();
        $controller->updateTaskStatusAPI($matches[1]);
        exit;
    }

    // 404
    else {
        // Cảnh báo kiến trúc: Đoạn code này sẽ khiến mọi request không nằm trong
        // danh sách if/else phía trên bị trả về 404 ngay lập tức, làm mất tác dụng 
        // của khối Router động ($routes) bên dưới. Giữ lại để đúng yêu cầu "giữ nguyên logic".
        // http_response_code(404);
        // echo json_encode([
        //     "status"  => "error",
        //     "message" => "404 Not Found - Đường dẫn $method $path không tồn tại (Router tĩnh)."
        // ]);
    }
} catch (\Throwable $e) { 
    // FIX 2: Bổ sung } catch để đóng khối try bị thiếu
    error_log($e->getMessage());
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Lỗi hệ thống nhánh cứng: " . $e->getMessage()
    ]);
    exit;
}

$routes = require __DIR__ . '/../routes/api.php';

try {
    foreach ($routes as [$rMethod, $rPath, $controllerClass, $action, $roles]) {
        // Chuyển :id thành regex để match dynamic route
        $pattern = preg_replace('#:(\w+)#', '(\d+)', $rPath);
        $pattern = '#^' . $pattern . '$#';

        if ($method === $rMethod && preg_match($pattern, $path, $matches)) {
            array_shift($matches); // Bỏ full match, giữ lại các params
            $authUser = null;

            if ($roles !== null) {
                $authUser = AuthMiddleware::check();
                RoleMiddleware::handle($authUser, $roles);
            }

            $controller = new $controllerClass($authUser);
            $controller->$action(...$matches);
            exit;
        }
    }

    http_response_code(404);
    echo json_encode(["status" => "error", "message" => "API Route không tồn tại."]);
} catch (\Throwable $e) {
    error_log($e->getMessage());

    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Lỗi hệ thống, vui lòng thử lại sau."
    ]);
}