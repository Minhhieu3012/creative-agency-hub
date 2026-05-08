<?php
namespace App\Models\HRM;

use PDO;
use Exception;

class Position {
    private $db;

    public function __construct(PDO $dbConnection) {
        $this->db = $dbConnection;
    }

    public function getAllActive() {
        $stmt = $this->db->prepare("SELECT * FROM positions WHERE deleted_at IS NULL ORDER BY id DESC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($id) {
        $stmt = $this->db->prepare("SELECT * FROM positions WHERE id = :id AND deleted_at IS NULL");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create($name, $description) {
        $stmt = $this->db->prepare("INSERT INTO positions (name, description, status) VALUES (:name, :description, 'active')");
        return $stmt->execute([
            ':name' => $name,
            ':description' => $description
        ]);
    }

    // SOFT DELETE GUARD: Đếm số nhân viên đang giữ chức vụ này
    public function countActiveEmployees($positionId) {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as total 
            FROM employees 
            WHERE position_id = :pos_id 
            AND status != 'resigned' 
            AND deleted_at IS NULL
        ");
        $stmt->execute([':pos_id' => $positionId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int) $result['total'];
    }

    public function softDelete($id) {
        $position = $this->getById($id);
        if (!$position) return false;

        // Bóp méo tên để giải phóng UNIQUE constraint
        $newName = $position['name'] . '_deleted_' . time();

        $stmt = $this->db->prepare("
            UPDATE positions 
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