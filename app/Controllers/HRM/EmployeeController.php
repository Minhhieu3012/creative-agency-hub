<?php
namespace App\Controllers\HRM;

use App\Models\HRM\Employee;
use Core\Database;
use Core\JwtHandler;
use PDO;
use Throwable;

class EmployeeController {
    private PDO $db;
    private JwtHandler $jwt;
    private Employee $employeeModel;

    public function __construct() {
        $this->db = Database::getConnection();
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->jwt = new JwtHandler();
        $this->employeeModel = new Employee();
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

    private function getAuthUser(): ?array {
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
            return [
                'id' => (int)$_SESSION['user_id'],
                'email' => $_SESSION['user_email'] ?? null,
                'role' => strtolower((string)($_SESSION['user_role'] ?? '')),
                'full_name' => $_SESSION['full_name'] ?? null,
            ];
        }

        return null;
    }

    private function requireAuth(): array {
        $authUser = $this->getAuthUser();

        if (!$authUser || empty($authUser['id'])) {
            $this->json([
                'status' => 'error',
                'message' => 'Bạn cần đăng nhập lại để thực hiện thao tác này.'
            ], 401);
        }

        return $authUser;
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

    private function canAccessEmployee(array $authUser, int $employeeId): bool {
        $role = strtolower((string)($authUser['role'] ?? ''));

        if ($role === 'admin') {
            return true;
        }

        if ((int)$authUser['id'] === $employeeId) {
            return true;
        }

        if ($role === 'manager') {
            $employee = $this->employeeModel->findById($employeeId);

            if (!$employee) {
                return false;
            }

            if ((int)($employee['manager_id'] ?? 0) === (int)$authUser['id']) {
                return true;
            }

            return in_array(strtolower((string)($employee['role'] ?? '')), ['employee', 'client'], true);
        }

        return false;
    }

    private function getProjectRoot(): string {
        return dirname(__DIR__, 3);
    }

    private function sanitizeFileName(string $name): string {
        $name = preg_replace('/[^\pL\pN\.\-_ ]/u', '', $name);
        $name = trim((string)$name);

        return $name !== '' ? $name : 'document';
    }

    private function validateBasicAccountInput(array $input): void {
        if (empty($input['full_name']) || empty($input['email']) || empty($input['password']) || empty($input['role'])) {
            $this->json([
                'status' => 'error',
                'message' => 'Vui lòng nhập đầy đủ họ tên, email, mật khẩu và vai trò.'
            ], 422);
        }

        if (!filter_var((string)$input['email'], FILTER_VALIDATE_EMAIL)) {
            $this->json([
                'status' => 'error',
                'message' => 'Email không hợp lệ.'
            ], 422);
        }

        if (mb_strlen((string)$input['password']) < 6) {
            $this->json([
                'status' => 'error',
                'message' => 'Mật khẩu tối thiểu 6 ký tự.'
            ], 422);
        }
    }

    private function normalizeAccountRole($role): string {
        $role = strtolower(trim((string)$role));

        if (!in_array($role, ['admin', 'manager', 'employee', 'client'], true)) {
            $role = 'employee';
        }

        return $role;
    }

    private function withoutPassword(?array $employee): ?array {
        if (!$employee) {
            return null;
        }

        unset($employee['password']);

        return $employee;
    }

    public function index(): void {
        try {
            $authUser = $this->requireRole(['admin', 'manager']);

            $search = trim((string)($_GET['search'] ?? ''));
            $status = trim((string)($_GET['status'] ?? ''));
            $role = trim((string)($_GET['role'] ?? ''));
            $departmentId = trim((string)($_GET['department_id'] ?? ''));
            $positionId = trim((string)($_GET['position_id'] ?? ''));

            $params = [
                'search' => $search,
                'status' => $status,
                'role' => $role,
                'department_id' => $departmentId,
                'position_id' => $positionId,
                'limit' => $_GET['limit'] ?? 100,
                'page' => $_GET['page'] ?? 1,
            ];

            if ($authUser['role'] === 'manager') {
                $params['manager_id'] = (int)$authUser['id'];

                if ($params['role'] === '') {
                    $params['role'] = $_GET['role'] ?? '';
                }
            }

            $result = $this->employeeModel->getList($params);

            $this->json([
                'status' => 'success',
                'data' => $result['items'],
                'pagination' => $result['pagination'] ?? null,
            ]);
        } catch (Throwable $e) {
            $this->json([
                'status' => 'error',
                'message' => 'Không thể tải danh sách nhân sự: ' . $e->getMessage()
            ], 400);
        }
    }

    public function show($id = null): void {
        try {
            $authUser = $this->requireAuth();
            $employeeId = $this->resolveId($id);

            if (!$employeeId) {
                $this->json([
                    'status' => 'error',
                    'message' => 'Thiếu ID nhân viên.'
                ], 400);
            }

            if (!$this->canAccessEmployee($authUser, $employeeId)) {
                $this->json([
                    'status' => 'error',
                    'message' => 'Bạn không có quyền xem hồ sơ nhân sự này.'
                ], 403);
            }

            $employee = $this->employeeModel->findProfileById($employeeId);

            if (!$employee) {
                $this->json([
                    'status' => 'error',
                    'message' => 'Không tìm thấy nhân viên.'
                ], 404);
            }

            $this->json([
                'status' => 'success',
                'data' => $this->withoutPassword($employee)
            ]);
        } catch (Throwable $e) {
            $this->json([
                'status' => 'error',
                'message' => 'Không thể tải hồ sơ nhân sự: ' . $e->getMessage()
            ], 400);
        }
    }

    public function store(): void {
        try {
            $authUser = $this->requireRole(['admin', 'manager']);
            $input = $this->getInput();

            if ($authUser['role'] === 'manager') {
                $this->storeAccount();
                return;
            }

            $this->validateBasicAccountInput($input);

            $role = $this->normalizeAccountRole($input['role']);

            if ($this->employeeModel->findByEmail($input['email'])) {
                $this->json([
                    'status' => 'error',
                    'message' => 'Email này đã tồn tại trong hệ thống.'
                ], 409);
            }

            $employeeId = $this->employeeModel->create([
                'department_id' => $input['department_id'] ?? null,
                'position_id' => $input['position_id'] ?? null,
                'manager_id' => $input['manager_id'] ?? null,
                'employee_code' => $input['employee_code'] ?? null,
                'full_name' => trim((string)$input['full_name']),
                'email' => trim((string)$input['email']),
                'password' => (string)$input['password'],
                'role' => $role,
                'phone' => $input['phone'] ?? null,
                'gender' => $input['gender'] ?? null,
                'date_of_birth' => $input['date_of_birth'] ?? null,
                'address' => $input['address'] ?? null,
                'total_leave_days' => $input['total_leave_days'] ?? 12,
                'remaining_leave_days' => $input['remaining_leave_days'] ?? 12,
                'status' => $input['status'] ?? 'active',
                'hire_date' => $input['hire_date'] ?? date('Y-m-d'),
            ]);

            $employee = $this->employeeModel->findProfileById($employeeId);

            $this->json([
                'status' => 'success',
                'message' => 'Admin đã tạo tài khoản thành công.',
                'data' => $this->withoutPassword($employee)
            ], 201);
        } catch (Throwable $e) {
            $this->json([
                'status' => 'error',
                'message' => 'Không thể tạo tài khoản: ' . $e->getMessage()
            ], 400);
        }
    }

    public function storeAccount(): void {
        try {
            $authUser = $this->requireRole(['manager']);
            $input = $this->getInput();

            $this->validateBasicAccountInput($input);

            $role = $this->normalizeAccountRole($input['role']);

            if (!in_array($role, ['employee', 'client'], true)) {
                $this->json([
                    'status' => 'error',
                    'message' => 'Manager chỉ được tạo tài khoản Employee hoặc Client.'
                ], 403);
            }

            if ($this->employeeModel->findByEmail($input['email'])) {
                $this->json([
                    'status' => 'error',
                    'message' => 'Email này đã tồn tại trong hệ thống.'
                ], 409);
            }

            $employeeId = $this->employeeModel->createPendingAccount([
                'department_id' => $input['department_id'] ?? null,
                'position_id' => $input['position_id'] ?? null,
                'employee_code' => $input['employee_code'] ?? null,
                'full_name' => trim((string)$input['full_name']),
                'email' => trim((string)$input['email']),
                'password' => (string)$input['password'],
                'role' => $role,
                'phone' => $input['phone'] ?? null,
                'gender' => $input['gender'] ?? null,
                'date_of_birth' => $input['date_of_birth'] ?? null,
                'address' => $input['address'] ?? null,
                'hire_date' => $input['hire_date'] ?? date('Y-m-d'),
            ], (int)$authUser['id']);

            $employee = $this->employeeModel->findProfileById($employeeId);

            $this->json([
                'status' => 'success',
                'message' => 'Tạo tài khoản thành công. Tài khoản đang chờ Admin duyệt trước khi đăng nhập.',
                'data' => $this->withoutPassword($employee)
            ], 201);
        } catch (Throwable $e) {
            $this->json([
                'status' => 'error',
                'message' => 'Không thể tạo tài khoản chờ duyệt: ' . $e->getMessage()
            ], 400);
        }
    }

    public function pendingAccounts(): void {
        try {
            $this->requireRole(['admin']);

            $filters = [
                'search' => $_GET['search'] ?? '',
                'manager_id' => $_GET['manager_id'] ?? null,
            ];

            $accounts = $this->employeeModel->listPendingAccounts($filters);

            $this->json([
                'status' => 'success',
                'data' => array_map(function ($account) {
                    return $this->withoutPassword($account);
                }, $accounts)
            ]);
        } catch (Throwable $e) {
            $this->json([
                'status' => 'error',
                'message' => 'Không thể tải danh sách tài khoản chờ duyệt: ' . $e->getMessage()
            ], 400);
        }
    }

    public function approveAccount($id = null): void {
        try {
            $authUser = $this->requireRole(['admin']);
            $employeeId = $this->resolveId($id);

            if (!$employeeId) {
                $this->json([
                    'status' => 'error',
                    'message' => 'Thiếu ID tài khoản cần duyệt.'
                ], 400);
            }

            $employee = $this->employeeModel->findById($employeeId);

            if (!$employee) {
                $this->json([
                    'status' => 'error',
                    'message' => 'Không tìm thấy tài khoản.'
                ], 404);
            }

            if (($employee['status'] ?? '') !== 'inactive') {
                $this->json([
                    'status' => 'error',
                    'message' => 'Chỉ tài khoản đang chờ duyệt mới có thể approve.'
                ], 422);
            }

            if (!in_array(strtolower((string)$employee['role']), ['employee', 'client'], true)) {
                $this->json([
                    'status' => 'error',
                    'message' => 'Admin chỉ duyệt tài khoản Employee hoặc Client do Manager tạo.'
                ], 422);
            }

            $updated = $this->employeeModel->approveAccount($employeeId, (int)$authUser['id']);

            if (!$updated) {
                $this->json([
                    'status' => 'error',
                    'message' => 'Không thể duyệt tài khoản hoặc tài khoản đã được xử lý trước đó.'
                ], 409);
            }

            $approved = $this->employeeModel->findProfileById($employeeId);

            $this->json([
                'status' => 'success',
                'message' => 'Admin đã duyệt tài khoản. Người dùng hiện có thể đăng nhập.',
                'data' => $this->withoutPassword($approved)
            ]);
        } catch (Throwable $e) {
            $this->json([
                'status' => 'error',
                'message' => 'Không thể duyệt tài khoản: ' . $e->getMessage()
            ], 400);
        }
    }

    public function rejectAccount($id = null): void {
        try {
            $authUser = $this->requireRole(['admin']);
            $employeeId = $this->resolveId($id);

            if (!$employeeId) {
                $this->json([
                    'status' => 'error',
                    'message' => 'Thiếu ID tài khoản cần từ chối.'
                ], 400);
            }

            $employee = $this->employeeModel->findById($employeeId);

            if (!$employee) {
                $this->json([
                    'status' => 'error',
                    'message' => 'Không tìm thấy tài khoản.'
                ], 404);
            }

            if (($employee['status'] ?? '') !== 'inactive') {
                $this->json([
                    'status' => 'error',
                    'message' => 'Chỉ tài khoản đang chờ duyệt mới có thể reject.'
                ], 422);
            }

            if (!in_array(strtolower((string)$employee['role']), ['employee', 'client'], true)) {
                $this->json([
                    'status' => 'error',
                    'message' => 'Admin chỉ từ chối tài khoản Employee hoặc Client do Manager tạo.'
                ], 422);
            }

            $updated = $this->employeeModel->rejectAccount($employeeId, (int)$authUser['id']);

            if (!$updated) {
                $this->json([
                    'status' => 'error',
                    'message' => 'Không thể từ chối tài khoản hoặc tài khoản đã được xử lý trước đó.'
                ], 409);
            }

            $rejected = $this->employeeModel->findProfileById($employeeId);

            $this->json([
                'status' => 'success',
                'message' => 'Admin đã từ chối tài khoản. Tài khoản đã chuyển sang trạng thái bị khóa.',
                'data' => $this->withoutPassword($rejected)
            ]);
        } catch (Throwable $e) {
            $this->json([
                'status' => 'error',
                'message' => 'Không thể từ chối tài khoản: ' . $e->getMessage()
            ], 400);
        }
    }

    public function update($id = null): void {
        try {
            $authUser = $this->requireAuth();
            $employeeId = $this->resolveId($id);

            if (!$employeeId) {
                $this->json([
                    'status' => 'error',
                    'message' => 'Thiếu ID nhân viên.'
                ], 400);
            }

            if (!$this->canAccessEmployee($authUser, $employeeId)) {
                $this->json([
                    'status' => 'error',
                    'message' => 'Bạn không có quyền chỉnh sửa hồ sơ này.'
                ], 403);
            }

            $employee = $this->employeeModel->findById($employeeId);

            if (!$employee) {
                $this->json([
                    'status' => 'error',
                    'message' => 'Không tìm thấy nhân viên.'
                ], 404);
            }

            $input = $this->getInput();
            $role = strtolower((string)($authUser['role'] ?? ''));

            if ($role === 'admin') {
                $allowedFields = [
                    'department_id',
                    'position_id',
                    'manager_id',
                    'employee_code',
                    'full_name',
                    'email',
                    'password',
                    'role',
                    'phone',
                    'gender',
                    'date_of_birth',
                    'address',
                    'total_leave_days',
                    'remaining_leave_days',
                    'status',
                    'hire_date',
                    'resigned_date',
                ];
            } elseif ($role === 'manager' && (int)$authUser['id'] !== $employeeId) {
                $allowedFields = [
                    'department_id',
                    'position_id',
                    'manager_id',
                    'full_name',
                    'phone',
                    'gender',
                    'date_of_birth',
                    'address',
                    'hire_date',
                ];
            } else {
                $allowedFields = [
                    'full_name',
                    'phone',
                    'gender',
                    'date_of_birth',
                    'address',
                ];
            }

            $data = [];

            foreach ($allowedFields as $field) {
                if (array_key_exists($field, $input)) {
                    $value = is_string($input[$field]) ? trim($input[$field]) : $input[$field];
                    $data[$field] = ($value === '') ? null : $value;
                }
            }

            if (isset($data['full_name']) && $data['full_name'] === null) {
                $this->json([
                    'status' => 'error',
                    'message' => 'Họ và tên không được để trống.'
                ], 422);
            }

            if (isset($data['email']) && $data['email'] !== null) {
                if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                    $this->json([
                        'status' => 'error',
                        'message' => 'Email không hợp lệ.'
                    ], 422);
                }

                $existing = $this->employeeModel->findByEmail($data['email']);

                if ($existing && (int)$existing['id'] !== $employeeId) {
                    $this->json([
                        'status' => 'error',
                        'message' => 'Email này đã tồn tại trong hệ thống.'
                    ], 409);
                }
            }

            if (isset($data['gender']) && $data['gender'] !== null && !in_array($data['gender'], ['male', 'female', 'other'], true)) {
                $this->json([
                    'status' => 'error',
                    'message' => 'Giới tính không hợp lệ.'
                ], 422);
            }

            if (isset($data['phone']) && $data['phone'] !== null && mb_strlen($data['phone']) > 20) {
                $this->json([
                    'status' => 'error',
                    'message' => 'Số điện thoại tối đa 20 ký tự.'
                ], 422);
            }

            if (isset($data['date_of_birth']) && $data['date_of_birth'] !== null) {
                $date = date_create_from_format('Y-m-d', $data['date_of_birth']);

                if (!$date || $date->format('Y-m-d') !== $data['date_of_birth']) {
                    $this->json([
                        'status' => 'error',
                        'message' => 'Ngày sinh không hợp lệ.'
                    ], 422);
                }
            }

            if (empty($data)) {
                $this->json([
                    'status' => 'error',
                    'message' => 'Không có dữ liệu hợp lệ để cập nhật.'
                ], 422);
            }

            $this->employeeModel->update($employeeId, $data);
            $updated = $this->employeeModel->findProfileById($employeeId);

            $this->json([
                'status' => 'success',
                'message' => 'Cập nhật hồ sơ thành công.',
                'data' => $this->withoutPassword($updated)
            ]);
        } catch (Throwable $e) {
            $this->json([
                'status' => 'error',
                'message' => 'Không thể cập nhật hồ sơ: ' . $e->getMessage()
            ], 400);
        }
    }

    public function uploadAvatar($id = null): void {
        try {
            $authUser = $this->requireAuth();
            $employeeId = $this->resolveId($id);

            if (!$employeeId) {
                $this->json([
                    'status' => 'error',
                    'message' => 'Thiếu ID nhân viên.'
                ], 400);
            }

            if (!$this->canAccessEmployee($authUser, $employeeId)) {
                $this->json([
                    'status' => 'error',
                    'message' => 'Bạn không có quyền upload ảnh cho hồ sơ này.'
                ], 403);
            }

            $employee = $this->employeeModel->findById($employeeId);

            if (!$employee) {
                $this->json([
                    'status' => 'error',
                    'message' => 'Không tìm thấy nhân viên.'
                ], 404);
            }

            if (empty($_FILES['avatar']) || !is_uploaded_file($_FILES['avatar']['tmp_name'])) {
                $this->json([
                    'status' => 'error',
                    'message' => 'Vui lòng chọn ảnh đại diện.'
                ], 422);
            }

            $file = $_FILES['avatar'];

            if ($file['error'] !== UPLOAD_ERR_OK) {
                $this->json([
                    'status' => 'error',
                    'message' => 'Upload thất bại. Mã lỗi: ' . $file['error']
                ], 400);
            }

            $maxSize = 4 * 1024 * 1024;

            if ($file['size'] > $maxSize) {
                $this->json([
                    'status' => 'error',
                    'message' => 'Ảnh đại diện tối đa 4MB.'
                ], 422);
            }

            $mimeType = null;

            if (function_exists('finfo_open')) {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mimeType = finfo_file($finfo, $file['tmp_name']);
                finfo_close($finfo);
            }

            if (!$mimeType && function_exists('mime_content_type')) {
                $mimeType = mime_content_type($file['tmp_name']);
            }

            $allowedMimeTypes = [
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/webp' => 'webp',
            ];

            if (!isset($allowedMimeTypes[$mimeType])) {
                $this->json([
                    'status' => 'error',
                    'message' => 'Chỉ cho phép ảnh JPG, PNG hoặc WEBP.'
                ], 422);
            }

            $extension = $allowedMimeTypes[$mimeType];
            $filename = 'avatar_' . $employeeId . '_' . bin2hex(random_bytes(12)) . '.' . $extension;

            $uploadDir = $this->getProjectRoot() . '/public/uploads/avatars';

            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0775, true);
            }

            $destination = $uploadDir . '/' . $filename;

            if (!move_uploaded_file($file['tmp_name'], $destination)) {
                $this->json([
                    'status' => 'error',
                    'message' => 'Không thể lưu ảnh đại diện.'
                ], 500);
            }

            $oldAvatar = $this->employeeModel->getAvatar($employeeId);
            $this->employeeModel->updateAvatar($employeeId, $filename);

            if (!empty($oldAvatar)) {
                $oldPath = $uploadDir . '/' . basename((string)$oldAvatar);

                if (is_file($oldPath)) {
                    @unlink($oldPath);
                }
            }

            $this->json([
                'status' => 'success',
                'message' => 'Cập nhật ảnh đại diện thành công.',
                'data' => [
                    'avatar' => $filename,
                    'avatar_url' => '/creative-agency-hub/public/uploads/avatars/' . rawurlencode($filename)
                ]
            ]);
        } catch (Throwable $e) {
            $this->json([
                'status' => 'error',
                'message' => 'Không thể upload avatar: ' . $e->getMessage()
            ], 400);
        }
    }

    public function documents($id = null): void {
        try {
            $authUser = $this->requireAuth();
            $employeeId = $this->resolveId($id);

            if (!$employeeId) {
                $this->json([
                    'status' => 'error',
                    'message' => 'Thiếu ID nhân viên.'
                ], 400);
            }

            if (!$this->canAccessEmployee($authUser, $employeeId)) {
                $this->json([
                    'status' => 'error',
                    'message' => 'Bạn không có quyền xem hồ sơ điện tử của nhân sự này.'
                ], 403);
            }

            $documents = $this->employeeModel->listDocumentsByEmployee($employeeId);

            foreach ($documents as &$document) {
                $document['download_url'] = '/creative-agency-hub/public/api/employee-documents/' . $document['id'] . '/download';
            }

            unset($document);

            $this->json([
                'status' => 'success',
                'data' => $documents
            ]);
        } catch (Throwable $e) {
            $this->json([
                'status' => 'error',
                'message' => 'Không thể tải hồ sơ điện tử: ' . $e->getMessage()
            ], 400);
        }
    }

    public function uploadDocument($id = null): void {
        try {
            $authUser = $this->requireAuth();
            $employeeId = $this->resolveId($id);

            if (!$employeeId) {
                $this->json([
                    'status' => 'error',
                    'message' => 'Thiếu ID nhân viên.'
                ], 400);
            }

            if (!$this->canAccessEmployee($authUser, $employeeId)) {
                $this->json([
                    'status' => 'error',
                    'message' => 'Bạn không có quyền upload hồ sơ điện tử cho nhân sự này.'
                ], 403);
            }

            $employee = $this->employeeModel->findById($employeeId);

            if (!$employee) {
                $this->json([
                    'status' => 'error',
                    'message' => 'Không tìm thấy nhân viên.'
                ], 404);
            }

            if (empty($_FILES['document']) || !is_uploaded_file($_FILES['document']['tmp_name'])) {
                $this->json([
                    'status' => 'error',
                    'message' => 'Vui lòng chọn tài liệu hồ sơ.'
                ], 422);
            }

            $file = $_FILES['document'];

            if ($file['error'] !== UPLOAD_ERR_OK) {
                $this->json([
                    'status' => 'error',
                    'message' => 'Upload thất bại. Mã lỗi: ' . $file['error']
                ], 400);
            }

            $maxSize = 10 * 1024 * 1024;

            if ($file['size'] > $maxSize) {
                $this->json([
                    'status' => 'error',
                    'message' => 'Tài liệu tối đa 10MB.'
                ], 422);
            }

            $mimeType = null;

            if (function_exists('finfo_open')) {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mimeType = finfo_file($finfo, $file['tmp_name']);
                finfo_close($finfo);
            }

            if (!$mimeType && function_exists('mime_content_type')) {
                $mimeType = mime_content_type($file['tmp_name']);
            }

            $allowedMimeTypes = [
                'application/pdf' => 'pdf',
                'application/msword' => 'doc',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/webp' => 'webp',
            ];

            if (!isset($allowedMimeTypes[$mimeType])) {
                $this->json([
                    'status' => 'error',
                    'message' => 'Chỉ cho phép PDF, DOC, DOCX, JPG, PNG hoặc WEBP.'
                ], 422);
            }

            $extension = $allowedMimeTypes[$mimeType];
            $originalName = $this->sanitizeFileName($file['name']);
            $title = trim((string)($_POST['title'] ?? ''));

            if ($title === '') {
                $title = pathinfo($originalName, PATHINFO_FILENAME);
            }

            $documentType = strtolower((string)($_POST['document_type'] ?? 'other'));
            $allowedTypes = ['identity', 'contract', 'education', 'profile', 'other'];

            if (!in_array($documentType, $allowedTypes, true)) {
                $documentType = 'other';
            }

            $storedName = 'document_' . $employeeId . '_' . bin2hex(random_bytes(12)) . '.' . $extension;
            $relativeDir = 'public/uploads/employee-documents/employee_' . $employeeId;
            $uploadDir = $this->getProjectRoot() . '/' . $relativeDir;

            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0775, true);
            }

            $destination = $uploadDir . '/' . $storedName;

            if (!move_uploaded_file($file['tmp_name'], $destination)) {
                $this->json([
                    'status' => 'error',
                    'message' => 'Không thể lưu tài liệu hồ sơ.'
                ], 500);
            }

            $documentId = $this->employeeModel->createDocument([
                'employee_id' => $employeeId,
                'uploaded_by' => $authUser['id'] ?? null,
                'document_type' => $documentType,
                'title' => $title,
                'original_name' => $originalName,
                'stored_name' => $storedName,
                'file_path' => $relativeDir . '/' . $storedName,
                'mime_type' => $mimeType,
                'file_size' => (int)$file['size'],
            ]);

            $document = $this->employeeModel->findDocumentById($documentId);

            if ($document) {
                $document['download_url'] = '/creative-agency-hub/public/api/employee-documents/' . $document['id'] . '/download';
            }

            $this->json([
                'status' => 'success',
                'message' => 'Tải hồ sơ điện tử thành công.',
                'data' => $document
            ], 201);
        } catch (Throwable $e) {
            $this->json([
                'status' => 'error',
                'message' => 'Không thể upload hồ sơ điện tử: ' . $e->getMessage()
            ], 400);
        }
    }

    public function downloadDocument($id = null): void {
        try {
            $authUser = $this->requireAuth();
            $documentId = $this->resolveId($id);

            if (!$documentId) {
                $this->json([
                    'status' => 'error',
                    'message' => 'Thiếu ID tài liệu.'
                ], 400);
            }

            $document = $this->employeeModel->findDocumentById($documentId);

            if (!$document) {
                $this->json([
                    'status' => 'error',
                    'message' => 'Không tìm thấy tài liệu.'
                ], 404);
            }

            if (!$this->canAccessEmployee($authUser, (int)$document['employee_id'])) {
                $this->json([
                    'status' => 'error',
                    'message' => 'Bạn không có quyền tải tài liệu này.'
                ], 403);
            }

            $absolutePath = $this->getProjectRoot() . '/' . ltrim((string)$document['file_path'], '/');

            if (!is_file($absolutePath)) {
                $this->json([
                    'status' => 'error',
                    'message' => 'File vật lý không còn tồn tại trên server.'
                ], 404);
            }

            if (ob_get_length()) {
                ob_clean();
            }

            header('Content-Type: ' . $document['mime_type']);
            header('Content-Length: ' . filesize($absolutePath));
            header('Content-Disposition: attachment; filename="' . addslashes($document['original_name']) . '"');
            header('X-Content-Type-Options: nosniff');

            readfile($absolutePath);
            exit;
        } catch (Throwable $e) {
            $this->json([
                'status' => 'error',
                'message' => 'Không thể tải tài liệu: ' . $e->getMessage()
            ], 400);
        }
    }

    public function deleteDocument($id = null): void {
        try {
            $authUser = $this->requireAuth();
            $documentId = $this->resolveId($id);

            if (!$documentId) {
                $this->json([
                    'status' => 'error',
                    'message' => 'Thiếu ID tài liệu.'
                ], 400);
            }

            $document = $this->employeeModel->findDocumentById($documentId);

            if (!$document) {
                $this->json([
                    'status' => 'error',
                    'message' => 'Không tìm thấy tài liệu.'
                ], 404);
            }

            if (!$this->canAccessEmployee($authUser, (int)$document['employee_id'])) {
                $this->json([
                    'status' => 'error',
                    'message' => 'Bạn không có quyền xóa tài liệu này.'
                ], 403);
            }

            $this->employeeModel->softDeleteDocument($documentId);

            $absolutePath = $this->getProjectRoot() . '/' . ltrim((string)$document['file_path'], '/');

            if (is_file($absolutePath)) {
                @unlink($absolutePath);
            }

            $this->json([
                'status' => 'success',
                'message' => 'Đã xóa tài liệu hồ sơ.'
            ]);
        } catch (Throwable $e) {
            $this->json([
                'status' => 'error',
                'message' => 'Không thể xóa tài liệu: ' . $e->getMessage()
            ], 400);
        }
    }

    public function adjustLeave($id = null): void {
        try {
            $authUser = $this->requireRole(['admin', 'manager']);
            $employeeId = $this->resolveId($id);

            if (!$employeeId) {
                $this->json([
                    'status' => 'error',
                    'message' => 'Thiếu ID nhân viên.'
                ], 400);
            }

            if (!$this->canAccessEmployee($authUser, $employeeId)) {
                $this->json([
                    'status' => 'error',
                    'message' => 'Bạn không có quyền điều chỉnh quỹ phép cho nhân sự này.'
                ], 403);
            }

            $input = $this->getInput();
            $adjustDays = isset($input['adjustment_days']) ? (float)$input['adjustment_days'] : null;
            $reason = trim((string)($input['reason'] ?? ''));

            if ($adjustDays === null || $adjustDays == 0) {
                $this->json([
                    'status' => 'error',
                    'message' => 'Số ngày điều chỉnh không hợp lệ.'
                ], 422);
            }

            if ($reason === '') {
                $this->json([
                    'status' => 'error',
                    'message' => 'Vui lòng nhập lý do điều chỉnh.'
                ], 422);
            }

            $this->employeeModel->adjustLeaveBalance($employeeId, $adjustDays, $reason, (int)$authUser['id']);

            $this->json([
                'status' => 'success',
                'message' => 'Điều chỉnh quỹ phép thành công.'
            ]);
        } catch (Throwable $e) {
            $this->json([
                'status' => 'error',
                'message' => 'Không thể điều chỉnh quỹ phép: ' . $e->getMessage()
            ], 400);
        }
    }

    public function destroy($id = null): void {
        try {
            $authUser = $this->requireRole(['admin']);
            $employeeId = $this->resolveId($id);

            if (!$employeeId) {
                $this->json([
                    'status' => 'error',
                    'message' => 'Thiếu ID nhân viên.'
                ], 400);
            }

            if ((int)$authUser['id'] === $employeeId) {
                $this->json([
                    'status' => 'error',
                    'message' => 'Bạn không thể tự xóa chính mình.'
                ], 422);
            }

            $employee = $this->employeeModel->findById($employeeId);

            if (!$employee) {
                $this->json([
                    'status' => 'error',
                    'message' => 'Không tìm thấy nhân viên.'
                ], 404);
            }

            $this->employeeModel->softDelete($employeeId);

            $this->json([
                'status' => 'success',
                'message' => 'Đã xóa mềm nhân sự.'
            ]);
        } catch (Throwable $e) {
            $this->json([
                'status' => 'error',
                'message' => 'Không thể xóa nhân sự: ' . $e->getMessage()
            ], 400);
        }
    }
}