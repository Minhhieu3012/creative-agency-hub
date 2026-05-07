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
        return (int)($this->authUser['id'] ?? $this->authUser['employee_id'] ?? 0);
    }

    private function normalizeStatus($status) {
        $status = trim((string)($status ?: 'Active'));

        $map = [
            'active' => 'Active',
            'đang triển khai' => 'Active',
            'completed' => 'Completed',
            'done' => 'Completed',
            'hoàn thành' => 'Completed',
            'archived' => 'Archived',
            'đã lưu trữ' => 'Archived',
        ];

        return $map[strtolower($status)] ?? $status;
    }

    private function normalizeNullableInt($value) {
        if ($value === null || $value === '' || $value === false) {
            return null;
        }

        $number = (int)$value;

        return $number > 0 ? $number : null;
    }

    private function validate($data, $isUpdate = false) {
        if (empty($data['name'])) {
            throw new Exception("Tên project không được để trống");
        }

        if (strlen($data['name']) > 255) {
            throw new Exception("Tên project tối đa 255 ký tự");
        }

        $validStatus = ['Active', 'Completed', 'Archived'];
        $status = $this->normalizeStatus($data['status'] ?? 'Active');

        if (!in_array($status, $validStatus, true)) {
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

        if (!empty($data['client_id'])) {
            if (!is_numeric($data['client_id'])) {
                throw new Exception("client_id phải là số");
            }

            if (method_exists($this->model, 'existsClient') && !$this->model->existsClient((int)$data['client_id'])) {
                throw new Exception("Khách hàng giám sát không tồn tại hoặc không còn hoạt động");
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

        // EMPLOYEE → được xem project đang mở để chọn khi tạo task chờ duyệt
        if ($this->getRole() === 'employee') {
            if (method_exists($this->model, 'getVisibleForEmployee')) {
                return $this->model->getVisibleForEmployee($this->getUserId());
            }

            $projects = $this->model->getAll();

            return array_values(array_filter($projects, function ($project) {
                if (!empty($project['is_virtual'])) {
                    return false;
                }

                return ($project['status'] ?? 'Active') !== 'Archived';
            }));
        }

        // CLIENT không dùng API nội bộ này, client portal có API riêng
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
         * "__unassigned__" là nhóm ảo gom task chưa có project_id.
         * Đây không phải project thật trong bảng projects nên chỉ cho xem, không update/delete.
         */
        if (!empty($project['is_virtual'])) {
            if (!in_array($this->getRole(), ['admin', 'manager'], true)) {
                throw new Exception("Bạn không có quyền");
            }

            return $project;
        }

        // ADMIN xem mọi project
        if ($this->getRole() === 'admin') {
            return $project;
        }

        // MANAGER chỉ được xem project của mình
        if ($this->getRole() === 'manager') {
            if ((int)($project['manager_id'] ?? 0) !== $this->getUserId()) {
                throw new Exception("Bạn không có quyền truy cập project này");
            }

            return $project;
        }

        // EMPLOYEE được xem project đang mở hoặc project có task liên quan tới mình
        if ($this->getRole() === 'employee') {
            if (method_exists($this->model, 'employeeCanSeeProject')) {
                if (!$this->model->employeeCanSeeProject((int)$project['id'], $this->getUserId())) {
                    throw new Exception("Bạn không có quyền");
                }

                return $project;
            }

            if (($project['status'] ?? 'Active') === 'Archived') {
                throw new Exception("Bạn không có quyền");
            }

            return $project;
        }

        throw new Exception("Bạn không có quyền");
    }

    // =========================
    // CREATE
    // =========================
    public function create($data) {
        if (!in_array($this->getRole(), ['admin', 'manager'], true)) {
            throw new Exception("Bạn không có quyền tạo project");
        }

        $data['name'] = trim((string)($data['name'] ?? ''));
        $data['description'] = trim((string)($data['description'] ?? ''));
        $data['status'] = $this->normalizeStatus($data['status'] ?? 'Active');

        // Nếu là manager → ép manager_id = chính nó
        if ($this->getRole() === 'manager') {
            $data['manager_id'] = $this->getUserId();
        } else {
            $data['manager_id'] = $this->normalizeNullableInt($data['manager_id'] ?? null);
        }

        $data['client_id'] = $this->normalizeNullableInt($data['client_id'] ?? null);

        $this->validate($data);

        if (!$data['manager_id']) {
            throw new Exception("Vui lòng chọn người phụ trách dự án");
        }

        return $this->model->create([
            'name' => $data['name'],
            'description' => $data['description'] !== '' ? $data['description'] : null,
            'manager_id' => $data['manager_id'],
            'client_id' => $data['client_id'],
            'status' => $data['status']
        ]);
    }

    // =========================
    // UPDATE
    // =========================
    public function update($id, $data) {
        if ((string)$id === '__unassigned__') {
            throw new Exception("Nhóm task chưa gán dự án là dữ liệu tổng hợp, không thể cập nhật trực tiếp");
        }

        $project = $this->getById($id); // check quyền luôn

        if (!in_array($this->getRole(), ['admin', 'manager'], true)) {
            throw new Exception("Bạn không có quyền cập nhật");
        }

        // MANAGER chỉ sửa project của mình
        if ($this->getRole() === 'manager' && (int)($project['manager_id'] ?? 0) !== $this->getUserId()) {
            throw new Exception("Bạn không có quyền sửa project này");
        }

        $data['name'] = trim((string)($data['name'] ?? ($project['name'] ?? '')));
        $data['description'] = trim((string)($data['description'] ?? ($project['description'] ?? '')));
        $data['status'] = $this->normalizeStatus($data['status'] ?? ($project['status'] ?? 'Active'));

        if ($this->getRole() === 'admin') {
            $data['manager_id'] = $this->normalizeNullableInt($data['manager_id'] ?? ($project['manager_id'] ?? null));
        } else {
            $data['manager_id'] = (int)($project['manager_id'] ?? 0);
        }

        $data['client_id'] = array_key_exists('client_id', $data)
            ? $this->normalizeNullableInt($data['client_id'])
            : $this->normalizeNullableInt($project['client_id'] ?? null);

        $this->validate($data, true);

        if (!$data['manager_id']) {
            throw new Exception("Vui lòng chọn người phụ trách dự án");
        }

        return $this->model->update($id, [
            'name' => $data['name'],
            'description' => $data['description'] !== '' ? $data['description'] : null,
            'manager_id' => $data['manager_id'],
            'client_id' => $data['client_id'],
            'status' => $data['status']
        ]);
    }

    // =========================
    // DELETE
    // =========================
    public function delete($id) {
        if ((string)$id === '__unassigned__') {
            throw new Exception("Nhóm task chưa gán dự án là dữ liệu tổng hợp, không thể xoá");
        }

        $project = $this->getById($id);

        if (!in_array($this->getRole(), ['admin', 'manager'], true)) {
            throw new Exception("Bạn không có quyền xoá");
        }

        if ($this->getRole() === 'manager' && (int)($project['manager_id'] ?? 0) !== $this->getUserId()) {
            throw new Exception("Bạn không có quyền xoá project này");
        }

        if (method_exists($this->model, 'countTasksByProject')) {
            $taskCount = $this->model->countTasksByProject($id);

            if ($taskCount > 0) {
                throw new Exception("Project đang có task, không thể xoá trực tiếp. Hãy chuyển/xoá task trước.");
            }
        }

        return $this->model->delete($id);
    }
}