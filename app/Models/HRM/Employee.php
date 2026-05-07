<?php
namespace App\Models\HRM;

use PDO;
use Core\Database;
use Exception;

class Employee {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    public function findByEmail($email) {
        $stmt = $this->db->prepare("SELECT * FROM employees WHERE email = ? AND deleted_at IS NULL LIMIT 1");
        $stmt->execute([$email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function findById($id) {
        $stmt = $this->db->prepare("SELECT * FROM employees WHERE id = :id AND deleted_at IS NULL LIMIT 1");
        $stmt->execute([':id' => $id]);
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
        $stmt->execute([':id' => $id]);
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
        $stmt->execute([':email' => $email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getList($params) {
        $limit = isset($params['limit']) ? max(1, (int)$params['limit']) : 10;
        $page = isset($params['page']) ? max(1, (int)$params['page']) : 1;
        $offset = ($page - 1) * $limit;

        $search = $params['search'] ?? null;
        $deptId = $params['department_id'] ?? null;
        $posId = $params['position_id'] ?? null;
        $status = $params['status'] ?? null;

        $where = ["e.deleted_at IS NULL"];
        $bindParams = [];

        if ($search) {
            $where[] = "(e.full_name LIKE :search OR e.employee_code LIKE :search2 OR e.email LIKE :search3)";
            $bindParams[':search'] = "%{$search}%";
            $bindParams[':search2'] = "%{$search}%";
            $bindParams[':search3'] = "%{$search}%";
        }

        if ($deptId) {
            $where[] = "e.department_id = :dept_id";
            $bindParams[':dept_id'] = $deptId;
        }

        if ($posId) {
            $where[] = "e.position_id = :pos_id";
            $bindParams[':pos_id'] = $posId;
        }

        if ($status) {
            $where[] = "e.status = :status";
            $bindParams[':status'] = $status;
        }

        $whereSql = implode(' AND ', $where);

        $sql = "SELECT e.*, d.name AS department_name, p.name AS position_name
                FROM employees e
                LEFT JOIN departments d ON e.department_id = d.id
                LEFT JOIN positions p ON e.position_id = p.id
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

        $countSql = "SELECT COUNT(*)
                     FROM employees e
                     WHERE {$whereSql}";

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
                'limit' => $limit
            ]
        ];
    }

    public function update($id, $data) {
        if (empty($data)) {
            return false;
        }

        $fields = [];
        $params = [':id' => $id];

        foreach ($data as $key => $value) {
            $fields[] = "{$key} = :{$key}";
            $params[":{$key}"] = $value;
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
        $stmt = $this->db->prepare("SELECT avatar FROM employees WHERE id = :id AND deleted_at IS NULL");
        $stmt->execute([':id' => $id]);
        return $stmt->fetchColumn();
    }

    public function updateAvatar($id, $filename) {
        $stmt = $this->db->prepare("UPDATE employees
                                    SET avatar = :avatar,
                                        updated_at = NOW()
                                    WHERE id = :id
                                      AND deleted_at IS NULL");

        return $stmt->execute([
            ':avatar' => $filename,
            ':id' => $id
        ]);
    }

    public function softDelete($id) {
        $stmt = $this->db->prepare("UPDATE employees
                                    SET deleted_at = NOW(),
                                        status = 'inactive',
                                        updated_at = NOW()
                                    WHERE id = :id
                                      AND deleted_at IS NULL");

        return $stmt->execute([':id' => $id]);
    }

    public function adjustLeaveBalance($employeeId, $adjustDays, $reason, $createdBy = null) {
        try {
            $this->db->beginTransaction();

            $sqlAdjust = "UPDATE employees
                          SET remaining_leave_days = remaining_leave_days + :adjust,
                              updated_at = NOW()
                          WHERE id = :id
                            AND deleted_at IS NULL
                            AND (remaining_leave_days + :adjust2) >= 0
                            AND (remaining_leave_days + :adjust3) <= total_leave_days";

            $stmtAdjust = $this->db->prepare($sqlAdjust);
            $stmtAdjust->execute([
                ':id' => $employeeId,
                ':adjust' => $adjustDays,
                ':adjust2' => $adjustDays,
                ':adjust3' => $adjustDays
            ]);

            if ($stmtAdjust->rowCount() === 0) {
                throw new Exception("Số dư phép không hợp lệ hoặc nhân viên không tồn tại.");
            }

            $stmtNew = $this->db->prepare("SELECT remaining_leave_days FROM employees WHERE id = :id");
            $stmtNew->execute([':id' => $employeeId]);
            $newDays = (float)$stmtNew->fetchColumn();

            $sqlLog = "INSERT INTO employee_leave_adjustments
                       (employee_id, adjustment_days, old_remaining_days, new_remaining_days, reason, created_by)
                       VALUES (:id, :adjust, :old, :new, :reason, :created_by)";

            $stmtLog = $this->db->prepare($sqlLog);
            $stmtLog->execute([
                ':id' => $employeeId,
                ':adjust' => $adjustDays,
                ':old' => $newDays - $adjustDays,
                ':new' => $newDays,
                ':reason' => $reason,
                ':created_by' => $createdBy
            ]);

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $e;
        }
    }

    public function create($data) {
        $password = (string)($data['password'] ?? '123456');
        $passwordInfo = password_get_info($password);
        $passwordHash = !empty($passwordInfo['algo']) ? $password : password_hash($password, PASSWORD_BCRYPT);

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
            ':department_id' => $data['department_id'],
            ':position_id' => $data['position_id'],
            ':manager_id' => $data['manager_id'] ?? null,
            ':employee_code' => $data['employee_code'],
            ':full_name' => $data['full_name'],
            ':email' => $data['email'],
            ':password' => $passwordHash,
            ':role' => $data['role'] ?? 'employee',
            ':phone' => $data['phone'] ?? null,
            ':gender' => $data['gender'] ?? null,
            ':date_of_birth' => $data['date_of_birth'] ?? null,
            ':address' => $data['address'] ?? null,
            ':hire_date' => $data['hire_date'],
            ':status' => $data['status'] ?? 'active'
        ]);

        return $this->db->lastInsertId();
    }

    public function createDocument(array $data) {
        $sql = "INSERT INTO employee_documents (
                    employee_id,
                    uploaded_by,
                    document_type,
                    title,
                    original_name,
                    stored_name,
                    file_path,
                    mime_type,
                    file_size
                ) VALUES (
                    :employee_id,
                    :uploaded_by,
                    :document_type,
                    :title,
                    :original_name,
                    :stored_name,
                    :file_path,
                    :mime_type,
                    :file_size
                )";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':employee_id' => $data['employee_id'],
            ':uploaded_by' => $data['uploaded_by'] ?? null,
            ':document_type' => $data['document_type'] ?? 'other',
            ':title' => $data['title'],
            ':original_name' => $data['original_name'],
            ':stored_name' => $data['stored_name'],
            ':file_path' => $data['file_path'],
            ':mime_type' => $data['mime_type'],
            ':file_size' => $data['file_size']
        ]);

        return $this->db->lastInsertId();
    }

    public function listDocumentsByEmployee($employeeId) {
        $stmt = $this->db->prepare("SELECT
                                        id,
                                        employee_id,
                                        uploaded_by,
                                        document_type,
                                        title,
                                        original_name,
                                        stored_name,
                                        file_path,
                                        mime_type,
                                        file_size,
                                        created_at,
                                        updated_at
                                    FROM employee_documents
                                    WHERE employee_id = :employee_id
                                      AND deleted_at IS NULL
                                    ORDER BY created_at DESC, id DESC");

        $stmt->execute([':employee_id' => $employeeId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findDocumentById($documentId) {
        $stmt = $this->db->prepare("SELECT
                                        id,
                                        employee_id,
                                        uploaded_by,
                                        document_type,
                                        title,
                                        original_name,
                                        stored_name,
                                        file_path,
                                        mime_type,
                                        file_size,
                                        created_at,
                                        updated_at,
                                        deleted_at
                                    FROM employee_documents
                                    WHERE id = :id
                                      AND deleted_at IS NULL
                                    LIMIT 1");

        $stmt->execute([':id' => $documentId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function softDeleteDocument($documentId) {
        $stmt = $this->db->prepare("UPDATE employee_documents
                                    SET deleted_at = NOW(),
                                        updated_at = NOW()
                                    WHERE id = :id
                                      AND deleted_at IS NULL");

        return $stmt->execute([':id' => $documentId]);
    }
}