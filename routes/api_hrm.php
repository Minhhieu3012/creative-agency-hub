<?php
// Bắt buộc trả về định dạng JSON cho API
header('Content-Type: application/json; charset=utf-8');

// 1. NẠP FILE THỦ CÔNG (Bỏ qua Autoload để tránh lỗi môi trường)
require_once __DIR__ . '/../app/Models/HRM/Department.php';
require_once __DIR__ . '/../app/Controllers/HRM/DepartmentController.php';

use App\Controllers\HRM\DepartmentController;

// 2. KHỞI TẠO KẾT NỐI CSDL (PDO)
// Lưu ý: Giả định bạn dùng XAMPP mặc định (user: root, password rỗng)
try {
    $db = new PDO('mysql:host=localhost;dbname=creative_agency;charset=utf8mb4', 'root', '');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (\PDOException $e) {
    http_response_code(500);
    die(json_encode(['status' => 500, 'error' => 'Lỗi kết nối CSDL: ' . $e->getMessage()]));
}

// 3. KHỞI TẠO CONTROLLER
$departmentController = new DepartmentController($db);

// 4. ĐỊNH TUYẾN (ROUTING)
// Mẹo xử lý Edge Case: Chấp nhận method GET với tham số ?action=... để bạn dễ dàng test trực tiếp trên trình duyệt mà không cần cài Postman.
$action = $_GET['action'] ?? 'index';
$id = $_GET['id'] ?? null;

// Luồng dữ liệu (Data Flow)
if ($action === 'index') {
    // Test lấy danh sách
    $departmentController->index();

} elseif ($action === 'delete' && $id) {
    // Test xóa mềm phòng ban
    $departmentController->destroy($id);

} else {
    http_response_code(404);
    echo json_encode(['status' => 404, 'error' => 'Endpoint hoặc Action không tồn tại']);
}