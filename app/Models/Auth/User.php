<?php
namespace App\Models\Auth;

use Core\Database;
use PDO;

class User {
    private $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    // Tìm người dùng theo Email - Sửa từ 'employees' thành 'users'
    public function findByEmail($email) {
        // Bảng 'users' bạn tạo ở image_e53b18.png không có deleted_at
        $sql = "SELECT * FROM users WHERE email = :email AND status = 'active' LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['email' => $email]);
        return $stmt->fetch();
    }

    // Tìm người dùng theo ID
    public function findById($id) {
        $sql = "SELECT id, full_name, email, role FROM users WHERE id = :id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    // Đăng ký (Nếu cần dùng)
    public function create($data) {
        $sql = "INSERT INTO users (full_name, email, password, role, status) 
                VALUES (:full_name, :email, :password, :role, :status)";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'full_name' => $data['full_name'],
            'email'     => $data['email'],
            'password'  => password_hash($data['password'], PASSWORD_BCRYPT),
            'role'      => $data['role'] ?? 'client',
            'status'    => 'active'
        ]);

        return $this->db->lastInsertId();
    }
}