<?php
namespace App\Models\Auth;

use Core\Database;
use PDO;
use RuntimeException;

class User {
    protected PDO $db;
    protected string $table = 'employees';

    public function __construct() {
        $this->db = Database::getConnection();
    }

    public function findByEmail($email) {
        $sql = "SELECT *
                FROM {$this->table}
                WHERE email = :email
                  AND status = 'active'
                  AND deleted_at IS NULL
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'email' => trim((string)$email),
        ]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function findById($id) {
        $sql = "SELECT
                    id,
                    department_id,
                    position_id,
                    manager_id,
                    employee_code,
                    full_name,
                    email,
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
                    resigned_date,
                    created_at,
                    updated_at
                FROM {$this->table}
                WHERE id = :id
                  AND status = 'active'
                  AND deleted_at IS NULL
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'id' => (int)$id,
        ]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function getFirstActiveId(string $table): ?int {
        $allowedTables = ['departments', 'positions'];

        if (!in_array($table, $allowedTables, true)) {
            return null;
        }

        $sql = "SELECT id
                FROM {$table}
                WHERE status = 'active'
                  AND deleted_at IS NULL
                ORDER BY id ASC
                LIMIT 1";

        $stmt = $this->db->query($sql);
        $id = $stmt->fetchColumn();

        return $id ? (int)$id : null;
    }

    private function generateEmployeeCode(string $role): string {
        $prefixes = [
            'admin' => 'ADM',
            'manager' => 'MGR',
            'employee' => 'EMP',
            'client' => 'CL',
        ];

        $prefix = $prefixes[$role] ?? 'USR';

        return $prefix . date('YmdHis') . random_int(10, 99);
    }

    public function create($data) {
        $role = strtolower((string)($data['role'] ?? 'client'));

        $departmentId = !empty($data['department_id'])
            ? (int)$data['department_id']
            : $this->getFirstActiveId('departments');

        $positionId = !empty($data['position_id'])
            ? (int)$data['position_id']
            : $this->getFirstActiveId('positions');

        if (!$departmentId || !$positionId) {
            throw new RuntimeException('Thiếu department hoặc position mặc định để tạo tài khoản.');
        }

        $employeeCode = trim((string)($data['employee_code'] ?? ''));

        if ($employeeCode === '') {
            $employeeCode = $this->generateEmployeeCode($role);
        }

        $sql = "INSERT INTO {$this->table}
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
                    status,
                    hire_date
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
                    'active',
                    :hire_date
                )";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':department_id' => $departmentId,
            ':position_id' => $positionId,
            ':manager_id' => !empty($data['manager_id']) ? (int)$data['manager_id'] : null,
            ':employee_code' => $employeeCode,
            ':full_name' => trim((string)$data['full_name']),
            ':email' => trim((string)$data['email']),
            ':password' => password_hash((string)$data['password'], PASSWORD_BCRYPT),
            ':role' => $role,
            ':phone' => $data['phone'] ?? null,
            ':gender' => $data['gender'] ?? null,
            ':date_of_birth' => $data['date_of_birth'] ?? null,
            ':address' => $data['address'] ?? null,
            ':hire_date' => $data['hire_date'] ?? date('Y-m-d'),
        ]);

        return $this->db->lastInsertId();
    }
}