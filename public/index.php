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

use App\Middleware\AuthMiddleware;
use App\Middleware\RoleMiddleware;

require_once __DIR__ . '/../app/Controllers/TaskController.php';

header('Content-Type: application/json; charset=utf-8');

require_once BASE_PATH . '/core/Router.php';
$router = new Router();

require_once BASE_PATH . '/routes/web.php';

// Lấy $uri và $method
$uri    = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];
$base   = '/creative-agency-hub/public';
$path   = str_replace($base, '', $uri); // <-- dùng $path thống nhất

// Load và đăng ký api routes vào router (tùy chọn — hoặc dùng foreach bên dưới)
$routes = require __DIR__ . '/../routes/api.php';

try {
    foreach ($routes as [$rMethod, $rPath, $controllerClass, $action, $roles]) {
        $pattern = preg_replace('#:(\w+)#', '(\d+)', $rPath);
        $pattern = '#^' . $pattern . '$#';

        if ($method === $rMethod && preg_match($pattern, $path, $matches)) {
            array_shift($matches);
            $authUser = null;

            if ($roles !== null) {
                $authUser = AuthMiddleware::check();
                RoleMiddleware::handle($authUser, $roles);
            }

            // Xử lý đặc biệt: TaskController không dùng namespace
            if ($controllerClass === 'TaskController') {
                $controller = new $controllerClass();
            } else {
                $controller = new $controllerClass($authUser);
            }

            $controller->$action(...$matches);
            exit;
        }
    }

    // Route UI Kanban (trả HTML, không phải JSON)
    if ($method === 'GET' && $path === '/tasks/board') {
        header('Content-Type: text/html; charset=utf-8');
        $controller = new TaskController();
        $controller->showBoard();
        exit;
    }

    http_response_code(404);
    echo json_encode([
        "status"  => "error",
        "message" => "API Route không tồn tại: $method $path"
    ]);

} catch (\Throwable $e) {
    error_log($e->getMessage());
    http_response_code(500);
    echo json_encode([
        "status"  => "error",
        "message" => "Lỗi hệ thống, vui lòng thử lại sau."
    ]);
}