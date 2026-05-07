<?php
use App\Models\HRM\Employee;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../core/Database.php';
require_once __DIR__ . '/../../Models/HRM/Employee.php';

$pageTitle = 'Hồ sơ cá nhân | Creative Agency Hub';
$pageCss = ['hrm.css'];
$pageJs = ['hrm.js'];
$activeMenu = 'profile';
$topbarTitle = 'Hồ sơ của tôi';
$brandName = 'Creative Agency Hub';

if (!function_exists('e')) {
    function e($value) {
        return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('displayValue')) {
    function displayValue($value, $fallback = 'Chưa cập nhật') {
        if ($value === null || $value === '') {
            return $fallback;
        }

        return (string)$value;
    }
}

if (!function_exists('formatDateVi')) {
    function formatDateVi($date, $fallback = 'Chưa cập nhật') {
        if (empty($date) || $date === '0000-00-00') {
            return $fallback;
        }

        $timestamp = strtotime($date);
        if (!$timestamp) {
            return $fallback;
        }

        return date('d/m/Y', $timestamp);
    }
}

if (!function_exists('roleLabel')) {
    function roleLabel($role) {
        $labels = [
            'admin' => 'Quản trị viên',
            'manager' => 'Quản lý',
            'employee' => 'Nhân viên',
            'client' => 'Khách hàng',
        ];

        $role = strtolower((string)$role);
        return $labels[$role] ?? ucfirst($role ?: 'Nhân viên');
    }
}

if (!function_exists('statusLabel')) {
    function statusLabel($status) {
        $labels = [
            'active' => 'Đang hoạt động',
            'inactive' => 'Tạm ngưng',
            'resigned' => 'Đã nghỉ việc',
            'suspended' => 'Bị khóa',
        ];

        $status = strtolower((string)$status);
        return $labels[$status] ?? ucfirst($status ?: 'Chưa cập nhật');
    }
}

if (!function_exists('genderLabel')) {
    function genderLabel($gender) {
        $labels = [
            'male' => 'Nam',
            'female' => 'Nữ',
            'other' => 'Khác',
        ];

        $gender = strtolower((string)$gender);
        return $labels[$gender] ?? 'Chưa cập nhật';
    }
}

if (!function_exists('initialsFromName')) {
    function initialsFromName($name) {
        $name = trim((string)$name);
        if ($name === '') {
            return 'CA';
        }

        $parts = preg_split('/\s+/u', $name);
        $first = $parts[0] ?? '';
        $last = count($parts) > 1 ? $parts[count($parts) - 1] : '';

        $firstInitial = function_exists('mb_substr') ? mb_substr($first, 0, 1, 'UTF-8') : substr($first, 0, 1);
        $lastInitial = function_exists('mb_substr') ? mb_substr($last, 0, 1, 'UTF-8') : substr($last, 0, 1);

        $initials = $firstInitial . ($lastInitial ?: '');
        return function_exists('mb_strtoupper') ? mb_strtoupper($initials, 'UTF-8') : strtoupper($initials);
    }
}

if (!function_exists('resolveAvatarUrl')) {
    function resolveAvatarUrl($avatar) {
        $avatar = trim((string)$avatar);
        if ($avatar === '') {
            return null;
        }

        if (preg_match('/^https?:\/\//i', $avatar) || str_starts_with($avatar, '/')) {
            return $avatar;
        }

        return '/creative-agency-hub/public/uploads/avatars/' . rawurlencode($avatar);
    }
}

if (!function_exists('calculateProfileCompleteness')) {
    function calculateProfileCompleteness($employee) {
        $fields = [
            'full_name',
            'email',
            'phone',
            'gender',
            'date_of_birth',
            'address',
            'hire_date',
            'department_name',
            'position_name',
        ];

        $filled = 0;
        foreach ($fields as $field) {
            if (!empty($employee[$field])) {
                $filled++;
            }
        }

        return (int)round(($filled / count($fields)) * 100);
    }
}

$profileError = null;
$employee = null;

try {
    $employeeModel = new Employee();

    if (!empty($_SESSION['user_id'])) {
        $employee = $employeeModel->findProfileById((int)$_SESSION['user_id']);
    }

    if (!$employee && !empty($_SESSION['user_email'])) {
        $employee = $employeeModel->findProfileByEmail($_SESSION['user_email']);
    }

    if (!$employee) {
        $profileError = 'Không tìm thấy hồ sơ người dùng đang đăng nhập. Vui lòng đăng nhập lại.';
    }
} catch (Throwable $e) {
    $profileError = 'Không thể tải hồ sơ nhân viên: ' . $e->getMessage();
}

if ($profileError !== null) {
    $currentUser = [
        'name' => 'Khách',
        'role' => 'Chưa xác thực',
        'avatar' => null,
    ];

    ob_start();
    ?>
    <section class="hrm-grid">
        <article class="card">
            <div class="card-body">
                <h1 class="section-title">Không thể tải hồ sơ</h1>
                <p style="margin-top: 12px; color: var(--text-muted, #64748b);">
                    <?php echo e($profileError); ?>
                </p>
                <div style="margin-top: 20px; display: flex; gap: 12px; flex-wrap: wrap;">
                    <a class="btn btn-emerald" href="/creative-agency-hub/app/View/auth/login.php">Đăng nhập lại</a>
                    <a class="btn btn-light" href="/creative-agency-hub/app/View/hrm/profile.php">Tải lại trang</a>
                </div>
            </div>
        </article>
    </section>
    <?php
    $content = ob_get_clean();
    require __DIR__ . '/../layouts/app.php';
    exit;
}

$avatarUrl = resolveAvatarUrl($employee['avatar'] ?? null);
$fullName = displayValue($employee['full_name'] ?? null, 'Nhân viên chưa đặt tên');
$departmentName = displayValue($employee['department_name'] ?? null);
$positionName = displayValue($employee['position_name'] ?? null, roleLabel($employee['role'] ?? 'employee'));
$employeeCode = displayValue($employee['employee_code'] ?? null, 'Chưa có mã');
$statusText = statusLabel($employee['status'] ?? null);
$roleText = roleLabel($employee['role'] ?? null);
$hireDateText = formatDateVi($employee['hire_date'] ?? null);
$birthDateText = formatDateVi($employee['date_of_birth'] ?? null);
$totalLeaveDays = (float)($employee['total_leave_days'] ?? 0);
$remainingLeaveDays = (float)($employee['remaining_leave_days'] ?? 0);
$leavePercent = $totalLeaveDays > 0 ? (int)round(($remainingLeaveDays / $totalLeaveDays) * 100) : 0;
$leavePercent = max(0, min(100, $leavePercent));
$profileCompleteness = calculateProfileCompleteness($employee);
$accountHealth = (($employee['status'] ?? '') === 'active') ? 100 : 0;

$currentUser = [
    'name' => $fullName,
    'role' => $roleText,
    'avatar' => $avatarUrl,
];

$kpis = [
    ['label' => 'Độ hoàn thiện hồ sơ', 'value' => $profileCompleteness],
    ['label' => 'Quỹ phép còn lại', 'value' => $leavePercent],
    ['label' => 'Trạng thái tài khoản', 'value' => $accountHealth],
];

ob_start();
?>

<section class="hrm-grid">
    <article class="hrm-profile-hero">
        <div class="profile-avatar-large">
            <?php if (!empty($avatarUrl)): ?>
                <img src="<?php echo e($avatarUrl); ?>" alt="<?php echo e($fullName); ?>">
            <?php else: ?>
                <span><?php echo e(initialsFromName($fullName)); ?></span>
            <?php endif; ?>
        </div>

        <div class="profile-main-info">
            <h1><?php echo e($fullName); ?></h1>
            <p><?php echo e($positionName); ?> • <?php echo e($departmentName); ?></p>

            <div class="profile-badge-row">
                <span class="badge badge-success"><?php echo e($statusText); ?></span>
                <span class="badge badge-info">ID: <?php echo e($employeeCode); ?></span>
                <span class="badge badge-info"><?php echo e($roleText); ?></span>
            </div>
        </div>

        <div class="profile-actions">
            <a href="/creative-agency-hub/app/View/payroll/attendance.php" class="btn btn-emerald">
                <span>↪</span>
                <span>Check-in Trực tuyến</span>
            </a>

            <button class="btn btn-light" type="button" data-hrm-action="mock-save">
                <span>✎</span>
                <span>Chỉnh sửa hồ sơ</span>
            </button>
        </div>
    </article>

    <section class="hrm-two-column">
        <div class="hrm-grid">
            <article class="card">
                <div class="card-body">
                    <h2 class="section-title">Chỉ số hồ sơ hiện tại</h2>

                    <div class="kpi-list" style="margin-top: 26px;">
                        <?php foreach ($kpis as $kpi): ?>
                            <div class="kpi-line">
                                <div class="kpi-line-head">
                                    <span><?php echo e($kpi['label']); ?></span>
                                    <span><?php echo (int)$kpi['value']; ?>%</span>
                                </div>

                                <div class="progress-line">
                                    <div class="progress-line-fill" style="width: <?php echo (int)$kpi['value']; ?>%;"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </article>

            <article class="card">
                <div class="card-body">
                    <h2 class="section-title">Thông tin công việc & quyền lợi</h2>

                    <div class="hrm-grid" style="margin-top: 24px;">
                        <div class="document-card">
                            <div class="document-icon">▤</div>
                            <div class="document-info">
                                <strong>Vai trò hệ thống</strong>
                                <small><?php echo e($roleText); ?></small>
                            </div>
                            <button class="document-download" type="button">›</button>
                        </div>

                        <div class="document-card">
                            <div class="document-icon">✚</div>
                            <div class="document-info">
                                <strong>Ngày phép còn lại</strong>
                                <small><?php echo e(rtrim(rtrim(number_format($remainingLeaveDays, 2, '.', ''), '0'), '.')); ?> / <?php echo e(rtrim(rtrim(number_format($totalLeaveDays, 2, '.', ''), '0'), '.')); ?> ngày</small>
                            </div>
                            <button class="document-download" type="button">›</button>
                        </div>

                        <div class="document-card">
                            <div class="document-icon">◷</div>
                            <div class="document-info">
                                <strong>Ngày bắt đầu</strong>
                                <small><?php echo e($hireDateText); ?></small>
                            </div>
                            <button class="document-download" type="button">›</button>
                        </div>
                    </div>
                </div>
            </article>
        </div>

        <div class="hrm-grid">
            <article class="card">
                <div class="card-header dashboard-card-title-row">
                    <h2>Thông tin cá nhân</h2>
                    <button class="btn btn-soft" type="button" data-hrm-action="mock-save">✎ Chỉnh sửa</button>
                </div>

                <div class="card-body">
                    <div class="info-grid">
                        <div class="info-item">
                            <small>Họ và tên</small>
                            <strong><?php echo e($fullName); ?></strong>
                        </div>

                        <div class="info-item">
                            <small>Ngày sinh</small>
                            <strong><?php echo e($birthDateText); ?></strong>
                        </div>

                        <div class="info-item">
                            <small>Email cá nhân</small>
                            <strong><?php echo e(displayValue($employee['email'] ?? null)); ?></strong>
                        </div>

                        <div class="info-item">
                            <small>Số điện thoại</small>
                            <strong><?php echo e(displayValue($employee['phone'] ?? null)); ?></strong>
                        </div>

                        <div class="info-item">
                            <small>Giới tính</small>
                            <strong><?php echo e(genderLabel($employee['gender'] ?? null)); ?></strong>
                        </div>

                        <div class="info-item">
                            <small>Quản lý trực tiếp</small>
                            <strong><?php echo e(displayValue($employee['manager_name'] ?? null)); ?></strong>
                        </div>

                        <div class="info-item" style="grid-column: 1 / -1;">
                            <small>Địa chỉ thường trú</small>
                            <span><?php echo e(displayValue($employee['address'] ?? null)); ?></span>
                        </div>
                    </div>
                </div>
            </article>

            <article class="card">
                <div class="card-header dashboard-card-title-row">
                    <h2>Hồ sơ điện tử</h2>
                    <button class="btn btn-soft" type="button" data-hrm-action="upload-doc">＋ Tải lên</button>
                </div>

                <div class="card-body">
                    <div class="document-grid">
                        <div class="document-card" style="border-style: dashed;">
                            <div class="document-icon">＋</div>
                            <div class="document-info">
                                <strong>Chưa có tài liệu thật trong cơ sở dữ liệu</strong>
                                <small>Phần này sẽ hiển thị khi module upload hồ sơ điện tử được nối dữ liệu.</small>
                            </div>
                            <span></span>
                        </div>

                        <div class="document-card">
                            <div class="document-icon">ℹ</div>
                            <div class="document-info">
                                <strong>Nguồn dữ liệu hiện tại</strong>
                                <small>Đang lấy trực tiếp từ bảng employees, departments và positions.</small>
                            </div>
                            <button class="document-download" type="button">›</button>
                        </div>
                    </div>
                </div>
            </article>
        </div>
    </section>
</section>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/app.php';
?>