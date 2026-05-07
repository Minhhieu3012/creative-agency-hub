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

if (!function_exists('formatDateTimeVi')) {
    function formatDateTimeVi($date, $fallback = 'Chưa cập nhật') {
        if (empty($date)) {
            return $fallback;
        }

        $timestamp = strtotime($date);
        if (!$timestamp) {
            return $fallback;
        }

        return date('d/m/Y H:i', $timestamp);
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

if (!function_exists('documentTypeLabel')) {
    function documentTypeLabel($type) {
        $labels = [
            'identity' => 'Giấy tờ định danh',
            'contract' => 'Hợp đồng',
            'education' => 'Bằng cấp',
            'profile' => 'Hồ sơ cá nhân',
            'other' => 'Tài liệu khác',
        ];

        $type = strtolower((string)$type);
        return $labels[$type] ?? 'Tài liệu khác';
    }
}

if (!function_exists('formatFileSize')) {
    function formatFileSize($bytes) {
        $bytes = (int)$bytes;

        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 2) . ' MB';
        }

        if ($bytes >= 1024) {
            return round($bytes / 1024, 2) . ' KB';
        }

        return $bytes . ' B';
    }
}

if (!function_exists('fileIcon')) {
    function fileIcon($mimeType, $originalName = '') {
        $extension = strtolower(pathinfo((string)$originalName, PATHINFO_EXTENSION));

        if ($extension !== '') {
            return strtoupper(substr($extension, 0, 4));
        }

        if (str_contains((string)$mimeType, 'pdf')) {
            return 'PDF';
        }

        if (str_contains((string)$mimeType, 'word')) {
            return 'DOC';
        }

        if (str_contains((string)$mimeType, 'image')) {
            return 'IMG';
        }

        return 'FILE';
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

        if (preg_match('/^https?:\/\//i', $avatar) || strpos($avatar, '/') === 0) {
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

if (!function_exists('formatLeaveNumber')) {
    function formatLeaveNumber($value) {
        return rtrim(rtrim(number_format((float)$value, 2, '.', ''), '0'), '.');
    }
}

$profileError = null;
$employee = null;
$documents = [];

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
    } else {
        $documents = $employeeModel->listDocumentsByEmployee((int)$employee['id']);
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

<section class="hrm-grid" data-profile-page data-employee-id="<?php echo e($employee['id']); ?>">
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

            <button class="btn btn-light" type="button" data-hrm-action="edit-profile">
                <span>✎</span>
                <span>Chỉnh sửa hồ sơ</span>
            </button>

            <button class="btn btn-light" type="button" data-hrm-action="upload-avatar">
                <span>＋</span>
                <span>Tải ảnh đại diện</span>
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
                                <small><?php echo e(formatLeaveNumber($remainingLeaveDays)); ?> / <?php echo e(formatLeaveNumber($totalLeaveDays)); ?> ngày</small>
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
                    <button class="btn btn-soft" type="button" data-hrm-action="edit-profile">✎ Chỉnh sửa</button>
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
                    <button class="btn btn-soft" type="button" data-hrm-action="upload-document">＋ Tải lên</button>
                </div>

                <div class="card-body">
                    <div class="document-grid">
                        <button class="document-card" type="button" data-hrm-action="upload-document" style="border-style: dashed;">
                            <div class="document-icon">＋</div>
                            <div class="document-info">
                                <strong>Tải tài liệu hồ sơ</strong>
                                <small>PDF, DOC, DOCX, JPG, PNG, WEBP, tối đa 10MB</small>
                            </div>
                            <span></span>
                        </button>

                        <?php if (empty($documents)): ?>
                            <div class="document-card">
                                <div class="document-icon">ℹ</div>
                                <div class="document-info">
                                    <strong>Chưa có tài liệu</strong>
                                    <small>Tài liệu upload sẽ hiển thị tại đây.</small>
                                </div>
                                <button class="document-download" type="button">›</button>
                            </div>
                        <?php else: ?>
                            <?php foreach ($documents as $document): ?>
                                <div class="document-card" data-document-id="<?php echo e($document['id']); ?>">
                                    <div class="document-icon"><?php echo e(fileIcon($document['mime_type'] ?? '', $document['original_name'] ?? '')); ?></div>
                                    <div class="document-info">
                                        <strong><?php echo e($document['title']); ?></strong>
                                        <small>
                                            <?php echo e(documentTypeLabel($document['document_type'] ?? 'other')); ?>
                                            • <?php echo e(formatFileSize($document['file_size'] ?? 0)); ?>
                                            • <?php echo e(formatDateTimeVi($document['created_at'] ?? null)); ?>
                                        </small>
                                    </div>
                                    <div style="display: flex; gap: 8px;">
                                        <button class="document-download" type="button" title="Tải xuống" data-document-download="<?php echo e($document['id']); ?>">↓</button>
                                        <button class="document-download" type="button" title="Xóa" data-document-delete="<?php echo e($document['id']); ?>">×</button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </article>
        </div>
    </section>

    <template id="profile-edit-template">
        <form data-profile-edit-form data-employee-id="<?php echo e($employee['id']); ?>">
            <div style="display: grid; gap: 16px;">
                <div style="display: grid; gap: 8px;">
                    <label for="profile_full_name" style="font-weight: 800;">Họ và tên</label>
                    <input id="profile_full_name" name="full_name" type="text" required value="<?php echo e($employee['full_name'] ?? ''); ?>" style="width: 100%; padding: 12px 14px; border: 1px solid var(--line-dark); border-radius: 12px;">
                </div>

                <div style="display: grid; gap: 8px;">
                    <label for="profile_phone" style="font-weight: 800;">Số điện thoại</label>
                    <input id="profile_phone" name="phone" type="text" maxlength="20" value="<?php echo e($employee['phone'] ?? ''); ?>" style="width: 100%; padding: 12px 14px; border: 1px solid var(--line-dark); border-radius: 12px;">
                </div>

                <div style="display: grid; gap: 8px;">
                    <label for="profile_gender" style="font-weight: 800;">Giới tính</label>
                    <select id="profile_gender" name="gender" style="width: 100%; padding: 12px 14px; border: 1px solid var(--line-dark); border-radius: 12px;">
                        <option value="other" <?php echo (($employee['gender'] ?? '') === 'other') ? 'selected' : ''; ?>>Khác</option>
                        <option value="male" <?php echo (($employee['gender'] ?? '') === 'male') ? 'selected' : ''; ?>>Nam</option>
                        <option value="female" <?php echo (($employee['gender'] ?? '') === 'female') ? 'selected' : ''; ?>>Nữ</option>
                    </select>
                </div>

                <div style="display: grid; gap: 8px;">
                    <label for="profile_date_of_birth" style="font-weight: 800;">Ngày sinh</label>
                    <input id="profile_date_of_birth" name="date_of_birth" type="date" value="<?php echo e($employee['date_of_birth'] ?? ''); ?>" style="width: 100%; padding: 12px 14px; border: 1px solid var(--line-dark); border-radius: 12px;">
                </div>

                <div style="display: grid; gap: 8px;">
                    <label for="profile_address" style="font-weight: 800;">Địa chỉ thường trú</label>
                    <textarea id="profile_address" name="address" rows="4" style="width: 100%; padding: 12px 14px; border: 1px solid var(--line-dark); border-radius: 12px; resize: vertical;"><?php echo e($employee['address'] ?? ''); ?></textarea>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 12px; flex-wrap: wrap; margin-top: 8px;">
                    <button class="btn btn-light" type="button" data-modal-close>Hủy</button>
                    <button class="btn btn-emerald" type="submit">Lưu thay đổi</button>
                </div>
            </div>
        </form>
    </template>

    <template id="profile-avatar-template">
        <form data-profile-avatar-form data-employee-id="<?php echo e($employee['id']); ?>" enctype="multipart/form-data">
            <div style="display: grid; gap: 16px;">
                <div class="document-card">
                    <div class="document-icon">IMG</div>
                    <div class="document-info">
                        <strong>Ảnh đại diện mới</strong>
                        <small>Chỉ nhận JPG, PNG, WEBP. Dung lượng tối đa 4MB.</small>
                    </div>
                    <span></span>
                </div>

                <input name="avatar" type="file" accept="image/jpeg,image/png,image/webp" required style="width: 100%; padding: 12px; border: 1px dashed var(--line-dark); border-radius: 12px;">

                <div style="display: flex; justify-content: flex-end; gap: 12px; flex-wrap: wrap; margin-top: 8px;">
                    <button class="btn btn-light" type="button" data-modal-close>Hủy</button>
                    <button class="btn btn-emerald" type="submit">Tải lên</button>
                </div>
            </div>
        </form>
    </template>

    <template id="profile-document-template">
        <form data-profile-document-form data-employee-id="<?php echo e($employee['id']); ?>" enctype="multipart/form-data">
            <div style="display: grid; gap: 16px;">
                <div style="display: grid; gap: 8px;">
                    <label style="font-weight: 800;">Tên tài liệu</label>
                    <input name="title" type="text" placeholder="Ví dụ: CCCD mặt trước, Bằng đại học, Hợp đồng thử việc..." style="width: 100%; padding: 12px 14px; border: 1px solid var(--line-dark); border-radius: 12px;">
                </div>

                <div style="display: grid; gap: 8px;">
                    <label style="font-weight: 800;">Loại tài liệu</label>
                    <select name="document_type" style="width: 100%; padding: 12px 14px; border: 1px solid var(--line-dark); border-radius: 12px;">
                        <option value="identity">Giấy tờ định danh</option>
                        <option value="contract">Hợp đồng</option>
                        <option value="education">Bằng cấp</option>
                        <option value="profile">Hồ sơ cá nhân</option>
                        <option value="other">Tài liệu khác</option>
                    </select>
                </div>

                <div style="display: grid; gap: 8px;">
                    <label style="font-weight: 800;">File hồ sơ</label>
                    <input name="document" type="file" accept=".pdf,.doc,.docx,image/jpeg,image/png,image/webp" required style="width: 100%; padding: 12px; border: 1px dashed var(--line-dark); border-radius: 12px;">
                    <small style="color: var(--text-muted);">Chỉ nhận PDF, DOC, DOCX, JPG, PNG, WEBP. Dung lượng tối đa 10MB.</small>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 12px; flex-wrap: wrap; margin-top: 8px;">
                    <button class="btn btn-light" type="button" data-modal-close>Hủy</button>
                    <button class="btn btn-emerald" type="submit">Tải tài liệu</button>
                </div>
            </div>
        </form>
    </template>
</section>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/app.php';
?>