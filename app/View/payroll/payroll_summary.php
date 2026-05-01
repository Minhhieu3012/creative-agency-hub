<?php
$pageTitle = 'Bảng lương | Creative Agency Hub';
$pageCss = ['payroll.css', 'hrm.css'];
$pageJs = ['payroll.js'];
$activeMenu = 'payroll';
$topbarTitle = 'Payroll Summary';
$brandName = 'Creative Agency Hub';

ob_start();
?>

<?php
$pageHeading = 'Báo cáo Chấm công & Bảng lương';
$pageSubtitle = 'Tổng hợp ngày công, KPI, thưởng phạt và lương thực nhận của nhân sự.';
$pageAction = '<button class="btn btn-light" type="button" onclick="exportExcel()">⇩ Xuất Excel</button>
               <button class="btn btn-primary" type="button" onclick="loadPayrollData()">Chốt bảng lương</button>';
require __DIR__ . '/../components/page-header.php';
?>

<section class="payroll-shell">
    <!-- KHỐI 1: THỐNG KÊ TỔNG QUAN -->
    <div class="stat-grid">
        <article class="stat-card">
            <div class="stat-card-icon">▧</div>
            <div class="stat-card-body">
                <span>Tổng quỹ lương</span>
                <strong id="stat-total-salary">0đ</strong>
                <small>Tháng hiện tại</small>
            </div>
        </article>

        <article class="stat-card">
            <div class="stat-card-icon">◷</div>
            <div class="stat-card-body">
                <span>Tổng ngày công</span>
                <strong id="stat-total-days">0</strong>
                <small id="stat-emp-count">0 nhân sự</small>
            </div>
        </article>

        <article class="stat-card">
            <div class="stat-card-icon">✦</div>
            <div class="stat-card-body">
                <span>KPI trung bình</span>
                <strong id="stat-avg-kpi">0%</strong>
                <small>Hiệu suất toàn team</small>
            </div>
        </article>

        <article class="stat-card stat-card-danger">
            <div class="stat-card-icon">!</div>
            <div class="stat-card-body">
                <span>Cần kiểm tra</span>
                <strong id="stat-warnings">0</strong>
                <small>Nhân sự KPI < 80%</small>
            </div>
        </article>
    </div>

    <!-- KHỐI 2: BỘ LỌC (Tạm thời giữ UI, có thể phát triển thêm tính năng filter API sau) -->
    <div class="payroll-filter">
        <select class="form-select" id="filter-month">
            <option value="<?php echo date('m'); ?>">Tháng <?php echo date('m/Y'); ?></option>
            <option value="<?php echo date('m', strtotime('-1 month')); ?>">Tháng <?php echo date('m/Y', strtotime('-1 month')); ?></option>
        </select>

        <select class="form-select">
            <option>Tất cả phòng ban</option>
        </select>

        <div class="input-with-icon">
            <span class="input-icon">⌕</span>
            <input class="form-control" type="search" placeholder="Tìm nhân sự...">
        </div>

        <button class="btn btn-soft" type="button" onclick="loadPayrollData()">Lọc dữ liệu</button>
    </div>

    <section class="payroll-grid">
        <!-- KHỐI 3: BẢNG LƯƠNG CHI TIẾT -->
        <article class="card employee-table-card">
            <div class="card-header dashboard-card-title-row">
                <div>
                    <h2>Bảng lương tháng</h2>
                    <p class="section-subtitle">Dữ liệu được lấy trực tiếp từ hệ thống chấm công và KPI.</p>
                </div>
            </div>

            <div class="table-responsive">
                <table class="data-table payroll-summary-table">
                    <thead>
                        <tr>
                            <th>Nhân sự</th>
                            <th>Phòng ban / Role</th>
                            <th>Ngày công</th>
                            <th>Đi muộn</th>
                            <th>KPI</th>
                            <th>Lương cơ bản</th>
                            <th>Thực nhận</th>
                            <th>Trạng thái</th>
                        </tr>
                    </thead>
                    <tbody id="js-payroll-table-body">
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 30px; color: #888;">
                                Đang tải dữ liệu bảng lương...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </article>

        <!-- KHỐI 4: CHI TIẾT TỔNG HỢP (SIDEBAR PHẢI) -->
        <aside class="card">
            <div class="card-header">
                <h2 class="section-title">Chi tiết tổng hợp</h2>
                <p class="section-subtitle">Tạm tính theo dữ liệu chấm công và KPI.</p>
            </div>

            <div class="card-body">
                <div class="payroll-detail-card">
                    <div class="payroll-detail-row">
                        <span>Lương cơ bản</span>
                        <strong id="side-base-salary">0đ</strong>
                    </div>
                    <div class="payroll-detail-row">
                        <span>Thưởng KPI</span>
                        <strong id="side-bonus">0đ</strong>
                    </div>
                    <div class="payroll-detail-row">
                        <span>Phạt đi muộn/về sớm</span>
                        <strong id="side-penalty">0đ</strong>
                    </div>
                    <div class="payroll-detail-row total">
                        <span>Tổng thực nhận</span>
                        <strong id="side-net-salary">0đ</strong>
                    </div>
                </div>

                <button class="btn btn-primary btn-block" type="button" style="margin-top: 24px;" onclick="loadPayrollData()">
                    Cập nhật mới nhất
                </button>
            </div>
        </aside>
    </section>
</section>

<!-- ========================================== -->
<!-- SCRIPT XỬ LÝ FETCH API VÀ RENDER DỮ LIỆU ĐỘNG -->
<!-- ========================================== -->
<script>
document.addEventListener("DOMContentLoaded", () => {
    loadPayrollData();
});

// Format tiền tệ VNĐ
const formatCurrency = (value) => {
    return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(value);
};

// Lấy chữ cái đầu của tên
const getInitials = (name) => {
    if (!name) return 'NV';
    const parts = name.split(' ');
    const last = parts[parts.length - 1];
    const first = parts[0];
    return (first.charAt(0) + (parts.length > 1 ? last.charAt(0) : '')).toUpperCase();
};

async function loadPayrollData() {
    const token = localStorage.getItem('cah_token');
    const baseUrl = '/creative-agency-hub/public';
    const month = document.getElementById('filter-month').value;
    const year = new Date().getFullYear();

    const tbody = document.getElementById('js-payroll-table-body');
    tbody.innerHTML = '<tr><td colspan="8" style="text-align: center; padding: 20px;">Đang tính toán...</td></tr>';

    try {
        const response = await fetch(`${baseUrl}/api/payroll/summary?month=${month}&year=${year}`, {
            headers: { 
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json'
            }
        });

        const res = await response.json();

        if (res.status === 'success') {
            const data = res.data;
            
            if (data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="8" style="text-align: center; padding: 20px;">Chưa có dữ liệu lương tháng này.</td></tr>';
                return;
            }

            let html = '';
            let totalBase = 0, totalNet = 0, totalBonus = 0, totalPenalty = 0;
            let totalDays = 0, totalKpi = 0, warnings = 0;

            data.forEach(emp => {
                // Tính tổng cho các chỉ số
                totalBase += emp.financial.actual_salary; // Lương ngày công thực tế
                totalNet += emp.financial.net_salary;
                totalBonus += emp.financial.bonus;
                totalPenalty += emp.financial.penalty;
                totalDays += emp.attendance.actual_days;
                totalKpi += emp.kpi.percent;
                
                if (emp.kpi.percent < 80) warnings++;

                // Xác định trạng thái màu sắc dựa vào KPI
                let statusTone = 'success';
                let statusText = 'Đã chốt';
                if (emp.kpi.percent < 80) { statusTone = 'danger'; statusText = 'Cần kiểm tra'; }
                else if (emp.attendance.late > 0) { statusTone = 'warning'; statusText = 'Có đi muộn'; }

                html += `
                    <tr>
                        <td>
                            <div class="employee-cell">
                                <div class="employee-avatar">${getInitials(emp.full_name)}</div>
                                <div class="employee-name">
                                    <strong>${emp.full_name}</strong>
                                    <small>ID: ${emp.employee_id}</small>
                                </div>
                            </div>
                        </td>
                        <td style="text-transform: capitalize;">${emp.role}</td>
                        <td><strong>${emp.attendance.actual_days} / ${emp.attendance.standard_days}</strong></td>
                        <td>${emp.attendance.late}</td>
                        <td><strong>${emp.kpi.percent}%</strong></td>
                        <td>${formatCurrency(emp.base_salary)}</td>
                        <td class="salary-amount">${formatCurrency(emp.financial.net_salary)}</td>
                        <td>
                            <span class="badge badge-${statusTone}">
                                ${statusText}
                            </span>
                        </td>
                    </tr>
                `;
            });

            // 1. Gắn HTML vào bảng
            tbody.innerHTML = html;

            // 2. Cập nhật khối Thống kê trên cùng
            document.getElementById('stat-total-salary').innerText = (totalNet / 1000000).toFixed(1) + 'M';
            document.getElementById('stat-total-days').innerText = totalDays;
            document.getElementById('stat-emp-count').innerText = data.length + ' nhân sự';
            document.getElementById('stat-avg-kpi').innerText = Math.round(totalKpi / data.length) + '%';
            document.getElementById('stat-warnings').innerText = warnings < 10 ? '0'+warnings : warnings;

            // 3. Cập nhật khối Sidebar tổng hợp
            document.getElementById('side-base-salary').innerText = formatCurrency(totalBase);
            document.getElementById('side-bonus').innerText = formatCurrency(totalBonus);
            document.getElementById('side-penalty').innerText = '-' + formatCurrency(totalPenalty);
            document.getElementById('side-net-salary').innerText = formatCurrency(totalNet);

        } else {
            tbody.innerHTML = `<tr><td colspan="8" style="text-align: center; color: red;">Lỗi: ${res.message}</td></tr>`;
        }
    } catch (error) {
        tbody.innerHTML = `<tr><td colspan="8" style="text-align: center; color: red;">Lỗi kết nối máy chủ!</td></tr>`;
        console.error(error);
    }
}

// Hàm xử lý tải file Excel với JWT Token
async function exportExcel() {
    const token = localStorage.getItem('cah_token');
    const baseUrl = '/creative-agency-hub/public';
    const month = document.getElementById('filter-month').value;
    const year = new Date().getFullYear();

    try {
        const response = await fetch(`${baseUrl}/api/payroll/export?month=${month}&year=${year}`, {
            method: 'GET',
            headers: { 'Authorization': `Bearer ${token}` }
        });

        if (!response.ok) throw new Error("Không thể xuất file");

        // Nhận dữ liệu dạng Blob (File)
        const blob = await response.blob();
        const url = window.URL.createObjectURL(blob);
        
        // Tạo thẻ a ảo để kích hoạt tải xuống
        const a = document.createElement('a');
        a.style.display = 'none';
        a.href = url;
        a.download = `Bang_Luong_Thang_${month}_${year}.csv`;
        document.body.appendChild(a);
        a.click();
        
        // Dọn dẹp
        window.URL.revokeObjectURL(url);
        document.body.removeChild(a);
        
    } catch (error) {
        alert("Lỗi xuất Excel: " + error.message);
    }
}
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/app.php';
?>