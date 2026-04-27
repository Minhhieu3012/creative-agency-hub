<?php

// DEV MODE 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

//  BASE PATH 
define('BASE_PATH', dirname(__DIR__));

// JSON HEADER 
header('Content-Type: application/json');

// AUTOLOAD 
require_once BASE_PATH . '/vendor/autoload.php';

//  ENV 
$dotenv = Dotenv\Dotenv::createImmutable(BASE_PATH);
$dotenv->load();

// ROUTER 
require_once BASE_PATH . '/core/Router.php';
$router = new Router();

// ROUTES 
require_once BASE_PATH . '/routes/web.php';

// REQUEST 
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

// REMOVE /public nếu cần 
$base = '/creative-agency-hub/public'; 
$uri = str_replace($base, '', $uri);

// RESOLVE 
$router->resolve($method, $uri);