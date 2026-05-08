<?php
namespace App\Controllers\HRM;

use App\Models\HRM\EmployeeContract;
use PDO;

class EmployeeContractController {
    private $contractModel;

    public function __construct(PDO $db) {
        $this->contractModel = new EmployeeContract($db);
    }

    public function index() {
        $contracts = $this->contractModel->getAllWithDynamicStatus();
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 200,
            'message' => 'Lấy danh sách hợp đồng thành công',
            'data' => $contracts
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    public function destroy($id) {
        header('Content-Type: application/json');

        if (!is_numeric($id) || $id <= 0) {
            http_response_code(400);
            echo json_encode(['status' => 400, 'error' => 'ID hợp đồng không hợp lệ']);
            exit;
        }

        $isDeleted = $this->contractModel->softDelete($id);

        if ($isDeleted) {
            http_response_code(200);
            echo json_encode(['status' => 200, 'message' => 'Đã xóa/chấm dứt hợp đồng thành công'], JSON_UNESCAPED_UNICODE);
        } else {
            http_response_code(404);
            echo json_encode(['status' => 404, 'error' => 'Không tìm thấy hợp đồng'], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }
}