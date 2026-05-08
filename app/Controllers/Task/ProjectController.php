 
<?php
namespace App\Controllers\Task;

use Core\Database;
use Core\JwtHandler;
use PDO;
use Throwable;

class ProjectController {
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

    private function normalizeStatus($status): string {
        $status = trim((string)$status);
        $allowed = ['Active', 'Completed', 'Archived'];

        return in_array($status, $allowed, true) ? $status : 'Active';
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

    private function notifyUser(?int $userId, string $message): void {
        $userId = (int)($userId ?? 0);
        $message = trim($message);

        if ($userId <= 0 || $message === '') {
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
                    0
                )
            " );

            $stmt->execute([
                ':user_id' => $userId,
                ':message' => $message,
            ]);
        } catch (Throwable $e) {
            // Notification không được làm hỏng flow chính.
        }
    }

    private function notifyRole(string $role, string $message): void {
        $role = strtolower(trim($role));
        $message = trim($message);

        if ($role === '' || $message === '') {
            return;
        }

        try {
            $stmt = $this->db->prepare("
                SELECT id
                FROM employees
                WHERE role = :role
                  AND status = 'active'
                  AND deleted_at IS NULL
            " );

            $stmt->execute([
                ':role' => $role,
            ]);

            $userIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

            foreach ($userIds as $userId) {
                $this->notifyUser((int)$userId, $message);
            }
        } catch (Throwable $e) {
            // Notification không được làm hỏng flow chính.
        }
    }

    private function notifyAdmins(string $message): void {
        $this->notifyRole('admin', $message);
    }

    private function notifyUsers(array $userIds, string $message): void {
        foreach (array_unique(array_map('intval', $userIds)) as $userId) {
            $this->notifyUser($userId, $message);
        }
    }

    private function projectMembersEnabled(): bool {
        return $this->tableExists('project_members');
    }

    private function clientVisibilityEnabled(): bool {
        return $this->columnExists('tasks', 'is_client_visible');
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
                manager.email AS manager_email,

                client.full_name AS client_name,
                client.email AS client_email
            FROM projects p
            LEFT JOIN employees manager ON manager.id = p.manager_id
            LEFT JOIN employees client ON client.id = p.client_id
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
                'message' => 'Chỉ Manager được thao tác project.'
            ], 403);
        }

        if ((int)$project['manager_id'] !== (int)$authUser['id']) {
            $this->json([
                'status' => 'error',
                'message' => 'Bạn chỉ được thao tác project do mình quản lý.'
            ], 403);
        }
    }

    private function isActiveClient(?int $clientId): bool {
        if (!$clientId) {
            return true;
        }

        $stmt = $this->db->prepare("
            SELECT COUNT(*)
            FROM employees
            WHERE id = :id
              AND role = 'client'
              AND status = 'active'
              AND deleted_at IS NULL
        ");

        $stmt->execute([
            ':id' => $clientId,
        ]);

        return (int)$stmt->fetchColumn() > 0;
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

    private function normalizeMemberIds($memberIds): array {
        if ($memberIds === null || $memberIds === '') {
            return [];
        }

        if (!is_array($memberIds)) {
            $memberIds = [$memberIds];
        }

        $ids = [];

        foreach ($memberIds as $id) {
            $id = (int)$id;

            if ($id > 0) {
                $ids[] = $id;
            }
        }

        return array_values(array_unique($ids));
    }

    private function validateActiveMemberIds(array $memberIds): array {
        if (empty($memberIds)) {
            return [];
        }

        $validIds = [];

        foreach ($memberIds as $employeeId) {
            if ($this->isActiveEmployee((int)$employeeId)) {
                $validIds[] = (int)$employeeId;
            }
        }

        return array_values(array_unique($validIds));
    }

    private function canAccessProject(array $authUser, array $project): bool {
        $role = strtolower((string)($authUser['role'] ?? ''));
        $userId = (int)($authUser['id'] ?? 0);
        $projectId = (int)$project['id'];

        if ($role === 'admin') {
            return true;
        }

        if ($role === 'manager') {
            return (int)$project['manager_id'] === $userId;
        }

        if ($role === 'client') {
            return (int)$project['client_id'] === $userId;
        }

        if ($role === 'employee') {
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
                    ':employee_id' => $userId,
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
                ':employee_id' => $userId,
            ]);

            return (int)$stmt->fetchColumn() > 0;
        }

        return false;
    }

    private function selectProjectList(array $authUser, array $filters = []): array {
        $role = strtolower((string)$authUser['role']);
        $userId = (int)$authUser['id'];

        $where = [];
        $params = [];

        if (!empty($filters['status'])) {
            $where[] = "p.status = :status";
            $params[':status'] = $this->normalizeStatus($filters['status']);
        }

        if (!empty($filters['search'])) {
            $where[] = "(p.name LIKE :search_name OR p.description LIKE :search_description OR client.full_name LIKE :search_client)";
            $params[':search_name'] = '%' . trim((string)$filters['search']) . '%';
            $params[':search_description'] = '%' . trim((string)$filters['search']) . '%';
            $params[':search_client'] = '%' . trim((string)$filters['search']) . '%';
        }

        if ($role === 'manager') {
            $where[] = "p.manager_id = :manager_id";
            $params[':manager_id'] = $userId;
        }

        if ($role === 'client') {
            $where[] = "p.client_id = :client_id";
            $params[':client_id'] = $userId;
        }

        if ($role === 'employee') {
            if ($this->projectMembersEnabled()) {
                $where[] = "(
                    EXISTS (
                        SELECT 1
                        FROM project_members pm
                        WHERE pm.project_id = p.id
                          AND pm.employee_id = :employee_member_id
                          AND pm.status = 'active'
                    )
                    OR EXISTS (
                        SELECT 1
                        FROM tasks tx
                        WHERE tx.project_id = p.id
                          AND tx.assignee_id = :employee_task_id
                    )
                )";
                $params[':employee_member_id'] = $userId;
                $params[':employee_task_id'] = $userId;
            } else {
                $where[] = "EXISTS (
                    SELECT 1
                    FROM tasks tx
                    WHERE tx.project_id = p.id
                      AND tx.assignee_id = :employee_task_id
                )";
                $params[':employee_task_id'] = $userId;
            }
        }

        $taskJoinExtra = '';

        if ($role === 'client' && $this->clientVisibilityEnabled()) {
            $taskJoinExtra = "AND t.is_client_visible = 1";
        }

        $whereSql = count($where) > 0 ? ('WHERE ' . implode(' AND ', $where)) : '';

        $memberCountSql = $this->projectMembersEnabled()
            ? "(SELECT COUNT(*) FROM project_members pmc WHERE pmc.project_id = p.id AND pmc.status = 'active')"
            : "(SELECT COUNT(DISTINCT tx.assignee_id) FROM tasks tx WHERE tx.project_id = p.id AND tx.assignee_id IS NOT NULL)";

        $sql = "
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
                manager.email AS manager_email,

                client.full_name AS client_name,
                client.email AS client_email,

                COUNT(t.id) AS total_tasks,
                SUM(CASE WHEN t.status = 'Done' THEN 1 ELSE 0 END) AS done_tasks,
                SUM(CASE WHEN t.deadline < CURDATE() AND t.status <> 'Done' THEN 1 ELSE 0 END) AS overdue_tasks,
                MIN(t.deadline) AS nearest_deadline,

                {$memberCountSql} AS member_count
            FROM projects p
            LEFT JOIN employees manager ON manager.id = p.manager_id
            LEFT JOIN employees client ON client.id = p.client_id
            LEFT JOIN tasks t ON t.project_id = p.id {$taskJoinExtra}
            {$whereSql}
            GROUP BY
                p.id,
                p.name,
                p.description,
                p.manager_id,
                p.client_id,
                p.status,
                p.created_at,
                p.updated_at,
                manager.full_name,
                manager.email,
                client.full_name,
                client.email
            ORDER BY
                CASE WHEN MIN(t.deadline) IS NULL THEN 1 ELSE 0 END,
                MIN(t.deadline) ASC,
                p.created_at DESC,
                p.id DESC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($projects as &$project) {
            $project = $this->decorateProject($project);
        }

        unset($project);

        return $projects;
    }

    private function decorateProject(array $project): array {
        $project['id'] = (int)$project['id'];
        $project['manager_id'] = $project['manager_id'] !== null ? (int)$project['manager_id'] : null;
        $project['client_id'] = $project['client_id'] !== null ? (int)$project['client_id'] : null;

        $project['total_tasks'] = (int)($project['total_tasks'] ?? 0);
        $project['done_tasks'] = (int)($project['done_tasks'] ?? 0);
        $project['overdue_tasks'] = (int)($project['overdue_tasks'] ?? 0);
        $project['member_count'] = (int)($project['member_count'] ?? 0);

        $project['progress'] = $project['total_tasks'] > 0
            ? (int)round(($project['done_tasks'] / $project['total_tasks']) * 100)
            : 0;

        return $project;
    }

    private function fetchProjectMembers(int $projectId): array {
        if ($this->projectMembersEnabled()) {
            $stmt = $this->db->prepare("
                SELECT
                    e.id,
                    e.employee_code,
                    e.full_name,
                    e.email,
                    e.role,
                    e.avatar,
                    e.status,
                    e.department_id,
                    e.position_id,
                    d.name AS department_name,
                    p.name AS position_name,
                    pm.created_at AS joined_at
                FROM project_members pm
                INNER JOIN employees e ON e.id = pm.employee_id
                LEFT JOIN departments d ON d.id = e.department_id
                LEFT JOIN positions p ON p.id = e.position_id
                WHERE pm.project_id = :project_id
                  AND pm.status = 'active'
                  AND e.deleted_at IS NULL
                ORDER BY e.full_name ASC
            ");

            $stmt->execute([
                ':project_id' => $projectId,
            ]);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        $stmt = $this->db->prepare("
            SELECT DISTINCT
                e.id,
                e.employee_code,
                e.full_name,
                e.email,
                e.role,
                e.avatar,
                e.status,
                e.department_id,
                e.position_id,
                d.name AS department_name,
                p.name AS position_name,
                NULL AS joined_at
            FROM tasks t
            INNER JOIN employees e ON e.id = t.assignee_id
            LEFT JOIN departments d ON d.id = e.department_id
            LEFT JOIN positions p ON p.id = e.position_id
            WHERE t.project_id = :project_id
              AND e.deleted_at IS NULL
            ORDER BY e.full_name ASC
        ");

        $stmt->execute([
            ':project_id' => $projectId,
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function syncProjectMembers(int $projectId, array $memberIds): void {
        if (!$this->projectMembersEnabled()) {
            return;
        }

        $validMemberIds = $this->validateActiveMemberIds($memberIds);

        $this->db->prepare("
            DELETE FROM project_members
            WHERE project_id = :project_id
        ")->execute([
            ':project_id' => $projectId,
        ]);

        if (empty($validMemberIds)) {
            return;
        }

        foreach ($validMemberIds as $employeeId) {
            $this->insertProjectMember($projectId, $employeeId);
        }
    }

    private function insertProjectMember(int $projectId, int $employeeId): void {
        if (!$this->projectMembersEnabled()) {
            $this->json([
                'status' => 'error',
                'message' => 'Bảng project_members chưa tồn tại. Không thể thêm thành viên project.'
            ], 400);
        }

        if (!$this->isActiveEmployee($employeeId)) {
            $this->json([
                'status' => 'error',
                'message' => 'Chỉ được thêm Employee đã active vào project.'
            ], 422);
        }

        $hasRoleColumn = $this->columnExists('project_members', 'role');
        $hasStatusColumn = $this->columnExists('project_members', 'status');

        $columns = ['project_id', 'employee_id'];
        $values = [':project_id', ':employee_id'];
        $params = [
            ':project_id' => $projectId,
            ':employee_id' => $employeeId,
        ];

        if ($hasRoleColumn) {
            $columns[] = 'role';
            $values[] = ':role';
            $params[':role'] = 'member';
        }

        if ($hasStatusColumn) {
            $columns[] = 'status';
            $values[] = ':status';
            $params[':status'] = 'active';
        }

        $sql = "
            INSERT INTO project_members
            (" . implode(', ', $columns) . ")
            VALUES
            (" . implode(', ', $values) . ")
        ";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
        } catch (Throwable $e) {
            // Nếu bị duplicate thì coi như thành viên đã tồn tại.
        }
    }

    public function index(): void {
        try {
            $authUser = $this->requireRole(['admin', 'manager', 'employee', 'client']);

            $projects = $this->selectProjectList($authUser, [
                'status' => $_GET['status'] ?? '',
                'search' => $_GET['search'] ?? ($_GET['q'] ?? ''),
            ]);

            $this->json([
                'status' => 'success',
                'data' => $projects
            ]);
        } catch (Throwable $e) {
            $this->json([
                'status' => 'error',
                'message' => 'Không thể tải danh sách project: ' . $e->getMessage()
            ], 400);
        }
    }

    public function options(): void {
        try {
            $this->requireRole(['admin', 'manager']);

            $clients = $this->db->query("
                SELECT
                    e.id,
                    e.employee_code,
                    e.full_name,
                    e.email,
                    e.role,
                    e.status,
                    e.avatar
                FROM employees e
                WHERE e.deleted_at IS NULL
                  AND e.status = 'active'
                  AND e.role = 'client'
                ORDER BY e.full_name ASC, e.email ASC
            ")->fetchAll(PDO::FETCH_ASSOC);

            $employees = $this->db->query("
                SELECT
                    e.id,
                    e.employee_code,
                    e.full_name,
                    e.email,
                    e.role,
                    e.status,
                    e.avatar,
                    d.name AS department_name,
                    p.name AS position_name
                FROM employees e
                LEFT JOIN departments d ON d.id = e.department_id
                LEFT JOIN positions p ON p.id = e.position_id
                WHERE e.deleted_at IS NULL
                  AND e.status = 'active'
                  AND e.role = 'employee'
                ORDER BY e.full_name ASC, e.email ASC
            ")->fetchAll(PDO::FETCH_ASSOC);

            $managers = $this->db->query("
                SELECT
                    e.id,
                    e.employee_code,
                    e.full_name,
                    e.email,
                    e.role,
                    e.status,
                    e.avatar
                FROM employees e
                WHERE e.deleted_at IS NULL
                  AND e.status = 'active'
                  AND e.role = 'manager'
                ORDER BY e.full_name ASC, e.email ASC
            ")->fetchAll(PDO::FETCH_ASSOC);

            $this->json([
                'status' => 'success',
                'data' => [
                    'clients' => $clients,
                    'employees' => $employees,
                    'members' => $employees,
                    'managers' => $managers,
                    'statuses' => ['Active', 'Completed', 'Archived'],
                    'project_members_enabled' => $this->projectMembersEnabled(),
                ]
            ]);
        } catch (Throwable $e) {
            $this->json([
                'status' => 'error',
                'message' => 'Không thể tải dữ liệu project options: ' . $e->getMessage()
            ], 400);
        }
    }

    public function show($id = null): void {
        try {
            $authUser = $this->requireRole(['admin', 'manager', 'employee', 'client']);
            $projectId = $this->resolveId($id);

            if (!$projectId) {
                $this->json([
                    'status' => 'error',
                    'message' => 'Thiếu ID project.'
                ], 400);
            }

            $project = $this->ensureProjectExists($projectId);

            if (!$this->canAccessProject($authUser, $project)) {
                $this->json([
                    'status' => 'error',
                    'message' => 'Bạn không có quyền xem project này.'
                ], 403);
            }

            $projects = $this->selectProjectList($authUser, []);
            $decorated = null;

            foreach ($projects as $item) {
                if ((int)$item['id'] === $projectId) {
                    $decorated = $item;
                    break;
                }
            }

            if (!$decorated) {
                $decorated = $this->decorateProject($project);
            }

            $decorated['members'] = $this->fetchProjectMembers($projectId);

            $this->json([
                'status' => 'success',
                'data' => $decorated
            ]);
        } catch (Throwable $e) {
            $this->json([
                'status' => 'error',
                'message' => 'Không thể tải project: ' . $e->getMessage()
            ], 400);
        }
    }

    public function store(): void {
        try {
            $authUser = $this->requireRole(['manager']);
            $input = $this->getInput();

            $name = trim((string)($input['name'] ?? ''));
            $description = trim((string)($input['description'] ?? ''));
            $clientId = $this->normalizeNullableId($input['client_id'] ?? null);
            $status = $this->normalizeStatus($input['status'] ?? 'Active');
            $memberIds = $this->normalizeMemberIds($input['member_ids'] ?? []);

            if ($name === '') {
                $this->json([
                    'status' => 'error',
                    'message' => 'Tên project không được để trống.'
                ], 422);
            }

            if (!$this->isActiveClient($clientId)) {
                $this->json([
                    'status' => 'error',
                    'message' => 'Client được gán vào project phải là tài khoản active đã được Admin duyệt.'
                ], 422);
            }

            $validMemberIds = $this->validateActiveMemberIds($memberIds);

            if (count($memberIds) !== count($validMemberIds)) {
                $this->json([
                    'status' => 'error',
                    'message' => 'Danh sách members chỉ được chứa Employee active đã được Admin duyệt.'
                ], 422);
            }

            $stmt = $this->db->prepare("
                INSERT INTO projects
                (
                    name,
                    description,
                    manager_id,
                    client_id,
                    status
                )
                VALUES
                (
                    :name,
                    :description,
                    :manager_id,
                    :client_id,
                    :status
                )
            ");

            $stmt->execute([
                ':name' => $name,
                ':description' => $description !== '' ? $description : null,
                ':manager_id' => (int)$authUser['id'],
                ':client_id' => $clientId,
                ':status' => $status,
            ]);

            $projectId = (int)$this->db->lastInsertId();

            if ($this->projectMembersEnabled()) {
                $this->syncProjectMembers($projectId, $validMemberIds);
            }

            $project = $this->getProjectById($projectId);

            if ($project) {
                $project = $this->decorateProject(array_merge($project, [
                    'total_tasks' => 0,
                    'done_tasks' => 0,
                    'overdue_tasks' => 0,
                    'member_count' => count($validMemberIds),
                    'nearest_deadline' => null,
                ]));
                $project['members'] = $this->fetchProjectMembers($projectId);
            }

            $managerName = $authUser['full_name'] ?? $authUser['email'] ?? ('Manager #' . (int)$authUser['id']);

            $this->notifyAdmins(
                'Manager ' . $managerName . ' vừa tạo project "' . $name . '".'
            );

            if (!empty($validMemberIds)) {
                $this->notifyUsers(
                    $validMemberIds,
                    'Bạn vừa được thêm vào project "' . $name . '".'
                );
            }

            if (!empty($clientId)) {
                $this->notifyUser(
                    $clientId,
                    'Bạn vừa được gán theo dõi project "' . $name . '" trên Client Portal.'
                );
            }

            $this->json([
                'status' => 'success',
                'message' => $this->projectMembersEnabled()
                    ? 'Tạo project thành công.'
                    : 'Tạo project thành công. Chưa có bảng project_members nên members chưa được lưu.',
                'data' => $project
            ], 201);
        } catch (Throwable $e) {
            $this->json([
                'status' => 'error',
                'message' => 'Không thể tạo project: ' . $e->getMessage()
            ], 400);
        }
    }

    public function update($id = null): void {
        try {
            $authUser = $this->requireRole(['manager']);
            $projectId = $this->resolveId($id);

            if (!$projectId) {
                $this->json([
                    'status' => 'error',
                    'message' => 'Thiếu ID project.'
                ], 400);
            }

            $project = $this->ensureProjectExists($projectId);
            $this->ensureManagerOwnsProject($authUser, $project);

            $input = $this->getInput();

            $name = array_key_exists('name', $input)
                ? trim((string)$input['name'])
                : (string)$project['name'];

            if ($name === '') {
                $this->json([
                    'status' => 'error',
                    'message' => 'Tên project không được để trống.'
                ], 422);
            }

            $description = array_key_exists('description', $input)
                ? trim((string)$input['description'])
                : ($project['description'] ?? null);

            $clientId = array_key_exists('client_id', $input)
                ? $this->normalizeNullableId($input['client_id'])
                : $this->normalizeNullableId($project['client_id'] ?? null);

            $status = array_key_exists('status', $input)
                ? $this->normalizeStatus($input['status'])
                : $this->normalizeStatus($project['status']);

            if (!$this->isActiveClient($clientId)) {
                $this->json([
                    'status' => 'error',
                    'message' => 'Client được gán vào project phải là tài khoản active đã được Admin duyệt.'
                ], 422);
            }

            if (array_key_exists('member_ids', $input)) {
                $memberIds = $this->normalizeMemberIds($input['member_ids']);
                $validMemberIds = $this->validateActiveMemberIds($memberIds);

                if (count($memberIds) !== count($validMemberIds)) {
                    $this->json([
                        'status' => 'error',
                        'message' => 'Danh sách members chỉ được chứa Employee active đã được Admin duyệt.'
                    ], 422);
                }
            } else {
                $validMemberIds = null;
            }

            $stmt = $this->db->prepare("
                UPDATE projects
                SET name = :name,
                    description = :description,
                    client_id = :client_id,
                    status = :status,
                    updated_at = NOW()
                WHERE id = :id
                  AND manager_id = :manager_id
            ");

            $stmt->execute([
                ':id' => $projectId,
                ':manager_id' => (int)$authUser['id'],
                ':name' => $name,
                ':description' => $description !== '' ? $description : null,
                ':client_id' => $clientId,
                ':status' => $status,
            ]);

            if (is_array($validMemberIds) && $this->projectMembersEnabled()) {
                $this->syncProjectMembers($projectId, $validMemberIds);
            }

            $updated = $this->getProjectById($projectId);

            if ($updated) {
                $updated = $this->decorateProject(array_merge($updated, [
                    'total_tasks' => 0,
                    'done_tasks' => 0,
                    'overdue_tasks' => 0,
                    'member_count' => count($this->fetchProjectMembers($projectId)),
                    'nearest_deadline' => null,
                ]));
                $updated['members'] = $this->fetchProjectMembers($projectId);
            }

            if (is_array($validMemberIds) && !empty($validMemberIds)) {
                $this->notifyUsers(
                    $validMemberIds,
                    'Project "' . $name . '" vừa được Manager cập nhật thông tin hoặc danh sách thành viên.'
                );
            }

            if (!empty($clientId) && (int)$clientId !== (int)($project['client_id'] ?? 0)) {
                $this->notifyUser(
                    $clientId,
                    'Bạn vừa được gán theo dõi project "' . $name . '" trên Client Portal.'
                );
            }

            $this->json([
                'status' => 'success',
                'message' => 'Cập nhật project thành công.',
                'data' => $updated
            ]);
        } catch (Throwable $e) {
            $this->json([
                'status' => 'error',
                'message' => 'Không thể cập nhật project: ' . $e->getMessage()
            ], 400);
        }
    }

    public function delete($id = null): void {
        try {
            $authUser = $this->requireRole(['manager']);
            $projectId = $this->resolveId($id);

            if (!$projectId) {
                $this->json([
                    'status' => 'error',
                    'message' => 'Thiếu ID project.'
                ], 400);
            }

            $project = $this->ensureProjectExists($projectId);
            $this->ensureManagerOwnsProject($authUser, $project);

            $stmt = $this->db->prepare("
                DELETE FROM projects
                WHERE id = :id
                  AND manager_id = :manager_id
            ");

            $stmt->execute([
                ':id' => $projectId,
                ':manager_id' => (int)$authUser['id'],
            ]);

            $this->json([
                'status' => 'success',
                'message' => 'Đã xóa project.'
            ]);
        } catch (Throwable $e) {
            $this->json([
                'status' => 'error',
                'message' => 'Không thể xóa project: ' . $e->getMessage()
            ], 400);
        }
    }

    public function members($id = null): void {
        try {
            $authUser = $this->requireRole(['admin', 'manager', 'employee']);
            $projectId = $this->resolveId($id);

            if (!$projectId) {
                $this->json([
                    'status' => 'error',
                    'message' => 'Thiếu ID project.'
                ], 400);
            }

            $project = $this->ensureProjectExists($projectId);

            if (!$this->canAccessProject($authUser, $project)) {
                $this->json([
                    'status' => 'error',
                    'message' => 'Bạn không có quyền xem members của project này.'
                ], 403);
            }

            $this->json([
                'status' => 'success',
                'data' => $this->fetchProjectMembers($projectId)
            ]);
        } catch (Throwable $e) {
            $this->json([
                'status' => 'error',
                'message' => 'Không thể tải members project: ' . $e->getMessage()
            ], 400);
        }
    }

    public function addMember($id = null): void {
        try {
            $authUser = $this->requireRole(['manager']);
            $projectId = $this->resolveId($id);

            if (!$projectId) {
                $this->json([
                    'status' => 'error',
                    'message' => 'Thiếu ID project.'
                ], 400);
            }

            $project = $this->ensureProjectExists($projectId);
            $this->ensureManagerOwnsProject($authUser, $project);

            $input = $this->getInput();
            $employeeId = $this->normalizeNullableId($input['employee_id'] ?? $input['member_id'] ?? null);

            if (!$employeeId) {
                $this->json([
                    'status' => 'error',
                    'message' => 'Thiếu ID employee cần thêm vào project.'
                ], 400);
            }

            $this->insertProjectMember($projectId, $employeeId);

            $projectName = $project['name'] ?? ('Project #' . $projectId);
            $managerName = $authUser['full_name'] ?? $authUser['email'] ?? ('Manager #' . (int)$authUser['id']);

            $this->notifyUser(
                $employeeId,
                'Manager ' . $managerName . ' vừa thêm bạn vào project "' . $projectName . '".'
            );

            $this->json([
                'status' => 'success',
                'message' => 'Đã thêm employee active vào project.',
                'data' => $this->fetchProjectMembers($projectId)
            ]);
        } catch (Throwable $e) {
            $this->json([
                'status' => 'error',
                'message' => 'Không thể thêm member: ' . $e->getMessage()
            ], 400);
        }
    }

    public function removeMember($id = null, $employeeId = null): void {
        try {
            $authUser = $this->requireRole(['manager']);
            $projectId = $this->resolveId($id);
            $memberId = $this->resolveId($employeeId);

            if (!$projectId) {
                $this->json([
                    'status' => 'error',
                    'message' => 'Thiếu ID project.'
                ], 400);
            }

            if (!$memberId) {
                $memberId = $this->resolveId($_GET['employeeId'] ?? $_GET['employee_id'] ?? null);
            }

            if (!$memberId) {
                $this->json([
                    'status' => 'error',
                    'message' => 'Thiếu ID employee cần xóa khỏi project.'
                ], 400);
            }

            $project = $this->ensureProjectExists($projectId);
            $this->ensureManagerOwnsProject($authUser, $project);

            if (!$this->projectMembersEnabled()) {
                $this->json([
                    'status' => 'error',
                    'message' => 'Bảng project_members chưa tồn tại. Không thể xóa member.'
                ], 400);
            }

            $stmt = $this->db->prepare("
                DELETE FROM project_members
                WHERE project_id = :project_id
                  AND employee_id = :employee_id
            ");

            $stmt->execute([
                ':project_id' => $projectId,
                ':employee_id' => $memberId,
            ]);

            $this->json([
                'status' => 'success',
                'message' => 'Đã xóa member khỏi project.',
                'data' => $this->fetchProjectMembers($projectId)
            ]);
        } catch (Throwable $e) {
            $this->json([
                'status' => 'error',
                'message' => 'Không thể xóa member: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * Backward-compatible aliases.
     */
    public function getAll(): void {
        $this->index();
    }

    public function getById($id = null): void {
        $this->show($id);
    }

    public function create(): void {
        $this->store();
    }

    public function destroy($id = null): void {
        $this->delete($id);
    }
}
 