<?php
// test

require_once '../app/Controllers/TaskController.php';

// Router siêu đơn giản
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];
$controller = new TaskController();

// 1. Tuyến đường tải giao diện
if ($uri === '/tasks/board' && $method === 'GET') {
    $controller->showBoard();
} 
// 2. Tuyến đường gọi API lấy dữ liệu
elseif ($uri === '/api/tasks' && $method === 'GET') {
    $controller->getTasksAPI();
}
// 3. Tuyến đường gọi API cập nhật trạng thái
elseif ($uri === '/api/tasks/update' && $method === 'POST') {
    $controller->updateTaskStatusAPI();
} 
else {
    echo "404 Not Found";
}
?>