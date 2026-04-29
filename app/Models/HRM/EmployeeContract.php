<?php
namespace App\Models\HRM;

use PDO;

class EmployeeContract {
    private $db;

    public function __construct(PDO $dbConnection) {
        $this->db = $dbConnection;
    }

    // Lấy danh sách hợp đồng kèm thông tin nhân viên và trạng thái động (Dynamic Status)
    public function getAllWithDynamicStatus() {
        $sql = "
            SELECT 
                c.id, c.contract_code, c.contract_type, c.start_date, c.end_date, c.salary, c.status AS original_status, c.note,
                e.full_name, e.employee_code,
                CASE
                    WHEN c.status = 'terminated' THEN 'terminated'
                    WHEN c.end_date IS NOT NULL AND c.end_date < CURRENT_DATE THEN 'expired'
                    WHEN c.end_date IS NOT NULL AND DATEDIFF(c.end_date, CURRENT_DATE) <= 30 THEN 'expiring_soon'
                    ELSE 'active'
                END as dynamic_status
            FROM employee_contracts c
            JOIN employees e ON c.employee_id = e.id
            WHERE c.deleted_at IS NULL
            ORDER BY c.end_date ASC
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Xóa mềm hợp đồng không làm Cascade (Bảo toàn lịch sử theo đúng Acceptance Criteria)
    public function softDelete($id) {
        // Đổi mã hợp đồng để giải phóng UNIQUE
        $stmt = $this->db->prepare("SELECT contract_code FROM employee_contracts WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $contract = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$contract) return false;

        $newCode = $contract['contract_code'] . '_del_' . time();

        $deleteStmt = $this->db->prepare("
            UPDATE employee_contracts 
            SET deleted_at = CURRENT_TIMESTAMP, contract_code = :new_code, status = 'terminated' 
            WHERE id = :id
        ");
        return $deleteStmt->execute([
            ':new_code' => $newCode,
            ':id' => $id
        ]);
    }
}