<?php
/**
 * SIDEBAR COMPONENT - ULTIMATE HYBRID VERSION
 * Giải pháp tối ưu: Stateless UI (JS) + Session Sync (PHP).
 * Triệt tiêu hoàn toàn lỗi nhảy Role bằng kiến trúc Independent Rendering.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Lấy thông tin từ PHP (Dùng làm dự phòng và xác định trang Active)
$userRoleSession = isset($_SESSION['user_role']) ? strtolower($_SESSION['user_role']) : null;
$activeMenu = $activeMenu ?? 'dashboard';
$brandName  = $brandName  ?? 'Creative Agency Hub';
$baseUrl    = $baseUrl    ?? '/creative-agency-hub';
$viewUrl    = $viewUrl    ?? ($baseUrl . '/app/View');
$publicUrl  = $baseUrl    . '/public';
?>

<!-- 2. GIAO DIỆN KHUNG (Ẩn mặc định để JS tính toán Role trước khi hiện) -->
<aside class="app-sidebar" id="appSidebar" style="display: none; flex-direction: column;">
    <div class="sidebar-header">
        <a href="<?php echo htmlspecialchars($viewUrl); ?>/dashboard/index.php" class="sidebar-brand">
            <span class="brand-mark">CA</span>
            <span class="brand-text"><strong><?php echo htmlspecialchars($brandName); ?></strong></span>
        </a>
    </div>

    <div class="sidebar-scroll" style="flex: 1; overflow-y: auto;">
        <nav class="sidebar-nav" id="mainNavigation">
            <!-- JavaScript sẽ render danh sách Menu vào đây -->
            <div style="padding: 20px; color: #999; font-size: 0.8rem; text-align: center;">
                Đang đồng bộ quyền hạn...
            </div>
        </nav>

        <div class="sidebar-section">
            <div class="sidebar-section-title">KHÔNG GIAN KHÁC</div>
            <nav class="sidebar-nav sidebar-nav-compact">
                <a href="<?php echo htmlspecialchars($viewUrl); ?>/client-portal/projects.php" class="sidebar-link">
                    <span class="sidebar-icon">◇</span><span>Client Portal</span>
                </a>
                <a href="#help" class="sidebar-link">
                    <span class="sidebar-icon">?</span><span>Trợ giúp</span>
                </a>
            </nav>
        </div>
    </div>

    <div class="sidebar-footer">
        <div id="sidebarActionBtn"></div> <!-- Nút Tạo mới render động -->
        
        <a href="javascript:void(0)" onclick="handleLogout()" class="sidebar-link sidebar-link-danger">
            <span class="sidebar-icon">↪</span><span>Đăng xuất</span>
        </a>
    </div>
</aside>

<script>
/**
 * 3. LOGIC RENDER STATELESS (CHỐNG NHẢY ROLE)
 */
const SidebarController = {
    config: {
        activeMenu: "<?php echo $activeMenu; ?>",
        viewUrl: "<?php echo $viewUrl; ?>",
        baseUrl: "<?php echo $baseUrl; ?>",
        publicUrl: "<?php echo $publicUrl; ?>"
    },

    // Danh sách 12 Menu đầy đủ
    allMenus: [
        { key: 'manager_workspace', label: 'Trung tâm quản lý', href: '/dashboard/manager_workspace.php', icon: '▦', roles: ['admin', 'manager'] },
        { key: 'dashboard', label: 'Bảng điều khiển', href: '/dashboard/index.php', icon: '▤', roles: ['admin', 'manager'] },
        { key: 'departments', label: 'Tổ chức', href: '/hrm/departments.php', icon: '▤', roles: ['admin', 'manager'] },
        { key: 'employees', label: 'Nhân sự', href: '/hrm/employees.php', icon: '◉', roles: ['admin', 'manager'] },
        { key: 'profile', label: 'Hồ sơ cá nhân', href: '/hrm/profile.php', icon: '◌', roles: ['admin', 'manager', 'employee'] },
        { key: 'projects', label: 'Dự án', href: '/tasks/projects.php', icon: '▣', roles: ['admin', 'manager'] },
        { key: 'kanban', label: 'Bảng Kanban', href: '/tasks/kanban.php', icon: '☑', roles: ['admin', 'manager', 'employee'] },
        { key: 'gantt', label: 'Gantt Chart', href: '/tasks/gantt.php', icon: '▥', roles: ['admin', 'manager'] },
        { key: 'attendance', label: 'Chấm công', href: '/payroll/attendance.php', icon: '◴', roles: ['admin', 'manager', 'employee'] },
        { key: 'leave_request', label: 'Nghỉ phép', href: '/payroll/leave_request.php', icon: '✦', roles: ['admin', 'manager', 'employee'] },
        { key: 'approvals', label: 'Phê duyệt', href: '/payroll/manager_approvals.php', icon: '☷', roles: ['admin', 'manager'] },
        { key: 'payroll_summary', label: 'Bảng lương', href: '/payroll/payroll_summary.php', icon: '▧', roles: ['admin', 'manager'] }
    ],

    init() {
        const userData = JSON.parse(localStorage.getItem('cah_user') || '{}');
        const userRole = (userData.role || '').toLowerCase();
        const token = localStorage.getItem('cah_token');

        // Nếu không có Token -> Ép logout
        if (!token) {
            this.forceLogout();
            return;
        }

        // Nếu có Token nhưng dữ liệu User trống -> Gọi API phục hồi
        if (!userRole) {
            this.recoverSession(token);
            return;
        }

        this.renderUI(userRole);
        this.syncWithServer(token); // Chạy ngầm để nắn lại PHP Session nếu cần
    },

    renderUI(role) {
        const navContainer = document.getElementById('mainNavigation');
        const actionBtnContainer = document.getElementById('sidebarActionBtn');
        const sidebar = document.getElementById('appSidebar');

        let menuHtml = '';
        this.allMenus.forEach(item => {
            if (item.roles.includes(role)) {
                const isActive = (this.config.activeMenu === item.key) ? 'is-active' : '';
                menuHtml += `
                    <a href="${this.config.viewUrl}${item.href}" class="sidebar-link ${isActive}">
                        <span class="sidebar-icon">${item.icon}</span>
                        <span>${item.label}</span>
                    </a>`;
            }
        });
        navContainer.innerHTML = menuHtml;

        // Render nút Tạo mới
        if (['admin', 'manager'].includes(role)) {
            actionBtnContainer.innerHTML = `
                <a href="${this.config.viewUrl}/tasks/projects.php" class="btn btn-primary btn-block">
                    <span>＋</span><span>Tạo mới</span>
                </a>`;
        }

        sidebar.style.display = 'flex';
    },

    recoverSession(token) {
        fetch(`${this.config.baseUrl}/api/auth/me`, {
            headers: { 'Authorization': `Bearer ${token}` }
        })
        .then(res => res.json())
        .then(res => {
            if (res.status === 'success') {
                localStorage.setItem('cah_user', JSON.stringify(res.data.user));
                window.location.reload();
            } else {
                this.forceLogout();
            }
        });
    },

    syncWithServer(token) {
        // Gọi API nhẹ để server cập nhật Session/Cookie theo Token hiện tại
        fetch(`${this.config.baseUrl}/api/auth/me`, {
            headers: { 'Authorization': `Bearer ${token}` }
        });
    },

    forceLogout() {
        handleLogout();
    }
};

// Thực thi khi DOM sẵn sàng
document.addEventListener('DOMContentLoaded', () => SidebarController.init());

/**
 * Xử lý đăng xuất sạch dấu vết
 */
function handleLogout() {
    localStorage.clear();
    // Xóa Cookie cah_token
    document.cookie = "cah_token=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
    window.location.href = "<?php echo $publicUrl; ?>/auth/logout";
}
</script>