<?php
namespace App\Services\Task;

use App\Models\Task\ProjectModel;
use Exception;

class ProjectService {
    private $model;
    private $authUser;

    public function __construct($authUser) {
        $this->model = new ProjectModel();
        $this->authUser = $authUser;
    }

    private function validate($data, $isUpdate = false) {

        if (empty($data['name'])) {
            throw new Exception("Tên project không được để trống");
        }

        if (strlen($data['name']) > 255) {
            throw new Exception("Tên project tối đa 255 ký tự");
        }

        $validStatus = ['Active', 'Completed', 'Archived'];
        if (!empty($data['status']) && !in_array($data['status'], $validStatus)) {
            throw new Exception("Status không hợp lệ");
        }

        if (!empty($data['manager_id'])) {
            if (!is_numeric($data['manager_id'])) {
                throw new Exception("manager_id phải là số");
            }

            if (!$this->model->existsManager($data['manager_id'])) {
                throw new Exception("Manager không tồn tại");
            }
        }

        return true;
    }

    // =========================
    // GET ALL
    // =========================
    public function getAll() {
        // ADMIN → xem tất cả
        if ($this->authUser['role'] === 'admin') {
            return $this->model->getAll();
        }

        // MANAGER → chỉ xem project của mình
        if ($this->authUser['role'] === 'manager') {
            return $this->model->getByManager($this->authUser['id']);
        }

        // EMPLOYEE → không có quyền
        throw new Exception("Bạn không có quyền xem project");
    }

    // =========================
    // GET BY ID
    // =========================
    public function getById($id) {
        $project = $this->model->findById($id);

        if (!$project) {
            throw new Exception("Project không tồn tại");
        }

        // MANAGER chỉ được xem project của mình
        if ($this->authUser['role'] === 'manager' &&
            $project['manager_id'] != $this->authUser['id']) {
            throw new Exception("Bạn không có quyền truy cập project này");
        }

        // EMPLOYEE cấm
        if ($this->authUser['role'] === 'employee') {
            throw new Exception("Bạn không có quyền");
        }

        return $project;
    }

    // =========================
    // CREATE
    // =========================
    public function create($data) {

        if (!in_array($this->authUser['role'], ['admin', 'manager'])) {
            throw new Exception("Bạn không có quyền tạo project");
        }

        $this->validate($data);

        // Nếu là manager → ép manager_id = chính nó
        if ($this->authUser['role'] === 'manager') {
            $data['manager_id'] = $this->authUser['id'];
        }

        $data['status'] = $data['status'] ?? 'Active';

        return $this->model->create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'manager_id' => $data['manager_id'] ?? null,
            'status' => $data['status']
        ]);
    }

    // =========================
    // UPDATE
    // =========================
    public function update($id, $data) {

        $project = $this->getById($id); // check quyền luôn

        if (!in_array($this->authUser['role'], ['admin', 'manager'])) {
            throw new Exception("Bạn không có quyền cập nhật");
        }

        // MANAGER chỉ sửa project của mình
        if ($this->authUser['role'] === 'manager' &&
            $project['manager_id'] != $this->authUser['id']) {
            throw new Exception("Bạn không có quyền sửa project này");
        }

        $this->validate($data, true);

        return $this->model->update($id, [
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'manager_id' => $project['manager_id'], // KHÔNG cho manager đổi
            'status' => $data['status'] ?? $project['status']
        ]);
    }

    // =========================
    // DELETE
    // =========================
    public function delete($id) {

        $project = $this->getById($id);

        if (!in_array($this->authUser['role'], ['admin', 'manager'])) {
            throw new Exception("Bạn không có quyền xoá");
        }

        if ($this->authUser['role'] === 'manager' &&
            $project['manager_id'] != $this->authUser['id']) {
            throw new Exception("Bạn không có quyền xoá project này");
        }

        return $this->model->delete($id);
    }
}