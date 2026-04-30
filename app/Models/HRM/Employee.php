<?php
namespace App\Models\HRM;

use PDO;
use Core\Database;
use Exception;

class Employee {
    private $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    public function findByEmail($email) {
        $stmt = $this->db->prepare("SELECT * FROM employees WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
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

    /**
     * Điều chỉnh quỹ phép (Atomic Update) - Giai đoạn 4
     */
    public function adjustLeaveBalance($employeeId, $adjustDays, $reason) {
        try {
            $this->db->beginTransaction();

            // 1. Cập nhật số dư phép trực tiếp trong SQL (Ngăn lỗi lost update và số âm)
            $sqlAdjust = "UPDATE employees 
                          SET remaining_leave_days = remaining_leave_days + :adjust 
                          WHERE id = :id 
                          AND (remaining_leave_days + :adjust2) >= 0";
            
            $stmtAdjust = $this->db->prepare($sqlAdjust);
            $stmtAdjust->execute([
                ':id' => $employeeId,
                ':adjust' => $adjustDays,
                ':adjust2' => $adjustDays
            ]);

            if ($stmtAdjust->rowCount() === 0) {
                throw new Exception("Số dư phép không đủ hoặc nhân viên không tồn tại");
            }

            // 2. Lấy số dư mới để ghi log (Audit Trail)
            $stmtNew = $this->db->prepare("SELECT remaining_leave_days FROM employees WHERE id = :id");
            $stmtNew->execute([':id' => $employeeId]);
            $newDays = $stmtNew->fetchColumn();

            // 3. Ghi lịch sử điều chỉnh
            $sqlLog = "INSERT INTO employee_leave_adjustments 
                       (employee_id, adjustment_days, old_remaining_days, new_remaining_days, reason, created_by) 
                       VALUES (:id, :adjust, :old, :new, :reason, :by)";
            
            $stmtLog = $this->db->prepare($sqlLog);
            $stmtLog->execute([
                ':id' => $employeeId,
                ':adjust' => $adjustDays,
                ':old' => $newDays - $adjustDays,
                ':new' => $newDays,
                ':reason' => $reason,
                ':by' => 1 // Mặc định ID Admin là 1 để test
            ]);

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
    public function create($data) {
        $sql = "INSERT INTO employees (
            department_id,
            position_id,
            manager_id,
            employee_code,
            full_name,
            email,
            password,
            role,
            phone,
            gender,
            date_of_birth,
            address,
            hire_date,
            status
        ) VALUES (
            :department_id,
            :position_id,
            :manager_id,
            :employee_code,
            :full_name,
            :email,
            :password,
            :role,
            :phone,
            :gender,
            :date_of_birth,
            :address,
            :hire_date,
            :status
        )";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':department_id'  => $data['department_id'],
            ':position_id'    => $data['position_id'],
            ':manager_id'     => $data['manager_id'] ?? null,
            ':employee_code'  => $data['employee_code'],
            ':full_name'      => $data['full_name'],
            ':email'          => $data['email'],
            ':password'       => password_hash($data['password'], PASSWORD_BCRYPT),
            ':role'           => $data['role'] ?? 'employee',
            ':phone'          => $data['phone'] ?? null,
            ':gender'         => $data['gender'] ?? null,
            ':date_of_birth'  => $data['date_of_birth'] ?? null,
            ':address'        => $data['address'] ?? null,
            ':hire_date'      => $data['hire_date'],
            ':status'         => $data['status'] ?? 'active'
        ]);

        return $this->db->lastInsertId();
    }
}