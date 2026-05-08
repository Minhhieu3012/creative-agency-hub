<?php
namespace App\Controllers\Task;

use Core\Database;
use Core\JwtHandler;
use PDO;
use Throwable;

class TaskCommentController {
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
            return (int)$idOrParams;
        }

        return null;
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

    private function hasCommentVisibility(): bool {
        return $this->columnExists('task_comments', 'visibility');
    }

    private function getTaskContext(int $taskId): ?array {
        $clientVisibleSelect = $this->columnExists('tasks', 'is_client_visible')
            ? 't.is_client_visible'
            : '1 AS is_client_visible';

        $stmt = $this->db->prepare("
            SELECT
                t.id,
                t.title,
                t.project_id,
                t.assignee_id,
                t.assigner_id,
                t.watcher_id,
                {$clientVisibleSelect},

                p.name AS project_name,
                p.manager_id AS project_manager_id,
                p.client_id AS project_client_id
            FROM tasks t
            LEFT JOIN projects p ON p.id = t.project_id
            WHERE t.id = :task_id
            LIMIT 1
        ");

        $stmt->execute([
            ':task_id' => $taskId,
        ]);

        $task = $stmt->fetch(PDO::FETCH_ASSOC);

        return $task ?: null;
    }

    private function getCommentById(int $commentId): ?array {
        $visibilitySelect = $this->hasCommentVisibility()
            ? 'tc.visibility'
            : "'internal' AS visibility";

        $stmt = $this->db->prepare("
            SELECT
                tc.id,
                tc.task_id,
                tc.user_id,
                tc.comment_text,
                {$visibilitySelect},
                tc.created_at,
                tc.updated_at,

                u.full_name AS user_name,
                u.email AS user_email,
                u.role AS user_role,

                t.title AS task_title,
                t.assignee_id,
                p.id AS project_id,
                p.manager_id AS project_manager_id,
                p.client_id AS project_client_id
            FROM task_comments tc
            INNER JOIN tasks t ON t.id = tc.task_id
            LEFT JOIN projects p ON p.id = t.project_id
            LEFT JOIN employees u ON u.id = tc.user_id
            WHERE tc.id = :comment_id
            LIMIT 1
        ");

        $stmt->execute([
            ':comment_id' => $commentId,
        ]);

        $comment = $stmt->fetch(PDO::FETCH_ASSOC);

        return $comment ?: null;
    }

    private function canAccessTaskComments(array $authUser, array $task): bool {
        $role = strtolower((string)($authUser['role'] ?? ''));
        $userId = (int)($authUser['id'] ?? 0);

        if ($role === 'admin') {
            return true;
        }

        if ($role === 'manager') {
            return (int)($task['project_manager_id'] ?? 0) === $userId;
        }

        if ($role === 'employee') {
            return (int)($task['assignee_id'] ?? 0) === $userId
                || (int)($task['assigner_id'] ?? 0) === $userId
                || (int)($task['watcher_id'] ?? 0) === $userId;
        }

        if ($role === 'client') {
            return (int)($task['project_client_id'] ?? 0) === $userId
                && (int)($task['is_client_visible'] ?? 0) === 1;
        }

        return false;
    }

    private function canAccessComment(array $authUser, array $comment): bool {
        $task = [
            'project_manager_id' => $comment['project_manager_id'] ?? null,
            'project_client_id' => $comment['project_client_id'] ?? null,
            'assignee_id' => $comment['assignee_id'] ?? null,
            'assigner_id' => null,
            'watcher_id' => null,
            'is_client_visible' => 1,
        ];

        if (!$this->canAccessTaskComments($authUser, $task)) {
            return false;
        }

        $role = strtolower((string)($authUser['role'] ?? ''));
        $visibility = strtolower((string)($comment['visibility'] ?? 'internal'));

        if ($role === 'client') {
            return $visibility === 'client';
        }

        if ($role === 'employee') {
            return $visibility === 'internal';
        }

        return true;
    }

    private function canModifyComment(array $authUser, array $comment): bool {
        $role = strtolower((string)($authUser['role'] ?? ''));
        $userId = (int)($authUser['id'] ?? 0);

        if ($role === 'admin') {
            return true;
        }

        if ($role === 'manager' && (int)($comment['project_manager_id'] ?? 0) === $userId) {
            return true;
        }

        return (int)($comment['user_id'] ?? 0) === $userId;
    }

    private function visibilityForRole(array $authUser): string {
        $role = strtolower((string)($authUser['role'] ?? ''));

        if ($role === 'client') {
            return 'client';
        }

        return 'internal';
    }

    private function buildCommentSelectSql(): string {
        $visibilitySelect = $this->hasCommentVisibility()
            ? 'tc.visibility'
            : "'internal' AS visibility";

        return "
            SELECT
                tc.id,
                tc.task_id,
                tc.user_id,
                tc.comment_text,
                {$visibilitySelect},
                tc.created_at,
                tc.updated_at,

                u.full_name AS user_name,
                u.email AS user_email,
                u.role AS user_role,

                t.title AS task_title,
                t.assignee_id,
                t.assigner_id,
                t.watcher_id,

                p.id AS project_id,
                p.name AS project_name,
                p.manager_id AS project_manager_id,
                p.client_id AS project_client_id
            FROM task_comments tc
            INNER JOIN tasks t ON t.id = tc.task_id
            LEFT JOIN projects p ON p.id = t.project_id
            LEFT JOIN employees u ON u.id = tc.user_id
        ";
    }

    private function decorateComment(array $comment): array {
        $comment['id'] = (int)$comment['id'];
        $comment['task_id'] = (int)$comment['task_id'];
        $comment['user_id'] = (int)$comment['user_id'];
        $comment['project_id'] = isset($comment['project_id']) ? (int)$comment['project_id'] : null;
        $comment['visibility'] = $comment['visibility'] ?? 'internal';

        return $comment;
    }

    private function logActivity(int $taskId, int $userId, string $description): void {
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
                    'comment',
                    :description
                )
            ");

            $stmt->execute([
                ':task_id' => $taskId,
                ':user_id' => $userId,
                ':description' => $description,
            ]);
        } catch (Throwable $e) {
            // Activity log không được làm hỏng flow comment.
        }
    }

    private function notifyManagerForClientFeedback(array $task, array $authUser): void {
        if (!$this->tableExists('notifications')) {
            return;
        }

        $role = strtolower((string)($authUser['role'] ?? ''));

        if ($role !== 'client') {
            return;
        }

        $managerId = (int)($task['project_manager_id'] ?? 0);

        if ($managerId <= 0) {
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
                ':user_id' => $managerId,
                ':message' => 'Client đã gửi feedback cho task "' . ($task['title'] ?? 'Không rõ') . '".',
            ]);
        } catch (Throwable $e) {
            // Notification là phụ trợ.
        }
    }

    public function getAll(): void {
        try {
            $authUser = $this->requireRole(['admin', 'manager']);
            $role = strtolower((string)$authUser['role']);
            $userId = (int)$authUser['id'];

            $params = [];
            $where = [];

            if ($role === 'manager') {
                $where[] = 'p.manager_id = :manager_id';
                $params[':manager_id'] = $userId;
            }

            if (!empty($_GET['task_id'])) {
                $where[] = 'tc.task_id = :task_id';
                $params[':task_id'] = (int)$_GET['task_id'];
            }

            if (!empty($_GET['visibility']) && in_array($_GET['visibility'], ['internal', 'client'], true)) {
                if ($this->hasCommentVisibility()) {
                    $where[] = 'tc.visibility = :visibility';
                    $params[':visibility'] = $_GET['visibility'];
                }
            }

            $whereSql = count($where) > 0 ? ('WHERE ' . implode(' AND ', $where)) : '';

            $sql = $this->buildCommentSelectSql() . "
                {$whereSql}
                ORDER BY tc.created_at DESC
                LIMIT 100
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);

            $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $comments = array_map([$this, 'decorateComment'], $comments);

            $this->json([
                'status' => 'success',
                'data' => $comments
            ]);
        } catch (Throwable $e) {
            $this->json([
                'status' => 'error',
                'message' => 'Không thể tải bình luận: ' . $e->getMessage()
            ], 400);
        }
    }

    public function getById($id = null): void {
        try {
            $authUser = $this->requireRole(['admin', 'manager', 'employee', 'client']);
            $commentId = $this->resolveId($id);

            if (!$commentId) {
                $this->json([
                    'status' => 'error',
                    'message' => 'Thiếu ID bình luận.'
                ], 400);
            }

            $comment = $this->getCommentById($commentId);

            if (!$comment) {
                $this->json([
                    'status' => 'error',
                    'message' => 'Không tìm thấy bình luận.'
                ], 404);
            }

            if (!$this->canAccessComment($authUser, $comment)) {
                $this->json([
                    'status' => 'error',
                    'message' => 'Bạn không có quyền xem bình luận này.'
                ], 403);
            }

            $this->json([
                'status' => 'success',
                'data' => $this->decorateComment($comment)
            ]);
        } catch (Throwable $e) {
            $this->json([
                'status' => 'error',
                'message' => 'Không thể tải bình luận: ' . $e->getMessage()
            ], 400);
        }
    }

    public function getByTask($id = null): void {
        try {
            $authUser = $this->requireRole(['admin', 'manager', 'employee', 'client']);
            $taskId = $this->resolveId($id);

            if (!$taskId) {
                $this->json([
                    'status' => 'error',
                    'message' => 'Thiếu ID task.'
                ], 400);
            }

            $task = $this->getTaskContext($taskId);

            if (!$task) {
                $this->json([
                    'status' => 'error',
                    'message' => 'Không tìm thấy task.'
                ], 404);
            }

            if (!$this->canAccessTaskComments($authUser, $task)) {
                $this->json([
                    'status' => 'error',
                    'message' => 'Bạn không có quyền xem bình luận của task này.'
                ], 403);
            }

            $role = strtolower((string)$authUser['role']);
            $params = [
                ':task_id' => $taskId,
            ];

            $where = [
                'tc.task_id = :task_id',
            ];

            if ($this->hasCommentVisibility()) {
                if ($role === 'client') {
                    $where[] = "tc.visibility = 'client'";
                } elseif ($role === 'employee') {
                    $where[] = "tc.visibility = 'internal'";
                }
            }

            $sql = $this->buildCommentSelectSql() . "
                WHERE " . implode(' AND ', $where) . "
                ORDER BY tc.created_at ASC
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);

            $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $comments = array_map([$this, 'decorateComment'], $comments);

            $this->json([
                'status' => 'success',
                'data' => $comments
            ]);
        } catch (Throwable $e) {
            $this->json([
                'status' => 'error',
                'message' => 'Không thể tải bình luận task: ' . $e->getMessage()
            ], 400);
        }
    }

    public function store($id = null): void {
        try {
            $authUser = $this->requireRole(['manager', 'employee', 'client']);
            $taskId = $this->resolveId($id);

            if (!$taskId) {
                $this->json([
                    'status' => 'error',
                    'message' => 'Thiếu ID task.'
                ], 400);
            }

            $task = $this->getTaskContext($taskId);

            if (!$task) {
                $this->json([
                    'status' => 'error',
                    'message' => 'Không tìm thấy task.'
                ], 404);
            }

            if (!$this->canAccessTaskComments($authUser, $task)) {
                $this->json([
                    'status' => 'error',
                    'message' => 'Bạn không có quyền bình luận task này.'
                ], 403);
            }

            $input = $this->getInput();
            $commentText = trim((string)($input['comment_text'] ?? $input['comment'] ?? $input['message'] ?? ''));

            if ($commentText === '') {
                $this->json([
                    'status' => 'error',
                    'message' => 'Nội dung bình luận không được để trống.'
                ], 422);
            }

            $visibility = $this->visibilityForRole($authUser);
            $hasVisibility = $this->hasCommentVisibility();

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
                    ':user_id' => (int)$authUser['id'],
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
                    ':user_id' => (int)$authUser['id'],
                    ':comment_text' => $commentText,
                ]);
            }

            $commentId = (int)$this->db->lastInsertId();
            $comment = $this->getCommentById($commentId);

            $this->logActivity($taskId, (int)$authUser['id'], $visibility === 'client' ? 'Client gửi feedback task.' : 'Thêm bình luận nội bộ.');
            $this->notifyManagerForClientFeedback($task, $authUser);

            $this->json([
                'status' => 'success',
                'message' => $visibility === 'client' ? 'Đã gửi feedback cho task.' : 'Đã thêm bình luận.',
                'data' => $comment ? $this->decorateComment($comment) : null
            ], 201);
        } catch (Throwable $e) {
            $this->json([
                'status' => 'error',
                'message' => 'Không thể thêm bình luận: ' . $e->getMessage()
            ], 400);
        }
    }

    public function update($id = null): void {
        try {
            $authUser = $this->requireRole(['admin', 'manager', 'employee', 'client']);
            $commentId = $this->resolveId($id);

            if (!$commentId) {
                $this->json([
                    'status' => 'error',
                    'message' => 'Thiếu ID bình luận.'
                ], 400);
            }

            $comment = $this->getCommentById($commentId);

            if (!$comment) {
                $this->json([
                    'status' => 'error',
                    'message' => 'Không tìm thấy bình luận.'
                ], 404);
            }

            if (!$this->canModifyComment($authUser, $comment)) {
                $this->json([
                    'status' => 'error',
                    'message' => 'Bạn không có quyền cập nhật bình luận này.'
                ], 403);
            }

            $input = $this->getInput();
            $commentText = trim((string)($input['comment_text'] ?? $input['comment'] ?? $input['message'] ?? ''));

            if ($commentText === '') {
                $this->json([
                    'status' => 'error',
                    'message' => 'Nội dung bình luận không được để trống.'
                ], 422);
            }

            $stmt = $this->db->prepare("
                UPDATE task_comments
                SET comment_text = :comment_text
                WHERE id = :comment_id
            ");

            $stmt->execute([
                ':comment_text' => $commentText,
                ':comment_id' => $commentId,
            ]);

            $updated = $this->getCommentById($commentId);

            $this->logActivity((int)$comment['task_id'], (int)$authUser['id'], 'Cập nhật bình luận task.');

            $this->json([
                'status' => 'success',
                'message' => 'Đã cập nhật bình luận.',
                'data' => $updated ? $this->decorateComment($updated) : null
            ]);
        } catch (Throwable $e) {
            $this->json([
                'status' => 'error',
                'message' => 'Không thể cập nhật bình luận: ' . $e->getMessage()
            ], 400);
        }
    }

    public function delete($id = null): void {
        try {
            $authUser = $this->requireRole(['admin', 'manager', 'employee', 'client']);
            $commentId = $this->resolveId($id);

            if (!$commentId) {
                $this->json([
                    'status' => 'error',
                    'message' => 'Thiếu ID bình luận.'
                ], 400);
            }

            $comment = $this->getCommentById($commentId);

            if (!$comment) {
                $this->json([
                    'status' => 'error',
                    'message' => 'Không tìm thấy bình luận.'
                ], 404);
            }

            if (!$this->canModifyComment($authUser, $comment)) {
                $this->json([
                    'status' => 'error',
                    'message' => 'Bạn không có quyền xoá bình luận này.'
                ], 403);
            }

            $stmt = $this->db->prepare("
                DELETE FROM task_comments
                WHERE id = :comment_id
            ");

            $stmt->execute([
                ':comment_id' => $commentId,
            ]);

            $this->logActivity((int)$comment['task_id'], (int)$authUser['id'], 'Xoá bình luận task.');

            $this->json([
                'status' => 'success',
                'message' => 'Đã xoá bình luận.'
            ]);
        } catch (Throwable $e) {
            $this->json([
                'status' => 'error',
                'message' => 'Không thể xoá bình luận: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * Backward-compatible aliases.
     */
    public function getComments($id = null): void {
        $this->getByTask($id);
    }

    public function create($id = null): void {
        $this->store($id);
    }

    public function destroy($id = null): void {
        $this->delete($id);
    }
}