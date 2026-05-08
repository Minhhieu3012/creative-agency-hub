<?php
namespace App\Models\Task;

use Core\Database;
use PDO;
use RuntimeException;
use Throwable;

class TaskModel {
    private PDO $db;
    private string $table = 'tasks';

    public function __construct() {
        $this->db = Database::getConnection();
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    private function tableExists(string $table): bool {
        $stmt = $this->db->prepare("
            SELECT COUNT(*)
            FROM INFORMATION_SCHEMA.TABLES
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = :table_name
        ");

        $stmt->execute([
            ':table_name' => $table,
        ]);

        return (int)$stmt->fetchColumn() > 0;
    }

    private function columnExists(string $table, string $column): bool {
        $stmt = $this->db->prepare("
            SELECT COUNT(*)
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = :table_name
              AND COLUMN_NAME = :column_name
        ");

        $stmt->execute([
            ':table_name' => $table,
            ':column_name' => $column,
        ]);

        return (int)$stmt->fetchColumn() > 0;
    }

    private function normalizeId($value): ?int {
        if ($value === null || $value === '') {
            return null;
        }

        $id = (int)$value;

        return $id > 0 ? $id : null;
    }

    private function normalizeStatus(?string $status): string {
        $status = trim((string)$status);

        $allowed = ['To do', 'Doing', 'Review', 'Done'];

        return in_array($status, $allowed, true) ? $status : 'To do';
    }

    private function normalizePriority(?string $priority): string {
        $priority = trim((string)$priority);

        $allowed = ['Low', 'Medium', 'High'];

        return in_array($priority, $allowed, true) ? $priority : 'Medium';
    }

    private function normalizeBoolean($value): int {
        if (is_bool($value)) {
            return $value ? 1 : 0;
        }

        if (is_numeric($value)) {
            return ((int)$value) === 1 ? 1 : 0;
        }

        $value = strtolower(trim((string)$value));

        return in_array($value, ['1', 'true', 'yes', 'on'], true) ? 1 : 0;
    }

    private function projectMembersEnabled(): bool {
        return $this->tableExists('project_members');
    }

    private function clientVisibilityEnabled(): bool {
        return $this->columnExists('tasks', 'is_client_visible');
    }

    private function reviewMetadataEnabled(): bool {
        return $this->columnExists('tasks', 'reviewed_by')
            && $this->columnExists('tasks', 'reviewed_at')
            && $this->columnExists('tasks', 'reject_reason');
    }

    private function getProjectById(int $projectId): ?array {
        $stmt = $this->db->prepare("
            SELECT
                p.id,
                p.name,
                p.manager_id,
                p.client_id,
                p.status
            FROM projects p
            WHERE p.id = :id
            LIMIT 1
        ");

        $stmt->execute([
            ':id' => $projectId,
        ]);

        $project = $stmt->fetch(PDO::FETCH_ASSOC);

        return $project ?: null;
    }

    private function ensureProjectExists(int $projectId): array {
        $project = $this->getProjectById($projectId);

        if (!$project) {
            throw new RuntimeException('Không tìm thấy dự án.');
        }

        return $project;
    }

    private function ensureManagerOwnsProject(int $projectId, int $managerId): array {
        $project = $this->ensureProjectExists($projectId);

        if ((int)$project['manager_id'] !== $managerId) {
            throw new RuntimeException('Bạn chỉ được thao tác với dự án do mình quản lý.');
        }

        return $project;
    }

    public function isEmployeeInProject(int $projectId, int $employeeId): bool {
        if ($employeeId <= 0) {
            return false;
        }

        if ($this->projectMembersEnabled()) {
            $stmt = $this->db->prepare("
                SELECT COUNT(*)
                FROM project_members
                WHERE project_id = :project_id
                  AND employee_id = :employee_id
                  AND status = 'active'
            ");

            $stmt->execute([
                ':project_id' => $projectId,
                ':employee_id' => $employeeId,
            ]);

            if ((int)$stmt->fetchColumn() > 0) {
                return true;
            }
        }

        $stmt = $this->db->prepare("
            SELECT COUNT(*)
            FROM tasks
            WHERE project_id = :project_id
              AND assignee_id = :employee_id
        ");

        $stmt->execute([
            ':project_id' => $projectId,
            ':employee_id' => $employeeId,
        ]);

        return (int)$stmt->fetchColumn() > 0;
    }

    public function getAvailableAssignees(int $projectId): array {
        if ($this->projectMembersEnabled()) {
            $stmt = $this->db->prepare("
                SELECT
                    e.id,
                    e.full_name,
                    e.email,
                    e.avatar,
                    d.name AS department_name,
                    p.name AS position_name
                FROM project_members pm
                INNER JOIN employees e ON e.id = pm.employee_id
                LEFT JOIN departments d ON d.id = e.department_id
                LEFT JOIN positions p ON p.id = e.position_id
                WHERE pm.project_id = :project_id
                  AND pm.status = 'active'
                  AND e.role = 'employee'
                  AND e.status = 'active'
                  AND e.deleted_at IS NULL
                ORDER BY e.full_name ASC
            ");

            $stmt->execute([
                ':project_id' => $projectId,
            ]);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        $stmt = $this->db->prepare("
            SELECT
                e.id,
                e.full_name,
                e.email,
                e.avatar,
                d.name AS department_name,
                p.name AS position_name
            FROM employees e
            LEFT JOIN departments d ON d.id = e.department_id
            LEFT JOIN positions p ON p.id = e.position_id
            WHERE e.role = 'employee'
              AND e.status = 'active'
              AND e.deleted_at IS NULL
            ORDER BY e.full_name ASC
        ");

        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function buildSelectSql(): string {
        $clientVisibleSelect = $this->clientVisibilityEnabled()
            ? "t.is_client_visible,"
            : "0 AS is_client_visible,";

        $reviewSelect = $this->reviewMetadataEnabled()
            ? "
                t.reviewed_by,
                t.reviewed_at,
                t.reject_reason,
                reviewer.full_name AS reviewer_name,
            "
            : "
                NULL AS reviewed_by,
                NULL AS reviewed_at,
                NULL AS reject_reason,
                NULL AS reviewer_name,
            ";

        return "
            SELECT
                t.id,
                t.project_id,
                t.title,
                t.description,
                t.status,
                t.priority,
                t.deadline,
                t.assigner_id,
                t.assignee_id,
                t.watcher_id,
                {$clientVisibleSelect}
                {$reviewSelect}
                t.created_at,
                t.updated_at,

                p.name AS project_name,
                p.manager_id AS project_manager_id,
                p.client_id AS project_client_id,

                assigner.full_name AS assigner_name,
                assigner.email AS assigner_email,

                assignee.full_name AS assignee_name,
                assignee.email AS assignee_email,
                assignee.avatar AS assignee_avatar,

                watcher.full_name AS watcher_name
            FROM tasks t
            LEFT JOIN projects p ON p.id = t.project_id
            LEFT JOIN employees assigner ON assigner.id = t.assigner_id
            LEFT JOIN employees assignee ON assignee.id = t.assignee_id
            LEFT JOIN employees watcher ON watcher.id = t.watcher_id
            LEFT JOIN employees reviewer ON reviewer.id = " . ($this->reviewMetadataEnabled() ? "t.reviewed_by" : "NULL") . "
        ";
    }

    private function decorateTask(array $task): array {
        $task['id'] = (int)$task['id'];
        $task['project_id'] = $task['project_id'] !== null ? (int)$task['project_id'] : null;
        $task['assigner_id'] = $task['assigner_id'] !== null ? (int)$task['assigner_id'] : null;
        $task['assignee_id'] = $task['assignee_id'] !== null ? (int)$task['assignee_id'] : null;
        $task['watcher_id'] = $task['watcher_id'] !== null ? (int)$task['watcher_id'] : null;
        $task['is_client_visible'] = (int)($task['is_client_visible'] ?? 0);
        $task['is_overdue'] = !empty($task['deadline'])
            && $task['deadline'] < date('Y-m-d')
            && $task['status'] !== 'Done';

        return $task;
    }

    public function all(array $filters = [], ?array $authUser = null): array {
        $params = [];
        $where = [];

        $role = strtolower((string)($authUser['role'] ?? ''));
        $userId = (int)($authUser['id'] ?? 0);

        if (!empty($filters['project_id'])) {
            $where[] = "t.project_id = :project_id";
            $params[':project_id'] = (int)$filters['project_id'];
        }

        if (!empty($filters['status'])) {
            $where[] = "t.status = :status";
            $params[':status'] = $this->normalizeStatus($filters['status']);
        }

        if (!empty($filters['priority'])) {
            $where[] = "t.priority = :priority";
            $params[':priority'] = $this->normalizePriority($filters['priority']);
        }

        if (!empty($filters['search'])) {
            $where[] = "(t.title LIKE :search OR t.description LIKE :search OR p.name LIKE :search)";
            $params[':search'] = '%' . trim((string)$filters['search']) . '%';
        }

        if ($role === 'manager') {
            $where[] = "p.manager_id = :auth_manager_id";
            $params[':auth_manager_id'] = $userId;
        }

        if ($role === 'employee') {
            $where[] = "t.assignee_id = :auth_employee_id";
            $params[':auth_employee_id'] = $userId;
        }

        if ($role === 'client') {
            $where[] = "p.client_id = :auth_client_id";
            $params[':auth_client_id'] = $userId;

            if ($this->clientVisibilityEnabled()) {
                $where[] = "t.is_client_visible = 1";
            }
        }

        $whereSql = count($where) > 0 ? ('WHERE ' . implode(' AND ', $where)) : '';

        $sql = $this->buildSelectSql() . "
            {$whereSql}
            ORDER BY
                FIELD(t.status, 'To do', 'Doing', 'Review', 'Done'),
                CASE WHEN t.deadline IS NULL THEN 1 ELSE 0 END,
                t.deadline ASC,
                t.updated_at DESC,
                t.id DESC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($tasks as &$task) {
            $task = $this->decorateTask($task);
        }

        unset($task);

        return $tasks;
    }

    public function findById(int $id): ?array {
        $sql = $this->buildSelectSql() . "
            WHERE t.id = :id
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':id' => $id,
        ]);

        $task = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$task) {
            return null;
        }

        return $this->decorateTask($task);
    }

    public function canUserAccessTask(array $authUser, int $taskId): bool {
        $task = $this->findById($taskId);

        if (!$task) {
            return false;
        }

        $role = strtolower((string)($authUser['role'] ?? ''));
        $userId = (int)($authUser['id'] ?? 0);

        if ($role === 'admin') {
            return true;
        }

        if ($role === 'manager') {
            return (int)$task['project_manager_id'] === $userId;
        }

        if ($role === 'employee') {
            return (int)$task['assignee_id'] === $userId;
        }

        if ($role === 'client') {
            return (int)$task['project_client_id'] === $userId
                && (int)$task['is_client_visible'] === 1;
        }

        return false;
    }

    public function create(array $data, array $authUser): int {
        $role = strtolower((string)($authUser['role'] ?? ''));

        if ($role !== 'manager') {
            throw new RuntimeException('Chỉ Manager được tạo task.');
        }

        $managerId = (int)$authUser['id'];
        $projectId = $this->normalizeId($data['project_id'] ?? null);
        $title = trim((string)($data['title'] ?? ''));
        $description = trim((string)($data['description'] ?? ''));
        $status = $this->normalizeStatus($data['status'] ?? 'To do');
        $priority = $this->normalizePriority($data['priority'] ?? 'Medium');
        $deadline = trim((string)($data['deadline'] ?? ''));
        $assigneeId = $this->normalizeId($data['assignee_id'] ?? null);
        $watcherId = $this->normalizeId($data['watcher_id'] ?? null);
        $isClientVisible = $this->normalizeBoolean($data['is_client_visible'] ?? 0);

        if (!$projectId) {
            throw new RuntimeException('Vui lòng chọn dự án.');
        }

        if ($title === '') {
            throw new RuntimeException('Tiêu đề task không được để trống.');
        }

        $this->ensureManagerOwnsProject($projectId, $managerId);

        if ($assigneeId && !$this->isEmployeeInProject($projectId, $assigneeId)) {
            throw new RuntimeException('Nhân viên được giao phải là thành viên của dự án.');
        }

        if ($deadline !== '') {
            $date = date_create_from_format('Y-m-d', $deadline);

            if (!$date || $date->format('Y-m-d') !== $deadline) {
                throw new RuntimeException('Deadline không hợp lệ.');
            }
        } else {
            $deadline = null;
        }

        $columns = [
            'project_id',
            'title',
            'description',
            'status',
            'priority',
            'deadline',
            'assigner_id',
            'assignee_id',
            'watcher_id',
        ];

        $params = [
            ':project_id' => $projectId,
            ':title' => $title,
            ':description' => $description !== '' ? $description : null,
            ':status' => $status,
            ':priority' => $priority,
            ':deadline' => $deadline,
            ':assigner_id' => $managerId,
            ':assignee_id' => $assigneeId,
            ':watcher_id' => $watcherId ?: $managerId,
        ];

        if ($this->clientVisibilityEnabled()) {
            $columns[] = 'is_client_visible';
            $params[':is_client_visible'] = $isClientVisible;
        }

        $placeholders = array_map(fn($column) => ':' . $column, $columns);

        $stmt = $this->db->prepare("
            INSERT INTO tasks
            (
                " . implode(', ', $columns) . "
            )
            VALUES
            (
                " . implode(', ', $placeholders) . "
            )
        ");

        $stmt->execute($params);

        $taskId = (int)$this->db->lastInsertId();

        $this->logActivity($taskId, $managerId, 'create', 'Manager tạo task mới.');
        $this->notifyAssignee($assigneeId, 'Bạn được giao task "' . $title . '".');

        return $taskId;
    }

    public function update(int $id, array $data, array $authUser): bool {
        $role = strtolower((string)($authUser['role'] ?? ''));

        if ($role !== 'manager') {
            throw new RuntimeException('Chỉ Manager được cập nhật task.');
        }

        $managerId = (int)$authUser['id'];
        $task = $this->findById($id);

        if (!$task) {
            throw new RuntimeException('Không tìm thấy task.');
        }

        if ((int)$task['project_manager_id'] !== $managerId) {
            throw new RuntimeException('Bạn chỉ được cập nhật task thuộc dự án do mình quản lý.');
        }

        $projectId = array_key_exists('project_id', $data)
            ? $this->normalizeId($data['project_id'])
            : (int)$task['project_id'];

        if (!$projectId) {
            throw new RuntimeException('Vui lòng chọn dự án.');
        }

        $this->ensureManagerOwnsProject($projectId, $managerId);

        $title = array_key_exists('title', $data)
            ? trim((string)$data['title'])
            : (string)$task['title'];

        if ($title === '') {
            throw new RuntimeException('Tiêu đề task không được để trống.');
        }

        $description = array_key_exists('description', $data)
            ? trim((string)$data['description'])
            : ($task['description'] ?? null);

        $status = array_key_exists('status', $data)
            ? $this->normalizeStatus($data['status'])
            : $this->normalizeStatus($task['status']);

        $priority = array_key_exists('priority', $data)
            ? $this->normalizePriority($data['priority'])
            : $this->normalizePriority($task['priority']);

        $deadline = array_key_exists('deadline', $data)
            ? trim((string)$data['deadline'])
            : ($task['deadline'] ?? null);

        if ($deadline === '') {
            $deadline = null;
        }

        if ($deadline !== null) {
            $date = date_create_from_format('Y-m-d', $deadline);

            if (!$date || $date->format('Y-m-d') !== $deadline) {
                throw new RuntimeException('Deadline không hợp lệ.');
            }
        }

        $assigneeId = array_key_exists('assignee_id', $data)
            ? $this->normalizeId($data['assignee_id'])
            : $this->normalizeId($task['assignee_id'] ?? null);

        if ($assigneeId && !$this->isEmployeeInProject($projectId, $assigneeId)) {
            throw new RuntimeException('Nhân viên được giao phải là thành viên của dự án.');
        }

        $watcherId = array_key_exists('watcher_id', $data)
            ? $this->normalizeId($data['watcher_id'])
            : $this->normalizeId($task['watcher_id'] ?? null);

        $sets = [
            'project_id = :project_id',
            'title = :title',
            'description = :description',
            'status = :status',
            'priority = :priority',
            'deadline = :deadline',
            'assignee_id = :assignee_id',
            'watcher_id = :watcher_id',
        ];

        $params = [
            ':id' => $id,
            ':project_id' => $projectId,
            ':title' => $title,
            ':description' => $description !== '' ? $description : null,
            ':status' => $status,
            ':priority' => $priority,
            ':deadline' => $deadline,
            ':assignee_id' => $assigneeId,
            ':watcher_id' => $watcherId ?: $managerId,
        ];

        if ($this->clientVisibilityEnabled()) {
            $sets[] = 'is_client_visible = :is_client_visible';
            $params[':is_client_visible'] = $this->normalizeBoolean($data['is_client_visible'] ?? $task['is_client_visible'] ?? 0);
        }

        if ($this->reviewMetadataEnabled() && $status !== 'Done') {
            $sets[] = 'reviewed_by = NULL';
            $sets[] = 'reviewed_at = NULL';

            if ($status !== 'Doing') {
                $sets[] = 'reject_reason = NULL';
            }
        }

        $stmt = $this->db->prepare("
            UPDATE tasks
            SET " . implode(', ', $sets) . "
            WHERE id = :id
        ");

        $result = $stmt->execute($params);

        $this->logActivity($id, $managerId, 'update', 'Manager cập nhật task.');

        if ($assigneeId && (int)$task['assignee_id'] !== $assigneeId) {
            $this->notifyAssignee($assigneeId, 'Bạn được giao task "' . $title . '".');
        }

        return $result;
    }

    public function updateStatus(int $id, string $status, array $authUser): bool {
        $task = $this->findById($id);

        if (!$task) {
            throw new RuntimeException('Không tìm thấy task.');
        }

        $role = strtolower((string)($authUser['role'] ?? ''));
        $userId = (int)($authUser['id'] ?? 0);
        $nextStatus = $this->normalizeStatus($status);

        if ($role === 'employee') {
            if ((int)$task['assignee_id'] !== $userId) {
                throw new RuntimeException('Bạn chỉ được cập nhật task được giao cho mình.');
            }

            if ($nextStatus === 'Done') {
                throw new RuntimeException('Employee không được tự chuyển task sang Done. Hãy gửi Review để Manager duyệt.');
            }
        } elseif ($role === 'manager') {
            if ((int)$task['project_manager_id'] !== $userId) {
                throw new RuntimeException('Bạn chỉ được cập nhật task thuộc dự án do mình quản lý.');
            }
        } else {
            throw new RuntimeException('Bạn không có quyền cập nhật trạng thái task.');
        }

        $sets = ['status = :status'];
        $params = [
            ':id' => $id,
            ':status' => $nextStatus,
        ];

        if ($this->reviewMetadataEnabled() && $nextStatus !== 'Done') {
            $sets[] = 'reviewed_by = NULL';
            $sets[] = 'reviewed_at = NULL';

            if ($nextStatus !== 'Doing') {
                $sets[] = 'reject_reason = NULL';
            }
        }

        $stmt = $this->db->prepare("
            UPDATE tasks
            SET " . implode(', ', $sets) . "
            WHERE id = :id
        ");

        $result = $stmt->execute($params);

        $this->logActivity($id, $userId, 'status_change', 'Cập nhật trạng thái task sang ' . $nextStatus . '.');

        if ($nextStatus === 'Review' && !empty($task['project_manager_id'])) {
            $this->notifyAssignee((int)$task['project_manager_id'], 'Task "' . $task['title'] . '" đã được gửi sang Review.');
        }

        return $result;
    }

    public function submitForReview(int $id, array $authUser): bool {
        return $this->updateStatus($id, 'Review', $authUser);
    }

    public function approve(int $id, array $authUser): bool {
        $task = $this->findById($id);

        if (!$task) {
            throw new RuntimeException('Không tìm thấy task.');
        }

        $role = strtolower((string)($authUser['role'] ?? ''));
        $managerId = (int)($authUser['id'] ?? 0);

        if ($role !== 'manager' || (int)$task['project_manager_id'] !== $managerId) {
            throw new RuntimeException('Bạn chỉ được duyệt task thuộc dự án do mình quản lý.');
        }

        $sets = ['status = "Done"'];
        $params = [':id' => $id];

        if ($this->reviewMetadataEnabled()) {
            $sets[] = 'reviewed_by = :reviewed_by';
            $sets[] = 'reviewed_at = NOW()';
            $sets[] = 'reject_reason = NULL';
            $params[':reviewed_by'] = $managerId;
        }

        $stmt = $this->db->prepare("
            UPDATE tasks
            SET " . implode(', ', $sets) . "
            WHERE id = :id
        ");

        $result = $stmt->execute($params);

        $this->logActivity($id, $managerId, 'status_change', 'Manager approve task.');
        $this->notifyAssignee((int)$task['assignee_id'], 'Task "' . $task['title'] . '" đã được duyệt hoàn thành.');

        return $result;
    }

    public function reject(int $id, string $reason, array $authUser): bool {
        $task = $this->findById($id);

        if (!$task) {
            throw new RuntimeException('Không tìm thấy task.');
        }

        $role = strtolower((string)($authUser['role'] ?? ''));
        $managerId = (int)($authUser['id'] ?? 0);
        $reason = trim($reason);

        if ($role !== 'manager' || (int)$task['project_manager_id'] !== $managerId) {
            throw new RuntimeException('Bạn chỉ được reject task thuộc dự án do mình quản lý.');
        }

        if ($reason === '') {
            $reason = 'Cần chỉnh sửa thêm trước khi duyệt.';
        }

        $sets = ['status = "Doing"'];
        $params = [':id' => $id];

        if ($this->reviewMetadataEnabled()) {
            $sets[] = 'reviewed_by = :reviewed_by';
            $sets[] = 'reviewed_at = NOW()';
            $sets[] = 'reject_reason = :reject_reason';
            $params[':reviewed_by'] = $managerId;
            $params[':reject_reason'] = $reason;
        }

        $stmt = $this->db->prepare("
            UPDATE tasks
            SET " . implode(', ', $sets) . "
            WHERE id = :id
        ");

        $result = $stmt->execute($params);

        $this->addComment($id, $managerId, $reason, 'internal');
        $this->logActivity($id, $managerId, 'status_change', 'Manager reject task: ' . $reason);
        $this->notifyAssignee((int)$task['assignee_id'], 'Task "' . $task['title'] . '" bị yêu cầu chỉnh sửa.');

        return $result;
    }

    public function delete(int $id, array $authUser): bool {
        $task = $this->findById($id);

        if (!$task) {
            throw new RuntimeException('Không tìm thấy task.');
        }

        $role = strtolower((string)($authUser['role'] ?? ''));
        $managerId = (int)($authUser['id'] ?? 0);

        if ($role !== 'manager' || (int)$task['project_manager_id'] !== $managerId) {
            throw new RuntimeException('Bạn chỉ được xoá task thuộc dự án do mình quản lý.');
        }

        $this->logActivity($id, $managerId, 'delete', 'Manager xoá task.');

        $stmt = $this->db->prepare("
            DELETE FROM tasks
            WHERE id = :id
        ");

        return $stmt->execute([
            ':id' => $id,
        ]);
    }

    public function addComment(int $taskId, int $userId, string $commentText, string $visibility = 'internal'): int {
        $commentText = trim($commentText);

        if ($commentText === '') {
            throw new RuntimeException('Nội dung bình luận không được để trống.');
        }

        $visibility = in_array($visibility, ['internal', 'client'], true) ? $visibility : 'internal';

        $hasVisibility = $this->columnExists('task_comments', 'visibility');

        if ($hasVisibility) {
            $stmt = $this->db->prepare("
                INSERT INTO task_comments
                (
                    task_id,
                    user_id,
                    comment_text,
                    visibility
                )
                VALUES
                (
                    :task_id,
                    :user_id,
                    :comment_text,
                    :visibility
                )
            ");

            $stmt->execute([
                ':task_id' => $taskId,
                ':user_id' => $userId,
                ':comment_text' => $commentText,
                ':visibility' => $visibility,
            ]);
        } else {
            $stmt = $this->db->prepare("
                INSERT INTO task_comments
                (
                    task_id,
                    user_id,
                    comment_text
                )
                VALUES
                (
                    :task_id,
                    :user_id,
                    :comment_text
                )
            ");

            $stmt->execute([
                ':task_id' => $taskId,
                ':user_id' => $userId,
                ':comment_text' => $commentText,
            ]);
        }

        $commentId = (int)$this->db->lastInsertId();
        $this->logActivity($taskId, $userId, 'comment', 'Thêm bình luận task.');

        return $commentId;
    }

    public function logActivity(int $taskId, int $userId, string $action, ?string $description = null): void {
        if (!$this->tableExists('task_activity_logs')) {
            return;
        }

        $allowedActions = [
            'create',
            'assign',
            'reassign',
            'status_change',
            'upload',
            'comment',
            'update',
            'delete',
            'download',
        ];

        if (!in_array($action, $allowedActions, true)) {
            $action = 'update';
        }

        try {
            $stmt = $this->db->prepare("
                INSERT INTO task_activity_logs
                (
                    task_id,
                    user_id,
                    action,
                    description
                )
                VALUES
                (
                    :task_id,
                    :user_id,
                    :action,
                    :description
                )
            ");

            $stmt->execute([
                ':task_id' => $taskId,
                ':user_id' => $userId,
                ':action' => $action,
                ':description' => $description,
            ]);
        } catch (Throwable $e) {
            // Không để log phụ làm hỏng flow chính.
        }
    }

    private function notifyAssignee(?int $userId, string $message): void {
        if (!$userId || !$this->tableExists('notifications')) {
            return;
        }

        try {
            $stmt = $this->db->prepare("
                INSERT INTO notifications
                (
                    user_id,
                    message,
                    is_read
                )
                VALUES
                (
                    :user_id,
                    :message,
                    FALSE
                )
            ");

            $stmt->execute([
                ':user_id' => $userId,
                ':message' => $message,
            ]);
        } catch (Throwable $e) {
            // Notification là phụ trợ, không chặn task flow.
        }
    }

    public function getReviewTasks(array $authUser): array {
        $role = strtolower((string)($authUser['role'] ?? ''));

        if ($role !== 'manager') {
            return [];
        }

        return $this->all([
            'status' => 'Review',
        ], $authUser);
    }

    public function getKanbanGrouped(array $filters = [], ?array $authUser = null): array {
        $tasks = $this->all($filters, $authUser);

        $grouped = [
            'To do' => [],
            'Doing' => [],
            'Review' => [],
            'Done' => [],
        ];

        foreach ($tasks as $task) {
            $status = $this->normalizeStatus($task['status'] ?? 'To do');
            $grouped[$status][] = $task;
        }

        return $grouped;
    }

    /**
     * Backward-compatible aliases.
     * Giữ để controller/JS cũ nếu còn gọi tên cũ không gãy ngay.
     */
    public function getAll(array $filters = [], ?array $authUser = null): array {
        return $this->all($filters, $authUser);
    }

    public function getAllTasks(array $filters = [], ?array $authUser = null): array {
        return $this->all($filters, $authUser);
    }

    public function getTaskById(int $id): ?array {
        return $this->findById($id);
    }

    public function createTask(array $data, array $authUser): int {
        return $this->create($data, $authUser);
    }

    public function updateTask(int $id, array $data, array $authUser): bool {
        return $this->update($id, $data, $authUser);
    }

    public function deleteTask(int $id, array $authUser): bool {
        return $this->delete($id, $authUser);
    }

    public function destroy(int $id, array $authUser): bool {
        return $this->delete($id, $authUser);
    }
}