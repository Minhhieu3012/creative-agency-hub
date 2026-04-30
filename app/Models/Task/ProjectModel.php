<?php
namespace App\Models\Task;

use Core\Database;
use PDO;

class ProjectModel {
    private $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }
    public function getByManager($managerId) {
        $stmt = $this->db->prepare("SELECT * FROM projects WHERE manager_id = ?");
        $stmt->execute([$managerId]);
        return $stmt->fetchAll();
    }

    public function getAll() {
        $stmt = $this->db->query("
            SELECT p.*, e.full_name as manager_name
            FROM projects p
            LEFT JOIN employees e ON p.manager_id = e.id
            ORDER BY p.created_at DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById($id) {
        $stmt = $this->db->prepare("
            SELECT * FROM projects WHERE id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create($data) {
        $stmt = $this->db->prepare("
            INSERT INTO projects (name, description, manager_id, status)
            VALUES (?, ?, ?, ?)
        ");

        $stmt->execute([
            $data['name'],
            $data['description'],
            $data['manager_id'],
            $data['status']
        ]);

        return $this->db->lastInsertId();
    }

    public function update($id, $data) {
        $stmt = $this->db->prepare("
            UPDATE projects
            SET name = ?, description = ?, manager_id = ?, status = ?
            WHERE id = ?
        ");

        return $stmt->execute([
            $data['name'],
            $data['description'],
            $data['manager_id'],
            $data['status'],
            $id
        ]);
    }

    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM projects WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function existsManager($id) {
        $stmt = $this->db->prepare("SELECT id FROM employees WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ? true : false;
    }
}