<?php
namespace App\Controllers\HRM;

use App\Models\HRM\Position;
use PDO;

class PositionController {
    private $positionModel;

    public function __construct(PDO $db) {
        $this->positionModel = new Position($db);
    }

    public function index() {
        $positions = $this->positionModel->getAllActive();
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 200,
            'message' => 'Lấy danh sách chức vụ thành công',
            'data' => $positions
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    public function destroy($id) {
        header('Content-Type: application/json');

        if (!is_numeric($id) || $id <= 0) {
            http_response_code(400);
            echo json_encode(['status' => 400, 'error' => 'ID chức vụ không hợp lệ']);
            exit;
        }

        // GUARD: Chặn xóa nếu còn người giữ chức vụ
        $activeEmployeesCount = $this->positionModel->countActiveEmployees($id);

        if ($activeEmployeesCount > 0) {
            http_response_code(400);
            echo json_encode([
                'status' => 400, 
                'error' => 'Từ chối xóa. Chức vụ này đang được gán cho ' . $activeEmployeesCount . ' nhân viên.',
                'code' => 'POSITION_NOT_EMPTY'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $isDeleted = $this->positionModel->softDelete($id);

        if ($isDeleted) {
            http_response_code(200);
            echo json_encode(['status' => 200, 'message' => 'Đã xóa chức vụ thành công'], JSON_UNESCAPED_UNICODE);
        } else {
            http_response_code(404);
            echo json_encode(['status' => 404, 'error' => 'Không tìm thấy chức vụ'], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }
}