<?php
/**
 * SIDEBAR COMPONENT
 *
 * Nguyên tắc hiện tại:
 * - Giữ UI cũ.
 * - Render menu theo role bằng JS để tránh nhảy role.
 * - Admin: quản trị hệ thống, duyệt tài khoản, khóa/mở khóa.
 * - Manager: vận hành project/task/employee.
 * - Employee: làm task, chấm công, nghỉ phép.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$userRoleSession = isset($_SESSION['user_role']) ? strtolower($_SESSION['user_role']) : null;
$activeMenu = $activeMenu ?? 'dashboard';
$brandName  = $brandName  ?? 'Creative Agency Hub';
$baseUrl    = $baseUrl    ?? '/creative-agency-hub';
$viewUrl    = $viewUrl    ?? ($baseUrl . '/app/View');
$publicUrl  = $baseUrl . '/public';

$defaultHomeHref = $viewUrl . '/dashboard/index.php';

if ($userRoleSession === 'admin') {
    $defaultHomeHref = $viewUrl . '/admin/dashboard/index.php';
}

if ($userRoleSession === 'client') {
    $defaultHomeHref = $viewUrl . '/client-portal/projects.php';
}
?>

<aside class="app-sidebar" id="appSidebar" style="display: none; flex-direction: column;">
    <div class="sidebar-header">
        <a
            href="<?php echo htmlspecialchars($defaultHomeHref, ENT_QUOTES, 'UTF-8'); ?>"
            class="sidebar-brand"
            id="sidebarBrandLink"
        >
            <span class="brand-mark">CA</span>
            <span class="brand-text">
                <strong><?php echo htmlspecialchars($brandName, ENT_QUOTES, 'UTF-8'); ?></strong>
            </span>
        </a>
    </div>

    <div class="sidebar-scroll" style="flex: 1; overflow-y: auto;">
        <nav class="sidebar-nav" id="mainNavigation">
            <div style="padding: 20px; color: #999; font-size: 0.8rem; text-align: center;">
                Đang đồng bộ quyền hạn...
            </div>
        </nav>

        <div class="sidebar-section" id="otherSpaceSection" style="display: none;">
            <div class="sidebar-section-title">KHÔNG GIAN KHÁC</div>

            <nav class="sidebar-nav sidebar-nav-compact">
                <a
                    href="<?php echo htmlspecialchars($viewUrl, ENT_QUOTES, 'UTF-8'); ?>/client-portal/projects.php"
                    class="sidebar-link"
                    target="_blank"
                    rel="noopener noreferrer"
                >
                    <span class="sidebar-icon">◇</span>
                    <span>Client Portal</span>
                </a>
            </nav>
        </div>
    </div>

    <div class="sidebar-footer">
        <div id="sidebarActionBtn"></div>

        <a href="javascript:void(0)" onclick="handleLogout()" class="sidebar-link sidebar-link-danger">
            <span class="sidebar-icon">↪</span>
            <span>Đăng xuất</span>
        </a>
    </div>
</aside>

<script>
const SidebarController = {
    config: {
        activeMenu: "<?php echo htmlspecialchars($activeMenu, ENT_QUOTES, 'UTF-8'); ?>",
        viewUrl: "<?php echo htmlspecialchars($viewUrl, ENT_QUOTES, 'UTF-8'); ?>",
        baseUrl: "<?php echo htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8'); ?>",
        publicUrl: "<?php echo htmlspecialchars($publicUrl, ENT_QUOTES, 'UTF-8'); ?>"
    },

    allMenus: [
        {
            key: 'admin-dashboard',
            label: 'Home',
            href: '/admin/dashboard/index.php',
            icon: '⌂',
            roles: ['admin']
        },
        {
            key: 'admin-approve-managers',
            label: 'Duyệt Manager',
            href: '/admin/approvals/managers.php',
            icon: 'M',
            roles: ['admin']
        },
        {
            key: 'admin-approve-accounts',
            label: 'Duyệt nhân sự',
            href: '/admin/approvals/accounts.php',
            icon: '✓',
            roles: ['admin']
        },
        {
            key: 'admin-accounts',
            label: 'Danh sách tài khoản',
            href: '/admin/accounts/index.php',
            icon: '◉',
            roles: ['admin']
        },
        {
            key: 'admin-account-security',
            label: 'Khóa / mở khóa',
            href: '/admin/accounts/security.php',
            icon: '!',
            roles: ['admin']
        },

        {
            key: 'dashboard',
            label: 'Home',
            href: '/dashboard/index.php',
            icon: '⌂',
            roles: ['manager', 'employee']
        },
        {
            key: 'employees',
            label: 'Nhân sự',
            href: '/hrm/employees.php',
            icon: '◉',
            roles: ['manager']
        },
        {
            key: 'projects',
            label: 'Dự án',
            href: '/tasks/projects.php',
            icon: '▣',
            roles: ['manager']
        },
        {
            key: 'kanban',
            label: 'Bảng Kanban',
            href: '/tasks/kanban.php',
            icon: '☑',
            roles: ['manager', 'employee']
        },
        {
            key: 'attendance',
            label: 'Chấm công',
            href: '/payroll/attendance.php',
            icon: '◴',
            roles: ['manager', 'employee']
        },
        {
            key: 'leave_request',
            label: 'Nghỉ phép',
            href: '/payroll/leave_request.php',
            icon: '✦',
            roles: ['manager', 'employee']
        },
        {
            key: 'approvals',
            label: 'Phê duyệt',
            href: '/payroll/manager_approvals.php',
            icon: '☷',
            roles: ['manager']
        }
    ],

    init() {
        const userData = this.getLocalUser();
        const userRole = String(userData.role || '').toLowerCase();
        const token = this.getToken();

        if (!token) {
            this.forceLogout();
            return;
        }

        if (!userRole) {
            this.recoverSession(token);
            return;
        }

        this.renderUI(userRole);
        this.syncWithServer(token);
    },

    getToken() {
        return localStorage.getItem('cah_token') || localStorage.getItem('cah_auth_token') || '';
    },

    getLocalUser() {
        const rawUser = localStorage.getItem('cah_user') || localStorage.getItem('cah_auth_user') || '{}';

        try {
            return JSON.parse(rawUser) || {};
        } catch (error) {
            return {};
        }
    },

    getHomeHref(role) {
        if (role === 'admin') {
            return `${this.config.viewUrl}/admin/dashboard/index.php`;
        }

        if (role === 'client') {
            return `${this.config.viewUrl}/client-portal/projects.php`;
        }

        return `${this.config.viewUrl}/dashboard/index.php`;
    },

    renderUI(role) {
        const navContainer = document.getElementById('mainNavigation');
        const actionBtnContainer = document.getElementById('sidebarActionBtn');
        const sidebar = document.getElementById('appSidebar');
        const brandLink = document.getElementById('sidebarBrandLink');
        const otherSpaceSection = document.getElementById('otherSpaceSection');

        if (!navContainer || !actionBtnContainer || !sidebar) {
            return;
        }

        if (brandLink) {
            brandLink.href = this.getHomeHref(role);
        }

        let menuHtml = '';

        this.allMenus.forEach((item) => {
            if (!item.roles.includes(role)) {
                return;
            }

            const isActive = this.config.activeMenu === item.key ? 'is-active' : '';

            menuHtml += `
                <a href="${this.config.viewUrl}${item.href}" class="sidebar-link ${isActive}">
                    <span class="sidebar-icon">${item.icon}</span>
                    <span>${item.label}</span>
                </a>
            `;
        });

        navContainer.innerHTML = menuHtml || `
            <div style="padding: 20px; color: #999; font-size: 0.8rem; text-align: center;">
                Chưa có menu khả dụng.
            </div>
        `;

        if (role === 'manager') {
            actionBtnContainer.innerHTML = `
                <a href="${this.config.viewUrl}/tasks/projects.php" class="btn btn-primary btn-block">
                    <span>＋</span>
                    <span>Tạo mới</span>
                </a>
            `;
        } else {
            actionBtnContainer.innerHTML = '';
        }

        if (otherSpaceSection) {
            otherSpaceSection.style.display = role === 'manager' || role === 'employee'
                ? 'block'
                : 'none';
        }

        sidebar.style.display = 'flex';
    },

    recoverSession(token) {
        fetch(`${this.config.publicUrl}/api/auth/me`, {
            headers: {
                'Authorization': `Bearer ${token}`,
                'Accept': 'application/json'
            }
        })
        .then((res) => res.json())
        .then((res) => {
            if (res.status === 'success') {
                const user = res.data && res.data.user ? res.data.user : res.data;

                localStorage.setItem('cah_user', JSON.stringify(user));
                localStorage.setItem('cah_auth_user', JSON.stringify(user));

                window.location.reload();
            } else {
                this.forceLogout();
            }
        })
        .catch(() => {
            this.forceLogout();
        });
    },

    syncWithServer(token) {
        fetch(`${this.config.publicUrl}/api/auth/me`, {
            headers: {
                'Authorization': `Bearer ${token}`,
                'Accept': 'application/json'
            }
        }).catch(() => {});
    },

    forceLogout() {
        handleLogout();
    }
};

document.addEventListener('DOMContentLoaded', () => SidebarController.init());

function handleLogout() {
    localStorage.removeItem('cah_token');
    localStorage.removeItem('cah_auth_token');
    localStorage.removeItem('cah_user');
    localStorage.removeItem('cah_auth_user');

    document.cookie = "cah_token=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";

    window.location.href = "<?php echo htmlspecialchars($publicUrl, ENT_QUOTES, 'UTF-8'); ?>/auth/logout";
}
</script>