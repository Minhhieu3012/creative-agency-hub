<?php
namespace App\Models\Auth;

use Core\Database;
use PDO;

class User {
    protected $db;
    protected $table = 'employees';

    public function __construct() {
        $this->db = Database::getConnection();
    }

    /**
     * Tìm người dùng theo Email.
     * Chỉ lấy tài khoản đang active để tránh đăng nhập tài khoản bị khóa.
     */
    public function findByEmail($email) {
        $sql = "SELECT * FROM {$this->table}
                WHERE email = :email
                AND status = 'active'
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['email' => $email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Tìm người dùng theo ID.
     * Dùng cho API /api/auth/me và phần header/topbar.
     */
    public function findById($id) {
        $sql = "SELECT
                    id,
                    employee_code,
                    full_name,
                    email,
                    role,
                    avatar,
                    phone,
                    status
                FROM {$this->table}
                WHERE id = :id
                AND deleted_at IS NULL
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Tạo tài khoản mới.
     */
    public function create($data) {
        $sql = "INSERT INTO {$this->table}
                (department_id, position_id, employee_code, full_name, email, password, role, status, hire_date)
                VALUES
                (:dept, :pos, :code, :name, :email, :pass, :role, 'active', CURDATE())";

        $stmt = $this->db->prepare($sql);
        $hashedPassword = password_hash($data['password'], PASSWORD_BCRYPT);
        $employeeCode = $data['employee_code'] ?? ('CL' . time());

        $stmt->execute([
            ':dept'  => $data['department_id'] ?? 1,
            ':pos'   => $data['position_id'] ?? 1,
            ':code'  => $employeeCode,
            ':name'  => $data['full_name'],
            ':email' => $data['email'],
            ':pass'  => $hashedPassword,
            ':role'  => $data['role'] ?? 'client'
        ]);

        return $this->db->lastInsertId();
    }
}