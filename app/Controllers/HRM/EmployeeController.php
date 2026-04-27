<?php
namespace App\Controllers\HRM;

use App\Models\HRM\Employee;
use PDO;

class EmployeeController {
    private $employeeModel;

    public function __construct(PDO $db) {
        $this->employeeModel = new Employee($db);
    }

    /**
     * API: Lấy danh sách nhân viên đa năng (Phân trang, Tìm kiếm, Lọc)
     * Giữ nguyên cấu trúc JSON có trường 'message' từ mã nguồn cũ của bạn.
     */
    public function index() {
        $params = [
            'search'        => $_GET['search'] ?? null,
            'department_id' => $_GET['department_id'] ?? null,
            'status'        => $_GET['status'] ?? null,
            'page'          => $_GET['page'] ?? 1,
            'limit'         => $_GET['limit'] ?? 10
        ];

        $result = $this->employeeModel->getList($params);

        header('Content-Type: application/json');
        echo json_encode([
            'status'     => 200,
            'message'    => 'Lấy danh sách nhân viên thành công',
            'data'       => $result['items'],
            'pagination' => $result['pagination']
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    /**
     * API Cập nhật thông tin (Security: Allowlist)
     * Ngăn chặn ghi đè các trường nhạy cảm như lương, ngày phép.
     */
    public function update($id) {
        header('Content-Type: application/json');
        
        // Đọc dữ liệu từ Raw JSON hoặc Form-data
        $input = json_decode(file_get_contents("php://input"), true) ?? $_POST;

        // BẢO MẬT: Chỉ cho phép các trường này được cập nhật (Allowlist)
        // Root Cause: Tránh việc người dùng tự ý cập nhật remaining_leave_days hoặc base_salary
        $allowlist = ['full_name', 'phone', 'gender', 'date_of_birth', 'address'];
        $filteredData = array_intersect_key($input, array_flip($allowlist));

        if (empty($filteredData)) {
            http_response_code(400);
            echo json_encode([
                'status' => 400, 
                'error'  => 'Không có dữ liệu hợp lệ để cập nhật'
            ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            exit;
        }

        if ($this->employeeModel->update($id, $filteredData)) {
            echo json_encode([
                'status'  => 200, 
                'message' => 'Cập nhật hồ sơ nhân viên thành công'
            ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        } else {
            http_response_code(500);
            echo json_encode([
                'status' => 500, 
                'error'  => 'Lỗi hệ thống khi cập nhật dữ liệu'
            ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        }
        exit;
    }

    /**
     * API Upload Avatar an toàn (Security: MIME Check & Storage Cleanup)
     */
    public function uploadAvatar($id) {
        header('Content-Type: application/json');

        // Kiểm tra file có tồn tại và không có lỗi upload
        if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
            http_response_code(400);
            echo json_encode([
                'status' => 400, 
                'error'  => 'Vui lòng chọn một file ảnh hợp lệ'
            ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            exit;
        }

        $fileTmpPath = $_FILES['avatar']['tmp_name'];
        $fileName    = $_FILES['avatar']['name'];
        
        // 1. Kiểm tra MIME Type thực tế (Security) - Không tin vào đuôi file
        $finfo     = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType  = $finfo->file($fileTmpPath);
        $allowedMimes = ['image/jpeg', 'image/png', 'image/gif'];

        if (!in_array($mimeType, $allowedMimes)) {
            http_response_code(400);
            echo json_encode([
                'status' => 400, 
                'error'  => 'Định dạng không hợp lệ. Chỉ chấp nhận JPG, PNG, GIF'
            ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            exit;
        }

        // 2. Tạo tên file duy nhất (Tránh ghi đè file của người khác)
        $extension   = pathinfo($fileName, PATHINFO_EXTENSION);
        $newFileName = bin2hex(random_bytes(10)) . '.' . $extension;

        // 3. Đường dẫn lưu trữ (đảm bảo thư mục tồn tại)
        $uploadDir = __DIR__ . '/../../../public/uploads/avatars/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $destPath = $uploadDir . $newFileName;

        if (move_uploaded_file($fileTmpPath, $destPath)) {
            // 4. Xóa ảnh cũ nếu có (Storage Cleanup) để tránh rác server
            $oldAvatar = $this->employeeModel->getAvatar($id);
            if ($oldAvatar && file_exists($uploadDir . $oldAvatar)) {
                unlink($uploadDir . $oldAvatar);
            }

            // 5. Cập nhật đường dẫn mới vào Database
            $this->employeeModel->updateAvatar($id, $newFileName);

            echo json_encode([
                'status'  => 200, 
                'message' => 'Upload ảnh đại diện thành công', 
                'avatar'  => $newFileName
            ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        } else {
            http_response_code(500);
            echo json_encode([
                'status' => 500, 
                'error'  => 'Không thể lưu file vào máy chủ'
            ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        }
        exit;
    }
}