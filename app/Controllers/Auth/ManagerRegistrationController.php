<?php
namespace App\Controllers\Auth;

use Core\Database;
use PDO;
use Throwable;

class ManagerRegistrationController {
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

        if (!empty($raw)) {
            $parsed = [];
            parse_str($raw, $parsed);

            if (is_array($parsed)) {
                return $parsed;
            }
        }

        return [];
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

    private function normalizeEmail(string $email): string {
        return strtolower(trim($email));
    }

    private function validateEmail(string $email): void {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->json([
                'status' => 'error',
                'message' => 'Email không hợp lệ.'
            ], 422);
        }
    }

    private function ensureEmailAvailable(string $email): void {
        $deletedCondition = $this->columnExists('employees', 'deleted_at')
            ? " AND deleted_at IS NULL "
            : "";

        $stmt = $this->db->prepare("
            SELECT id, status
            FROM employees
            WHERE email = :email
            {$deletedCondition}
            LIMIT 1
        ");

        $stmt->execute([
            ':email' => $email,
        ]);

        $existing = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            $this->json([
                'status' => 'error',
                'message' => 'Email này đã tồn tại trong hệ thống.'
            ], 409);
        }
    }

    private function generateEmployeeCode(): string {
        for ($i = 0; $i < 10; $i++) {
            $code = 'MNG-' . date('ymd') . '-' . str_pad((string)random_int(1, 9999), 4, '0', STR_PAD_LEFT);

            if (!$this->columnExists('employees', 'employee_code')) {
                return $code;
            }

            $stmt = $this->db->prepare("
                SELECT COUNT(*)
                FROM employees
                WHERE employee_code = :code
            ");

            $stmt->execute([
                ':code' => $code,
            ]);

            if ((int)$stmt->fetchColumn() === 0) {
                return $code;
            }
        }

        return 'MNG-' . date('ymd-His');
    }

    private function resolveReferenceId(string $table, string $preferredName, string $fallbackName, string $description): ?int {
        if (!$this->tableExists($table)) {
            return null;
        }

        $deletedCondition = $this->columnExists($table, 'deleted_at')
            ? " AND deleted_at IS NULL "
            : "";

        $statusCondition = $this->columnExists($table, 'status')
            ? " AND status = 'active' "
            : "";

        $stmt = $this->db->prepare("
            SELECT id
            FROM {$table}
            WHERE name IN (:preferred_name, :fallback_name)
            {$deletedCondition}
            LIMIT 1
        ");

        $stmt->execute([
            ':preferred_name' => $preferredName,
            ':fallback_name' => $fallbackName,
        ]);

        $found = $stmt->fetchColumn();

        if ($found) {
            return (int)$found;
        }

        try {
            $columns = ['name'];
            $values = [':name'];
            $params = [
                ':name' => $preferredName,
            ];

            if ($this->columnExists($table, 'description')) {
                $columns[] = 'description';
                $values[] = ':description';
                $params[':description'] = $description;
            }

            if ($this->columnExists($table, 'status')) {
                $columns[] = 'status';
                $values[] = ':status';
                $params[':status'] = 'active';
            }

            $sql = "
                INSERT INTO {$table}
                (" . implode(', ', $columns) . ")
                VALUES
                (" . implode(', ', $values) . ")
            ";

            $insert = $this->db->prepare($sql);
            $insert->execute($params);

            return (int)$this->db->lastInsertId();
        } catch (Throwable $e) {
            $stmt = $this->db->query("
                SELECT id
                FROM {$table}
                WHERE 1 = 1
                {$deletedCondition}
                {$statusCondition}
                ORDER BY id ASC
                LIMIT 1
            ");

            $fallback = $stmt->fetchColumn();

            return $fallback ? (int)$fallback : null;
        }
    }

    private function getAdminIds(): array {
        if (!$this->tableExists('employees')) {
            return [];
        }

        $deletedCondition = $this->columnExists('employees', 'deleted_at')
            ? " AND deleted_at IS NULL "
            : "";

        $stmt = $this->db->query("
            SELECT id
            FROM employees
            WHERE role = 'admin'
              AND status = 'active'
              {$deletedCondition}
        ");

        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    private function notifyAdmins(string $managerName): void {
        if (!$this->tableExists('notifications')) {
            return;
        }

        if (!$this->columnExists('notifications', 'user_id') || !$this->columnExists('notifications', 'message')) {
            return;
        }

        $adminIds = $this->getAdminIds();

        if (empty($adminIds)) {
            return;
        }

        $hasIsRead = $this->columnExists('notifications', 'is_read');

        foreach ($adminIds as $adminId) {
            try {
                if ($hasIsRead) {
                    $stmt = $this->db->prepare("
                        INSERT INTO notifications (user_id, message, is_read)
                        VALUES (:user_id, :message, 0)
                    ");

                    $stmt->execute([
                        ':user_id' => $adminId,
                        ':message' => 'Có tài khoản Manager mới cần duyệt: ' . $managerName,
                    ]);
                } else {
                    $stmt = $this->db->prepare("
                        INSERT INTO notifications (user_id, message)
                        VALUES (:user_id, :message)
                    ");

                    $stmt->execute([
                        ':user_id' => $adminId,
                        ':message' => 'Có tài khoản Manager mới cần duyệt: ' . $managerName,
                    ]);
                }
            } catch (Throwable $e) {
                continue;
            }
        }
    }

    public function store(): void {
        try {
            if (!$this->tableExists('employees')) {
                $this->json([
                    'status' => 'error',
                    'message' => 'Bảng employees chưa tồn tại.'
                ], 500);
            }

            $input = $this->getInput();

            $fullName = trim((string)($input['full_name'] ?? ''));
            $companyName = trim((string)($input['company_name'] ?? ''));
            $email = $this->normalizeEmail((string)($input['email'] ?? ''));
            $phone = trim((string)($input['phone'] ?? ''));
            $password = (string)($input['password'] ?? '');
            $passwordConfirm = (string)($input['password_confirm'] ?? '');
            $note = trim((string)($input['note'] ?? ''));

            if ($fullName === '') {
                $this->json([
                    'status' => 'error',
                    'message' => 'Vui lòng nhập họ và tên.'
                ], 422);
            }

            if ($companyName === '') {
                $this->json([
                    'status' => 'error',
                    'message' => 'Vui lòng nhập tên công ty hoặc đội nhóm.'
                ], 422);
            }

            $this->validateEmail($email);

            if (strlen($password) < 6) {
                $this->json([
                    'status' => 'error',
                    'message' => 'Mật khẩu tối thiểu 6 ký tự.'
                ], 422);
            }

            if ($password !== $passwordConfirm) {
                $this->json([
                    'status' => 'error',
                    'message' => 'Mật khẩu xác nhận không khớp.'
                ], 422);
            }

            $this->ensureEmailAvailable($email);

            $departmentId = $this->resolveReferenceId(
                'departments',
                'Phòng Dự án',
                'Project Management',
                'Phòng dành cho các tài khoản Manager quản lý workspace.'
            );

            $positionId = $this->resolveReferenceId(
                'positions',
                'Project Manager',
                'Manager',
                'Chức vụ mặc định cho tài khoản Manager đăng ký từ landing page.'
            );

            $employeeCode = $this->generateEmployeeCode();
            $passwordHash = password_hash($password, PASSWORD_BCRYPT);

            $columns = [];
            $values = [];
            $params = [];

            $put = function (string $column, $value) use (&$columns, &$values, &$params): void {
                if ($this->columnExists('employees', $column)) {
                    $param = ':' . $column;
                    $columns[] = $column;
                    $values[] = $param;
                    $params[$param] = $value;
                }
            };

            $put('department_id', $departmentId);
            $put('position_id', $positionId);
            $put('manager_id', null);
            $put('employee_code', $employeeCode);
            $put('full_name', $fullName);
            $put('email', $email);
            $put('password', $passwordHash);
            $put('role', 'manager');
            $put('phone', $phone !== '' ? $phone : null);
            $put('gender', 'other');
            $put('date_of_birth', '1999-01-01');

            $addressParts = [$companyName];

            if ($note !== '') {
                $addressParts[] = 'Ghi chú: ' . $note;
            }

            $put('address', implode(' | ', $addressParts));
            $put('avatar', null);
            $put('total_leave_days', 12);
            $put('remaining_leave_days', 12);
            $put('status', 'inactive');
            $put('hire_date', date('Y-m-d'));
            $put('resigned_date', null);
            $put('deleted_at', null);

            if (empty($columns)) {
                $this->json([
                    'status' => 'error',
                    'message' => 'Không xác định được cột hợp lệ để tạo tài khoản.'
                ], 500);
            }

            $sql = "
                INSERT INTO employees
                (" . implode(', ', $columns) . ")
                VALUES
                (" . implode(', ', $values) . ")
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);

            $managerId = (int)$this->db->lastInsertId();

            $this->notifyAdmins($fullName);

            $this->json([
                'status' => 'success',
                'message' => 'Đăng ký thành công. Tài khoản Manager của bạn đang chờ Admin duyệt.',
                'data' => [
                    'id' => $managerId,
                    'full_name' => $fullName,
                    'email' => $email,
                    'role' => 'manager',
                    'status' => 'inactive'
                ]
            ], 201);
        } catch (Throwable $e) {
            $this->json([
                'status' => 'error',
                'message' => 'Không thể đăng ký Manager: ' . $e->getMessage()
            ], 400);
        }
    }
}