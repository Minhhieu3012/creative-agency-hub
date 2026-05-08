<?php
namespace App\Models\HRM;

use Core\Database;
use Exception;
use PDO;
use Throwable;

class Employee {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    private function tableExists(string $table): bool {
        $stmt = $this->db->prepare("
            SELECT COUNT(*)
            FROM INFORMATION_SCHEMA.TABLES
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = :table_name
        ");

        $stmt->execute([
            ':table_name' => $table,
        ]);

        return (int)$stmt->fetchColumn() > 0;
    }

    private function columnExists(string $table, string $column): bool {
        $stmt = $this->db->prepare("
            SELECT COUNT(*)
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = :table_name
              AND COLUMN_NAME = :column_name
        ");

        $stmt->execute([
            ':table_name' => $table,
            ':column_name' => $column,
        ]);

        return (int)$stmt->fetchColumn() > 0;
    }

    private function normalizeNullableId($value): ?int {
        if ($value === null || $value === '') {
            return null;
        }

        $id = (int)$value;

        return $id > 0 ? $id : null;
    }

    private function normalizeRole(?string $role): string {
        $role = strtolower(trim((string)$role));
        $allowed = ['admin', 'manager', 'employee', 'client'];

        return in_array($role, $allowed, true) ? $role : 'employee';
    }

    private function normalizeStatus(?string $status): string {
        $status = strtolower(trim((string)$status));
        $allowed = ['active', 'inactive', 'resigned', 'suspended'];

        return in_array($status, $allowed, true) ? $status : 'active';
    }

    private function normalizeGender($gender): ?string {
        if ($gender === null || $gender === '') {
            return null;
        }

        $gender = strtolower(trim((string)$gender));
        $allowed = ['male', 'female', 'other'];

        return in_array($gender, $allowed, true) ? $gender : null;
    }

    private function normalizePasswordHash(string $password): string {
        $info = password_get_info($password);

        if (!empty($info['algo'])) {
            return $password;
        }

        return password_hash($password, PASSWORD_BCRYPT);
    }

    private function generateEmployeeCode(string $role = 'employee'): string {
        $prefixes = [
            'admin' => 'ADM',
            'manager' => 'MGR',
            'employee' => 'EMP',
            'client' => 'CLI',
        ];

        $prefix = $prefixes[$role] ?? 'EMP';

        return $prefix . date('ymdHis') . random_int(10, 99);
    }

    private function getDefaultDepartmentId(): int {
        $stmt = $this->db->query("
            SELECT id
            FROM departments
            WHERE deleted_at IS NULL
              AND status = 'active'
            ORDER BY id ASC
            LIMIT 1
        ");

        $id = (int)$stmt->fetchColumn();

        if ($id <= 0) {
            throw new Exception('Chưa có phòng ban active. Vui lòng tạo phòng ban trước khi tạo tài khoản.');
        }

        return $id;
    }

    private function getDefaultPositionId(): int {
        $stmt = $this->db->query("
            SELECT id
            FROM positions
            WHERE deleted_at IS NULL
              AND status = 'active'
            ORDER BY id ASC
            LIMIT 1
        ");

        $id = (int)$stmt->fetchColumn();

        if ($id <= 0) {
            throw new Exception('Chưa có chức vụ active. Vui lòng tạo chức vụ trước khi tạo tài khoản.');
        }

        return $id;
    }

    private function prepareCreateData(array $data): array {
        $role = $this->normalizeRole($data['role'] ?? 'employee');
        $status = $this->normalizeStatus($data['status'] ?? 'active');
        $password = trim((string)($data['password'] ?? ''));

        if (trim((string)($data['full_name'] ?? '')) === '') {
            throw new Exception('Họ và tên không được để trống.');
        }

        if (trim((string)($data['email'] ?? '')) === '') {
            throw new Exception('Email không được để trống.');
        }

        if (!filter_var(trim((string)$data['email']), FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Email không hợp lệ.');
        }

        if ($password === '') {
            throw new Exception('Mật khẩu không được để trống.');
        }

        return [
            'department_id' => $this->normalizeNullableId($data['department_id'] ?? null) ?? $this->getDefaultDepartmentId(),
            'position_id' => $this->normalizeNullableId($data['position_id'] ?? null) ?? $this->getDefaultPositionId(),
            'manager_id' => $this->normalizeNullableId($data['manager_id'] ?? null),
            'employee_code' => trim((string)($data['employee_code'] ?? '')) ?: $this->generateEmployeeCode($role),
            'full_name' => trim((string)$data['full_name']),
            'email' => trim((string)$data['email']),
            'password' => $this->normalizePasswordHash($password),
            'role' => $role,
            'phone' => isset($data['phone']) && trim((string)$data['phone']) !== '' ? trim((string)$data['phone']) : null,
            'gender' => $this->normalizeGender($data['gender'] ?? null),
            'date_of_birth' => isset($data['date_of_birth']) && $data['date_of_birth'] !== '' ? $data['date_of_birth'] : null,
            'address' => isset($data['address']) && trim((string)$data['address']) !== '' ? trim((string)$data['address']) : null,
            'avatar' => isset($data['avatar']) && trim((string)$data['avatar']) !== '' ? trim((string)$data['avatar']) : null,
            'total_leave_days' => isset($data['total_leave_days']) ? (int)$data['total_leave_days'] : 12,
            'remaining_leave_days' => isset($data['remaining_leave_days']) ? (float)$data['remaining_leave_days'] : 12.00,
            'status' => $status,
            'hire_date' => !empty($data['hire_date']) ? $data['hire_date'] : date('Y-m-d'),
            'resigned_date' => !empty($data['resigned_date']) ? $data['resigned_date'] : null,
        ];
    }

    public function findByEmail($email) {
        $stmt = $this->db->prepare("
            SELECT *
            FROM employees
            WHERE email = :email
              AND deleted_at IS NULL
            LIMIT 1
        ");

        $stmt->execute([
            ':email' => $email,
        ]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function findById($id) {
        $stmt = $this->db->prepare("
            SELECT *
            FROM employees
            WHERE id = :id
              AND deleted_at IS NULL
            LIMIT 1
        ");

        $stmt->execute([
            ':id' => (int)$id,
        ]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function findProfileById($id) {
        $sql = "SELECT
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
                    m.full_name AS manager_name
                FROM employees e
                LEFT JOIN departments d ON d.id = e.department_id
                LEFT JOIN positions p ON p.id = e.position_id
                LEFT JOIN employees m ON m.id = e.manager_id
                WHERE e.id = :id
                  AND e.deleted_at IS NULL
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':id' => (int)$id,
        ]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function findProfileByEmail($email) {
        $sql = "SELECT
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
                    m.full_name AS manager_name
                FROM employees e
                LEFT JOIN departments d ON d.id = e.department_id
                LEFT JOIN positions p ON p.id = e.position_id
                LEFT JOIN employees m ON m.id = e.manager_id
                WHERE e.email = :email
                  AND e.deleted_at IS NULL
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':email' => $email,
        ]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getList($params = []) {
        $limit = isset($params['limit']) ? max(1, min(200, (int)$params['limit'])) : 50;
        $page = isset($params['page']) ? max(1, (int)$params['page']) : 1;
        $offset = ($page - 1) * $limit;

        $search = trim((string)($params['search'] ?? ''));
        $deptId = $this->normalizeNullableId($params['department_id'] ?? null);
        $posId = $this->normalizeNullableId($params['position_id'] ?? null);
        $status = trim((string)($params['status'] ?? ''));
        $role = trim((string)($params['role'] ?? ''));
        $managerId = $this->normalizeNullableId($params['manager_id'] ?? null);

        $where = ["e.deleted_at IS NULL"];
        $bindParams = [];

        if ($search !== '') {
            $where[] = "(e.full_name LIKE :search_name OR e.employee_code LIKE :search_code OR e.email LIKE :search_email)";
            $bindParams[':search_name'] = "%{$search}%";
            $bindParams[':search_code'] = "%{$search}%";
            $bindParams[':search_email'] = "%{$search}%";
        }

        if ($deptId) {
            $where[] = "e.department_id = :department_id";
            $bindParams[':department_id'] = $deptId;
        }

        if ($posId) {
            $where[] = "e.position_id = :position_id";
            $bindParams[':position_id'] = $posId;
        }

        if ($status !== '') {
            $where[] = "e.status = :status";
            $bindParams[':status'] = $this->normalizeStatus($status);
        }

        if ($role !== '') {
            $where[] = "e.role = :role";
            $bindParams[':role'] = $this->normalizeRole($role);
        }

        if ($managerId) {
            $where[] = "e.manager_id = :manager_id";
            $bindParams[':manager_id'] = $managerId;
        }

        $whereSql = implode(' AND ', $where);

        $sql = "SELECT
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
                    m.full_name AS manager_name
                FROM employees e
                LEFT JOIN departments d ON e.department_id = d.id
                LEFT JOIN positions p ON p.id = e.position_id
                LEFT JOIN employees m ON m.id = e.manager_id
                WHERE {$whereSql}
                ORDER BY e.id DESC
                LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);

        foreach ($bindParams as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $countSql = "SELECT COUNT(*) FROM employees e WHERE {$whereSql}";
        $countStmt = $this->db->prepare($countSql);

        foreach ($bindParams as $key => $value) {
            $countStmt->bindValue($key, $value);
        }

        $countStmt->execute();
        $total = (int)$countStmt->fetchColumn();

        return [
            'items' => $items,
            'pagination' => [
                'total_items' => $total,
                'total_pages' => (int)ceil($total / $limit),
                'current_page' => $page,
                'limit' => $limit,
            ],
        ];
    }

    public function create($data) {
        $prepared = $this->prepareCreateData($data);

        $stmt = $this->db->prepare("
            INSERT INTO employees
            (
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
                avatar,
                total_leave_days,
                remaining_leave_days,
                status,
                hire_date,
                resigned_date
            )
            VALUES
            (
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
                :avatar,
                :total_leave_days,
                :remaining_leave_days,
                :status,
                :hire_date,
                :resigned_date
            )
        ");

        $stmt->execute([
            ':department_id' => $prepared['department_id'],
            ':position_id' => $prepared['position_id'],
            ':manager_id' => $prepared['manager_id'],
            ':employee_code' => $prepared['employee_code'],
            ':full_name' => $prepared['full_name'],
            ':email' => $prepared['email'],
            ':password' => $prepared['password'],
            ':role' => $prepared['role'],
            ':phone' => $prepared['phone'],
            ':gender' => $prepared['gender'],
            ':date_of_birth' => $prepared['date_of_birth'],
            ':address' => $prepared['address'],
            ':avatar' => $prepared['avatar'],
            ':total_leave_days' => $prepared['total_leave_days'],
            ':remaining_leave_days' => $prepared['remaining_leave_days'],
            ':status' => $prepared['status'],
            ':hire_date' => $prepared['hire_date'],
            ':resigned_date' => $prepared['resigned_date'],
        ]);

        return (int)$this->db->lastInsertId();
    }

    public function createPendingAccount(array $data, int $managerId): int {
        $role = $this->normalizeRole($data['role'] ?? 'employee');

        if (!in_array($role, ['employee', 'client'], true)) {
            throw new Exception('Manager chỉ được tạo tài khoản Employee hoặc Client.');
        }

        $data['role'] = $role;
        $data['status'] = 'inactive';
        $data['manager_id'] = $managerId;
        $data['employee_code'] = $data['employee_code'] ?? $this->generateEmployeeCode($role);

        return $this->create($data);
    }

    public function listPendingAccounts(array $filters = []): array {
        $search = trim((string)($filters['search'] ?? ''));
        $managerId = $this->normalizeNullableId($filters['manager_id'] ?? null);

        $where = [
            "e.deleted_at IS NULL",
            "e.status = 'inactive'",
            "e.role IN ('employee', 'client')",
        ];

        $params = [];

        if ($search !== '') {
            $where[] = "(e.full_name LIKE :search_name OR e.email LIKE :search_email OR e.employee_code LIKE :search_code)";
            $params[':search_name'] = "%{$search}%";
            $params[':search_email'] = "%{$search}%";
            $params[':search_code'] = "%{$search}%";
        }

        if ($managerId) {
            $where[] = "e.manager_id = :manager_id";
            $params[':manager_id'] = $managerId;
        }

        $sql = "SELECT
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
                    e.avatar,
                    e.status,
                    e.hire_date,
                    e.created_at,
                    e.updated_at,
                    d.name AS department_name,
                    p.name AS position_name,
                    m.full_name AS manager_name
                FROM employees e
                LEFT JOIN departments d ON d.id = e.department_id
                LEFT JOIN positions p ON p.id = e.position_id
                LEFT JOIN employees m ON m.id = e.manager_id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY e.created_at DESC, e.id DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function approveAccount(int $employeeId, ?int $adminId = null): bool {
        $stmt = $this->db->prepare("
            UPDATE employees
            SET status = 'active',
                updated_at = NOW()
            WHERE id = :id
              AND status = 'inactive'
              AND role IN ('employee', 'client')
              AND deleted_at IS NULL
        ");

        $stmt->execute([
            ':id' => $employeeId,
        ]);

        return $stmt->rowCount() > 0;
    }

    public function rejectAccount(int $employeeId, ?int $adminId = null): bool {
        $stmt = $this->db->prepare("
            UPDATE employees
            SET status = 'suspended',
                updated_at = NOW()
            WHERE id = :id
              AND status = 'inactive'
              AND role IN ('employee', 'client')
              AND deleted_at IS NULL
        ");

        $stmt->execute([
            ':id' => $employeeId,
        ]);

        return $stmt->rowCount() > 0;
    }

    public function update($id, $data) {
        if (empty($data)) {
            return false;
        }

        $allowedFields = [
            'department_id',
            'position_id',
            'manager_id',
            'employee_code',
            'full_name',
            'email',
            'password',
            'role',
            'phone',
            'gender',
            'date_of_birth',
            'address',
            'avatar',
            'total_leave_days',
            'remaining_leave_days',
            'status',
            'hire_date',
            'resigned_date',
        ];

        $fields = [];
        $params = [':id' => (int)$id];

        foreach ($data as $key => $value) {
            if (!in_array($key, $allowedFields, true)) {
                continue;
            }

            if ($key === 'password') {
                $value = $this->normalizePasswordHash((string)$value);
            }

            if ($key === 'role') {
                $value = $this->normalizeRole((string)$value);
            }

            if ($key === 'status') {
                $value = $this->normalizeStatus((string)$value);
            }

            if ($key === 'gender') {
                $value = $this->normalizeGender($value);
            }

            $fields[] = "{$key} = :{$key}";
            $params[":{$key}"] = $value;
        }

        if (empty($fields)) {
            return false;
        }

        $fields[] = "updated_at = NOW()";

        $sql = "UPDATE employees
                SET " . implode(', ', $fields) . "
                WHERE id = :id
                  AND deleted_at IS NULL";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute($params);
    }

    public function getAvatar($id) {
        $stmt = $this->db->prepare("
            SELECT avatar
            FROM employees
            WHERE id = :id
              AND deleted_at IS NULL
        ");

        $stmt->execute([
            ':id' => (int)$id,
        ]);

        return $stmt->fetchColumn();
    }

    public function updateAvatar($id, $filename) {
        $stmt = $this->db->prepare("
            UPDATE employees
            SET avatar = :avatar,
                updated_at = NOW()
            WHERE id = :id
              AND deleted_at IS NULL
        ");

        return $stmt->execute([
            ':avatar' => $filename,
            ':id' => (int)$id,
        ]);
    }

    public function softDelete($id) {
        $stmt = $this->db->prepare("
            UPDATE employees
            SET deleted_at = NOW(),
                status = 'inactive',
                updated_at = NOW()
            WHERE id = :id
              AND deleted_at IS NULL
        ");

        return $stmt->execute([
            ':id' => (int)$id,
        ]);
    }

    public function adjustLeaveBalance($employeeId, $adjustDays, $reason, $createdBy = null) {
        try {
            $this->db->beginTransaction();

            $stmtOld = $this->db->prepare("
                SELECT remaining_leave_days
                FROM employees
                WHERE id = :id
                  AND deleted_at IS NULL
                FOR UPDATE
            ");

            $stmtOld->execute([
                ':id' => (int)$employeeId,
            ]);

            $oldDays = $stmtOld->fetchColumn();

            if ($oldDays === false) {
                throw new Exception('Nhân viên không tồn tại.');
            }

            $oldDays = (float)$oldDays;
            $newDays = $oldDays + (float)$adjustDays;

            if ($newDays < 0) {
                throw new Exception('Số dư phép không đủ.');
            }

            $stmtUpdate = $this->db->prepare("
                UPDATE employees
                SET remaining_leave_days = :new_days,
                    updated_at = NOW()
                WHERE id = :id
                  AND deleted_at IS NULL
            ");

            $stmtUpdate->execute([
                ':new_days' => $newDays,
                ':id' => (int)$employeeId,
            ]);

            if ($this->tableExists('employee_leave_adjustments')) {
                $stmtLog = $this->db->prepare("
                    INSERT INTO employee_leave_adjustments
                    (
                        employee_id,
                        adjustment_days,
                        old_remaining_days,
                        new_remaining_days,
                        reason,
                        created_by
                    )
                    VALUES
                    (
                        :employee_id,
                        :adjustment_days,
                        :old_remaining_days,
                        :new_remaining_days,
                        :reason,
                        :created_by
                    )
                ");

                $stmtLog->execute([
                    ':employee_id' => (int)$employeeId,
                    ':adjustment_days' => (float)$adjustDays,
                    ':old_remaining_days' => $oldDays,
                    ':new_remaining_days' => $newDays,
                    ':reason' => $reason,
                    ':created_by' => $createdBy ?: 1,
                ]);
            }

            $this->db->commit();

            return true;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $e;
        }
    }

    public function listDocumentsByEmployee($employeeId): array {
        if (!$this->tableExists('employee_documents')) {
            return [];
        }

        $stmt = $this->db->prepare("
            SELECT *
            FROM employee_documents
            WHERE employee_id = :employee_id
              AND deleted_at IS NULL
            ORDER BY created_at DESC, id DESC
        ");

        $stmt->execute([
            ':employee_id' => (int)$employeeId,
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function createDocument(array $data): int {
        if (!$this->tableExists('employee_documents')) {
            throw new Exception('Bảng employee_documents chưa tồn tại.');
        }

        $stmt = $this->db->prepare("
            INSERT INTO employee_documents
            (
                employee_id,
                uploaded_by,
                document_type,
                title,
                original_name,
                stored_name,
                file_path,
                mime_type,
                file_size
            )
            VALUES
            (
                :employee_id,
                :uploaded_by,
                :document_type,
                :title,
                :original_name,
                :stored_name,
                :file_path,
                :mime_type,
                :file_size
            )
        ");

        $stmt->execute([
            ':employee_id' => (int)$data['employee_id'],
            ':uploaded_by' => $this->normalizeNullableId($data['uploaded_by'] ?? null),
            ':document_type' => $data['document_type'] ?? 'other',
            ':title' => $data['title'],
            ':original_name' => $data['original_name'],
            ':stored_name' => $data['stored_name'],
            ':file_path' => $data['file_path'],
            ':mime_type' => $data['mime_type'],
            ':file_size' => (int)$data['file_size'],
        ]);

        return (int)$this->db->lastInsertId();
    }

    public function findDocumentById($documentId) {
        if (!$this->tableExists('employee_documents')) {
            return null;
        }

        $stmt = $this->db->prepare("
            SELECT *
            FROM employee_documents
            WHERE id = :id
              AND deleted_at IS NULL
            LIMIT 1
        ");

        $stmt->execute([
            ':id' => (int)$documentId,
        ]);

        $document = $stmt->fetch(PDO::FETCH_ASSOC);

        return $document ?: null;
    }

    public function softDeleteDocument($documentId): bool {
        if (!$this->tableExists('employee_documents')) {
            return false;
        }

        $stmt = $this->db->prepare("
            UPDATE employee_documents
            SET deleted_at = NOW(),
                updated_at = NOW()
            WHERE id = :id
              AND deleted_at IS NULL
        ");

        $stmt->execute([
            ':id' => (int)$documentId,
        ]);

        return $stmt->rowCount() > 0;
    }
}