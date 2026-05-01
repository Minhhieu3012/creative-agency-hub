<?php
namespace App\Controllers\HRM;

use Core\Database;
use Exception;
use PDO;

class EmployeeController {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    private function jsonResponse(array $payload, int $statusCode = 200): void {
        http_response_code($statusCode);
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }

    private function input(): array {
        $raw = file_get_contents('php://input');
        $json = json_decode($raw, true);

        if (is_array($json)) {
            return $json;
        }

        return $_POST ?? [];
    }

    private function nullableInt($value): ?int {
        if ($value === null || $value === '' || $value === false) {
            return null;
        }

        $number = (int) $value;
        return $number > 0 ? $number : null;
    }

    private function normalizeStatus(?string $status): string {
        $status = strtolower(trim((string) $status));
        $allowed = ['active', 'inactive', 'resigned', 'suspended'];

        return in_array($status, $allowed, true) ? $status : 'active';
    }

    private function normalizeRole(?string $role): string {
        $role = strtolower(trim((string) $role));
        $allowed = ['admin', 'manager', 'employee', 'client'];

        return in_array($role, $allowed, true) ? $role : 'employee';
    }

    private function normalizeGender(?string $gender): ?string {
        $gender = strtolower(trim((string) $gender));
        $allowed = ['male', 'female', 'other'];

        return in_array($gender, $allowed, true) ? $gender : 'other';
    }

    private function getEmployeeById(int $id): ?array {
        $stmt = $this->db->prepare("
            SELECT
                e.*,
                d.name AS department_name,
                p.name AS position_name,
                manager.full_name AS manager_name
            FROM employees e
            LEFT JOIN departments d ON e.department_id = d.id
            LEFT JOIN positions p ON e.position_id = p.id
            LEFT JOIN employees manager ON e.manager_id = manager.id
            WHERE e.id = :id
            AND e.deleted_at IS NULL
            LIMIT 1
        ");

        $stmt->execute([':id' => $id]);
        $employee = $stmt->fetch(PDO::FETCH_ASSOC);

        return $employee ?: null;
    }

    public function index(): void {
        $search = trim((string) ($_GET['search'] ?? ''));
        $departmentId = $this->nullableInt($_GET['department_id'] ?? null);
        $positionId = $this->nullableInt($_GET['position_id'] ?? null);
        $status = trim((string) ($_GET['status'] ?? ''));
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $limit = min(100, max(1, (int) ($_GET['limit'] ?? 20)));
        $offset = ($page - 1) * $limit;

        $where = ["e.deleted_at IS NULL"];
        $params = [];

        if ($search !== '') {
            $where[] = "(e.full_name LIKE :search OR e.email LIKE :search OR e.employee_code LIKE :search)";
            $params[':search'] = '%' . $search . '%';
        }

        if ($departmentId) {
            $where[] = "e.department_id = :department_id";
            $params[':department_id'] = $departmentId;
        }

        if ($positionId) {
            $where[] = "e.position_id = :position_id";
            $params[':position_id'] = $positionId;
        }

        if ($status !== '') {
            $where[] = "e.status = :status";
            $params[':status'] = $this->normalizeStatus($status);
        }

        $whereSql = implode(' AND ', $where);

        $sql = "
            SELECT
                e.id,
                e.department_id,
                e.position_id,
                e.manager_id,
                e.employee_code,
                e.full_name,
                e.email,
                e.role,
                e.phone,
                e.gender,
                e.date_of_birth,
                e.address,
                e.avatar,
                e.total_leave_days,
                e.remaining_leave_days,
                e.status,
                e.hire_date,
                e.resigned_date,
                e.created_at,
                e.updated_at,
                d.name AS department_name,
                p.name AS position_name,
                manager.full_name AS manager_name
            FROM employees e
            LEFT JOIN departments d ON e.department_id = d.id
            LEFT JOIN positions p ON e.position_id = p.id
            LEFT JOIN employees manager ON e.manager_id = manager.id
            WHERE {$whereSql}
            ORDER BY e.id DESC
            LIMIT :limit OFFSET :offset
        ";

        $stmt = $this->db->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $countStmt = $this->db->prepare("
            SELECT COUNT(*)
            FROM employees e
            WHERE {$whereSql}
        ");

        foreach ($params as $key => $value) {
            $countStmt->bindValue($key, $value);
        }

        $countStmt->execute();
        $total = (int) $countStmt->fetchColumn();

        $this->jsonResponse([
            'status' => 'success',
            'message' => 'Lấy danh sách nhân viên thành công',
            'data' => $items,
            'pagination' => [
                'total_items' => $total,
                'total_pages' => (int) ceil($total / $limit),
                'current_page' => $page,
                'limit' => $limit
            ]
        ]);
    }

    public function show($id): void {
        $employee = $this->getEmployeeById((int) $id);

        if (!$employee) {
            $this->jsonResponse([
                'status' => 'error',
                'message' => 'Không tìm thấy nhân viên'
            ], 404);
        }

        $this->jsonResponse([
            'status' => 'success',
            'message' => 'Lấy thông tin nhân viên thành công',
            'data' => $employee
        ]);
    }

    public function store(): void {
        $input = $this->input();

        $required = ['department_id', 'position_id', 'employee_code', 'full_name', 'email', 'hire_date'];

        foreach ($required as $field) {
            if (empty($input[$field])) {
                $this->jsonResponse([
                    'status' => 'error',
                    'message' => "Thiếu trường bắt buộc: {$field}"
                ], 400);
            }
        }

        $password = trim((string) ($input['password'] ?? ''));
        $passwordHash = password_hash($password !== '' ? $password : '123456', PASSWORD_BCRYPT);

        try {
            $stmt = $this->db->prepare("
                INSERT INTO employees (
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
                    total_leave_days,
                    remaining_leave_days,
                    status,
                    hire_date,
                    resigned_date
                )
                VALUES (
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
                    :total_leave_days,
                    :remaining_leave_days,
                    :status,
                    :hire_date,
                    :resigned_date
                )
            ");

            $status = $this->normalizeStatus($input['status'] ?? 'active');
            $resignedDate = $status === 'resigned'
                ? ($input['resigned_date'] ?? date('Y-m-d'))
                : null;

            $totalLeave = isset($input['total_leave_days']) ? (int) $input['total_leave_days'] : 12;
            $remainingLeave = isset($input['remaining_leave_days']) ? (float) $input['remaining_leave_days'] : $totalLeave;

            $stmt->execute([
                ':department_id' => (int) $input['department_id'],
                ':position_id' => (int) $input['position_id'],
                ':manager_id' => $this->nullableInt($input['manager_id'] ?? null),
                ':employee_code' => trim((string) $input['employee_code']),
                ':full_name' => trim((string) $input['full_name']),
                ':email' => trim((string) $input['email']),
                ':password' => $passwordHash,
                ':role' => $this->normalizeRole($input['role'] ?? 'employee'),
                ':phone' => trim((string) ($input['phone'] ?? '')),
                ':gender' => $this->normalizeGender($input['gender'] ?? 'other'),
                ':date_of_birth' => $input['date_of_birth'] ?: null,
                ':address' => trim((string) ($input['address'] ?? '')),
                ':total_leave_days' => $totalLeave,
                ':remaining_leave_days' => $remainingLeave,
                ':status' => $status,
                ':hire_date' => $input['hire_date'],
                ':resigned_date' => $resignedDate
            ]);

            $employeeId = (int) $this->db->lastInsertId();

            $this->jsonResponse([
                'status' => 'success',
                'message' => 'Tạo nhân viên thành công',
                'data' => $this->getEmployeeById($employeeId)
            ], 201);
        } catch (\PDOException $e) {
            $message = $e->getCode() === '23000'
                ? 'Email hoặc mã nhân viên đã tồn tại'
                : $e->getMessage();

            $this->jsonResponse([
                'status' => 'error',
                'message' => $message
            ], 400);
        }
    }

    public function update($id): void {
        $employee = $this->getEmployeeById((int) $id);

        if (!$employee) {
            $this->jsonResponse([
                'status' => 'error',
                'message' => 'Không tìm thấy nhân viên'
            ], 404);
        }

        $input = $this->input();

        $allowed = [
            'department_id',
            'position_id',
            'manager_id',
            'full_name',
            'phone',
            'gender',
            'date_of_birth',
            'address',
            'role',
            'status',
            'hire_date',
            'resigned_date',
            'total_leave_days',
            'remaining_leave_days'
        ];

        $data = [];

        foreach ($allowed as $field) {
            if (array_key_exists($field, $input)) {
                $data[$field] = $input[$field] === '' ? null : $input[$field];
            }
        }

        if (array_key_exists('department_id', $data)) {
            $data['department_id'] = (int) $data['department_id'];
        }

        if (array_key_exists('position_id', $data)) {
            $data['position_id'] = (int) $data['position_id'];
        }

        if (array_key_exists('manager_id', $data)) {
            $data['manager_id'] = $this->nullableInt($data['manager_id']);
        }

        if (array_key_exists('role', $data)) {
            $data['role'] = $this->normalizeRole($data['role']);
        }

        if (array_key_exists('status', $data)) {
            $data['status'] = $this->normalizeStatus($data['status']);

            if ($data['status'] === 'resigned' && empty($data['resigned_date'])) {
                $data['resigned_date'] = date('Y-m-d');
            }

            if ($data['status'] !== 'resigned') {
                $data['resigned_date'] = null;
            }
        }

        if (array_key_exists('gender', $data)) {
            $data['gender'] = $this->normalizeGender($data['gender']);
        }

        if (isset($input['password']) && trim((string) $input['password']) !== '') {
            $data['password'] = password_hash(trim((string) $input['password']), PASSWORD_BCRYPT);
        }

        if (!$data) {
            $this->jsonResponse([
                'status' => 'error',
                'message' => 'Không có dữ liệu hợp lệ để cập nhật'
            ], 400);
        }

        $sets = [];
        $params = [':id' => (int) $id];

        foreach ($data as $key => $value) {
            $sets[] = "{$key} = :{$key}";
            $params[":{$key}"] = $value;
        }

        $sql = "
            UPDATE employees
            SET " . implode(', ', $sets) . "
            WHERE id = :id
            AND deleted_at IS NULL
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        $this->jsonResponse([
            'status' => 'success',
            'message' => 'Cập nhật nhân viên thành công',
            'data' => $this->getEmployeeById((int) $id)
        ]);
    }

    public function destroy($id): void {
        $employee = $this->getEmployeeById((int) $id);

        if (!$employee) {
            $this->jsonResponse([
                'status' => 'error',
                'message' => 'Không tìm thấy nhân viên'
            ], 404);
        }

        $stmt = $this->db->prepare("
            UPDATE employees
            SET deleted_at = CURRENT_TIMESTAMP,
                status = 'inactive',
                email = CONCAT(email, '.deleted.', UNIX_TIMESTAMP()),
                employee_code = CONCAT(employee_code, '-DEL-', UNIX_TIMESTAMP())
            WHERE id = :id
        ");

        $stmt->execute([':id' => (int) $id]);

        $this->jsonResponse([
            'status' => 'success',
            'message' => 'Xóa mềm nhân viên thành công'
        ]);
    }

    public function uploadAvatar($id): void {
        $employee = $this->getEmployeeById((int) $id);

        if (!$employee) {
            $this->jsonResponse([
                'status' => 'error',
                'message' => 'Không tìm thấy nhân viên'
            ], 404);
        }

        if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
            $this->jsonResponse([
                'status' => 'error',
                'message' => 'Vui lòng chọn file ảnh hợp lệ'
            ], 400);
        }

        $tmpPath = $_FILES['avatar']['tmp_name'];
        $originalName = $_FILES['avatar']['name'];

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($tmpPath);

        $allowed = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif'
        ];

        if (!isset($allowed[$mime])) {
            $this->jsonResponse([
                'status' => 'error',
                'message' => 'Chỉ chấp nhận JPG, PNG hoặc GIF'
            ], 400);
        }

        $extension = $allowed[$mime] ?: strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $fileName = 'avatar_' . bin2hex(random_bytes(10)) . '.' . $extension;

        $uploadDir = BASE_PATH . '/public/uploads/avatars/';

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        if (!move_uploaded_file($tmpPath, $uploadDir . $fileName)) {
            $this->jsonResponse([
                'status' => 'error',
                'message' => 'Không thể lưu avatar'
            ], 500);
        }

        if (!empty($employee['avatar']) && file_exists($uploadDir . $employee['avatar'])) {
            @unlink($uploadDir . $employee['avatar']);
        }

        $stmt = $this->db->prepare("
            UPDATE employees
            SET avatar = :avatar
            WHERE id = :id
        ");

        $stmt->execute([
            ':avatar' => $fileName,
            ':id' => (int) $id
        ]);

        $this->jsonResponse([
            'status' => 'success',
            'message' => 'Upload avatar thành công',
            'data' => [
                'avatar' => $fileName,
                'employee' => $this->getEmployeeById((int) $id)
            ]
        ]);
    }

    public function adjustLeave($id): void {
        $input = $this->input();
        $adjustDays = (float) ($input['adjust_days'] ?? 0);
        $reason = trim((string) ($input['reason'] ?? 'Điều chỉnh thủ công'));

        if ($adjustDays == 0) {
            $this->jsonResponse([
                'status' => 'error',
                'message' => 'Số ngày điều chỉnh phải khác 0'
            ], 400);
        }

        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare("
                SELECT remaining_leave_days
                FROM employees
                WHERE id = :id
                AND deleted_at IS NULL
                FOR UPDATE
            ");
            $stmt->execute([':id' => (int) $id]);
            $old = $stmt->fetchColumn();

            if ($old === false) {
                throw new Exception('Không tìm thấy nhân viên');
            }

            $new = (float) $old + $adjustDays;

            if ($new < 0) {
                throw new Exception('Số dư phép không đủ');
            }

            $update = $this->db->prepare("
                UPDATE employees
                SET remaining_leave_days = :new
                WHERE id = :id
            ");
            $update->execute([
                ':new' => $new,
                ':id' => (int) $id
            ]);

            try {
                $log = $this->db->prepare("
                    INSERT INTO employee_leave_adjustments (
                        employee_id,
                        adjustment_days,
                        old_remaining_days,
                        new_remaining_days,
                        reason,
                        created_by
                    )
                    VALUES (
                        :employee_id,
                        :adjustment_days,
                        :old_remaining_days,
                        :new_remaining_days,
                        :reason,
                        :created_by
                    )
                ");

                $log->execute([
                    ':employee_id' => (int) $id,
                    ':adjustment_days' => $adjustDays,
                    ':old_remaining_days' => $old,
                    ':new_remaining_days' => $new,
                    ':reason' => $reason,
                    ':created_by' => 1
                ]);
            } catch (\Throwable $e) {
                error_log('Skip leave adjustment log: ' . $e->getMessage());
            }

            $this->db->commit();

            $this->jsonResponse([
                'status' => 'success',
                'message' => 'Cập nhật quỹ phép thành công',
                'data' => $this->getEmployeeById((int) $id)
            ]);
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            $this->jsonResponse([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 400);
        }
    }
}