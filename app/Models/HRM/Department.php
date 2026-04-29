<?php
namespace App\Models\HRM;

use PDO;
use Exception;

class Department {
    private $db;

    public function __construct(PDO $dbConnection) {
        $this->db = $dbConnection;
    }

    // 1. Lấy danh sách các phòng ban đang hoạt động
    public function getAllActive() {
        $stmt = $this->db->prepare("SELECT * FROM departments WHERE deleted_at IS NULL ORDER BY id DESC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // 2. Lấy thông tin 1 phòng ban theo ID
    public function getById($id) {
        $stmt = $this->db->prepare("SELECT * FROM departments WHERE id = :id AND deleted_at IS NULL");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // 3. Tạo mới phòng ban
    public function create($name, $description) {
        $stmt = $this->db->prepare("INSERT INTO departments (name, description, status) VALUES (:name, :description, 'active')");
        return $stmt->execute([
            ':name' => $name,
            ':description' => $description
        ]);
    }

    // 4. Cập nhật thông tin phòng ban
    public function update($id, $name, $description, $status) {
        $stmt = $this->db->prepare("UPDATE departments SET name = :name, description = :description, status = :status WHERE id = :id AND deleted_at IS NULL");
        return $stmt->execute([
            ':id' => $id,
            ':name' => $name,
            ':description' => $description,
            ':status' => $status
        ]);
    }

    // 5. [SOFT DELETE GUARD] Đếm số lượng nhân viên đang active thuộc phòng ban này
    public function countActiveEmployees($departmentId) {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as total 
            FROM employees 
            WHERE department_id = :dept_id 
            AND status != 'resigned' 
            AND deleted_at IS NULL
        ");
        $stmt->execute([':dept_id' => $departmentId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int) $result['total'];
    }

    // 6. Thực hiện Xóa mềm (Soft Delete)
    public function softDelete($id) {
        // Lấy tên hiện tại của phòng ban
        $dept = $this->getById($id);
        if (!$dept) return false;

        // Bóp méo tên cũ để giải phóng UNIQUE constraint (Ví dụ: "IT" thành "IT_deleted_1714000000")
        $newName = $dept['name'] . '_deleted_' . time();

        $stmt = $this->db->prepare("
            UPDATE departments 
            SET deleted_at = CURRENT_TIMESTAMP, 
                name = :new_name, 
                status = 'inactive' 
            WHERE id = :id
        ");
        
        return $stmt->execute([
            ':new_name' => $newName,
            ':id' => $id
        ]);
    }
}