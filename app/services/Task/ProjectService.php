<?php
namespace App\Services\Task;

use App\Models\Task\ProjectModel;
use Exception;

class ProjectService {
    private $model;
    private $authUser;

    public function __construct($authUser) {
        $this->model = new ProjectModel();
        $this->authUser = is_array($authUser) ? $authUser : [];
    }

    private function getRole() {
        return strtolower((string)($this->authUser['role'] ?? ''));
    }

    private function getUserId() {
        return (int)($this->authUser['id'] ?? 0);
    }

    private function validate($data, $isUpdate = false) {

        if (empty($data['name'])) {
            throw new Exception("Tên project không được để trống");
        }

        if (strlen($data['name']) > 255) {
            throw new Exception("Tên project tối đa 255 ký tự");
        }

        $validStatus = ['Active', 'Completed', 'Archived'];
        if (!empty($data['status']) && !in_array($data['status'], $validStatus, true)) {
            throw new Exception("Status không hợp lệ");
        }

        if (!empty($data['manager_id'])) {
            if (!is_numeric($data['manager_id'])) {
                throw new Exception("manager_id phải là số");
            }

            if (!$this->model->existsManager((int)$data['manager_id'])) {
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
        if ($this->getRole() === 'admin') {
            return $this->model->getAll();
        }

        // MANAGER → chỉ xem project của mình
        if ($this->getRole() === 'manager') {
            return $this->model->getByManager($this->getUserId());
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

        /*
         * BỔ SUNG AN TOÀN:
         * "__unassigned__" là nhóm ảo dùng để gom các task chưa có project_id.
         * Đây không phải project thật trong DB, nên chỉ cho xem, không check manager_id.
         */
        if (!empty($project['is_virtual'])) {
            if (!in_array($this->getRole(), ['admin', 'manager'], true)) {
                throw new Exception("Bạn không có quyền");
            }

            return $project;
        }

        // MANAGER chỉ được xem project của mình
        if ($this->getRole() === 'manager' &&
            (int)$project['manager_id'] !== $this->getUserId()) {
            throw new Exception("Bạn không có quyền truy cập project này");
        }

        // EMPLOYEE cấm
        if ($this->getRole() === 'employee') {
            throw new Exception("Bạn không có quyền");
        }

        return $project;
    }

    // =========================
    // CREATE
    // =========================
    public function create($data) {

        if (!in_array($this->getRole(), ['admin', 'manager'], true)) {
            throw new Exception("Bạn không có quyền tạo project");
        }

        $this->validate($data);

        // Nếu là manager → ép manager_id = chính nó
        if ($this->getRole() === 'manager') {
            $data['manager_id'] = $this->getUserId();
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

        /*
         * BỔ SUNG AN TOÀN:
         * Không cho update nhóm ảo "Công việc chưa gán dự án"
         * vì nó không tồn tại thật trong bảng projects.
         */
        if ((string)$id === '__unassigned__') {
            throw new Exception("Nhóm task chưa gán dự án là dữ liệu tổng hợp, không thể cập nhật trực tiếp");
        }

        $project = $this->getById($id); // check quyền luôn

        if (!in_array($this->getRole(), ['admin', 'manager'], true)) {
            throw new Exception("Bạn không có quyền cập nhật");
        }

        // MANAGER chỉ sửa project của mình
        if ($this->getRole() === 'manager' &&
            (int)$project['manager_id'] !== $this->getUserId()) {
            throw new Exception("Bạn không có quyền sửa project này");
        }

        $this->validate($data, true);

        return $this->model->update($id, [
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'manager_id' => $this->getRole() === 'admin'
                ? ($data['manager_id'] ?? $project['manager_id'])
                : $project['manager_id'], // KHÔNG cho manager đổi
            'status' => $data['status'] ?? $project['status']
        ]);
    }

    // =========================
    // DELETE
    // =========================
    public function delete($id) {

        /*
         * BỔ SUNG AN TOÀN:
         * Không cho xoá nhóm ảo vì đây chỉ là view tổng hợp task project_id NULL.
         */
        if ((string)$id === '__unassigned__') {
            throw new Exception("Nhóm task chưa gán dự án là dữ liệu tổng hợp, không thể xoá");
        }

        $project = $this->getById($id);

        if (!in_array($this->getRole(), ['admin', 'manager'], true)) {
            throw new Exception("Bạn không có quyền xoá");
        }

        if ($this->getRole() === 'manager' &&
            (int)$project['manager_id'] !== $this->getUserId()) {
            throw new Exception("Bạn không có quyền xoá project này");
        }

        return $this->model->delete($id);
    }
}