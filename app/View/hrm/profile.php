<?php
$pageTitle = 'Hồ sơ cá nhân | Creative Agency Hub';
$pageCss = ['hrm.css'];
$pageJs = ['hrm.js'];
$activeMenu = 'profile';
$topbarTitle = 'Hồ sơ của tôi';
$brandName = 'Creative Agency Hub';

$baseUrl = $baseUrl ?? (function () {
    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    if (strpos($scriptName, '/public/') !== false) {
        return substr($scriptName, 0, strpos($scriptName, '/public'));
    }
    if (strpos($scriptName, '/app/View/') !== false) {
        return substr($scriptName, 0, strpos($scriptName, '/app/View'));
    }
    $dir = dirname($scriptName);
    return $dir === '/' ? '' : $dir;
})();

$currentUser = $currentUser ?? [
    'name' => 'Nguyễn Minh Tú',
    'role' => 'Senior HR Specialist',
    'avatar' => null,
];

$employee = $employee ?? [
    'name' => 'Nguyễn Minh Tú',
    'position' => 'Chuyên viên Nhân sự Cấp cao',
    'department' => 'Phòng HR & Hành chính',
    'employee_code' => 'EMP-09224',
    'birth_date' => '24/09/1992',
    'email' => 'minhtu.hr@gmail.com',
    'phone' => '+84 908 123 456',
    'address' => '123 Đường Lê Lợi, Phường Bến Thành, Quận 1, TP. Hồ Chí Minh',
    'contract_type' => 'Vô thời hạn',
    'insurance_status' => 'Đang hiệu lực',
    'start_date' => '15/05/2021',
];

$kpis = $kpis ?? [
    ['label' => 'Tiến độ công việc', 'value' => 85],
    ['label' => 'Chỉ số hài lòng', 'value' => 92],
    ['label' => 'Chuyên cần', 'value' => 100],
];

$documents = $documents ?? [
    ['name' => 'CCCD_Mat_Truoc.pdf', 'updated' => 'Cập nhật 2 ngày trước', 'icon' => 'PDF'],
    ['name' => 'So_Yeu_Ly_Lich.docx', 'updated' => 'Cập nhật 1 tháng trước', 'icon' => 'DOC'],
    ['name' => 'Bang_Dai_Hoc.pdf', 'updated' => 'Cập nhật 1 năm trước', 'icon' => 'PDF'],
];

ob_start();
?>

<section class="hrm-grid">
    <article class="hrm-profile-hero">
        <div class="profile-avatar-large">
            <?php if (!empty($currentUser['avatar'])): ?>
                <img src="<?php echo htmlspecialchars($currentUser['avatar']); ?>" alt="<?php echo htmlspecialchars($employee['name']); ?>">
            <?php else: ?>
                <span>MT</span>
            <?php endif; ?>
        </div>

        <div class="profile-main-info">
            <h1><?php echo htmlspecialchars($employee['name']); ?></h1>
            <p><?php echo htmlspecialchars($employee['position']); ?> • <?php echo htmlspecialchars($employee['department']); ?></p>

            <div class="profile-badge-row">
                <span class="badge badge-success">Đã xác minh</span>
                <span class="badge badge-info">ID: <?php echo htmlspecialchars($employee['employee_code']); ?></span>
            </div>
        </div>

        <div class="profile-actions">
            <a href="<?php echo htmlspecialchars($baseUrl); ?>/app/View/payroll/attendance.php" class="btn btn-emerald">
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
                    <h2 class="section-title">Chỉ số KPI Tháng 10</h2>

                    <div class="kpi-list" style="margin-top: 26px;">
                        <?php foreach ($kpis as $kpi): ?>
                            <div class="kpi-line">
                                <div class="kpi-line-head">
                                    <span><?php echo htmlspecialchars($kpi['label']); ?></span>
                                    <span><?php echo (int) $kpi['value']; ?>%</span>
                                </div>

                                <div class="progress-line">
                                    <div class="progress-line-fill" style="width: <?php echo (int) $kpi['value']; ?>%;"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </article>

            <article class="card">
                <div class="card-body">
                    <h2 class="section-title">Hợp đồng & Bảo hiểm</h2>

                    <div class="hrm-grid" style="margin-top: 24px;">
                        <div class="document-card">
                            <div class="document-icon">▤</div>
                            <div class="document-info">
                                <strong>Loại hợp đồng</strong>
                                <small><?php echo htmlspecialchars($employee['contract_type']); ?></small>
                            </div>
                            <button class="document-download" type="button">›</button>
                        </div>

                        <div class="document-card">
                            <div class="document-icon">✚</div>
                            <div class="document-info">
                                <strong>Bảo hiểm y tế</strong>
                                <small><?php echo htmlspecialchars($employee['insurance_status']); ?></small>
                            </div>
                            <button class="document-download" type="button">›</button>
                        </div>

                        <div class="document-card">
                            <div class="document-icon">◷</div>
                            <div class="document-info">
                                <strong>Ngày bắt đầu</strong>
                                <small><?php echo htmlspecialchars($employee['start_date']); ?></small>
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
                            <strong><?php echo htmlspecialchars($employee['name']); ?></strong>
                        </div>

                        <div class="info-item">
                            <small>Ngày sinh</small>
                            <strong><?php echo htmlspecialchars($employee['birth_date']); ?></strong>
                        </div>

                        <div class="info-item">
                            <small>Email cá nhân</small>
                            <strong><?php echo htmlspecialchars($employee['email']); ?></strong>
                        </div>

                        <div class="info-item">
                            <small>Số điện thoại</small>
                            <strong><?php echo htmlspecialchars($employee['phone']); ?></strong>
                        </div>

                        <div class="info-item" style="grid-column: 1 / -1;">
                            <small>Địa chỉ thường trú</small>
                            <span><?php echo htmlspecialchars($employee['address']); ?></span>
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
                        <?php foreach ($documents as $document): ?>
                            <div class="document-card">
                                <div class="document-icon"><?php echo htmlspecialchars($document['icon']); ?></div>

                                <div class="document-info">
                                    <strong><?php echo htmlspecialchars($document['name']); ?></strong>
                                    <small><?php echo htmlspecialchars($document['updated']); ?></small>
                                </div>

                                <button class="document-download" type="button">⇩</button>
                            </div>
                        <?php endforeach; ?>

                        <button class="document-card" type="button" data-hrm-action="upload-doc" style="border-style: dashed;">
                            <div class="document-icon">＋</div>
                            <div class="document-info">
                                <strong>Tải lên hồ sơ mới</strong>
                                <small>PDF, DOCX hoặc hình ảnh</small>
                            </div>
                            <span></span>
                        </button>
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