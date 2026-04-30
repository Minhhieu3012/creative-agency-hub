<?php
namespace App\Models;

use Core\Database;
use PDO;

class User {
    private $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    // Tìm người dùng theo Email (Dùng cho Login)
    public function findByEmail($email) {
        $sql = "SELECT * FROM employees WHERE email = :email AND deleted_at IS NULL LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['email' => $email]);
        return $stmt->fetch();
    }

    // Tìm người dùng theo ID (Dùng cho GET /api/auth/me)
    public function findById($id) {
        $sql = "SELECT id, full_name, email, role, department_id, position_id, employee_code, hire_date 
                FROM employees WHERE id = :id AND deleted_at IS NULL LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    // Đăng ký nhân viên mới
    public function create($data) {
        $sql = "INSERT INTO employees (full_name, email, password, role, department_id, position_id, employee_code, hire_date) 
                VALUES (:full_name, :email, :password, :role, :department_id, :position_id, :employee_code, :hire_date)";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'full_name'     => $data['full_name'],
            'email'         => $data['email'],
            'password'      => password_hash($data['password'], PASSWORD_BCRYPT),
            'role'          => $data['role'] ?? 'employee',
            'department_id' => $data['department_id'],
            'position_id'   => $data['position_id'],
            'employee_code' => $data['employee_code'],
            'hire_date'     => date('Y-m-d')
        ]);

        return $this->db->lastInsertId();
    }
}