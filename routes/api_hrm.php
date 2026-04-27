<?php
// Bắt buộc trả về định dạng JSON cho API
header('Content-Type: application/json; charset=utf-8');

// 1. NẠP FILE THỦ CÔNG (Bỏ qua Autoload để tránh lỗi môi trường)
// Module Phòng ban
require_once __DIR__ . '/../app/Models/HRM/Department.php';
require_once __DIR__ . '/../app/Controllers/HRM/DepartmentController.php';

// Module Chức vụ
require_once __DIR__ . '/../app/Models/HRM/Position.php';
require_once __DIR__ . '/../app/Controllers/HRM/PositionController.php';

// Module Hợp đồng
require_once __DIR__ . '/../app/Models/HRM/EmployeeContract.php';
require_once __DIR__ . '/../app/Controllers/HRM/EmployeeContractController.php';

// Module Nhân viên (Mới - Giai đoạn 3)
require_once __DIR__ . '/../app/Models/HRM/Employee.php';
require_once __DIR__ . '/../app/Controllers/HRM/EmployeeController.php';

use App\Controllers\HRM\DepartmentController;
use App\Controllers\HRM\PositionController;
use App\Controllers\HRM\EmployeeContractController;
use App\Controllers\HRM\EmployeeController;

// 2. KHỞI TẠO KẾT NỐI CSDL (PDO)
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
$employeeController = new EmployeeController($db);

// 4. ĐỊNH TUYẾN (ROUTING)
$module = $_GET['module'] ?? 'departments'; 
$action = $_GET['action'] ?? 'index'; 
$id = $_GET['id'] ?? null;

// Luồng dữ liệu (Data Flow) - Điều hướng theo từng Module
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
elseif ($module === 'employees') {
    // Luồng xử lý cho Module Nhân viên
    if ($action === 'index') {
        $employeeController->index();
    } else {
        http_response_code(404);
        echo json_encode(['status' => 404, 'error' => 'Action không tồn tại trong module Employees']);
    }
}
else {
    // Xử lý Edge Case: Nhập sai tên module
    http_response_code(404);
    echo json_encode(['status' => 404, 'error' => 'Module hoặc Endpoint không tồn tại']);
}