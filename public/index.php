<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// =======================
// CORS (dev)
// =======================
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


header('Content-Type: application/json; charset=utf-8');

require_once BASE_PATH . '/core/Router.php';
$router = new Router();


require_once BASE_PATH . '/routes/web.php';

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

$base = '/creative-agency-hub/public';
$uri = str_replace($base, '', $uri);


try {
    $router->resolve($method, $uri);
} catch (\Throwable $e) {
    error_log($e->getMessage());

    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Lỗi hệ thống, vui lòng thử lại sau."
    ]);
}