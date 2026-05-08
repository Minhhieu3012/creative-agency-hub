<?php
namespace App\Controllers\HRM;

use App\Models\HRM\Department;
use PDO;

class DepartmentController {
    private $departmentModel;

    public function __construct(PDO $db) {
        // Giả định $db được truyền vào từ Core Router
        $this->departmentModel = new Department($db);
    }

    // API: GET /api/hrm/departments
    public function index() {
        $departments = $this->departmentModel->getAllActive();
        
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 200,
            'message' => 'Lấy danh sách phòng ban thành công',
            'data' => $departments
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    // API: POST /api/hrm/departments
    public function store() {
        header('Content-Type: application/json');
        
        // Lấy dữ liệu từ Request (Giả định là form-data hoặc JSON)
        $data = json_decode(file_get_contents("php://input"), true) ?? $_POST;
        
        $name = trim($data['name'] ?? '');
        $description = trim($data['description'] ?? '');

        if (empty($name)) {
            http_response_code(400);
            echo json_encode(['status' => 400, 'error' => 'Tên phòng ban không được để trống']);
            exit;
        }

        try {
            $isCreated = $this->departmentModel->create($name, $description);
            if ($isCreated) {
                http_response_code(201);
                echo json_encode(['status' => 201, 'message' => 'Tạo phòng ban thành công']);
            }
        } catch (\PDOException $e) {
            // Xử lý Edge Case: Trùng tên phòng ban (do ràng buộc UNIQUE ở Schema)
            if ($e->getCode() == 23000) {
                http_response_code(409);
                echo json_encode(['status' => 409, 'error' => 'Tên phòng ban đã tồn tại trong hệ thống']);
            } else {
                http_response_code(500);
                echo json_encode(['status' => 500, 'error' => 'Lỗi hệ thống: ' . $e->getMessage()]);
            }
        }
        exit;
    }

    // API: DELETE /api/hrm/departments/{id}
    public function destroy($id) {
        header('Content-Type: application/json');

        if (!is_numeric($id) || $id <= 0) {
            http_response_code(400);
            echo json_encode(['status' => 400, 'error' => 'ID phòng ban không hợp lệ']);
            exit;
        }

        // --- BẮT ĐẦU: SOFT DELETE GUARD ---
        // Truy xuất Data Flow: Controller -> Model -> CSDL (Đếm nhân viên)
        $activeEmployeesCount = $this->departmentModel->countActiveEmployees($id);

        // Chặn đứng hành động xóa nếu phòng ban đang có người
        if ($activeEmployeesCount > 0) {
            http_response_code(400);
            echo json_encode([
                'status' => 400, 
                'error' => 'Từ chối xóa. Phòng ban này vẫn còn ' . $activeEmployeesCount . ' nhân viên đang hoạt động.',
                'code' => 'DEPARTMENT_NOT_EMPTY'
            ]);
            exit;
        }
        // --- KẾT THÚC: SOFT DELETE GUARD ---

        // Thực thi xóa mềm
        $isDeleted = $this->departmentModel->softDelete($id);

        if ($isDeleted) {
            http_response_code(200);
            echo json_encode(['status' => 200, 'message' => 'Đã xóa (mềm) phòng ban thành công']);
        } else {
            http_response_code(404);
            echo json_encode(['status' => 404, 'error' => 'Không tìm thấy phòng ban hoặc đã bị xóa']);
        }
        exit;
    }
}