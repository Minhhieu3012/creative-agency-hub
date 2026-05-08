<?php
$pageTitle = 'Cơ cấu tổ chức | Creative Agency Hub';
$pageCss = ['hrm.css', 'organization.css']; 
$pageJs = ['hrm.js'];
$activeMenu = 'departments';
$topbarTitle = 'Cơ cấu tổ chức';
$brandName = 'Creative Agency Hub';

$departments = [];
$roles = [];

ob_start();
?>

<?php
$pageHeading = 'Cơ cấu Tổ chức';
$pageSubtitle = 'Quản lý sơ đồ phòng ban, chức danh và phân quyền hệ thống.';
$pageAction = '
    <button class="btn btn-light" type="button" data-action="export-report">⇩ Xuất báo cáo</button>
    <button class="btn btn-primary" type="button" data-action="add-department">＋ Thêm phòng ban</button>
';
require __DIR__ . '/../components/page-header.php';
?>

<section class="org-grid">
    <article class="card">
        <div class="card-header dashboard-card-title-row">
            <h2>Sơ đồ phòng ban</h2>
            <button class="btn btn-soft" type="button" data-action="expand-all">Mở rộng tất cả</button>
        </div>
        <div class="card-body">
            <div class="org-tree" id="js-dept-container">
                <p style="text-align: center; color: #6c757d; padding: 20px;">Đang tải sơ đồ tổ chức...</p>
            </div>
        </div>
    </article>

    <aside class="hrm-grid">
        <article class="card">
            <div class="card-header dashboard-card-title-row">
                <h2>Chức danh & Phân quyền</h2>
                <button class="btn btn-soft" type="button" data-action="add-role">＋ Thêm</button>
            </div>
            <div class="card-body">
                <div class="role-list" id="js-pos-container">
                    <p style="text-align: center; color: #6c757d; padding: 20px;">Đang tải chức danh...</p>
                </div>
            </div>
        </article>

        <article class="quick-summary-card">
            <div>
                <span>Tổng quy mô tổ chức</span>
                <strong id="js-total-org">--</strong>
                <p>Cơ cấu hiện tại ổn định và sẵn sàng mở rộng dựa trên dữ liệu thực tế.</p>
            </div>
            <button class="btn btn-light" type="button" data-action="view-hr-report">Xem báo cáo nhân sự</button>
        </article>
    </aside>
</section>

<section class="card" style="margin-top: 26px;">
    <div class="card-header dashboard-card-title-row">
        <h2>Danh sách nhân sự nòng cốt</h2>
        <a href="/creative-agency-hub/app/View/hrm/employees.php" class="text-primary" style="font-weight: 800;">Xem tất cả</a>
    </div>
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Nhân sự</th>
                    <th>Phòng ban</th>
                    <th>Chức danh</th>
                    <th>Trạng thái</th>
                    <th>Hành động</th>
                </tr>
            </thead>
            <tbody id="js-emp-container">
                <tr><td colspan="5" style="text-align: center; padding: 20px;">Đang tải danh sách nhân sự...</td></tr>
            </tbody>
        </table>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const token = localStorage.getItem('cah_token'); 
    const baseUrl = '/creative-agency-hub';

    if (!token) { 
        window.location.href = baseUrl + '/public/auth/login.php'; 
        return; 
    }

    const handleAction = (action, id = null) => {
        const viewUrl = baseUrl + '/app/View/hrm';
        switch(action) {
            case 'add-department': window.location.href = `${viewUrl}/add-department.php`; break;
            case 'add-role': window.location.href = `${viewUrl}/add-role.php`; break;
            case 'view-hr-report':
            case 'export-report': window.location.href = `${viewUrl}/employees.php`; break;
            case 'expand-all':
                document.querySelectorAll('.org-node').forEach(node => {
                    node.style.border = '1px solid #2e7d32';
                    setTimeout(() => node.style.border = '1px solid #f0f0f0', 1000);
                });
                break;
            case 'edit-employee': window.location.href = `${viewUrl}/edit-employee.php?id=${id}`; break;
        }
    };

    document.addEventListener('click', function(e) {
        const btn = e.target.closest('[data-action]');
        if (btn) handleAction(btn.getAttribute('data-action'), btn.getAttribute('data-id'));
    });

    // Gọi API lấy dữ liệu thực
    fetch(baseUrl + '/public/api/organization/data', {
        headers: { 'Authorization': 'Bearer ' + token }
    })
    .then(res => res.json())
    .then(res => {
        if (res.status === 'success') {
            const { departments, positions, employees } = res.data;

            // Render Departments
            const deptBox = document.getElementById('js-dept-container');
            deptBox.innerHTML = departments.length ? departments.map(d => `
                <div class="org-node">
                    <div class="org-node-icon">🏢</div>
                    <div class="org-node-text">
                        <strong>${d.name}</strong>
                        <small>${d.employee_count} Thành viên • ${d.description || 'Phòng ban'}</small>
                    </div>
                </div>
            `).join('') : '<p>Chưa có dữ liệu.</p>';

            // Render Positions
            const posBox = document.getElementById('js-pos-container');
            posBox.innerHTML = positions.length ? positions.map(p => `
                <div class="role-card">
                    <div class="role-card-head"><h3>${p.name}</h3><span class="badge badge-primary">Active</span></div>
                    <p>${p.description || '...'}</p>
                </div>
            `).join('') : '<p>Chưa có chức danh.</p>';

            document.getElementById('js-total-org').textContent = employees.length;

            // Render Employees với Trạng thái động (Hợp nhất Logic mới nhất)
            const empBox = document.getElementById('js-emp-container');
            empBox.innerHTML = employees.length ? employees.map(e => {
                // Mặc định là Đang làm việc
                let statusLabel = 'ĐANG LÀM VIỆC';
                let badgeClass = 'badge-success'; // Màu xanh lá

                if (e.status === 'probation') {
                    statusLabel = 'THỬ VIỆC';
                    badgeClass = 'badge-warning'; // Màu vàng/cam
                } else if (e.status === 'on_leave') {
                    statusLabel = 'TẠM NGHỈ';
                    badgeClass = 'badge-danger'; // Màu đỏ/hồng
                } else if (e.status === 'inactive') {
                    statusLabel = 'ĐÃ NGHỈ VIỆC';
                    badgeClass = 'badge-danger'; 
                }

                return `
                <tr>
                    <td>
                        <div class="employee-cell">
                            <div class="employee-avatar">${e.full_name?.charAt(0)?.toUpperCase()}</div>
                            <div class="employee-name"><strong>${e.full_name}</strong><small>${e.email}</small></div>
                        </div>
                    </td>
                    <td>${e.department_name || '-'}</td>
                    <td><strong class="text-primary">${e.position_name || '-'}</strong></td>
                    <td><span class="badge ${badgeClass}">${statusLabel}</span></td>
                    <td><button class="icon-btn" type="button" data-action="edit-employee" data-id="${e.id}">✎</button></td>
                </tr>`;
            }).join('') : '<tr><td colspan="5" style="text-align:center;">Trống.</td></tr>';
        }
    });
});
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/app.php';
?>