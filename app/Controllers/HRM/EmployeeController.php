<?php
namespace App\Controllers\HRM;

use App\Models\HRM\Employee;
use PDO;

class EmployeeController {
    private $employeeModel;

    public function __construct(PDO $db) {
        $this->employeeModel = new Employee($db);
    }

    // API: Lấy danh sách nhân viên đa năng
    public function index() {
        $params = [
            'search' => $_GET['search'] ?? null,
            'department_id' => $_GET['department_id'] ?? null,
            'status' => $_GET['status'] ?? null,
            'page' => $_GET['page'] ?? 1,
            'limit' => $_GET['limit'] ?? 10
        ];

        $result = $this->employeeModel->getList($params);

        header('Content-Type: application/json');
        echo json_encode([
            'status' => 200,
            'message' => 'Lấy danh sách nhân viên thành công',
            'data' => $result['items'],
            'pagination' => $result['pagination']
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    // Gợi ý cho API Update (Allowlist Security)
    public function update($id) {
        // Root Cause: Tránh ghi đè các trường nhạy cảm
        $allowedFields = ['full_name', 'phone', 'address', 'gender', 'date_of_birth'];
        // Logic sẽ thực hiện lọc $data đầu vào chỉ lấy các field trong allowedFields
    }
}