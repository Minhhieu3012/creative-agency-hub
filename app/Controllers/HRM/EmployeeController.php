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

        return [
            'id' => isset($payload['id']) ? (int)$payload['id'] : null,
            'email' => $payload['email'] ?? null,
            'role' => strtolower((string)($payload['role'] ?? '')),
            'full_name' => $payload['full_name'] ?? null,
        ];
    }

    private function getAuthUser(): ?array {
        $headers = function_exists('getallheaders') ? getallheaders() : [];
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';

        if ($authHeader) {
            $token = trim(str_replace('Bearer ', '', $authHeader));

            try {
                $decoded = $this->jwt->decode($token);
                $authUser = $this->normalizeAuthPayload($decoded);

                if ($authUser && !empty($authUser['id'])) {
                    return $authUser;
                }
            } catch (Throwable $e) {
                // Fallback xuống cookie/session.
            }
        }

        if (!empty($_COOKIE['cah_token'])) {
            try {
                $decoded = $this->jwt->decode($_COOKIE['cah_token']);
                $authUser = $this->normalizeAuthPayload($decoded);

                if ($authUser && !empty($authUser['id'])) {
                    return $authUser;
                }
            } catch (Throwable $e) {
                // Fallback xuống session.
            }
        }

        if (!empty($_COOKIE['cah_auth_token'])) {
            try {
                $decoded = $this->jwt->decode($_COOKIE['cah_auth_token']);
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
                'full_name' => null,
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
        if (in_array($authUser['role'], ['admin', 'manager'], true)) {
            return true;
        }

        return (int)$authUser['id'] === $employeeId;
    }

    private function getProjectRoot(): string {
        return dirname(__DIR__, 3);
    }

    private function sanitizeFileName(string $name): string {
        $name = preg_replace('/[^\pL\pN\.\-_ ]/u', '', $name);
        $name = trim((string)$name);

        return $name !== '' ? $name : 'document';
    }

    private function departmentExists(int $departmentId): bool {
        $stmt = $this->db->prepare("
            SELECT id
            FROM departments
            WHERE id = :id
              AND status = 'active'
              AND deleted_at IS NULL
            LIMIT 1
        ");

        $stmt->execute([':id' => $departmentId]);
        return (bool)$stmt->fetchColumn();
    }

    private function positionExists(int $positionId): bool {
        $stmt = $this->db->prepare("
            SELECT id
            FROM positions
            WHERE id = :id
              AND status = 'active'
              AND deleted_at IS NULL
            LIMIT 1
        ");

        $stmt->execute([':id' => $positionId]);
        return (bool)$stmt->fetchColumn();
    }

    public function index(): void {
        try {
            $authUser = $this->requireAuth();

            if (!in_array($authUser['role'], ['admin', 'manager'], true)) {
                $this->json([
                    'status' => 'error',
                    'message' => 'Bạn không có quyền xem danh sách nhân sự.'
                ], 403);
            }

            $search = trim($_GET['search'] ?? '');
            $status = trim($_GET['status'] ?? '');

            $allowedStatus = ['active', 'inactive', 'resigned', 'suspended'];

            $query = "SELECT e.*, d.name AS department_name, p.name AS position_name
                      FROM employees e
                      LEFT JOIN departments d ON e.department_id = d.id
                      LEFT JOIN positions p ON e.position_id = p.id
                      WHERE e.deleted_at IS NULL
                        AND (e.full_name LIKE :s1 OR e.email LIKE :s2 OR e.employee_code LIKE :s3)";

            if ($status !== '' && in_array($status, $allowedStatus, true)) {
                $query .= " AND e.status = :status";
            }

            $query .= " ORDER BY e.id DESC";

            $stmt = $this->db->prepare($query);
            $stmt->bindValue(':s1', "%{$search}%");
            $stmt->bindValue(':s2', "%{$search}%");
            $stmt->bindValue(':s3', "%{$search}%");

            if ($status !== '' && in_array($status, $allowedStatus, true)) {
                $stmt->bindValue(':status', $status);
            }

            $stmt->execute();

            $this->json([
                'status' => 'success',
                'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)
            ]);
        } catch (Throwable $e) {
            $this->json([
                'status' => 'error',
                'message' => $e->getMessage()
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

            unset($employee['password']);

            $data = $employee;
            $data['employee'] = $employee;

            $this->json([
                'status' => 'success',
                'data' => $data
            ]);
        } catch (Throwable $e) {
            $this->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function store(): void {
        try {
            $authUser = $this->requireAuth();
            $input = $this->getInput();

            if (!in_array($authUser['role'], ['admin', 'manager'], true)) {
                $this->json([
                    'status' => 'error',
                    'message' => 'Bạn không có quyền tạo nhân sự.'
                ], 403);
            }

            if (empty($input['full_name']) || empty($input['email']) || empty($input['password']) || empty($input['role'])) {
                $this->json([
                    'status' => 'error',
                    'message' => 'Vui lòng điền đầy đủ tên, email, mật khẩu và vai trò.'
                ], 400);
            }

            $role = strtolower((string)$input['role']);
            $allowedRoles = ['admin', 'manager', 'employee', 'client'];

            if (!in_array($role, $allowedRoles, true)) {
                $this->json([
                    'status' => 'error',
                    'message' => 'Vai trò không hợp lệ.'
                ], 422);
            }

            if ($authUser['role'] === 'manager' && $role !== 'employee') {
                $this->json([
                    'status' => 'error',
                    'message' => 'Quản lý chỉ được phép tạo tài khoản nhân viên.'
                ], 403);
            }

            if ($this->employeeModel->findByEmail($input['email'])) {
                $this->json([
                    'status' => 'error',
                    'message' => 'Email này đã tồn tại trong hệ thống.'
                ], 409);
            }

            $id = $this->employeeModel->create([
                'department_id' => $input['department_id'] ?? null,
                'position_id' => $input['position_id'] ?? null,
                'manager_id' => $input['manager_id'] ?? null,
                'employee_code' => $input['employee_code'] ?? ('EMP' . time()),
                'full_name' => trim((string)$input['full_name']),
                'email' => trim((string)$input['email']),
                'password' => (string)$input['password'],
                'role' => $role,
                'phone' => $input['phone'] ?? null,
                'gender' => $input['gender'] ?? null,
                'date_of_birth' => $input['date_of_birth'] ?? null,
                'address' => $input['address'] ?? null,
                'status' => 'active',
                'hire_date' => $input['hire_date'] ?? date('Y-m-d'),
            ]);

            $this->json([
                'status' => 'success',
                'message' => 'Tạo nhân sự thành công.',
                'data' => ['id' => $id]
            ], 201);
        } catch (Throwable $e) {
            $this->json([
                'status' => 'error',
                'message' => $e->getMessage()
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
                    'message' => 'Bạn không có quyền chỉnh sửa hồ sơ nhân sự này.'
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
            $isAdmin = $authUser['role'] === 'admin';
            $isManager = $authUser['role'] === 'manager';
            $isSelf = (int)$authUser['id'] === $employeeId;

            if ($isManager && !$isSelf && strtolower((string)($employee['role'] ?? '')) !== 'employee') {
                $this->json([
                    'status' => 'error',
                    'message' => 'Manager chỉ được cập nhật nhân viên cấp employee.'
                ], 403);
            }

            $profileFields = [
                'full_name',
                'phone',
                'gender',
                'date_of_birth',
                'address',
            ];

            $hrmFields = [
                'department_id',
                'position_id',
                'status',
                'role',
                'manager_id',
            ];

            $allowedFields = ($isAdmin || $isManager)
                ? array_merge($profileFields, $hrmFields)
                : $profileFields;

            $data = [];

            foreach ($allowedFields as $field) {
                if (!array_key_exists($field, $input)) {
                    continue;
                }

                $value = is_string($input[$field]) ? trim($input[$field]) : $input[$field];
                $data[$field] = ($value === '') ? null : $value;
            }

            if (isset($data['full_name']) && $data['full_name'] === null) {
                $this->json([
                    'status' => 'error',
                    'message' => 'Họ và tên không được để trống.'
                ], 422);
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

            if (isset($data['department_id'])) {
                $departmentId = (int)$data['department_id'];

                if ($departmentId <= 0 || !$this->departmentExists($departmentId)) {
                    $this->json([
                        'status' => 'error',
                        'message' => 'Phòng ban không hợp lệ.'
                    ], 422);
                }

                $data['department_id'] = $departmentId;
            }

            if (isset($data['position_id'])) {
                $positionId = (int)$data['position_id'];

                if ($positionId <= 0 || !$this->positionExists($positionId)) {
                    $this->json([
                        'status' => 'error',
                        'message' => 'Chức danh không hợp lệ.'
                    ], 422);
                }

                $data['position_id'] = $positionId;
            }

            if (isset($data['manager_id'])) {
                $managerId = $data['manager_id'] !== null ? (int)$data['manager_id'] : null;

                if ($managerId !== null && $managerId <= 0) {
                    $this->json([
                        'status' => 'error',
                        'message' => 'Quản lý trực tiếp không hợp lệ.'
                    ], 422);
                }

                if ($managerId !== null && $managerId === $employeeId) {
                    $this->json([
                        'status' => 'error',
                        'message' => 'Nhân sự không thể tự quản lý chính mình.'
                    ], 422);
                }

                $data['manager_id'] = $managerId;
            }

            if (isset($data['role'])) {
                $role = strtolower((string)$data['role']);
                $allowedRoles = ['admin', 'manager', 'employee', 'client'];

                if (!in_array($role, $allowedRoles, true)) {
                    $this->json([
                        'status' => 'error',
                        'message' => 'Vai trò không hợp lệ.'
                    ], 422);
                }

                if ($isManager && $role !== 'employee') {
                    $this->json([
                        'status' => 'error',
                        'message' => 'Manager chỉ được giữ nhân sự ở vai trò employee.'
                    ], 403);
                }

                $data['role'] = $role;
            }

            if (isset($data['status'])) {
                $status = strtolower((string)$data['status']);
                $allowedStatus = ['active', 'inactive', 'resigned', 'suspended'];

                if (!in_array($status, $allowedStatus, true)) {
                    $this->json([
                        'status' => 'error',
                        'message' => 'Trạng thái nhân sự không hợp lệ.'
                    ], 422);
                }

                $data['status'] = $status;

                if ($status === 'resigned') {
                    $data['resigned_date'] = date('Y-m-d');
                } else {
                    $data['resigned_date'] = null;
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

            if ($updated) {
                unset($updated['password']);
            }

            $responseData = $updated ?: [];
            $responseData['employee'] = $updated;

            $this->json([
                'status' => 'success',
                'message' => 'Cập nhật hồ sơ thành công.',
                'data' => $responseData
            ]);
        } catch (Throwable $e) {
            $this->json([
                'status' => 'error',
                'message' => $e->getMessage()
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
                    'message' => 'Bạn chỉ được upload ảnh cho hồ sơ của chính mình.'
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
                'message' => $e->getMessage()
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
                    'message' => 'Bạn chỉ được xem hồ sơ điện tử của chính mình.'
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
                'message' => $e->getMessage()
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
                    'message' => 'Bạn chỉ được upload hồ sơ điện tử cho chính mình.'
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
                'message' => $e->getMessage()
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
                'message' => $e->getMessage()
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
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function adjustLeave($id = null): void {
        try {
            $authUser = $this->requireAuth();

            if (!in_array($authUser['role'], ['admin', 'manager'], true)) {
                $this->json([
                    'status' => 'error',
                    'message' => 'Bạn không có quyền điều chỉnh quỹ phép.'
                ], 403);
            }

            $employeeId = $this->resolveId($id);

            if (!$employeeId) {
                $this->json([
                    'status' => 'error',
                    'message' => 'Thiếu ID nhân viên.'
                ], 400);
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
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function destroy($id = null): void {
        try {
            $authUser = $this->requireAuth();

            if (!in_array($authUser['role'], ['admin', 'manager'], true)) {
                $this->json([
                    'status' => 'error',
                    'message' => 'Bạn không có quyền xóa nhân sự.'
                ], 403);
            }

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

            $this->employeeModel->softDelete($employeeId);

            $this->json([
                'status' => 'success',
                'message' => 'Đã xóa mềm nhân sự.'
            ]);
        } catch (Throwable $e) {
            $this->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 400);
        }
    }
}