<?php
namespace App\Controllers\Auth;

use App\Models\Auth\User;
use Core\Database;
use Core\JwtHandler;
use Core\Security;
use Exception;
use PDO;
use Throwable;

class AuthController {
    private User $userModel;
    private JwtHandler $jwt;
    private PDO $db;
    private $authUser;

    public function __construct($authUser = null) {
        $this->userModel = new User();
        $this->jwt = new JwtHandler();
        $this->db = Database::getConnection();
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->authUser = $authUser;
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

    private function getInputData(): array {
        $rawInput = file_get_contents('php://input');
        $json = json_decode($rawInput, true);

        if (is_array($json)) {
            return $json;
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

    private function findUserByEmail(string $email): ?array {
        $stmt = $this->db->prepare("
            SELECT *
            FROM employees
            WHERE email = :email
              AND deleted_at IS NULL
            LIMIT 1
        ");

        $stmt->execute([
            ':email' => $email,
        ]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        return $user ?: null;
    }

    private function findUserById(int $id): ?array {
        $stmt = $this->db->prepare("
            SELECT
                id,
                employee_code,
                full_name,
                email,
                role,
                avatar,
                status,
                manager_id,
                department_id,
                position_id
            FROM employees
            WHERE id = :id
              AND deleted_at IS NULL
            LIMIT 1
        ");

        $stmt->execute([
            ':id' => $id,
        ]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        return $user ?: null;
    }

    private function publicUser(array $user): array {
        return [
            'id' => (int)$user['id'],
            'employee_code' => $user['employee_code'] ?? null,
            'full_name' => $user['full_name'] ?? '',
            'name' => $user['full_name'] ?? '',
            'email' => $user['email'] ?? '',
            'role' => strtolower((string)($user['role'] ?? 'employee')),
            'avatar' => $user['avatar'] ?? null,
            'status' => $user['status'] ?? null,
            'manager_id' => isset($user['manager_id']) ? (int)$user['manager_id'] : null,
        ];
    }

    private function statusMessage(string $status): string {
        $status = strtolower(trim($status));

        return match ($status) {
            'inactive' => 'Tài khoản đang chờ Admin duyệt. Vui lòng liên hệ Manager hoặc Admin.',
            'suspended' => 'Tài khoản đã bị khóa hoặc bị từ chối duyệt.',
            'resigned' => 'Tài khoản đã ngưng hoạt động.',
            default => 'Tài khoản chưa được phép đăng nhập.',
        };
    }

    private function ensureAccountCanLogin(array $user): void {
        $status = strtolower((string)($user['status'] ?? ''));

        if ($status !== 'active') {
            throw new Exception($this->statusMessage($status));
        }
    }

    private function issueToken(array $user): string {
        $role = strtolower((string)$user['role']);

        return $this->jwt->encode([
            'id' => (int)$user['id'],
            'email' => $user['email'],
            'role' => $role,
            'full_name' => $user['full_name'] ?? '',
        ]);
    }

    private function persistAuth(array $user, string $token): void {
        $role = strtolower((string)$user['role']);

        setcookie('cah_token', $token, [
            'expires' => time() + 86400,
            'path' => '/',
            'secure' => false,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $_SESSION['user_id'] = (int)$user['id'];
        $_SESSION['user_email'] = $user['email'] ?? null;
        $_SESSION['user_role'] = $role;
        $_SESSION['full_name'] = $user['full_name'] ?? '';

        session_write_close();
    }

    private function authenticate(string $email, string $password): array {
        if ($email === '' || $password === '') {
            throw new Exception('Vui lòng nhập đầy đủ email và mật khẩu.');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Email không hợp lệ.');
        }

        $user = $this->findUserByEmail($email);

        if (!$user) {
            throw new Exception('Email hoặc mật khẩu không chính xác.');
        }

        if (!password_verify($password, $user['password'])) {
            throw new Exception('Email hoặc mật khẩu không chính xác.');
        }

        $user['role'] = strtolower((string)$user['role']);
        $this->ensureAccountCanLogin($user);

        return $user;
    }

    private function loginWithRoles(array $allowedRoles, string $wrongPortalMessage): void {
        try {
            $input = $this->getInputData();

            $email = trim((string)($input['email'] ?? ''));
            $password = trim((string)($input['password'] ?? ''));

            $user = $this->authenticate($email, $password);
            $role = strtolower((string)$user['role']);

            if (!in_array($role, $allowedRoles, true)) {
                $this->json([
                    'status' => 'error',
                    'message' => $wrongPortalMessage,
                    'data' => [
                        'actual_role' => $role,
                    ],
                ], 403);
            }

            $token = $this->issueToken($user);
            $this->persistAuth($user, $token);

            $this->json([
                'status' => 'success',
                'message' => 'Đăng nhập thành công.',
                'data' => [
                    'token' => $token,
                    'user' => $this->publicUser($user),
                ],
            ]);
        } catch (Exception $e) {
            $this->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 401);
        }
    }

    public function login(): void {
        $this->loginWithRoles(
            ['admin', 'manager', 'employee', 'client'],
            'Tài khoản không thuộc cổng đăng nhập này.'
        );
    }

    public function loginInternal(): void {
        $this->loginWithRoles(
            ['admin', 'manager', 'employee'],
            'Vui lòng đăng nhập bằng cổng Client Portal.'
        );
    }

    public function loginStaff(): void {
        $this->loginWithRoles(
            ['manager', 'employee'],
            'Cổng Staff chỉ dành cho Manager và Employee.'
        );
    }

    public function loginAdmin(): void {
        $this->loginWithRoles(
            ['admin'],
            'Cổng Admin chỉ dành cho tài khoản Admin.'
        );
    }

    public function loginClient(): void {
        $this->loginWithRoles(
            ['client'],
            'Cổng Client chỉ dành cho tài khoản Client.'
        );
    }

    public function me(): void {
        try {
            $token = '';
            $authHeader = $this->getAuthorizationHeader();

            if ($authHeader !== '') {
                $token = $this->extractBearerToken($authHeader);
            }

            if ($token === '' && !empty($_COOKIE['cah_token'])) {
                $token = (string)$_COOKIE['cah_token'];
            }

            if ($token === '' && session_status() === PHP_SESSION_NONE) {
                session_start();
            }

            if ($token !== '') {
                $decoded = $this->jwt->decode($token);
                $payload = $this->normalizeAuthPayload($decoded);

                if (!$payload || empty($payload['id'])) {
                    $this->json([
                        'status' => 'error',
                        'message' => 'Token không hợp lệ.'
                    ], 401);
                }

                $user = $this->findUserById((int)$payload['id']);

                if (!$user) {
                    $this->json([
                        'status' => 'error',
                        'message' => 'Không tìm thấy tài khoản.'
                    ], 404);
                }

                $this->ensureAccountCanLogin($user);

                $this->json([
                    'status' => 'success',
                    'data' => [
                        'user' => $this->publicUser($user),
                    ],
                ]);
            }

            if (!empty($_SESSION['user_id'])) {
                $user = $this->findUserById((int)$_SESSION['user_id']);

                if (!$user) {
                    $this->json([
                        'status' => 'error',
                        'message' => 'Không tìm thấy tài khoản.'
                    ], 404);
                }

                $this->ensureAccountCanLogin($user);

                $this->json([
                    'status' => 'success',
                    'data' => [
                        'user' => $this->publicUser($user),
                    ],
                ]);
            }

            $this->json([
                'status' => 'error',
                'message' => 'Thiếu token đăng nhập.'
            ], 401);
        } catch (Exception $e) {
            $this->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 401);
        } catch (Throwable $e) {
            $this->json([
                'status' => 'error',
                'message' => 'Không thể xác thực phiên đăng nhập.',
            ], 401);
        }
    }

    /**
     * Luồng mới không cho client tự đăng ký trực tiếp.
     * Manager sẽ tạo Client/Employee, sau đó Admin duyệt.
     */
    public function registerClient(): void {
        $this->json([
            'status' => 'error',
            'message' => 'Đăng ký client trực tiếp đã tắt. Vui lòng liên hệ Manager để tạo tài khoản chờ duyệt.'
        ], 403);
    }

    /**
     * Legacy register.
     * Giữ để route cũ không fatal, nhưng không dùng trong luồng mới.
     */
    public function register(): void {
        $this->json([
            'status' => 'error',
            'message' => 'Đăng ký trực tiếp đã tắt trong luồng quản lý tài khoản mới.'
        ], 403);
    }
}