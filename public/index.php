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

header('Content-Type: application/json; charset=utf-8');

$uri    = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path   = str_replace('/creative-agency-hub/public', '', $uri);
$method = $_SERVER['REQUEST_METHOD'];

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