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

// require_once __DIR__ . '/../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(BASE_PATH);
$dotenv->load();

use App\Middleware\AuthMiddleware;
use App\Middleware\RoleMiddleware;
// use App\Controllers\AuthController;
// use App\Controllers\TaskController;
// use App\Controllers\HRM\EmployeeController;

// Ghi chú: Nạp class TaskController của Huy (Không dùng namespace)
require_once __DIR__ . '/../app/Controllers/TaskController.php';
require_once __DIR__ . '/../app/Controllers/AuthController.php';
require_once __DIR__ . '/../app/Controllers/HRM/EmployeeController.php'; 

header('Content-Type: application/json; charset=utf-8');

// require_once BASE_PATH . '/core/Router.php';
// $router = new Router();

// load api.php routes
// $apiRoutes = require __DIR__ . '/../routes/api.php';
// foreach ($apiRoutes as $route) {

//     if (!is_array($route)) {
//         continue;
//     }

//     [$method, $uri, $controller, $action] = $route;

//     $router->{strtolower($method)}(
//         $uri,
//         $controller . '@' . $action
//     );
// }

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

$base = '/creative-agency-hub/public';

$path = str_replace($base, '', $uri);


$routes = require __DIR__ . '/../routes/api.php';

try {
    foreach ($routes as $route) {

        [$rMethod, $rPath, $handler, $roles] = $route;

        // convert :id → regex
        $pattern = preg_replace('#:(\w+)#', '(\d+)', $rPath);
        $pattern = '#^' . $pattern . '$#';

        if ($method === $rMethod && preg_match($pattern, $path, $matches)) {

            array_shift($matches);

            // nếu là closure (route "/")
            if (is_callable($handler)) {
                return $handler();
            }

            [$controllerName, $action] = explode('@', $handler);

            // resolve namespace
            $controllerClass = null;

            if (class_exists("App\\Controllers\\$controllerName")) {
                $controllerClass = "App\\Controllers\\$controllerName";
            } elseif (class_exists("App\\Controllers\\HRM\\$controllerName")) {
                $controllerClass = "App\\Controllers\\HRM\\$controllerName";
            } else {
                throw new Exception("Controller not found: $controllerName");
            }

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

    http_response_code(404);
    echo json_encode([
        "status" => "error",
        "message" => "API Route không tồn tại."
    ]);

} catch (\Throwable $e) {

    error_log($e->getMessage());

    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}