<?php
namespace App\Models\HRM;

use PDO;

class Employee {
    private $db;

    public function __construct(PDO $dbConnection) {
        $this->db = $dbConnection;
    }

    // Lấy danh sách kèm Phân trang, Tìm kiếm và Lọc
    public function getList($params) {
        $limit = isset($params['limit']) ? (int)$params['limit'] : 10;
        $page = isset($params['page']) ? (int)$params['page'] : 1;
        $offset = ($page - 1) * $limit;

        $search = $params['search'] ?? null;
        $deptId = $params['department_id'] ?? null;
        $posId = $params['position_id'] ?? null;
        $status = $params['status'] ?? null;

        $sql = "SELECT e.*, d.name as department_name, p.name as position_name 
                FROM employees e
                JOIN departments d ON e.department_id = d.id
                JOIN positions p ON e.position_id = p.id
                WHERE e.deleted_at IS NULL";
        
        $bindParams = [];

        if ($search) {
            $sql .= " AND (e.full_name LIKE :search OR e.employee_code LIKE :search2)";
            $bindParams[':search'] = "%$search%";
            $bindParams[':search2'] = "%$search%";
        }

        if ($deptId) {
            $sql .= " AND e.department_id = :dept_id";
            $bindParams[':dept_id'] = $deptId;
        }

        if ($status) {
            $sql .= " AND e.status = :status";
            $bindParams[':status'] = $status;
        }

        $sql .= " ORDER BY e.id DESC LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        foreach ($bindParams as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        
        $stmt->execute();
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Đếm tổng số bản ghi để làm phân trang ở Frontend
        $countSql = "SELECT COUNT(*) FROM employees WHERE deleted_at IS NULL";
        $total = $this->db->query($countSql)->fetchColumn();

        return [
            'items' => $items,
            'pagination' => [
                'total_items' => (int)$total,
                'total_pages' => ceil($total / $limit),
                'current_page' => $page,
                'limit' => $limit
            ]
        ];
    }
}