<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$pageTitle = 'Quản lý Nhân sự | Creative Agency Hub';
$pageCss = ['hrm.css'];
$activeMenu = 'employees';
$topbarTitle = 'Quản lý Nhân sự';

ob_start();
?>

<?php
$pageHeading = 'Quản lý Nhân sự';
$pageSubtitle = 'Theo dõi hồ sơ điện tử và trạng thái làm việc của nhân sự.';
$pageAction = '<button class="btn btn-primary" data-action="add-employee">＋ Thêm nhân viên</button>';
require __DIR__ . '/../components/page-header.php';
?>

<article class="card">
    <div class="card-header dashboard-card-title-row">
        <div><h2>Danh sách nhân sự</h2></div>

        <div style="display: flex; gap: 10px;">
            <input type="text" id="js-search-input" placeholder="Tìm tên, email..." style="padding: 8px; border: 1px solid #ddd; border-radius: 4px;">

            <select id="js-status-filter" style="padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                <option value="">Tất cả trạng thái</option>
                <option value="active">Đang làm việc</option>
                <option value="inactive">Tạm ngưng</option>
                <option value="resigned">Đã nghỉ việc</option>
                <option value="suspended">Bị khóa</option>
            </select>
        </div>
    </div>

    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 300px;">NHÂN SỰ</th>
                    <th>PHÒNG BAN</th>
                    <th>CHỨC DANH</th>
                    <th>TRẠNG THÁI</th>
                    <th style="text-align: right;">HÀNH ĐỘNG</th>
                </tr>
            </thead>

            <tbody id="js-employee-table-body">
                <tr>
                    <td colspan="5" style="text-align: center; padding: 40px;">Đang tải dữ liệu...</td>
                </tr>
            </tbody>
        </table>
    </div>
</article>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const token = localStorage.getItem('cah_token') || localStorage.getItem('cah_auth_token') || '';
    const baseUrl = '/creative-agency-hub';
    const tableBody = document.getElementById('js-employee-table-body');
    const searchInput = document.getElementById('js-search-input');
    const statusFilter = document.getElementById('js-status-filter');

    const statusLabels = {
        active: 'Đang làm việc',
        inactive: 'Tạm ngưng',
        resigned: 'Đã nghỉ việc',
        suspended: 'Bị khóa'
    };

    const statusBadgeClass = {
        active: 'badge-success',
        inactive: 'badge-warning',
        resigned: 'badge-danger',
        suspended: 'badge-danger'
    };

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    async function readJsonResponse(response) {
        const text = await response.text();

        try {
            const payload = JSON.parse(text);

            if (!response.ok || payload.status === 'error') {
                throw new Error(payload.message || `Request lỗi HTTP ${response.status}`);
            }

            return payload;
        } catch (error) {
            if (error.message && !error.message.includes('Unexpected')) {
                throw error;
            }

            console.error('Dữ liệu thô từ Server:', text);
            throw new Error('API đường dẫn sai hoặc server bị lỗi. Nhấn F12 tab Console để xem chi tiết.');
        }
    }

    if (!token) {
        tableBody.innerHTML = '<tr><td colspan="5" style="text-align: center; color: red; padding: 40px;">Lỗi: Bạn chưa đăng nhập hoặc token đã mất. Vui lòng đăng nhập lại.</td></tr>';
        return;
    }

    document.addEventListener('click', function(event) {
        const btn = event.target.closest('[data-action="add-employee"]');

        if (btn) {
            window.location.href = 'add-employee.php';
        }
    });

    const loadData = async () => {
        const search = searchInput.value.trim();
        const status = statusFilter.value.trim();

        tableBody.innerHTML = '<tr><td colspan="5" style="text-align: center; padding: 40px;">Đang tải dữ liệu...</td></tr>';

        const query = new URLSearchParams({
            search,
            status
        });

        try {
            const response = await fetch(`${baseUrl}/public/api/employees?${query.toString()}`, {
                headers: {
                    'Authorization': 'Bearer ' + token,
                    'Accept': 'application/json'
                }
            });

            const result = await readJsonResponse(response);

            if (!result.data || result.data.length === 0) {
                tableBody.innerHTML = '<tr><td colspan="5" style="text-align: center; padding: 40px;">Chưa có nhân sự nào trong hệ thống.</td></tr>';
                return;
            }

            tableBody.innerHTML = result.data.map((employee) => {
                const status = employee.status || 'inactive';
                const statusText = statusLabels[status] || status.toUpperCase();
                const badgeClass = statusBadgeClass[status] || 'badge-warning';

                return `
                    <tr>
                        <td>
                            <div class="employee-cell">
                                <div class="employee-avatar">${escapeHtml((employee.full_name || 'U').charAt(0).toUpperCase())}</div>
                                <div class="employee-name">
                                    <strong>${escapeHtml(employee.full_name || 'Chưa cập nhật')}</strong>
                                    <small>${escapeHtml(employee.email || '')}</small>
                                </div>
                            </div>
                        </td>

                        <td>${escapeHtml(employee.department_name || '-')}</td>
                        <td>${escapeHtml(employee.position_name || '-')}</td>

                        <td>
                            <span class="badge ${escapeHtml(badgeClass)}">${escapeHtml(statusText)}</span>
                        </td>

                        <td style="text-align: right;">
                            <button class="icon-btn" onclick="window.location.href='edit-employee.php?id=${encodeURIComponent(employee.id)}'">✎</button>
                        </td>
                    </tr>
                `;
            }).join('');
        } catch (error) {
            tableBody.innerHTML = `<tr><td colspan="5" style="text-align: center; color: red; padding: 40px;"><b>Lỗi hệ thống JS:</b> ${escapeHtml(error.message)}</td></tr>`;
        }
    };

    searchInput.addEventListener('input', loadData);
    statusFilter.addEventListener('change', loadData);

    loadData();
});
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/app.php';
?>