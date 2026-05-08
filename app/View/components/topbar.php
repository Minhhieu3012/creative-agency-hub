<?php
$currentUser = $currentUser ?? [
    'id' => null,
    'name' => 'Người dùng',
    'full_name' => 'Người dùng',
    'role' => 'user',
    'avatar' => null,
];

$topbarTitle = $topbarTitle ?? '';
$baseUrl = $baseUrl ?? '/creative-agency-hub';
$viewUrl = $viewUrl ?? ($baseUrl . '/app/View');

$userId = $currentUser['id'] ?? null;
$userName = $currentUser['full_name'] ?? ($currentUser['name'] ?? 'Người dùng');
$userRole = strtolower((string)($currentUser['role'] ?? 'user'));
$userAvatar = $currentUser['avatar'] ?? null;

$roleLabels = [
    'admin' => 'ADMIN',
    'manager' => 'MANAGER',
    'employee' => 'EMPLOYEE',
    'client' => 'CLIENT',
    'user' => 'USER',
];

$userRoleLabel = $roleLabels[$userRole] ?? strtoupper($userRole ?: 'USER');
$userInitial = function_exists('mb_substr')
    ? strtoupper(mb_substr($userName ?: 'U', 0, 1, 'UTF-8'))
    : strtoupper(substr($userName ?: 'U', 0, 1));

$canUseNotifications = in_array($userRole, ['admin', 'manager', 'employee'], true);

if (!function_exists('cah_topbar_avatar_url')) {
    function cah_topbar_avatar_url($avatar, $baseUrl) {
        $avatar = trim((string)$avatar);

        if ($avatar === '') {
            return null;
        }

        if (preg_match('/^https?:\/\//i', $avatar) || substr($avatar, 0, 1) === '/') {
            return $avatar;
        }

        if (substr($avatar, 0, 7) === 'public/') {
            return rtrim($baseUrl, '/') . '/' . ltrim($avatar, '/');
        }

        if (substr($avatar, 0, 8) === 'uploads/') {
            return rtrim($baseUrl, '/') . '/public/' . ltrim($avatar, '/');
        }

        return rtrim($baseUrl, '/') . '/public/uploads/avatars/' . ltrim($avatar, '/');
    }
}

$userAvatarUrl = cah_topbar_avatar_url($userAvatar, $baseUrl);
?>

<header class="app-topbar">
    <div class="topbar-left">
        <button class="icon-btn topbar-menu-btn" type="button" data-sidebar-toggle aria-label="Ẩn hoặc hiện sidebar">
            ☰
        </button>

        <?php if (!empty($topbarTitle)): ?>
        <div class="topbar-title"><?php echo htmlspecialchars($topbarTitle, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>
    </div>

    <div class="topbar-actions">
        <?php if ($canUseNotifications): ?>
        <div class="notification-menu" data-notification-menu style="position: relative;">
            <button class="icon-btn notification-trigger" type="button" aria-label="Thông báo" title="Thông báo"
                data-notification-trigger style="position: relative;">
                🔔
                <span data-notification-count style="
                            display:none;
                            position:absolute;
                            top:-4px;
                            right:-4px;
                            min-width:18px;
                            height:18px;
                            padding:0 5px;
                            border-radius:999px;
                            background:#ef4444;
                            color:#fff;
                            font-size:11px;
                            line-height:18px;
                            font-weight:800;
                            text-align:center;
                        ">0</span>
            </button>

            <div data-notification-dropdown style="
                        display:none;
                        position:absolute;
                        right:0;
                        top:calc(100% + 12px);
                        width:min(390px, calc(100vw - 32px));
                        max-height:470px;
                        overflow:hidden;
                        border:1px solid #e5e7eb;
                        border-radius:18px;
                        background:#fff;
                        box-shadow:0 24px 70px rgba(15,23,42,.18);
                        z-index:1000;
                    ">
                <div
                    style="display:flex;align-items:center;justify-content:space-between;gap:12px;padding:16px 18px;border-bottom:1px solid #e5e7eb;">
                    <div>
                        <strong style="display:block;color:#0f172a;">Thông báo</strong>
                        <small style="color:#64748b;">Cập nhật hệ thống và công việc</small>
                    </div>

                    <div style="display:flex;align-items:center;gap:8px;">
                        <button class="btn btn-light btn-sm" type="button" data-notification-mark-all>
                            Đã đọc
                        </button>

                        <button class="btn btn-light btn-sm" type="button" data-notification-refresh>
                            Làm mới
                        </button>
                    </div>
                </div>

                <div data-notification-list style="max-height:350px;overflow:auto;">
                    <div style="padding:18px;color:#64748b;text-align:center;">
                        Đang tải thông báo...
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="topbar-divider"></div>

        <div class="user-menu" data-dropdown>
            <button class="user-menu-trigger" type="button" data-dropdown-trigger aria-label="Menu người dùng">
                <span class="user-meta">
                    <strong data-user-name><?php echo htmlspecialchars($userName, ENT_QUOTES, 'UTF-8'); ?></strong>
                    <small data-user-role><?php echo htmlspecialchars($userRoleLabel, ENT_QUOTES, 'UTF-8'); ?></small>
                </span>

                <?php if (!empty($userAvatarUrl)): ?>
                <img src="<?php echo htmlspecialchars($userAvatarUrl, ENT_QUOTES, 'UTF-8'); ?>"
                    alt="<?php echo htmlspecialchars($userName, ENT_QUOTES, 'UTF-8'); ?>" class="user-avatar"
                    data-user-avatar>
                <?php else: ?>
                <span class="user-avatar"
                    data-user-avatar><?php echo htmlspecialchars($userInitial, ENT_QUOTES, 'UTF-8'); ?></span>
                <?php endif; ?>
            </button>

            <div class="dropdown-menu user-dropdown" data-dropdown-menu>
                <a href="<?php echo htmlspecialchars($viewUrl, ENT_QUOTES, 'UTF-8'); ?>/hrm/profile.php">Hồ sơ cá
                    nhân</a>
                <a href="<?php echo htmlspecialchars($viewUrl, ENT_QUOTES, 'UTF-8'); ?>/payroll/attendance.php">Chấm
                    công hôm nay</a>
                <a href="<?php echo htmlspecialchars($viewUrl, ENT_QUOTES, 'UTF-8'); ?>/client-portal/projects.php">Client
                    Portal</a>
                <a href="#" class="text-danger" data-logout>Đăng xuất</a>
            </div>
        </div>
    </div>
</header>

<?php if ($canUseNotifications): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const menu = document.querySelector('[data-notification-menu]');
    const trigger = document.querySelector('[data-notification-trigger]');
    const dropdown = document.querySelector('[data-notification-dropdown]');
    const list = document.querySelector('[data-notification-list]');
    const countBadge = document.querySelector('[data-notification-count]');
    const refreshButton = document.querySelector('[data-notification-refresh]');
    const markAllButton = document.querySelector('[data-notification-mark-all]');

    if (!menu || !trigger || !dropdown || !list || !countBadge) {
        return;
    }

    const apiRoot = window.CAH_CONFIG?.apiRoot ||
        '<?php echo htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8'); ?>/public';

    function getToken() {
        return localStorage.getItem('cah_auth_token') || localStorage.getItem('cah_token') || '';
    }

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function formatTime(value) {
        if (!value) {
            return '';
        }

        const safeValue = String(value).includes(' ') ? String(value).replace(' ', 'T') : String(value);
        const date = new Date(safeValue);

        if (Number.isNaN(date.getTime())) {
            return value;
        }

        return date.toLocaleString('vi-VN');
    }

    async function apiRequest(path, options = {}) {
        const headers = {
            Accept: 'application/json',
            ...(options.headers || {})
        };

        const token = getToken();

        if (token) {
            headers.Authorization = 'Bearer ' + token;
        }

        const response = await fetch(apiRoot + path, {
            credentials: 'same-origin',
            ...options,
            headers
        });

        const payload = await response.json().catch(() => ({
            status: 'error',
            message: 'Server không trả JSON hợp lệ.'
        }));

        if (!response.ok || payload.status === 'error') {
            throw new Error(payload.message || 'Yêu cầu không thành công.');
        }

        return payload;
    }

    function setCount(value) {
        const count = Number(value || 0);

        if (count > 0) {
            countBadge.style.display = 'inline-block';
            countBadge.textContent = count > 99 ? '99+' : String(count);
        } else {
            countBadge.style.display = 'none';
            countBadge.textContent = '0';
        }
    }

    function renderNotifications(items) {
        if (!Array.isArray(items) || items.length === 0) {
            list.innerHTML = `
                <div style="padding:24px;text-align:center;color:#64748b;">
                    Chưa có thông báo nào.
                </div>
            `;
            return;
        }

        list.innerHTML = items.map(function(item) {
            const isUnread = Number(item.is_read || 0) === 0;

            return `
                <button
                    type="button"
                    data-notification-item="${escapeHtml(item.id)}"
                    style="
                        width:100%;
                        display:block;
                        padding:14px 18px;
                        border:0;
                        border-bottom:1px solid #f1f5f9;
                        background:${isUnread ? '#ecfdf5' : '#fff'};
                        text-align:left;
                        cursor:pointer;
                    "
                >
                    <strong style="display:block;color:#0f172a;font-size:14px;line-height:1.45;">
                        ${escapeHtml(item.message)}
                    </strong>
                    <small style="display:block;color:#64748b;margin-top:6px;">
                        ${escapeHtml(formatTime(item.created_at))}
                    </small>
                </button>
            `;
        }).join('');
    }

    async function loadUnreadCount() {
        try {
            const payload = await apiRequest('/api/notifications/unread-count');
            const count = payload?.data?.unread ?? payload?.data?.total ?? payload?.data ?? 0;

            setCount(count);
        } catch (error) {
            setCount(0);
        }
    }

    async function loadNotifications() {
        list.innerHTML = `
            <div style="padding:18px;color:#64748b;text-align:center;">
                Đang tải thông báo...
            </div>
        `;

        try {
            const payload = await apiRequest('/api/notifications?limit=12');
            renderNotifications(payload.data || []);
            await loadUnreadCount();
        } catch (error) {
            list.innerHTML = `
                <div style="padding:18px;color:#b91c1c;text-align:center;">
                    Không thể tải thông báo: ${escapeHtml(error.message)}
                </div>
            `;
        }
    }

    async function markAsRead(id) {
        if (!id) {
            return;
        }

        try {
            await apiRequest('/api/notifications/' + encodeURIComponent(id) + '/read', {
                method: 'PATCH'
            });

            await loadNotifications();
        } catch (error) {
            await loadNotifications();
        }
    }

    async function markAllAsRead() {
        if (markAllButton) {
            markAllButton.disabled = true;
            markAllButton.textContent = 'Đang xử lý...';
        }

        try {
            await apiRequest('/api/notifications/read-all', {
                method: 'PATCH'
            });

            setCount(0);
            await loadNotifications();
        } catch (error) {
            list.innerHTML = `
                <div style="padding:18px;color:#b91c1c;text-align:center;">
                    Không thể đánh dấu đã đọc: ${escapeHtml(error.message)}
                </div>
            `;
        } finally {
            if (markAllButton) {
                markAllButton.disabled = false;
                markAllButton.textContent = 'Đã đọc';
            }
        }
    }

    trigger.addEventListener('click', function(event) {
        event.preventDefault();

        const isOpen = dropdown.style.display === 'block';
        dropdown.style.display = isOpen ? 'none' : 'block';

        if (!isOpen) {
            loadNotifications();
        }
    });

    refreshButton?.addEventListener('click', function(event) {
        event.preventDefault();
        loadNotifications();
    });

    markAllButton?.addEventListener('click', function(event) {
        event.preventDefault();
        markAllAsRead();
    });

    list.addEventListener('click', function(event) {
        const item = event.target.closest('[data-notification-item]');

        if (!item) {
            return;
        }

        markAsRead(item.getAttribute('data-notification-item'));
    });

    document.addEventListener('click', function(event) {
        if (!menu.contains(event.target)) {
            dropdown.style.display = 'none';
        }
    });

    loadUnreadCount();
    window.setInterval(loadUnreadCount, 60000);
});
</script>
<?php endif; ?>