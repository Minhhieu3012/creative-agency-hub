<?php
// Bắt buộc trả về định dạng JSON cho API
header('Content-Type: application/json; charset=utf-8');

// 1. NẠP FILE THỦ CÔNG (Bỏ qua Autoload để tránh lỗi môi trường)
require_once __DIR__ . '/../app/Models/HRM/Department.php';
require_once __DIR__ . '/../app/Controllers/HRM/DepartmentController.php';

require_once __DIR__ . '/../app/Models/HRM/Position.php';
require_once __DIR__ . '/../app/Controllers/HRM/PositionController.php';

require_once __DIR__ . '/../app/Models/HRM/EmployeeContract.php';
require_once __DIR__ . '/../app/Controllers/HRM/EmployeeContractController.php';

use App\Controllers\HRM\DepartmentController;
use App\Controllers\HRM\PositionController;
use App\Controllers\HRM\EmployeeContractController;

// 2. KHỞI TẠO KẾT NỐI CSDL (PDO)
// Lưu ý: Giả định bạn dùng XAMPP mặc định (user: root, password rỗng)
try {
    $db = new PDO('mysql:host=localhost;dbname=creative_agency;charset=utf8mb4', 'root', '');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (\PDOException $e) {
    http_response_code(500);
    die(json_encode(['status' => 500, 'error' => 'Lỗi kết nối CSDL: ' . $e->getMessage()]));
}

// 3. KHỞI TẠO CONTROLLERS
$departmentController = new DepartmentController($db);
$positionController = new PositionController($db);
$contractController = new EmployeeContractController($db);

// 4. ĐỊNH TUYẾN (ROUTING)
// Mẹo xử lý Edge Case: Chấp nhận method GET với tham số ?module=... & ?action=...
$module = $_GET['module'] ?? 'departments'; // Mặc định là phòng ban nếu không truyền
$action = $_GET['action'] ?? 'index';       // Mặc định là lấy danh sách nếu không truyền
$id = $_GET['id'] ?? null;

// Luồng dữ liệu (Data Flow) - Đã kết hợp bắt lỗi 404 cho từng Action
if ($module === 'departments') {
    if ($action === 'index') {
        $departmentController->index();
    } elseif ($action === 'delete' && $id) {
        $departmentController->destroy($id);
    } else {
        http_response_code(404);
        echo json_encode(['status' => 404, 'error' => 'Action không tồn tại trong module Departments']);
    }
} 
elseif ($module === 'positions') {
    if ($action === 'index') {
        $positionController->index();
    } elseif ($action === 'delete' && $id) {
        $positionController->destroy($id);
    } else {
        http_response_code(404);
        echo json_encode(['status' => 404, 'error' => 'Action không tồn tại trong module Positions']);
    }
} 
elseif ($module === 'contracts') {
    if ($action === 'index') {
        $contractController->index();
    } elseif ($action === 'delete' && $id) {
        $contractController->destroy($id);
    } else {
        http_response_code(404);
        echo json_encode(['status' => 404, 'error' => 'Action không tồn tại trong module Contracts']);
    }
} 
else {
    // Xử lý Edge Case: Nhập sai tên module (VD: ?module=abc)
    http_response_code(404);
    echo json_encode(['status' => 404, 'error' => 'Module hoặc Endpoint không tồn tại']);
}