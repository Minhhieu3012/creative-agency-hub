<?php
namespace App\Controllers\Auth;

use App\Models\Auth\User;
use Core\JwtHandler;
use Core\Security;
use Exception;
use Throwable;

class AuthController {
    private User $userModel;
    private JwtHandler $jwt;
    private $authUser;

    public function __construct($authUser = null) {
        $this->userModel = new User();
        $this->jwt = new JwtHandler();
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
        ];
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

        $user = $this->userModel->findByEmail($email);

        if (!$user) {
            throw new Exception('Tài khoản không tồn tại, đã bị khóa hoặc đã bị xoá.');
        }

        if (!password_verify($password, $user['password'])) {
            throw new Exception('Email hoặc mật khẩu không chính xác.');
        }

        $user['role'] = strtolower((string)$user['role']);

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

    /**
     * Legacy login.
     * Giữ lại để không phá code cũ, nhưng luồng mới nên dùng:
     * - loginAdmin()
     * - loginStaff()
     * - loginClient()
     */
    public function login(): void {
        try {
            $input = $this->getInputData();

            $email = trim((string)($input['email'] ?? ''));
            $password = trim((string)($input['password'] ?? ''));

            $user = $this->authenticate($email, $password);
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

    /**
     * Luồng staff mới.
     * Chỉ cho Manager và Employee.
     * Admin phải đi cổng admin riêng.
     * Client phải đi cổng client riêng.
     */
    public function loginInternal(): void {
        $this->loginStaff();
    }

    public function loginStaff(): void {
        $this->loginWithRoles(
            ['manager', 'employee'],
            'Tài khoản này không thuộc cổng nhân sự. Admin dùng cổng Admin, Client dùng cổng Client.'
        );
    }

    public function loginAdmin(): void {
        $this->loginWithRoles(
            ['admin'],
            'Tài khoản này không thuộc cổng Admin.'
        );
    }

    public function loginClient(): void {
        $this->loginWithRoles(
            ['client'],
            'Tài khoản này không thuộc cổng Client.'
        );
    }

    public function register(): void {
        $this->registerClient();
    }

    public function registerClient(): void {
        try {
            $input = $this->getInputData();

            $fullName = trim((string)($input['full_name'] ?? ''));
            $email = trim((string)($input['email'] ?? ''));
            $password = (string)($input['password'] ?? '');

            $fullName = Security::escape($fullName);
            $email = Security::escape($email);

            if ($fullName === '' || $email === '' || $password === '') {
                $this->json([
                    'status' => 'error',
                    'message' => 'Vui lòng điền đầy đủ họ tên, email và mật khẩu.',
                ], 400);
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->json([
                    'status' => 'error',
                    'message' => 'Email không hợp lệ.',
                ], 422);
            }

            if ($this->userModel->findByEmail($email)) {
                $this->json([
                    'status' => 'error',
                    'message' => 'Email đã tồn tại.',
                ], 409);
            }

            $newUserId = $this->userModel->create([
                'full_name' => $fullName,
                'email' => $email,
                'password' => $password,
                'role' => 'client',
            ]);

            $this->json([
                'status' => 'success',
                'message' => 'Đăng ký thành công.',
                'data' => [
                    'id' => (int)$newUserId,
                ],
            ], 201);
        } catch (Throwable $e) {
            $this->json([
                'status' => 'error',
                'message' => 'Không thể đăng ký: ' . $e->getMessage(),
            ], 400);
        }
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

            $authPayload = null;

            if ($token !== '') {
                try {
                    $decoded = $this->jwt->decode($token);
                    $authPayload = $this->normalizeAuthPayload($decoded);
                } catch (Throwable $e) {
                    $authPayload = null;
                }
            }

            if (!$authPayload) {
                if (session_status() === PHP_SESSION_NONE) {
                    session_start();
                }

                if (!empty($_SESSION['user_id'])) {
                    $authPayload = [
                        'id' => (int)$_SESSION['user_id'],
                        'email' => $_SESSION['user_email'] ?? null,
                        'role' => strtolower((string)($_SESSION['user_role'] ?? '')),
                        'full_name' => $_SESSION['full_name'] ?? null,
                    ];
                }
            }

            if (!$authPayload || empty($authPayload['id'])) {
                $this->json([
                    'status' => 'error',
                    'message' => 'Phiên đăng nhập không hợp lệ.',
                ], 401);
            }

            $user = $this->userModel->findById((int)$authPayload['id']);

            if (!$user) {
                $this->json([
                    'status' => 'error',
                    'message' => 'Không tìm thấy tài khoản hoặc tài khoản đã bị khóa.',
                ], 404);
            }

            $token = $token !== '' ? $token : $this->issueToken($user);
            $this->persistAuth($user, $token);

            $this->json([
                'status' => 'success',
                'data' => [
                    'user' => $this->publicUser($user),
                ],
            ]);
        } catch (Throwable $e) {
            $this->json([
                'status' => 'error',
                'message' => 'Không thể tải phiên đăng nhập: ' . $e->getMessage(),
            ], 401);
        }
    }
}