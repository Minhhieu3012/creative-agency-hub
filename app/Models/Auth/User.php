<?php
namespace App\Models\Auth;

use Core\Database;
use PDO;

class User {
    private $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    // =========================
    // FIND BY EMAIL
    // =========================
    public function findByEmail($email) {
        $sql = "SELECT * FROM employees 
                WHERE email = :email 
                AND status = 'active' 
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['email' => $email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // =========================
    // FIND BY ID
    // =========================
    public function findById($id) {
        $sql = "SELECT id, full_name, email, role 
                FROM employees 
                WHERE id = :id 
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // =========================
    // CREATE (REGISTER CLIENT)
    // =========================
    public function create($data) {

        $sql = "INSERT INTO employees 
            (department_id, position_id, employee_code, full_name, email, password, role, status, hire_date)
            VALUES 
            (:department_id, :position_id, :employee_code, :full_name, :email, :password, :role, 'active', CURDATE())";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'department_id' => 1, // default tạm
            'position_id'   => 1, // default tạm
            'employee_code' => 'CL' . time(), // auto code
            'full_name'     => $data['full_name'],
            'email'         => $data['email'],
            'password'      => password_hash($data['password'], PASSWORD_BCRYPT),
            'role'          => $data['role'] ?? 'client'
        ]);

        return $this->db->lastInsertId();
    }
}