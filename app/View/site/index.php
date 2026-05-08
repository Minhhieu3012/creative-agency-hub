<?php
$pageTitle = 'Creative Agency Hub | Nền tảng quản lý agency';
$baseUrl = $baseUrl ?? '/creative-agency-hub';
$assetUrl = $assetUrl ?? ($baseUrl . '/public/assets');
$viewUrl = $viewUrl ?? ($baseUrl . '/app/View');
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="stylesheet" href="<?php echo htmlspecialchars($assetUrl, ENT_QUOTES, 'UTF-8'); ?>/css/site.css?v=<?php echo time(); ?>">
</head>
<body class="site-body">
    <div class="site-orb site-orb-one"></div>
    <div class="site-orb site-orb-two"></div>

    <header class="site-header">
        <a class="site-brand" href="<?php echo htmlspecialchars($viewUrl, ENT_QUOTES, 'UTF-8'); ?>/site/index.php">
            <span class="site-brand-mark">CA</span>
            <span>Creative Agency Hub</span>
        </a>

        <nav class="site-nav">
            <a href="#solution">Giải pháp</a>
            <a href="#workflow">Luồng vận hành</a>
            <a href="#control">Quản trị</a>
        </nav>

        <div class="site-header-actions">
            <a class="site-btn site-btn-light" href="<?php echo htmlspecialchars($viewUrl, ENT_QUOTES, 'UTF-8'); ?>/auth/login.php">
                Đăng nhập
            </a>
            <a class="site-btn site-btn-primary" href="<?php echo htmlspecialchars($viewUrl, ENT_QUOTES, 'UTF-8'); ?>/site/register-manager.php">
                Đăng ký Manager
            </a>
        </div>
    </header>

    <main>
        <section class="site-hero">
            <div class="site-hero-copy">
                <span class="site-kicker">PROJECT • PEOPLE • CLIENT PORTAL</span>

                <h1>Điều hành agency của bạn trong một không gian sắc nét.</h1>

                <p>
                    Creative Agency Hub gom project, task, nhân sự, tiến độ và khách hàng vào một hệ thống thống nhất,
                    giúp Manager điều phối đội ngũ mượt hơn, rõ hơn và ít cháy deadline hơn.
                </p>

                <div class="site-hero-actions">
                    <a class="site-btn site-btn-primary site-btn-xl" href="<?php echo htmlspecialchars($viewUrl, ENT_QUOTES, 'UTF-8'); ?>/site/register-manager.php">
                        Tạo tài khoản quản lý
                    </a>
                    <a class="site-btn site-btn-ghost site-btn-xl" href="<?php echo htmlspecialchars($viewUrl, ENT_QUOTES, 'UTF-8'); ?>/auth/login.php">
                        Tôi đã có tài khoản
                    </a>
                </div>

                <div class="site-trust-row">
                    <span>Không cần cài đặt phức tạp</span>
                    <span>Phân quyền rõ ràng</span>
                    <span>Client chỉ thấy phần được public</span>
                </div>
            </div>

            <div class="site-hero-visual">
                <div class="site-dashboard-card site-dashboard-main">
                    <div class="site-window-dots">
                        <span></span><span></span><span></span>
                    </div>

                    <div class="site-board-head">
                        <div>
                            <small>Workspace Overview</small>
                            <strong>Vinamilk Website Redesign</strong>
                        </div>
                        <span class="site-status-pill">Active</span>
                    </div>

                    <div class="site-progress-wrap">
                        <div class="site-progress-top">
                            <span>Tiến độ</span>
                            <strong>76%</strong>
                        </div>
                        <div class="site-progress-line">
                            <span style="width: 76%;"></span>
                        </div>
                    </div>

                    <div class="site-kanban-preview">
                        <div>
                            <strong>Task mới</strong>
                            <span>12</span>
                        </div>
                        <div>
                            <strong>Chờ duyệt</strong>
                            <span>5</span>
                        </div>
                        <div>
                            <strong>Hoàn thành</strong>
                            <span>31</span>
                        </div>
                    </div>
                </div>

                <div class="site-dashboard-card site-floating-card site-floating-one">
                    <span>Manager Approval</span>
                    <strong>3 tài khoản chờ duyệt</strong>
                </div>

                <div class="site-dashboard-card site-floating-card site-floating-two">
                    <span>Client Portal</span>
                    <strong>File thiết kế đã sẵn sàng</strong>
                </div>
            </div>
        </section>

        <section class="site-section" id="solution">
            <div class="site-section-head">
                <span class="site-kicker">GIẢI PHÁP</span>
                <h2>Một hub cho agency vận hành không rối dây.</h2>
                <p>Từ lúc tạo project, kéo nhân sự vào team, giao task, duyệt file đến cho client xem tiến độ.</p>
            </div>

            <div class="site-feature-grid">
                <article class="site-feature-card">
                    <span class="site-feature-icon">▣</span>
                    <h3>Project rõ người rõ việc</h3>
                    <p>Manager tạo project, thêm employee vào nhóm, giao task và theo dõi trạng thái theo từng cột.</p>
                </article>

                <article class="site-feature-card">
                    <span class="site-feature-icon">☑</span>
                    <h3>Task flow đúng vai</h3>
                    <p>Employee gửi Chờ Duyệt, Manager approve hoặc reject về Cần sửa. Luồng nhẹ mà chắc.</p>
                </article>

                <article class="site-feature-card">
                    <span class="site-feature-icon">◇</span>
                    <h3>Client Portal gọn sạch</h3>
                    <p>Client chỉ xem project của họ, task được public và file được phép tải xuống.</p>
                </article>
            </div>
        </section>

        <section class="site-section site-split" id="workflow">
            <div>
                <span class="site-kicker">WORKFLOW</span>
                <h2>Từ đăng ký đến vận hành chỉ qua vài nấc.</h2>
                <p>
                    Người dùng đăng ký tài khoản Manager. Admin duyệt. Sau đó Manager bước vào workspace,
                    tạo nhân sự, tạo client, dựng project và vận hành production flow.
                </p>

                <a class="site-btn site-btn-primary" href="<?php echo htmlspecialchars($viewUrl, ENT_QUOTES, 'UTF-8'); ?>/site/register-manager.php">
                    Bắt đầu đăng ký
                </a>
            </div>

            <div class="site-steps">
                <div class="site-step">
                    <span>01</span>
                    <div>
                        <strong>Đăng ký Manager</strong>
                        <p>Tài khoản được tạo ở trạng thái chờ duyệt.</p>
                    </div>
                </div>

                <div class="site-step">
                    <span>02</span>
                    <div>
                        <strong>Admin kích hoạt</strong>
                        <p>Admin duyệt để Manager có thể đăng nhập.</p>
                    </div>
                </div>

                <div class="site-step">
                    <span>03</span>
                    <div>
                        <strong>Manager vận hành</strong>
                        <p>Tạo project, thêm employee/client và điều phối task.</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="site-section" id="control">
            <div class="site-control-panel">
                <div>
                    <span class="site-kicker">ADMIN CONTROL</span>
                    <h2>Admin chỉ quản trị, không nhảy vào vận hành.</h2>
                    <p>
                        Admin xem tổng quan hệ thống, duyệt Manager, duyệt Employee/Client và khóa hoặc mở khóa tài khoản.
                        Project và task thuộc quyền vận hành của Manager.
                    </p>
                </div>

                <div class="site-control-list">
                    <span>Duyệt Manager mới</span>
                    <span>Duyệt nhân sự và client</span>
                    <span>Khóa hoặc mở khóa tài khoản</span>
                    <span>Xem danh sách project toàn hệ thống</span>
                </div>
            </div>
        </section>

        <section class="site-final-cta">
            <h2>Biến mớ task hỗn độn thành một bản đồ vận hành rõ ràng.</h2>
            <p>Đăng ký tài khoản Manager, chờ Admin duyệt và bắt đầu xây workspace của bạn.</p>

            <div class="site-hero-actions">
                <a class="site-btn site-btn-primary site-btn-xl" href="<?php echo htmlspecialchars($viewUrl, ENT_QUOTES, 'UTF-8'); ?>/site/register-manager.php">
                    Đăng ký Manager
                </a>
                <a class="site-btn site-btn-light site-btn-xl" href="<?php echo htmlspecialchars($viewUrl, ENT_QUOTES, 'UTF-8'); ?>/auth/login.php">
                    Đăng nhập hệ thống
                </a>
            </div>
        </section>
    </main>

    <footer class="site-footer">
        <span>© 2026 Creative Agency Hub</span>
        <span>Built for project, people and beautifully boring operations.</span>
    </footer>
</body>
</html>