<?php

// CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

use App\Middleware\AuthMiddleware;
use App\Middleware\RoleMiddleware;

// Ghi chú: Nạp class TaskController của Huy (Không dùng namespace)
require_once __DIR__ . '/../app/Controllers/TaskController.php';

// 2. Ép kiểu trả về mặc định là JSON
header('Content-Type: application/json; charset=utf-8');

$uri    = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path   = str_replace('/creative-agency-hub/public', '', $uri);
$method = $_SERVER['REQUEST_METHOD'];

// 4. BỘ ĐỊNH TUYẾN (ROUTER)
try {
    // ==========================================
    // NHÁNH: core-auth-security (HIẾU)
    // ==========================================
    if ($method === 'POST' && $path === '/api/auth/login') {
        $controller = new AuthController();
        $controller->login();
    }
    elseif ($method === 'GET' && $path === '/api/auth/me') {
        $authUser   = AuthMiddleware::check();
        $controller = new AuthController($authUser);
        $controller->me();
    }
    elseif ($method === 'POST' && $path === '/api/auth/register') {
        $controller = new AuthController();
        $controller->register();
    }

    // ==========================================
    // NHÁNH: task-kanban-board (HUY)
    // ==========================================
    
    // UI: Tải giao diện Kanban (Ghi đè Header thành HTML)
    elseif ($method === 'GET' && $path === '/tasks/board') {
        header('Content-Type: text/html; charset=utf-8');
        $controller = new TaskController();
        $controller->showBoard();
    }

    // API: Lấy danh sách Task
    elseif ($method === 'GET' && $path === '/api/tasks') {
        $controller = new TaskController();
        $controller->getTasksAPI();
    }

    // API: Tạo Task mới
    elseif ($method === 'POST' && $path === '/api/tasks') {
        $controller = new TaskController();
        $controller->createTaskAPI();
    }

    // API: Cập nhật trạng thái Task (Kéo thả)
    elseif ($method === 'PATCH' && preg_match('#^/api/tasks/(\d+)/status$#', $path, $matches)) {
        $controller = new TaskController();
        $controller->updateTaskStatusAPI($matches[1]);
    }

    // 404
    else {
        http_response_code(404);
        echo json_encode([
            "status"  => "error",
            "message" => "404 Not Found - Đường dẫn $method $path không tồn tại."
        ]);
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
    echo json_encode(["status" => "error", "message" => "Lỗi hệ thống, vui lòng thử lại sau."]);
}