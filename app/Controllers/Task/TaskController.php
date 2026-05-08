<?php
namespace App\Controllers\Task;

use Core\Database;
use Core\JwtHandler;
use PDO;
use Throwable;

class TaskController {
    private PDO $db;
    private JwtHandler $jwt;
    private ?array $authUser;

    public function __construct($authUser = null) {
        $this->db = Database::getConnection();
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->jwt = new JwtHandler();
        $this->authUser = is_array($authUser) ? $authUser : null;
    }

    private function json(array $payload, int $statusCode = 200): void {
        if (ob_get_length()) {
            ob_clean();
        }

        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }

    private function getInput(): array {
        $raw = file_get_contents('php://input');
        $json = json_decode($raw, true);

        if (is_array($json)) {
            return $json;
        }

        if (!empty($raw)) {
            $parsed = [];
            parse_str($raw, $parsed);

            if (is_array($parsed) && !empty($parsed)) {
                return $parsed;
            }
        }

        return $_POST ?? [];
    }

    private function getAuthorizationHeader(): string {
        $headers = function_exists('getallheaders') ? getallheaders() : [];

        return trim((string)(
            $headers['Authorization']
            ?? $headers['authorization']
            ?? $_SERVER['HTTP_AUTHORIZATION']
            ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
            ?? ''
        ));
    }

    private function extractBearerToken(string $authHeader): string {
        if ($authHeader === '') {
            return '';
        }

        if (stripos($authHeader, 'Bearer ') === 0) {
            return trim(substr($authHeader, 7));
        }

        return trim($authHeader);
    }

    private function normalizeAuthPayload($payload): ?array {
        if (!$payload) {
            return null;
        }

        if (is_object($payload)) {
            $payload = (array)$payload;
        }

        if (!is_array($payload)) {
            return null;
        }

        if (isset($payload['data']) && (is_array($payload['data']) || is_object($payload['data']))) {
            $payload = (array)$payload['data'];
        }

        if (empty($payload['id'])) {
            return null;
        }

        return [
            'id' => (int)$payload['id'],
            'email' => $payload['email'] ?? null,
            'role' => strtolower((string)($payload['role'] ?? '')),
            'full_name' => $payload['full_name'] ?? ($payload['name'] ?? null),
        ];
    }

    private function requireAuth(): array {
        if ($this->authUser && !empty($this->authUser['id'])) {
            return $this->authUser;
        }

        $token = '';
        $authHeader = $this->getAuthorizationHeader();

        if ($authHeader !== '') {
            $token = $this->extractBearerToken($authHeader);
        }

        if ($token === '' && !empty($_COOKIE['cah_token'])) {
            $token = (string)$_COOKIE['cah_token'];
        }

        if ($token !== '') {
            try {
                $decoded = $this->jwt->decode($token);
                $authUser = $this->normalizeAuthPayload($decoded);

                if ($authUser && !empty($authUser['id'])) {
                    $this->authUser = $authUser;
                    return $authUser;
                }
            } catch (Throwable $e) {
                // Fallback xuống session.
            }
        }

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!empty($_SESSION['user_id'])) {
            $this->authUser = [
                'id' => (int)$_SESSION['user_id'],
                'email' => $_SESSION['user_email'] ?? null,
                'role' => strtolower((string)($_SESSION['user_role'] ?? '')),
                'full_name' => $_SESSION['full_name'] ?? null,
            ];

            return $this->authUser;
        }

        $this->json([
            'status' => 'error',
            'message' => 'Bạn cần đăng nhập lại.'
        ], 401);
    }

    private function requireRole(array $roles): array {
        $authUser = $this->requireAuth();
        $role = strtolower((string)($authUser['role'] ?? ''));

        if (!in_array($role, $roles, true)) {
            $this->json([
                'status' => 'error',
                'message' => 'Bạn không có quyền thực hiện thao tác này.'
            ], 403);
        }

        return $authUser;
    }

    private function resolveId($idOrParams): ?int {
        if (is_array($idOrParams)) {
            if (isset($idOrParams['id'])) {
                return (int)$idOrParams['id'];
            }

            if (isset($idOrParams[0])) {
                return (int)$idOrParams[0];
            }

            return null;
        }

        if ($idOrParams !== null && $idOrParams !== '') {
            $id = (int)$idOrParams;
            return $id > 0 ? $id : null;
        }

        return null;
    }

    private function normalizeNullableId($value): ?int {
        if ($value === null || $value === '') {
            return null;
        }

        $id = (int)$value;

        return $id > 0 ? $id : null;
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

    private function projectMembersEnabled(): bool {
        return $this->tableExists('project_members');
    }

    private function projectMemberStatusCondition(string $alias = 'pm'): string {
        if ($this->columnExists('project_members', 'status')) {
            return " AND {$alias}.status = 'active' ";
        }

        return '';
    }

    private function clientVisibilityEnabled(): bool {
        return $this->columnExists('tasks', 'is_client_visible');
    }

    private function rejectReasonEnabled(): bool {
        return $this->columnExists('tasks', 'reject_reason');
    }

    private function normalizeStatus($status): string {
        $status = trim((string)$status);
        $allowed = ['To do', 'Doing', 'Review', 'Done'];

        return in_array($status, $allowed, true) ? $status : 'To do';
    }

    private function normalizePriority($priority): string {
        $priority = trim((string)$priority);
        $allowed = ['Low', 'Medium', 'High'];

        return in_array($priority, $allowed, true) ? $priority : 'Medium';
    }

    private function normalizeClientVisible($value): int {
        if ($value === true || $value === 1 || $value === '1' || $value === 'true' || $value === 'on') {
            return 1;
        }

        return 0;
    }

    private function getProjectById(int $projectId): ?array {
        $stmt = $this->db->prepare("
            SELECT
                p.id,
                p.name,
                p.description,
                p.manager_id,
                p.client_id,
                p.status,
                p.created_at,
                p.updated_at,
                manager.full_name AS manager_name,
                client.full_name AS client_name
            FROM projects p
            LEFT JOIN employees manager ON manager.id = p.manager_id
            LEFT JOIN employees client ON client.id = p.client_id
            WHERE p.id = :project_id
            LIMIT 1
        ");

        $stmt->execute([
            ':project_id' => $projectId,
        ]);

        $project = $stmt->fetch(PDO::FETCH_ASSOC);

        return $project ?: null;
    }

    private function ensureProjectExists(int $projectId): array {
        $project = $this->getProjectById($projectId);

        if (!$project) {
            $this->json([
                'status' => 'error',
                'message' => 'Không tìm thấy project.'
            ], 404);
        }

        return $project;
    }

    private function ensureManagerOwnsProject(array $authUser, array $project): void {
        if (strtolower((string)$authUser['role']) !== 'manager') {
            $this->json([
                'status' => 'error',
                'message' => 'Chỉ Manager được thao tác project/task.'
            ], 403);
        }

        if ((int)$project['manager_id'] !== (int)$authUser['id']) {
            $this->json([
                'status' => 'error',
                'message' => 'Bạn chỉ được thao tác project do mình quản lý.'
            ], 403);
        }
    }

    private function ensureManagerCanManageTask(array $authUser, array $task): void {
        if (strtolower((string)$authUser['role']) !== 'manager') {
            $this->json([
                'status' => 'error',
                'message' => 'Chỉ Manager được thao tác task.'
            ], 403);
        }

        $managerId = (int)$authUser['id'];
        $projectManagerId = (int)($task['project_manager_id'] ?? 0);
        $assignerId = (int)($task['assigner_id'] ?? 0);
        $assigneeManagerId = (int)($task['assignee_manager_id'] ?? 0);

        if (
            $projectManagerId === $managerId
            || $assignerId === $managerId
            || $assigneeManagerId === $managerId
        ) {
            return;
        }

        $this->json([
            'status' => 'error',
            'message' => 'Bạn chỉ được thao tác task thuộc project mình quản lý, task do mình giao hoặc task của employee mình quản lý.'
        ], 403);
    }

    private function isActiveEmployee(int $employeeId): bool {
        $stmt = $this->db->prepare("
            SELECT COUNT(*)
            FROM employees
            WHERE id = :id
              AND role = 'employee'
              AND status = 'active'
              AND deleted_at IS NULL
        ");

        $stmt->execute([
            ':id' => $employeeId,
        ]);

        return (int)$stmt->fetchColumn() > 0;
    }

    private function isEmployeeInProject(int $projectId, int $employeeId): bool {
        if (!$this->projectMembersEnabled()) {
            return true;
        }

        $statusCondition = $this->projectMemberStatusCondition('pm');

        $stmt = $this->db->prepare("
            SELECT COUNT(*)
            FROM project_members pm
            WHERE pm.project_id = :project_id
              AND pm.employee_id = :employee_id
              {$statusCondition}
        ");

        $stmt->execute([
            ':project_id' => $projectId,
            ':employee_id' => $employeeId,
        ]);

        return (int)$stmt->fetchColumn() > 0;
    }

    private function validateAssigneeForProject(int $projectId, ?int $employeeId, string $fieldLabel = 'Employee'): void {
        if (!$employeeId) {
            return;
        }

        if (!$this->isActiveEmployee($employeeId)) {
            $this->json([
                'status' => 'error',
                'message' => "{$fieldLabel} phải là tài khoản Employee active đã được Admin duyệt."
            ], 422);
        }

        if (!$this->isEmployeeInProject($projectId, $employeeId)) {
            $this->json([
                'status' => 'error',
                'message' => "{$fieldLabel} phải thuộc members của project trước khi được assign task."
            ], 422);
        }
    }

    private function selectSql(): string {
        $clientVisibleSelect = $this->clientVisibilityEnabled()
            ? "t.is_client_visible AS is_client_visible"
            : "0 AS is_client_visible";

        $rejectReasonSelect = $this->rejectReasonEnabled()
            ? "t.reject_reason AS reject_reason"
            : "NULL AS reject_reason";

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
                {$clientVisibleSelect},
                {$rejectReasonSelect},
                t.created_at,
                t.updated_at,

                p.name AS project_name,
                p.manager_id AS project_manager_id,
                p.client_id AS project_client_id,
                p.status AS project_status,

                assigner.full_name AS assigner_name,
                assignee.full_name AS assignee_name,
                assignee.manager_id AS assignee_manager_id,
                watcher.full_name AS watcher_name
            FROM tasks t
            LEFT JOIN projects p ON p.id = t.project_id
            LEFT JOIN employees assigner ON assigner.id = t.assigner_id
            LEFT JOIN employees assignee ON assignee.id = t.assignee_id
            LEFT JOIN employees watcher ON watcher.id = t.watcher_id
        ";
    }

    private function decorateTask(array $task): array {
        $task['id'] = (int)$task['id'];
        $task['project_id'] = $task['project_id'] !== null ? (int)$task['project_id'] : null;
        $task['assigner_id'] = $task['assigner_id'] !== null ? (int)$task['assigner_id'] : null;
        $task['assignee_id'] = $task['assignee_id'] !== null ? (int)$task['assignee_id'] : null;
        $task['watcher_id'] = $task['watcher_id'] !== null ? (int)$task['watcher_id'] : null;
        $task['project_manager_id'] = $task['project_manager_id'] !== null ? (int)$task['project_manager_id'] : null;
        $task['project_client_id'] = $task['project_client_id'] !== null ? (int)$task['project_client_id'] : null;
        $task['assignee_manager_id'] = $task['assignee_manager_id'] !== null ? (int)$task['assignee_manager_id'] : null;
        $task['is_client_visible'] = (int)($task['is_client_visible'] ?? 0);

        $task['is_overdue'] = !empty($task['deadline'])
            && $task['deadline'] < date('Y-m-d')
            && $task['status'] !== 'Done';

        return $task;
    }

    private function fetchTaskById(int $taskId): ?array {
        $stmt = $this->db->prepare($this->selectSql() . "
            WHERE t.id = :task_id
            LIMIT 1
        ");

        $stmt->execute([
            ':task_id' => $taskId,
        ]);

        $task = $stmt->fetch(PDO::FETCH_ASSOC);

        return $task ? $this->decorateTask($task) : null;
    }

    private function canAccessTask(array $authUser, array $task): bool {
        $role = strtolower((string)($authUser['role'] ?? ''));
        $userId = (int)($authUser['id'] ?? 0);

        if ($role === 'admin') {
            return true;
        }

        if ($role === 'manager') {
            return (int)($task['project_manager_id'] ?? 0) === $userId
                || (int)($task['assigner_id'] ?? 0) === $userId
                || (int)($task['assignee_manager_id'] ?? 0) === $userId;
        }

        if ($role === 'employee') {
            if ((int)($task['assignee_id'] ?? 0) === $userId) {
                return true;
            }

            if ((int)($task['watcher_id'] ?? 0) === $userId) {
                return true;
            }

            return false;
        }

        if ($role === 'client') {
            return (int)($task['project_client_id'] ?? 0) === $userId
                && (int)($task['is_client_visible'] ?? 0) === 1;
        }

        return false;
    }

    private function buildRoleWhere(array $authUser, array &$params): array {
        $role = strtolower((string)$authUser['role']);
        $userId = (int)$authUser['id'];

        if ($role === 'admin') {
            return [];
        }

        if ($role === 'manager') {
            $params[':manager_user_id'] = $userId;
            $params[':manager_assigner_id'] = $userId;
            $params[':manager_assignee_manager_id'] = $userId;

            return ["
                (
                    p.manager_id = :manager_user_id
                    OR t.assigner_id = :manager_assigner_id
                    OR assignee.manager_id = :manager_assignee_manager_id
                )
            "];
        }

        if ($role === 'employee') {
            $params[':employee_assignee_id'] = $userId;
            $params[':employee_watcher_id'] = $userId;

            return ["
                (
                    t.assignee_id = :employee_assignee_id
                    OR t.watcher_id = :employee_watcher_id
                )
            "];
        }

        if ($role === 'client') {
            $params[':client_user_id'] = $userId;

            if ($this->clientVisibilityEnabled()) {
                return [
                    "p.client_id = :client_user_id",
                    "t.is_client_visible = 1"
                ];
            }

            return [
                "p.client_id = :client_user_id",
                "1 = 0"
            ];
        }

        return ["1 = 0"];
    }

    private function fetchTasks(array $authUser, array $filters = []): array {
        $params = [];
        $where = $this->buildRoleWhere($authUser, $params);

        $projectId = $this->normalizeNullableId($filters['project_id'] ?? null);

        if ($projectId) {
            $where[] = "t.project_id = :filter_project_id";
            $params[':filter_project_id'] = $projectId;
        }

        if (!empty($filters['status'])) {
            $where[] = "t.status = :filter_status";
            $params[':filter_status'] = $this->normalizeStatus($filters['status']);
        }

        if (!empty($filters['search'])) {
            $search = trim((string)$filters['search']);

            $where[] = "(t.title LIKE :search_title OR t.description LIKE :search_description OR p.name LIKE :search_project)";
            $params[':search_title'] = "%{$search}%";
            $params[':search_description'] = "%{$search}%";
            $params[':search_project'] = "%{$search}%";
        }

        $whereSql = count($where) > 0 ? ('WHERE ' . implode(' AND ', $where)) : '';

        $sql = $this->selectSql() . "
            {$whereSql}
            ORDER BY
                CASE WHEN t.deadline IS NULL THEN 1 ELSE 0 END,
                t.deadline ASC,
                t.updated_at DESC,
                t.id DESC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map([$this, 'decorateTask'], $tasks);
    }

    private function logActivity(int $taskId, int $userId, string $action, string $description): void {
        if (!$this->tableExists('task_activity_logs')) {
            return;
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
            // Activity log không được làm hỏng flow chính.
        }
    }

    private function notifyUser(?int $userId, string $message): void {
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
            // Notification là phụ trợ.
        }
    }

    private function insertRejectComment(int $taskId, int $userId, string $reason): void {
        if (!$this->tableExists('task_comments') || trim($reason) === '') {
            return;
        }

        try {
            if ($this->columnExists('task_comments', 'visibility')) {
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
                        'internal'
                    )
                ");

                $stmt->execute([
                    ':task_id' => $taskId,
                    ':user_id' => $userId,
                    ':comment_text' => 'Reject task: ' . $reason,
                ]);

                return;
            }

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
                ':comment_text' => 'Reject task: ' . $reason,
            ]);
        } catch (Throwable $e) {
            // Comment phụ trợ.
        }
    }

    private function updateTaskStatusInternal(int $taskId, string $status): void {
        $stmt = $this->db->prepare("
            UPDATE tasks
            SET status = :status,
                updated_at = NOW()
            WHERE id = :task_id
        ");

        $stmt->execute([
            ':status' => $status,
            ':task_id' => $taskId,
        ]);
    }

    private function ensureTaskExists(int $taskId): array {
        $task = $this->fetchTaskById($taskId);

        if (!$task) {
            $this->json([
                'status' => 'error',
                'message' => 'Không tìm thấy task.'
            ], 404);
        }

        return $task;
    }

    public function index(): void {
        try {
            $authUser = $this->requireRole(['admin', 'manager', 'employee', 'client']);

            $tasks = $this->fetchTasks($authUser, [
                'project_id' => $_GET['project_id'] ?? null,
                'status' => $_GET['status'] ?? '',
                'search' => $_GET['search'] ?? ($_GET['q'] ?? ''),
            ]);

            $this->json([
                'status' => 'success',
                'data' => $tasks
            ]);
        } catch (Throwable $e) {
            $this->json([
                'status' => 'error',
                'message' => 'Không thể tải danh sách task: ' . $e->getMessage()
            ], 400);
        }
    }

    public function kanban(): void {
        try {
            $authUser = $this->requireRole(['admin', 'manager', 'employee', 'client']);

            $tasks = $this->fetchTasks($authUser, [
                'project_id' => $_GET['project_id'] ?? null,
                'search' => $_GET['search'] ?? ($_GET['q'] ?? ''),
            ]);

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

            $this->json([
                'status' => 'success',
                'data' => $grouped
            ]);
        } catch (Throwable $e) {
            $this->json([
                'status' => 'error',
                'message' => 'Không thể tải Kanban: ' . $e->getMessage()
            ], 400);
        }
    }

    // ĐÃ THÊM: Hàm xử lý lấy task sắp hết hạn (của employee chỉ định)
    public function upcoming(): void {
        try {
            // Cho phép Admin và Manager xem task sắp hết hạn của nhân viên
            $authUser = $this->requireRole(['admin', 'manager']);
            $employeeId = $this->normalizeNullableId($_GET['employee_id'] ?? null);

            if (!$employeeId) {
                $this->json([
                    'status' => 'error',
                    'message' => 'Thiếu ID nhân viên cần soi deadline.'
                ], 400);
            }

            $taskModel = new \App\Models\Task\TaskModel();
            $tasks = $taskModel->getUpcomingTasks($employeeId);

            $this->json([
                'status' => 'success',
                'data' => $tasks
            ]);
        } catch (Throwable $e) {
            $this->json([
                'status' => 'error',
                'message' => 'Không thể tải task sắp hết hạn: ' . $e->getMessage()
            ], 400);
        }
    }

    public function options(): void {
        try {
            $authUser = $this->requireRole(['manager']);
            $projectId = $this->normalizeNullableId($_GET['project_id'] ?? null);

            $projectsStmt = $this->db->prepare("
                SELECT
                    p.id,
                    p.name,
                    p.status,
                    p.client_id,
                    client.full_name AS client_name
                FROM projects p
                LEFT JOIN employees client ON client.id = p.client_id
                WHERE p.manager_id = :manager_id
                ORDER BY p.created_at DESC, p.id DESC
            ");

            $projectsStmt->execute([
                ':manager_id' => (int)$authUser['id'],
            ]);

            $projects = $projectsStmt->fetchAll(PDO::FETCH_ASSOC);

            if ($projectId) {
                $project = $this->ensureProjectExists($projectId);
                $this->ensureManagerOwnsProject($authUser, $project);
            }

            if ($projectId && $this->projectMembersEnabled()) {
                $statusCondition = $this->projectMemberStatusCondition('pm');

                $employeeStmt = $this->db->prepare("
                    SELECT
                        e.id,
                        e.employee_code,
                        e.full_name,
                        e.email,
                        e.avatar,
                        e.status,
                        d.name AS department_name,
                        p.name AS position_name
                    FROM project_members pm
                    INNER JOIN employees e ON e.id = pm.employee_id
                    LEFT JOIN departments d ON d.id = e.department_id
                    LEFT JOIN positions p ON p.id = e.position_id
                    WHERE pm.project_id = :project_id
                      {$statusCondition}
                      AND e.role = 'employee'
                      AND e.status = 'active'
                      AND e.deleted_at IS NULL
                    ORDER BY e.full_name ASC
                ");

                $employeeStmt->execute([
                    ':project_id' => $projectId,
                ]);

                $employees = $employeeStmt->fetchAll(PDO::FETCH_ASSOC);
            } else {
                $employees = $this->db->query("
                    SELECT
                        e.id,
                        e.employee_code,
                        e.full_name,
                        e.email,
                        e.avatar,
                        e.status,
                        d.name AS department_name,
                        p.name AS position_name
                    FROM employees e
                    LEFT JOIN departments d ON d.id = e.department_id
                    LEFT JOIN positions p ON p.id = e.position_id
                    WHERE e.role = 'employee'
                      AND e.status = 'active'
                      AND e.deleted_at IS NULL
                    ORDER BY e.full_name ASC
                ")->fetchAll(PDO::FETCH_ASSOC);
            }

            $this->json([
                'status' => 'success',
                'data' => [
                    'projects' => $projects,
                    'assignees' => $employees,
                    'employees' => $employees,
                    'watchers' => $employees,
                    'statuses' => ['To do', 'Doing', 'Review', 'Done'],
                    'employee_statuses' => ['Review'],
                    'priorities' => ['Low', 'Medium', 'High'],
                    'client_visibility_enabled' => $this->clientVisibilityEnabled(),
                    'project_members_enabled' => $this->projectMembersEnabled(),
                ]
            ]);
        } catch (Throwable $e) {
            $this->json([
                'status' => 'error',
                'message' => 'Không thể tải task options: ' . $e->getMessage()
            ], 400);
        }
    }

    public function show($id = null): void {
        try {
            $authUser = $this->requireRole(['admin', 'manager', 'employee', 'client']);
            $taskId = $this->resolveId($id);

            if (!$taskId) {
                $this->json([
                    'status' => 'error',
                    'message' => 'Thiếu ID task.'
                ], 400);
            }

            $task = $this->ensureTaskExists($taskId);

            if (!$this->canAccessTask($authUser, $task)) {
                $this->json([
                    'status' => 'error',
                    'message' => 'Bạn không có quyền xem task này.'
                ], 403);
            }

            $this->json([
                'status' => 'success',
                'data' => $task
            ]);
        } catch (Throwable $e) {
            $this->json([
                'status' => 'error',
                'message' => 'Không thể tải task: ' . $e->getMessage()
            ], 400);
        }
    }

    public function store(): void {
        try {
            $authUser = $this->requireRole(['manager']);
            $input = $this->getInput();

            $title = trim((string)($input['title'] ?? ''));
            $description = trim((string)($input['description'] ?? ''));
            $projectId = $this->normalizeNullableId($input['project_id'] ?? null);
            $status = $this->normalizeStatus($input['status'] ?? 'To do');
            $priority = $this->normalizePriority($input['priority'] ?? 'Medium');
            $deadline = !empty($input['deadline']) ? $input['deadline'] : null;
            $assigneeId = $this->normalizeNullableId($input['assignee_id'] ?? null);
            $watcherId = $this->normalizeNullableId($input['watcher_id'] ?? null);
            $isClientVisible = $this->normalizeClientVisible($input['is_client_visible'] ?? 0);

            if ($title === '') {
                $this->json([
                    'status' => 'error',
                    'message' => 'Tên task không được để trống.'
                ], 422);
            }

            if (!$projectId) {
                $this->json([
                    'status' => 'error',
                    'message' => 'Task phải thuộc một project.'
                ], 422);
            }

            $project = $this->ensureProjectExists($projectId);
            $this->ensureManagerOwnsProject($authUser, $project);

            $this->validateAssigneeForProject($projectId, $assigneeId, 'Assignee');
            $this->validateAssigneeForProject($projectId, $watcherId, 'Watcher');

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

            $values = [
                ':project_id',
                ':title',
                ':description',
                ':status',
                ':priority',
                ':deadline',
                ':assigner_id',
                ':assignee_id',
                ':watcher_id',
            ];

            $params = [
                ':project_id' => $projectId,
                ':title' => $title,
                ':description' => $description !== '' ? $description : null,
                ':status' => $status,
                ':priority' => $priority,
                ':deadline' => $deadline,
                ':assigner_id' => (int)$authUser['id'],
                ':assignee_id' => $assigneeId,
                ':watcher_id' => $watcherId,
            ];

            if ($this->clientVisibilityEnabled()) {
                $columns[] = 'is_client_visible';
                $values[] = ':is_client_visible';
                $params[':is_client_visible'] = $isClientVisible;
            }

            $sql = "
                INSERT INTO tasks
                (" . implode(', ', $columns) . ")
                VALUES
                (" . implode(', ', $values) . ")
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);

            $taskId = (int)$this->db->lastInsertId();
            $task = $this->fetchTaskById($taskId);

            $this->logActivity($taskId, (int)$authUser['id'], 'create', 'Manager tạo task mới.');
            $this->notifyUser($assigneeId, 'Bạn vừa được giao task "' . $title . '".');

            $this->json([
                'status' => 'success',
                'message' => 'Tạo task thành công.',
                'data' => $task
            ], 201);
        } catch (Throwable $e) {
            $this->json([
                'status' => 'error',
                'message' => 'Không thể tạo task: ' . $e->getMessage()
            ], 400);
        }
    }

    public function update($id = null): void {
        try {
            $authUser = $this->requireRole(['manager']);
            $taskId = $this->resolveId($id);

            if (!$taskId) {
                $this->json([
                    'status' => 'error',
                    'message' => 'Thiếu ID task.'
                ], 400);
            }

            $task = $this->ensureTaskExists($taskId);
            $this->ensureManagerCanManageTask($authUser, $task);

            $input = $this->getInput();
            $data = [];

            if (array_key_exists('title', $input)) {
                $title = trim((string)$input['title']);

                if ($title === '') {
                    $this->json([
                        'status' => 'error',
                        'message' => 'Tên task không được để trống.'
                    ], 422);
                }

                $data['title'] = $title;
            }

            if (array_key_exists('description', $input)) {
                $description = trim((string)$input['description']);
                $data['description'] = $description !== '' ? $description : null;
            }

            if (array_key_exists('status', $input)) {
                $data['status'] = $this->normalizeStatus($input['status']);
            }

            if (array_key_exists('priority', $input)) {
                $data['priority'] = $this->normalizePriority($input['priority']);
            }

            if (array_key_exists('deadline', $input)) {
                $data['deadline'] = !empty($input['deadline']) ? $input['deadline'] : null;
            }

            if (array_key_exists('assignee_id', $input)) {
                $assigneeId = $this->normalizeNullableId($input['assignee_id']);
                $this->validateAssigneeForProject((int)$task['project_id'], $assigneeId, 'Assignee');
                $data['assignee_id'] = $assigneeId;
            }

            if (array_key_exists('watcher_id', $input)) {
                $watcherId = $this->normalizeNullableId($input['watcher_id']);
                $this->validateAssigneeForProject((int)$task['project_id'], $watcherId, 'Watcher');
                $data['watcher_id'] = $watcherId;
            }

            if ($this->clientVisibilityEnabled() && array_key_exists('is_client_visible', $input)) {
                $data['is_client_visible'] = $this->normalizeClientVisible($input['is_client_visible']);
            }

            if (empty($data)) {
                $this->json([
                    'status' => 'error',
                    'message' => 'Không có dữ liệu hợp lệ để cập nhật task.'
                ], 422);
            }

            $sets = [];
            $params = [
                ':task_id' => $taskId,
            ];

            foreach ($data as $field => $value) {
                $sets[] = "{$field} = :{$field}";
                $params[":{$field}"] = $value;
            }

            $sets[] = "updated_at = NOW()";

            $stmt = $this->db->prepare("
                UPDATE tasks
                SET " . implode(', ', $sets) . "
                WHERE id = :task_id
            ");

            $stmt->execute($params);

            $updated = $this->fetchTaskById($taskId);

            $this->logActivity($taskId, (int)$authUser['id'], 'update', 'Manager cập nhật task.');

            if (isset($data['assignee_id']) && $data['assignee_id']) {
                $this->notifyUser((int)$data['assignee_id'], 'Bạn được cập nhật phân công task "' . ($updated['title'] ?? 'Không rõ') . '".');
            }

            $this->json([
                'status' => 'success',
                'message' => 'Cập nhật task thành công.',
                'data' => $updated
            ]);
        } catch (Throwable $e) {
            $this->json([
                'status' => 'error',
                'message' => 'Không thể cập nhật task: ' . $e->getMessage()
            ], 400);
        }
    }

    public function destroy($id = null): void {
        try {
            $authUser = $this->requireRole(['manager']);
            $taskId = $this->resolveId($id);

            if (!$taskId) {
                $this->json([
                    'status' => 'error',
                    'message' => 'Thiếu ID task.'
                ], 400);
            }

            $task = $this->ensureTaskExists($taskId);
            $this->ensureManagerCanManageTask($authUser, $task);

            $this->logActivity($taskId, (int)$authUser['id'], 'delete', 'Manager xóa task.');

            $stmt = $this->db->prepare("
                DELETE FROM tasks
                WHERE id = :task_id
            ");

            $stmt->execute([
                ':task_id' => $taskId,
            ]);

            $this->json([
                'status' => 'success',
                'message' => 'Đã xóa task.'
            ]);
        } catch (Throwable $e) {
            $this->json([
                'status' => 'error',
                'message' => 'Không thể xóa task: ' . $e->getMessage()
            ], 400);
        }
    }

    public function updateStatus($id = null): void {
        try {
            $authUser = $this->requireRole(['manager', 'employee']);
            $taskId = $this->resolveId($id);

            if (!$taskId) {
                $this->json([
                    'status' => 'error',
                    'message' => 'Thiếu ID task.'
                ], 400);
            }

            $input = $this->getInput();
            $nextStatus = $this->normalizeStatus($input['status'] ?? '');

            $task = $this->ensureTaskExists($taskId);

            if (!$this->canAccessTask($authUser, $task)) {
                $this->json([
                    'status' => 'error',
                    'message' => 'Bạn không có quyền cập nhật task này.'
                ], 403);
            }

            $role = strtolower((string)$authUser['role']);

            if ($role === 'manager') {
                $this->ensureManagerCanManageTask($authUser, $task);

                $this->updateTaskStatusInternal($taskId, $nextStatus);
                $this->logActivity($taskId, (int)$authUser['id'], 'status_change', 'Manager đổi trạng thái task sang ' . $nextStatus . '.');

                $updated = $this->fetchTaskById($taskId);

                $this->json([
                    'status' => 'success',
                    'message' => 'Cập nhật trạng thái task thành công.',
                    'data' => $updated
                ]);
            }

            if ((int)$task['assignee_id'] !== (int)$authUser['id']) {
                $this->json([
                    'status' => 'error',
                    'message' => 'Employee chỉ được cập nhật task được giao trực tiếp cho mình.'
                ], 403);
            }

            if ($nextStatus === 'Done') {
                $this->json([
                    'status' => 'error',
                    'message' => 'Bạn không có quyền hoàn thành thao tác này.'
                ], 403);
            }

            if ($nextStatus !== 'Review') {
                $this->json([
                    'status' => 'error',
                    'message' => 'Employee chỉ được gửi task sang Chờ Duyệt.'
                ], 422);
            }

            if ($task['status'] === 'Done') {
                $this->json([
                    'status' => 'error',
                    'message' => 'Task đã hoàn thành không thể gửi lại Chờ Duyệt.'
                ], 422);
            }

            if ($task['status'] === 'Review') {
                $this->json([
                    'status' => 'success',
                    'message' => 'Task đã đang ở trạng thái Chờ Duyệt.',
                    'data' => $task
                ]);
            }

            if ($task['status'] === 'To do') {
                $this->updateTaskStatusInternal($taskId, 'Doing');
            }

            $this->updateTaskStatusInternal($taskId, 'Review');
            $this->logActivity($taskId, (int)$authUser['id'], 'status_change', 'Employee gửi task sang Chờ Duyệt.');

            if (!empty($task['project_manager_id'])) {
                $this->notifyUser((int)$task['project_manager_id'], 'Task "' . ($task['title'] ?? 'Không rõ') . '" đang chờ duyệt.');
            }

            if (!empty($task['assigner_id'])) {
                $this->notifyUser((int)$task['assigner_id'], 'Task "' . ($task['title'] ?? 'Không rõ') . '" đang chờ duyệt.');
            }

            if (!empty($task['assignee_manager_id'])) {
                $this->notifyUser((int)$task['assignee_manager_id'], 'Task "' . ($task['title'] ?? 'Không rõ') . '" đang chờ duyệt.');
            }

            $updated = $this->fetchTaskById($taskId);

            $this->json([
                'status' => 'success',
                'message' => 'Task đã được gửi sang Chờ Duyệt.',
                'data' => $updated
            ]);
        } catch (Throwable $e) {
            $this->json([
                'status' => 'error',
                'message' => 'Không thể cập nhật trạng thái task: ' . $e->getMessage()
            ], 400);
        }
    }

    public function submit($id = null): void {
        try {
            $authUser = $this->requireRole(['employee']);
            $taskId = $this->resolveId($id);

            if (!$taskId) {
                $this->json([
                    'status' => 'error',
                    'message' => 'Thiếu ID task.'
                ], 400);
            }

            $task = $this->ensureTaskExists($taskId);

            if ((int)$task['assignee_id'] !== (int)$authUser['id']) {
                $this->json([
                    'status' => 'error',
                    'message' => 'Bạn chỉ được submit task được giao cho mình.'
                ], 403);
            }

            if ($task['status'] === 'Done') {
                $this->json([
                    'status' => 'error',
                    'message' => 'Task đã hoàn thành không thể gửi lại Chờ Duyệt.'
                ], 422);
            }

            if ($task['status'] === 'Review') {
                $this->json([
                    'status' => 'success',
                    'message' => 'Task đã đang ở trạng thái Chờ Duyệt.',
                    'data' => $task
                ]);
            }

            if ($task['status'] === 'To do') {
                $this->updateTaskStatusInternal($taskId, 'Doing');
            }

            $this->updateTaskStatusInternal($taskId, 'Review');
            $this->logActivity($taskId, (int)$authUser['id'], 'status_change', 'Employee gửi task sang Chờ Duyệt.');

            if (!empty($task['project_manager_id'])) {
                $this->notifyUser((int)$task['project_manager_id'], 'Task "' . ($task['title'] ?? 'Không rõ') . '" đang chờ duyệt.');
            }

            if (!empty($task['assigner_id'])) {
                $this->notifyUser((int)$task['assigner_id'], 'Task "' . ($task['title'] ?? 'Không rõ') . '" đang chờ duyệt.');
            }

            if (!empty($task['assignee_manager_id'])) {
                $this->notifyUser((int)$task['assignee_manager_id'], 'Task "' . ($task['title'] ?? 'Không rõ') . '" đang chờ duyệt.');
            }

            $updated = $this->fetchTaskById($taskId);

            $this->json([
                'status' => 'success',
                'message' => 'Task đã được gửi sang Chờ Duyệt để Manager duyệt.',
                'data' => $updated
            ]);
        } catch (Throwable $e) {
            $this->json([
                'status' => 'error',
                'message' => 'Không thể submit task: ' . $e->getMessage()
            ], 400);
        }
    }

    public function approve($id = null): void {
        try {
            $authUser = $this->requireRole(['manager']);
            $taskId = $this->resolveId($id);

            if (!$taskId) {
                $this->json([
                    'status' => 'error',
                    'message' => 'Thiếu ID task.'
                ], 400);
            }

            $task = $this->ensureTaskExists($taskId);
            $this->ensureManagerCanManageTask($authUser, $task);

            if ($task['status'] !== 'Review') {
                $this->json([
                    'status' => 'error',
                    'message' => 'Chỉ task đang Chờ Duyệt mới được approve.'
                ], 422);
            }

            $this->updateTaskStatusInternal($taskId, 'Done');
            $this->logActivity($taskId, (int)$authUser['id'], 'status_change', 'Manager approve task sang Hoàn Thành.');
            $this->notifyUser((int)$task['assignee_id'], 'Task "' . ($task['title'] ?? 'Không rõ') . '" đã được Manager duyệt Hoàn Thành.');

            $updated = $this->fetchTaskById($taskId);

            $this->json([
                'status' => 'success',
                'message' => 'Đã approve task sang Hoàn Thành.',
                'data' => $updated
            ]);
        } catch (Throwable $e) {
            $this->json([
                'status' => 'error',
                'message' => 'Không thể approve task: ' . $e->getMessage()
            ], 400);
        }
    }

    public function reject($id = null): void {
        try {
            $authUser = $this->requireRole(['manager']);
            $taskId = $this->resolveId($id);

            if (!$taskId) {
                $this->json([
                    'status' => 'error',
                    'message' => 'Thiếu ID task.'
                ], 400);
            }

            $input = $this->getInput();
            $reason = trim((string)($input['reason'] ?? $input['reject_reason'] ?? 'Cần chỉnh sửa thêm.'));

            $task = $this->ensureTaskExists($taskId);
            $this->ensureManagerCanManageTask($authUser, $task);

            if ($task['status'] !== 'Review') {
                $this->json([
                    'status' => 'error',
                    'message' => 'Chỉ task đang Chờ Duyệt mới được reject.'
                ], 422);
            }

            if ($this->rejectReasonEnabled()) {
                $stmt = $this->db->prepare("
                    UPDATE tasks
                    SET status = 'Doing',
                        reject_reason = :reject_reason,
                        updated_at = NOW()
                    WHERE id = :task_id
                ");

                $stmt->execute([
                    ':reject_reason' => $reason,
                    ':task_id' => $taskId,
                ]);
            } else {
                $this->updateTaskStatusInternal($taskId, 'Doing');
            }

            $this->insertRejectComment($taskId, (int)$authUser['id'], $reason);
            $this->logActivity($taskId, (int)$authUser['id'], 'status_change', 'Manager reject task về Cần sửa. Lý do: ' . $reason);
            $this->notifyUser((int)$task['assignee_id'], 'Task "' . ($task['title'] ?? 'Không rõ') . '" bị reject: ' . $reason);

            $updated = $this->fetchTaskById($taskId);

            $this->json([
                'status' => 'success',
                'message' => 'Đã reject task về Cần sửa.',
                'data' => $updated
            ]);
        } catch (Throwable $e) {
            $this->json([
                'status' => 'error',
                'message' => 'Không thể reject task: ' . $e->getMessage()
            ], 400);
        }
    }

    public function getReviewTasks(): void {
        try {
            $authUser = $this->requireRole(['manager']);

            $tasks = $this->fetchTasks($authUser, [
                'status' => 'Review',
                'project_id' => $_GET['project_id'] ?? null,
                'search' => $_GET['search'] ?? '',
            ]);

            $this->json([
                'status' => 'success',
                'data' => $tasks
            ]);
        } catch (Throwable $e) {
            $this->json([
                'status' => 'error',
                'message' => 'Không thể tải task chờ duyệt: ' . $e->getMessage()
            ], 400);
        }
    }

    public function getAll(): void {
        $this->index();
    }

    public function getById($id = null): void {
        $this->show($id);
    }

    public function create(): void {
        $this->store();
    }

    public function delete($id = null): void {
        $this->destroy();
    }
}