<?php
namespace App\Models\Auth;

use Core\Database;
use PDO;

class User {
    protected $db;
    protected $table = 'employees'; // Đã đảm bảo trỏ chính xác vào bảng employees

    public function __construct() {
        // SỬA LỖI TẠI ĐÂY: Dùng phương thức tĩnh trực tiếp thay vì getInstance()
        $this->db = Database::getConnection();
    }

    /**
     * Tìm người dùng theo Email
     * Chỉ lấy user có status 'active' để đảm bảo an toàn đăng nhập
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
     * Tìm người dùng theo ID
     * Trả về các thông tin cơ bản cần thiết cho Session/Token
     */
    public function findById($id) {
        $sql = "SELECT id, full_name, email, role, status 
                FROM {$this->table} 
                WHERE id = :id 
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Tạo tài khoản mới
     * Logic điền đầy đủ các trường phòng ban, mã nhân viên và ngày vào làm
     */
    public function create($data) {
        $sql = "INSERT INTO {$this->table} 
                (department_id, position_id, employee_code, full_name, email, password, role, status, hire_date)
                VALUES 
                (:dept, :pos, :code, :name, :email, :pass, :role, 'active', CURDATE())";

        $stmt = $this->db->prepare($sql);

        // Mã hóa mật khẩu trước khi lưu
        $hashedPassword = password_hash($data['password'], PASSWORD_BCRYPT);

        // Sinh mã nhân viên tự động nếu là Client đăng ký, hoặc dùng mã có sẵn
        $employeeCode = $data['employee_code'] ?? ('CL' . time());

        $stmt->execute([
            ':dept'  => $data['department_id'] ?? 1, // Mặc định phòng ban đầu tiên
            ':pos'   => $data['position_id']   ?? 1, // Mặc định vị trí đầu tiên
            ':code'  => $employeeCode,
            ':name'  => $data['full_name'],
            ':email' => $data['email'],
            ':pass'  => $hashedPassword,
            ':role'  => $data['role'] ?? 'client'
        ]);

        return $this->db->lastInsertId();
    }
}