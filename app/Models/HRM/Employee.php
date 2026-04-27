<?php
namespace App\Models\HRM;

use PDO;
use Exception;

class Employee {
    private $db;

    public function __construct(PDO $dbConnection) {
        $this->db = $dbConnection;
    }

    /**
     * Lấy danh sách kèm Phân trang, Tìm kiếm và Lọc đa điều kiện
     * Kết hợp logic từ bản cũ (đầy đủ filter) và bản mới
     */
    public function getList($params) {
        // 1. Khởi tạo các tham số phân trang
        $limit = isset($params['limit']) ? (int)$params['limit'] : 10;
        $page = isset($params['page']) ? (int)$params['page'] : 1;
        $offset = ($page - 1) * $limit;

        // 2. Thu thập các bộ lọc
        $search = $params['search'] ?? null;
        $deptId = $params['department_id'] ?? null;
        $posId = $params['position_id'] ?? null;
        $status = $params['status'] ?? null;

        // 3. Xây dựng câu truy vấn SQL với JOIN để lấy tên Phòng ban/Chức vụ
        $sql = "SELECT e.*, d.name as department_name, p.name as position_name 
                FROM employees e
                JOIN departments d ON e.department_id = d.id
                JOIN positions p ON e.position_id = p.id
                WHERE e.deleted_at IS NULL";
        
        $bindParams = [];

        // 4. Xử lý logic Tìm kiếm (Tên hoặc Mã nhân viên)
        if ($search) {
            $sql .= " AND (e.full_name LIKE :search OR e.employee_code LIKE :search2)";
            $bindParams[':search'] = "%$search%";
            $bindParams[':search2'] = "%$search%";
        }

        // 5. Xử lý logic Lọc theo Phòng ban
        if ($deptId) {
            $sql .= " AND e.department_id = :dept_id";
            $bindParams[':dept_id'] = $deptId;
        }

        // 6. Xử lý logic Lọc theo Chức vụ
        if ($posId) {
            $sql .= " AND e.position_id = :pos_id";
            $bindParams[':pos_id'] = $posId;
        }

        // 7. Xử lý logic Lọc theo Trạng thái
        if ($status) {
            $sql .= " AND e.status = :status";
            $bindParams[':status'] = $status;
        }

        // 8. Sắp xếp và Phân trang
        $sql .= " ORDER BY e.id DESC LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);

        // Bind các tham số lọc
        foreach ($bindParams as $key => $val) {
            $stmt->bindValue($key, $val);
        }

        // Bắt buộc dùng bindValue cho LIMIT và OFFSET với kiểu INT để PDO không báo lỗi
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        
        $stmt->execute();
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 9. Đếm tổng số bản ghi (phục vụ tính toán số trang ở Frontend)
        $countSql = "SELECT COUNT(*) FROM employees WHERE deleted_at IS NULL";
        $total = $this->db->query($countSql)->fetchColumn();

        return [
            'items' => $items,
            'pagination' => [
                'total_items' => (int)$total,
                'total_pages' => ceil($total / $limit),
                'current_page' => $page,
                'limit' => $limit
            ]
        ];
    }

    /**
     * Cập nhật hồ sơ nhân viên
     * Sử dụng cơ chế động để cập nhật các trường trong Allowlist từ Controller
     */
    public function update($id, $data) {
        if (empty($data)) return false;

        $fields = [];
        $params = [':id' => $id];
        
        foreach ($data as $key => $value) {
            $fields[] = "$key = :$key";
            $params[":$key"] = $value;
        }
        
        $sql = "UPDATE employees SET " . implode(', ', $fields) . " WHERE id = :id AND deleted_at IS NULL";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Lấy thông tin avatar hiện tại để phục vụ việc xóa file vật lý khi đổi ảnh
     */
    public function getAvatar($id) {
        $stmt = $this->db->prepare("SELECT avatar FROM employees WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetchColumn();
    }

    /**
     * Cập nhật tên file avatar mới vào cơ sở dữ liệu
     */
    public function updateAvatar($id, $filename) {
        $stmt = $this->db->prepare("UPDATE employees SET avatar = :avatar WHERE id = :id");
        return $stmt->execute([':avatar' => $filename, ':id' => $id]);
    }
}