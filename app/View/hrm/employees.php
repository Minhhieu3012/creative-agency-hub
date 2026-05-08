<?php
// Bật lỗi để kiểm tra nếu trang bị trắng
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
                <option value="on_leave">Tạm nghỉ</option>
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
                <tr><td colspan="5" style="text-align: center; padding: 40px;">Đang tải dữ liệu...</td></tr>
            </tbody>
        </table>
    </div>
</article>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const token = localStorage.getItem('cah_token');
    const baseUrl = '/creative-agency-hub';
    const tableBody = document.getElementById('js-employee-table-body');

    // 1. Kiểm tra Token trước khi làm bất cứ việc gì
    if (!token) {
        tableBody.innerHTML = '<tr><td colspan="5" style="text-align: center; color: red; padding: 40px;">Lỗi: Bạn chưa đăng nhập hoặc Token đã mất. Vui lòng đăng nhập lại.</td></tr>';
        return;
    }

    // 2. Chuyển hướng khi nhấn nút thêm nhân viên
    document.addEventListener('click', function(e) {
        const btn = e.target.closest('[data-action="add-employee"]');
        if (btn) window.location.href = 'add-employee.php';
    });

    // 3. Hàm tải và render dữ liệu an toàn
    const loadData = () => {
        const search = document.getElementById('js-search-input').value;
        const status = document.getElementById('js-status-filter').value;

        // Reset bảng về trạng thái chờ mỗi khi tìm kiếm
        tableBody.innerHTML = '<tr><td colspan="5" style="text-align: center; padding: 40px;">Đang tải dữ liệu...</td></tr>';

        fetch(`${baseUrl}/public/api/employees?search=${search}&status=${status}`, { 
            headers: { 'Authorization': 'Bearer ' + token } 
        })
        .then(async res => {
            // Đọc dữ liệu thô để bắt lỗi sập Server (404/500)
            const text = await res.text();
            try {
                return JSON.parse(text);
            } catch (e) {
                console.error("Dữ liệu thô từ Server:", text);
                throw new Error("API đường dẫn sai hoặc Server bị sập. Nhấn F12 (tab Console) để xem chi tiết.");
            }
        })
        .then(res => {
            // Bắt lỗi Logic từ phía Backend (VD: Sai SQL)
            if (res.status === 'error') {
                tableBody.innerHTML = `<tr><td colspan="5" style="text-align: center; color: red; padding: 40px;"><b>Lỗi từ Database:</b> ${res.message}</td></tr>`;
                return;
            }

            // Xử lý khi Database chưa có ai
            if (!res.data || res.data.length === 0) {
                tableBody.innerHTML = '<tr><td colspan="5" style="text-align: center; padding: 40px;">Chưa có nhân sự nào trong hệ thống.</td></tr>';
                return;
            }

            // Render dữ liệu hoàn hảo (Happy Path)
            if (res.status === 'success') {
                tableBody.innerHTML = res.data.map(e => `
                    <tr>
                        <td>
                            <div class="employee-cell">
                                <div class="employee-avatar">${(e.full_name || 'U').charAt(0).toUpperCase()}</div>
                                <div class="employee-name"><strong>${e.full_name}</strong><small>${e.email}</small></div>
                            </div>
                        </td>
                        <td>${e.department_name || '-'}</td>
                        <td>${e.position_name || '-'}</td>
                        <td><span class="badge ${e.status === 'active' ? 'badge-success' : 'badge-warning'}">${(e.status || '').toUpperCase()}</span></td>
                        <td style="text-align: right;">
                            <button class="icon-btn" onclick="window.location.href='edit-employee.php?id=${e.id}'">✎</button>
                        </td>
                    </tr>
                `).join('');
            }
        })
        .catch(error => {
            // Bắt mọi lỗi Network hoặc Exception trong quá trình xử lý
            tableBody.innerHTML = `<tr><td colspan="5" style="text-align: center; color: red; padding: 40px;"><b>Lỗi Hệ Thống JS:</b> ${error.message}</td></tr>`;
        });
    };

    // 4. Lắng nghe sự kiện tìm kiếm và lọc
    document.getElementById('js-search-input').addEventListener('input', loadData);
    document.getElementById('js-status-filter').addEventListener('change', loadData);
    
    // 5. Khởi chạy load dữ liệu lần đầu
    loadData();
});
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/app.php';
?>