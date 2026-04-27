<?php
// Bắt buộc trả về định dạng JSON cho API
header('Content-Type: application/json; charset=utf-8');

/**
 * --- BẮT ĐẦU: LỚP BẢO MẬT (TOKEN CHECK) ---
 * Chặn đứng các yêu cầu không có quyền truy cập vào các thao tác nhạy cảm.
 */
function checkAuth() {
    $headers = getallheaders();
    // Kiểm tra Header Authorization có dạng 'Bearer <token>' không
    if (!isset($headers['Authorization']) || !preg_match('/Bearer\s(\S+)/', $headers['Authorization'], $matches)) {
        http_response_code(401);
        echo json_encode([
            'status' => 401, 
            'error' => 'Unauthorized: Thiếu Token bảo mật. Ae hãy dùng Postman gắn Bearer Token vào Header.'
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }
    // Ghi chú: Sau khi pull code mới, bạn có thể gọi hàm Verify của Hiếu tại đây:
    // $is_valid = Auth::verifyToken($matches[1]);
}
/** --- KẾT THÚC: LỚP BẢO MẬT --- */


// 1. NẠP FILE THỦ CÔNG (Module HRM hoàn chỉnh)
require_once __DIR__ . '/../app/Models/HRM/Department.php';
require_once __DIR__ . '/../app/Controllers/HRM/DepartmentController.php';

require_once __DIR__ . '/../app/Models/HRM/Position.php';
require_once __DIR__ . '/../app/Controllers/HRM/PositionController.php';

require_once __DIR__ . '/../app/Models/HRM/EmployeeContract.php';
require_once __DIR__ . '/../app/Controllers/HRM/EmployeeContractController.php';

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

/**
 * --- LỚP KIỂM TRA QUYỀN TRUY CẬP ---
 * Root Cause: Ngăn chặn việc thực hiện các hành động thay đổi dữ liệu mà không có Token.
 */
$secureActions = ['update', 'delete', 'adjust_leave', 'upload_avatar'];

if (in_array($action, $secureActions)) {
    checkAuth();
}


// 5. ĐIỀU HƯỚNG DỮ LIỆU (DATA FLOW)
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
    if ($action === 'index') {
        $employeeController->index();
    } elseif ($action === 'update' && $id) {
        $employeeController->update($id);
    } elseif ($action === 'upload_avatar' && $id) {
        $employeeController->uploadAvatar($id);
    } elseif ($action === 'adjust_leave' && $id) {
        // Hỗ trợ Action của Giai đoạn 4
        $employeeController->adjustLeave($id);
    } else {
        http_response_code(404);
        echo json_encode(['status' => 404, 'error' => 'Action không tồn tại trong module Employees']);
    }
}
else {
    http_response_code(404);
    echo json_encode(['status' => 404, 'error' => 'Module hoặc Endpoint không tồn tại']);
}