<?php
namespace App\Controllers\Auth;

use App\Models\Auth\User;
use Core\JwtHandler;

class AuthController {
    private User $userModel;
    private JwtHandler $jwt;
    private $authUser;

    public function __construct($authUser = null) {
        $this->ensureJwtDefaults();

        $this->userModel = new User();
        $this->jwt = new JwtHandler();
        $this->authUser = $authUser;
    }

    private function ensureJwtDefaults(): void {
        if (empty($_ENV['JWT_SECRET'])) {
            $_ENV['JWT_SECRET'] = 'creative_agency_hub_local_secret_key_2026';
        }

        if (empty($_ENV['JWT_EXPIRATION'])) {
            $_ENV['JWT_EXPIRATION'] = 86400;
        }
    }

    private function jsonResponse(array $payload, int $statusCode = 200): void {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code($statusCode);

        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }

    private function input(): array {
        $raw = file_get_contents('php://input');
        $json = json_decode($raw, true);

        if (is_array($json)) {
            return $json;
        }

        return $_POST ?? [];
    }

    private function sanitizeUser(array $user): array {
        return [
            'id' => (int) $user['id'],
            'employee_id' => (int) $user['id'],
            'employee_code' => $user['employee_code'] ?? '',
            'full_name' => $user['full_name'] ?? '',
            'name' => $user['full_name'] ?? '',
            'email' => $user['email'] ?? '',
            'role' => $user['role'] ?? 'employee',
            'department_id' => isset($user['department_id']) ? (int) $user['department_id'] : null,
            'department_name' => $user['department_name'] ?? '',
            'position_id' => isset($user['position_id']) ? (int) $user['position_id'] : null,
            'position_name' => $user['position_name'] ?? '',
            'manager_id' => isset($user['manager_id']) ? (int) $user['manager_id'] : null,
            'manager_name' => $user['manager_name'] ?? '',
            'status' => $user['status'] ?? 'active',
            'avatar' => $user['avatar'] ?? null,
            'phone' => $user['phone'] ?? '',
            'gender' => $user['gender'] ?? '',
            'date_of_birth' => $user['date_of_birth'] ?? '',
            'hire_date' => $user['hire_date'] ?? '',
            'total_leave_days' => isset($user['total_leave_days']) ? (float) $user['total_leave_days'] : 12,
            'remaining_leave_days' => isset($user['remaining_leave_days']) ? (float) $user['remaining_leave_days'] : 12,
        ];
    }

    public function login(): void {
        $input = $this->input();

        $email = trim((string) ($input['email'] ?? ''));
        $password = (string) ($input['password'] ?? '');

        if ($email === '' || $password === '') {
            $this->jsonResponse([
                'status' => 'error',
                'message' => 'Vui lòng nhập đầy đủ email và mật khẩu.'
            ], 400);
        }

        $user = $this->userModel->findByEmail($email);

        if (!$user || empty($user['password']) || !password_verify($password, $user['password'])) {
            $this->jsonResponse([
                'status' => 'error',
                'message' => 'Email hoặc mật khẩu không chính xác.'
            ], 401);
        }

        if (($user['status'] ?? '') !== 'active') {
            $this->jsonResponse([
                'status' => 'error',
                'message' => 'Tài khoản đang không hoạt động hoặc đã bị khóa.'
            ], 403);
        }

        $safeUser = $this->sanitizeUser($user);

        $tokenPayload = [
            'id' => $safeUser['id'],
            'employee_id' => $safeUser['employee_id'],
            'employee_code' => $safeUser['employee_code'],
            'email' => $safeUser['email'],
            'full_name' => $safeUser['full_name'],
            'role' => $safeUser['role'],
            'department_id' => $safeUser['department_id'],
            'position_id' => $safeUser['position_id'],
        ];

        $token = $this->jwt->encode($tokenPayload);

        $this->jsonResponse([
            'status' => 'success',
            'message' => 'Đăng nhập thành công.',
            'data' => [
                'token' => $token,
                'user' => $safeUser
            ]
        ]);
    }

    public function me(): void {
        $authUser = $this->authUser;

        if (!$authUser && class_exists('\\App\\Middleware\\AuthMiddleware')) {
            $authUser = \App\Middleware\AuthMiddleware::check();
        }

        $id = (int) ($authUser['id'] ?? $authUser['employee_id'] ?? 0);

        if ($id <= 0) {
            $this->jsonResponse([
                'status' => 'error',
                'message' => 'Không xác định được người dùng.'
            ], 401);
        }

        $user = $this->userModel->findById($id);

        if (!$user) {
            $this->jsonResponse([
                'status' => 'error',
                'message' => 'Không tìm thấy tài khoản.'
            ], 404);
        }

        $this->jsonResponse([
            'status' => 'success',
            'message' => 'Lấy thông tin tài khoản thành công.',
            'data' => [
                'user' => $this->sanitizeUser($user)
            ]
        ]);
    }

    public function register(): void {
        $input = $this->input();

        $required = ['department_id', 'position_id', 'employee_code', 'full_name', 'email', 'password', 'hire_date'];

        foreach ($required as $field) {
            if (empty($input[$field])) {
                $this->jsonResponse([
                    'status' => 'error',
                    'message' => "Thiếu trường bắt buộc: {$field}."
                ], 400);
            }
        }

        try {
            $id = $this->userModel->create([
                'department_id' => $input['department_id'],
                'position_id' => $input['position_id'],
                'manager_id' => $input['manager_id'] ?? null,
                'employee_code' => $input['employee_code'],
                'full_name' => $input['full_name'],
                'email' => $input['email'],
                'password' => $input['password'],
                'role' => $input['role'] ?? 'employee',
                'phone' => $input['phone'] ?? '',
                'gender' => $input['gender'] ?? 'other',
                'date_of_birth' => $input['date_of_birth'] ?? null,
                'address' => $input['address'] ?? '',
                'total_leave_days' => $input['total_leave_days'] ?? 12,
                'remaining_leave_days' => $input['remaining_leave_days'] ?? 12,
                'status' => $input['status'] ?? 'active',
                'hire_date' => $input['hire_date'],
                'resigned_date' => $input['resigned_date'] ?? null,
            ]);

            $user = $this->userModel->findById($id);

            $this->jsonResponse([
                'status' => 'success',
                'message' => 'Tạo tài khoản thành công.',
                'data' => [
                    'user' => $this->sanitizeUser($user)
                ]
            ], 201);
        } catch (\Throwable $e) {
            $this->jsonResponse([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 400);
        }
    }
}