<?php
namespace App\Models\Auth;

use Core\Database;
use PDO;
use PDOException;

class User {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    public function findByEmail(string $email): ?array {
        $stmt = $this->db->prepare("
            SELECT
                e.id,
                e.department_id,
                e.position_id,
                e.manager_id,
                e.employee_code,
                e.full_name,
                e.email,
                e.password,
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
                e.deleted_at,
                d.name AS department_name,
                p.name AS position_name,
                m.full_name AS manager_name
            FROM employees e
            LEFT JOIN departments d ON d.id = e.department_id
            LEFT JOIN positions p ON p.id = e.position_id
            LEFT JOIN employees m ON m.id = e.manager_id
            WHERE e.email = :email
            AND e.deleted_at IS NULL
            LIMIT 1
        ");

        $stmt->execute([
            ':email' => trim($email)
        ]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        return $user ?: null;
    }

    public function findById(int $id): ?array {
        $stmt = $this->db->prepare("
            SELECT
                e.id,
                e.department_id,
                e.position_id,
                e.manager_id,
                e.employee_code,
                e.full_name,
                e.email,
                e.password,
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
                e.deleted_at,
                d.name AS department_name,
                p.name AS position_name,
                m.full_name AS manager_name
            FROM employees e
            LEFT JOIN departments d ON d.id = e.department_id
            LEFT JOIN positions p ON p.id = e.position_id
            LEFT JOIN employees m ON m.id = e.manager_id
            WHERE e.id = :id
            AND e.deleted_at IS NULL
            LIMIT 1
        ");

        $stmt->execute([
            ':id' => $id
        ]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        return $user ?: null;
    }

    public function emailExists(string $email, ?int $excludeId = null): bool {
        $sql = "
            SELECT COUNT(*)
            FROM employees
            WHERE email = :email
            AND deleted_at IS NULL
        ";

        $params = [
            ':email' => trim($email)
        ];

        if ($excludeId !== null) {
            $sql .= " AND id != :exclude_id";
            $params[':exclude_id'] = $excludeId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn() > 0;
    }

    public function employeeCodeExists(string $employeeCode, ?int $excludeId = null): bool {
        $sql = "
            SELECT COUNT(*)
            FROM employees
            WHERE employee_code = :employee_code
            AND deleted_at IS NULL
        ";

        $params = [
            ':employee_code' => trim($employeeCode)
        ];

        if ($excludeId !== null) {
            $sql .= " AND id != :exclude_id";
            $params[':exclude_id'] = $excludeId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn() > 0;
    }

    public function create(array $data): int {
        $departmentId = (int) ($data['department_id'] ?? 0);
        $positionId = (int) ($data['position_id'] ?? 0);
        $employeeCode = trim((string) ($data['employee_code'] ?? ''));
        $fullName = trim((string) ($data['full_name'] ?? ''));
        $email = trim((string) ($data['email'] ?? ''));
        $password = (string) ($data['password'] ?? '123456');
        $role = $this->normalizeRole($data['role'] ?? 'employee');
        $hireDate = $data['hire_date'] ?? date('Y-m-d');

        if ($departmentId <= 0 || $positionId <= 0 || $employeeCode === '' || $fullName === '' || $email === '') {
            throw new \InvalidArgumentException('Thiếu dữ liệu bắt buộc để tạo tài khoản.');
        }

        if ($this->emailExists($email)) {
            throw new \RuntimeException('Email đã tồn tại.');
        }

        if ($this->employeeCodeExists($employeeCode)) {
            throw new \RuntimeException('Mã nhân viên đã tồn tại.');
        }

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
                avatar,
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
                :avatar,
                :total_leave_days,
                :remaining_leave_days,
                :status,
                :hire_date,
                :resigned_date
            )
        ");

        $stmt->execute([
            ':department_id' => $departmentId,
            ':position_id' => $positionId,
            ':manager_id' => $this->nullableInt($data['manager_id'] ?? null),
            ':employee_code' => $employeeCode,
            ':full_name' => $fullName,
            ':email' => $email,
            ':password' => password_hash($password, PASSWORD_BCRYPT),
            ':role' => $role,
            ':phone' => trim((string) ($data['phone'] ?? '')),
            ':gender' => $this->normalizeGender($data['gender'] ?? 'other'),
            ':date_of_birth' => !empty($data['date_of_birth']) ? $data['date_of_birth'] : null,
            ':address' => trim((string) ($data['address'] ?? '')),
            ':avatar' => $data['avatar'] ?? null,
            ':total_leave_days' => (int) ($data['total_leave_days'] ?? 12),
            ':remaining_leave_days' => (float) ($data['remaining_leave_days'] ?? 12),
            ':status' => $this->normalizeStatus($data['status'] ?? 'active'),
            ':hire_date' => $hireDate,
            ':resigned_date' => !empty($data['resigned_date']) ? $data['resigned_date'] : null
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function updatePassword(int $id, string $password): bool {
        $stmt = $this->db->prepare("
            UPDATE employees
            SET password = :password
            WHERE id = :id
            AND deleted_at IS NULL
        ");

        return $stmt->execute([
            ':id' => $id,
            ':password' => password_hash($password, PASSWORD_BCRYPT)
        ]);
    }

    private function nullableInt($value): ?int {
        if ($value === null || $value === '' || $value === false) {
            return null;
        }

        $number = (int) $value;

        return $number > 0 ? $number : null;
    }

    private function normalizeRole(string $role): string {
        $role = strtolower(trim($role));
        $allowed = ['admin', 'manager', 'employee', 'client'];

        return in_array($role, $allowed, true) ? $role : 'employee';
    }

    private function normalizeStatus(string $status): string {
        $status = strtolower(trim($status));
        $allowed = ['active', 'inactive', 'resigned', 'suspended'];

        return in_array($status, $allowed, true) ? $status : 'active';
    }

    private function normalizeGender(?string $gender): ?string {
        if ($gender === null || $gender === '') {
            return 'other';
        }

        $gender = strtolower(trim($gender));
        $allowed = ['male', 'female', 'other'];

        return in_array($gender, $allowed, true) ? $gender : 'other';
    }
}