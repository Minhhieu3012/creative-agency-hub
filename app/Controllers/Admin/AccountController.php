<?php
namespace App\Controllers\Admin;

use Core\Database;
use App\Services\Core\NotificationService;
use PDO;
use Throwable;

class AccountController {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
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

        if (!empty($_POST)) {
            return $_POST;
        }

        return [];
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

    private function validRole(?string $role): ?string {
        $role = strtolower(trim((string)$role));
        $allowed = ['admin', 'manager', 'employee', 'client'];

        return in_array($role, $allowed, true) ? $role : null;
    }

    private function validStatus(?string $status): ?string {
        $status = strtolower(trim((string)$status));
        $allowed = ['active', 'inactive', 'suspended', 'resigned'];

        return in_array($status, $allowed, true) ? $status : null;
    }

    private function selectSql(): string {
        return "
            SELECT
                e.id,
                e.employee_code,
                e.full_name,
                e.email,
                e.role,
                e.status,
                e.phone,
                e.gender,
                e.hire_date,
                e.created_at,
                e.department_id,
                e.position_id,
                e.manager_id,
                d.name AS department_name,
                p.name AS position_name,
                manager.full_name AS manager_name,
                manager.email AS manager_email
            FROM employees e
            LEFT JOIN departments d ON d.id = e.department_id
            LEFT JOIN positions p ON p.id = e.position_id
            LEFT JOIN employees manager ON manager.id = e.manager_id
        ";
    }

    private function findAccount(int $id): ?array {
        $stmt = $this->db->prepare($this->selectSql() . "
            WHERE e.id = :id
              AND e.deleted_at IS NULL
            LIMIT 1
        ");

        $stmt->execute([
            ':id' => $id,
        ]);

        $account = $stmt->fetch(PDO::FETCH_ASSOC);

        return $account ?: null;
    }

    private function ensureAccount(int $id): array {
        $account = $this->findAccount($id);

        if (!$account) {
            $this->json([
                'status' => 'error',
                'message' => 'Không tìm thấy tài khoản.'
            ], 404);
        }

        return $account;
    }

    private function updateStatusInternal(int $id, string $status): array {
        $status = $this->validStatus($status);

        if (!$status) {
            $this->json([
                'status' => 'error',
                'message' => 'Trạng thái tài khoản không hợp lệ.'
            ], 422);
        }

        $account = $this->ensureAccount($id);

        if ($account['role'] === 'admin' && $status !== 'active') {
            $adminCount = $this->countActiveAdmins();

            if ($adminCount <= 1) {
                $this->json([
                    'status' => 'error',
                    'message' => 'Không thể khóa admin active cuối cùng của hệ thống.'
                ], 422);
            }
        }

        $stmt = $this->db->prepare("
            UPDATE employees
            SET status = :status,
                updated_at = NOW()
            WHERE id = :id
              AND deleted_at IS NULL
        ");

        $stmt->execute([
            ':status' => $status,
            ':id' => $id,
        ]);

        return $this->ensureAccount($id);
    }

    private function countActiveAdmins(): int {
        $stmt = $this->db->query("
            SELECT COUNT(*)
            FROM employees
            WHERE role = 'admin'
              AND status = 'active'
              AND deleted_at IS NULL
        ");

        return (int)$stmt->fetchColumn();
    }

    public function index(): void {
        try {
            $params = [];
            $where = [
                "e.deleted_at IS NULL"
            ];

            $role = $this->validRole($_GET['role'] ?? null);

            if ($role) {
                $where[] = "e.role = :role";
                $params[':role'] = $role;
            }

            $status = $this->validStatus($_GET['status'] ?? null);

            if ($status) {
                $where[] = "e.status = :status";
                $params[':status'] = $status;
            }

            $search = trim((string)($_GET['search'] ?? $_GET['q'] ?? ''));

            if ($search !== '') {
                $where[] = "
                    (
                        e.full_name LIKE :search_name
                        OR e.email LIKE :search_email
                        OR e.employee_code LIKE :search_code
                    )
                ";

                $params[':search_name'] = "%{$search}%";
                $params[':search_email'] = "%{$search}%";
                $params[':search_code'] = "%{$search}%";
            }

            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 300;

            if ($limit <= 0 || $limit > 1000) {
                $limit = 300;
            }

            $whereSql = 'WHERE ' . implode(' AND ', $where);

            $stmt = $this->db->prepare($this->selectSql() . "
                {$whereSql}
                ORDER BY
                    FIELD(e.status, 'inactive', 'active', 'suspended', 'resigned'),
                    FIELD(e.role, 'admin', 'manager', 'employee', 'client'),
                    e.created_at DESC,
                    e.id DESC
                LIMIT :limit_value
            ");

            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }

            $stmt->bindValue(':limit_value', $limit, PDO::PARAM_INT);
            $stmt->execute();

            $accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $this->json([
                'status' => 'success',
                'data' => [
                    'accounts' => $accounts,
                    'total' => count($accounts)
                ]
            ]);
        } catch (Throwable $e) {
            $this->json([
                'status' => 'error',
                'message' => 'Không thể tải danh sách tài khoản: ' . $e->getMessage()
            ], 400);
        }
    }

    public function pending(): void {
        try {
            $stmt = $this->db->prepare($this->selectSql() . "
                WHERE e.deleted_at IS NULL
                  AND e.status = 'inactive'
                  AND e.role IN ('manager', 'employee', 'client')
                ORDER BY e.created_at DESC, e.id DESC
            ");

            $stmt->execute();
            $accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $this->json([
                'status' => 'success',
                'data' => [
                    'accounts' => $accounts,
                    'total' => count($accounts)
                ]
            ]);
        } catch (Throwable $e) {
            $this->json([
                'status' => 'error',
                'message' => 'Không thể tải tài khoản chờ duyệt: ' . $e->getMessage()
            ], 400);
        }
    }

    public function approve($id = null): void {
        try {
            $accountId = $this->resolveId($id);

            if (!$accountId) {
                $this->json([
                    'status' => 'error',
                    'message' => 'Thiếu ID tài khoản.'
                ], 400);
            }

            $account = $this->updateStatusInternal($accountId, 'active');

            NotificationService::safeSend(
                (int)$account['id'],
                'Tài khoản của bạn đã được Admin duyệt và có thể đăng nhập hệ thống.'
            );

            if (!empty($account['manager_id'])) {
                NotificationService::safeSend(
                    (int)$account['manager_id'],
                    'Admin đã duyệt tài khoản "' . ($account['full_name'] ?? 'Không rõ') . '".'
                );
            }

            $this->json([
                'status' => 'success',
                'message' => 'Đã duyệt tài khoản.',
                'data' => $account
            ]);
        } catch (Throwable $e) {
            $this->json([
                'status' => 'error',
                'message' => 'Không thể duyệt tài khoản: ' . $e->getMessage()
            ], 400);
        }
    }

    public function reject($id = null): void {
        try {
            $accountId = $this->resolveId($id);

            if (!$accountId) {
                $this->json([
                    'status' => 'error',
                    'message' => 'Thiếu ID tài khoản.'
                ], 400);
            }

            $account = $this->updateStatusInternal($accountId, 'suspended');

            if (!empty($account['manager_id'])) {
                NotificationService::safeSend(
                    (int)$account['manager_id'],
                    'Admin đã từ chối hoặc khóa tài khoản "' . ($account['full_name'] ?? 'Không rõ') . '".'
                );
            }

            $this->json([
                'status' => 'success',
                'message' => 'Đã từ chối tài khoản và chuyển sang trạng thái khóa.',
                'data' => $account
            ]);
        } catch (Throwable $e) {
            $this->json([
                'status' => 'error',
                'message' => 'Không thể từ chối tài khoản: ' . $e->getMessage()
            ], 400);
        }
    }

    public function suspend($id = null): void {
        try {
            $accountId = $this->resolveId($id);

            if (!$accountId) {
                $this->json([
                    'status' => 'error',
                    'message' => 'Thiếu ID tài khoản.'
                ], 400);
            }

            $account = $this->updateStatusInternal($accountId, 'suspended');

            NotificationService::safeSend(
                (int)$account['id'],
                'Tài khoản của bạn đã bị Admin khóa hoặc đóng băng.'
            );

            $this->json([
                'status' => 'success',
                'message' => 'Đã khóa/đóng băng tài khoản.',
                'data' => $account
            ]);
        } catch (Throwable $e) {
            $this->json([
                'status' => 'error',
                'message' => 'Không thể khóa tài khoản: ' . $e->getMessage()
            ], 400);
        }
    }

    public function activate($id = null): void {
        try {
            $accountId = $this->resolveId($id);

            if (!$accountId) {
                $this->json([
                    'status' => 'error',
                    'message' => 'Thiếu ID tài khoản.'
                ], 400);
            }

            $account = $this->updateStatusInternal($accountId, 'active');

            NotificationService::safeSend(
                (int)$account['id'],
                'Tài khoản của bạn đã được Admin mở khóa.'
            );

            $this->json([
                'status' => 'success',
                'message' => 'Đã mở khóa tài khoản.',
                'data' => $account
            ]);
        } catch (Throwable $e) {
            $this->json([
                'status' => 'error',
                'message' => 'Không thể mở khóa tài khoản: ' . $e->getMessage()
            ], 400);
        }
    }

    public function updateStatus($id = null): void {
        try {
            $accountId = $this->resolveId($id);

            if (!$accountId) {
                $this->json([
                    'status' => 'error',
                    'message' => 'Thiếu ID tài khoản.'
                ], 400);
            }

            $input = $this->getInput();
            $status = $this->validStatus($input['status'] ?? null);

            if (!$status) {
                $this->json([
                    'status' => 'error',
                    'message' => 'Trạng thái tài khoản không hợp lệ.'
                ], 422);
            }

            $account = $this->updateStatusInternal($accountId, $status);

            $this->json([
                'status' => 'success',
                'message' => 'Đã cập nhật trạng thái tài khoản.',
                'data' => $account
            ]);
        } catch (Throwable $e) {
            $this->json([
                'status' => 'error',
                'message' => 'Không thể cập nhật trạng thái tài khoản: ' . $e->getMessage()
            ], 400);
        }
    }
}