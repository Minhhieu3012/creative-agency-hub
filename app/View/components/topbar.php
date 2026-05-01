<?php
$topbarTitle = $topbarTitle ?? $pageTitle ?? 'Creative Agency Hub';
$baseUrl = $baseUrl ?? '/creative-agency-hub';
$viewUrl = $viewUrl ?? ($baseUrl . '/app/View');

$topbarUser = $topbarUser ?? [
    'full_name' => 'Creative User',
    'role_label' => 'Workspace',
    'initials' => 'CU',
];
?>

<header class="app-topbar">
    <div class="topbar-left">
        <button class="topbar-menu-btn" type="button" data-sidebar-toggle aria-label="Mở menu">
            ☰
        </button>

        <div class="topbar-search">
            <span>⌕</span>
            <input type="search" placeholder="Tìm kiếm dự án, nhân sự, công việc..." aria-label="Tìm kiếm">
        </div>

        <strong class="topbar-title">
            <?php echo htmlspecialchars($topbarTitle); ?>
        </strong>
    </div>

    <div class="topbar-right">
        <button class="topbar-icon-btn" type="button" aria-label="Thông báo">
            ◊
            <span class="topbar-dot"></span>
        </button>

        <button class="topbar-icon-btn" type="button" aria-label="Làm mới">
            ↻
        </button>

        <button class="topbar-icon-btn" type="button" aria-label="Trợ giúp">
            ?
        </button>

        <div class="topbar-divider"></div>

        <a class="topbar-user" href="<?php echo htmlspecialchars($viewUrl); ?>/hrm/profile.php" data-topbar-user-link>
            <span class="topbar-user-text">
                <strong data-topbar-user-name><?php echo htmlspecialchars($topbarUser['full_name']); ?></strong>
                <small data-topbar-user-role><?php echo htmlspecialchars($topbarUser['role_label']); ?></small>
            </span>

            <span class="topbar-avatar" data-topbar-user-avatar>
                <?php echo htmlspecialchars($topbarUser['initials']); ?>
            </span>
        </a>
    </div>
</header>

<script>
(function () {
    function readUser() {
        try {
            return JSON.parse(localStorage.getItem("cah_auth_user") || localStorage.getItem("cah_user") || "null");
        } catch (error) {
            return null;
        }
    }

    function initialsFromName(name) {
        var cleanName = String(name || "").trim();

        if (!cleanName) return "CA";

        var parts = cleanName
            .split(/\s+/)
            .filter(Boolean);

        if (parts.length === 1) {
            return parts[0].slice(0, 2).toUpperCase();
        }

        return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
    }

    function roleLabel(role) {
        var map = {
            admin: "SYSTEM ADMIN",
            manager: "PROJECT MANAGER",
            employee: "EMPLOYEE",
            client: "CLIENT PORTAL"
        };

        return map[String(role || "").toLowerCase()] || "WORKSPACE";
    }

    var user = readUser();

    if (!user) return;

    var baseUrl = <?php echo json_encode($baseUrl, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;
    var viewUrl = <?php echo json_encode($viewUrl, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;

    var fullName = user.full_name || user.name || user.email || "Creative User";
    var role = String(user.role || "").toLowerCase();

    var nameEl = document.querySelector("[data-topbar-user-name]");
    var roleEl = document.querySelector("[data-topbar-user-role]");
    var avatarEl = document.querySelector("[data-topbar-user-avatar]");
    var linkEl = document.querySelector("[data-topbar-user-link]");

    if (nameEl) nameEl.textContent = fullName;
    if (roleEl) roleEl.textContent = roleLabel(role);
    if (avatarEl) avatarEl.textContent = initialsFromName(fullName);

    if (linkEl) {
        var hrefMap = {
            admin: viewUrl + "/admin/index.php",
            manager: viewUrl + "/manager/index.php",
            employee: viewUrl + "/employee/index.php",
            client: viewUrl + "/client-portal/projects.php"
        };

        linkEl.href = hrefMap[role] || viewUrl + "/auth/login.php";
    }
})();
</script>