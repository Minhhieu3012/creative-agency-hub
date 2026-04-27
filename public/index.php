<?php
// Bật Session cho tính năng CSRF
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Xử lý CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// 1. Nạp Autoload và Biến môi trường
require_once __DIR__ . '/../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

use App\Controllers\AuthController;
use App\Middleware\AuthMiddleware;

// Ghi chú: Nạp class TaskController của Huy (Không dùng namespace)
require_once __DIR__ . '/../app/Controllers/TaskController.php';

// 2. Ép kiểu trả về mặc định là JSON
header('Content-Type: application/json; charset=utf-8');

// 3. Xử lý đường dẫn XAMPP
$uri      = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$basePath = '/creative-agency-hub/public';
$path     = str_replace($basePath, '', $uri);
$method   = $_SERVER['REQUEST_METHOD'];

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
} catch (\Throwable $e) {
    error_log($e->getMessage());
    http_response_code(500);
    echo json_encode([
        "status"  => "error",
        "message" => "Lỗi hệ thống, vui lòng thử lại sau."
    ]);
}