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

    <!-- KHỐI 2: BỘ LỌC -->
    <div class="payroll-filter">
        <select class="form-select" id="filter-month">
            <option value="<?php echo date('m'); ?>">Tháng <?php echo date('m/Y'); ?></option>
            <option value="<?php echo date('m', strtotime('-1 month')); ?>">Tháng <?php echo date('m/Y', strtotime('-1 month')); ?></option>
        </select>

        <select class="form-select" id="filter-department">
            <option value="">Tất cả phòng ban</option>
        </select>

        <div class="input-with-icon">
            <span class="input-icon">⌕</span>
            <input class="form-control" id="filter-employee-search" type="search" placeholder="Tìm nhân sự...">
        </div>

        <button class="btn btn-soft" type="button" onclick="applyPayrollFilters()">Lọc dữ liệu</button>
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

        <!-- KHỐI 4: CHI TIẾT TỔNG HỢP -->
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

<script>
document.addEventListener("DOMContentLoaded", () => {
    loadPayrollData();

    const departmentFilter = document.getElementById('filter-department');
    const searchInput = document.getElementById('filter-employee-search');
    const monthFilter = document.getElementById('filter-month');

    if (departmentFilter) {
        departmentFilter.addEventListener('change', applyPayrollFilters);
    }

    if (searchInput) {
        searchInput.addEventListener('input', applyPayrollFilters);
    }

    if (monthFilter) {
        monthFilter.addEventListener('change', loadPayrollData);
    }
});

let allPayrollData = [];

const escapeHtml = (value) => {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
};

const formatCurrency = (value) => {
    const safeValue = Number(value || 0);
    return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(safeValue);
};

const formatCompactMoney = (value) => {
    const safeValue = Number(value || 0);

    if (safeValue >= 1000000) {
        return (safeValue / 1000000).toFixed(1) + 'M';
    }

    return formatCurrency(safeValue);
};

const getInitials = (name) => {
    if (!name) return 'NV';

    const parts = String(name).trim().split(/\s+/);
    const first = parts[0] || '';
    const last = parts[parts.length - 1] || '';

    return (first.charAt(0) + (parts.length > 1 ? last.charAt(0) : '')).toUpperCase();
};

const getDepartmentValue = (emp) => {
    if (emp.department_id !== null && emp.department_id !== undefined && emp.department_id !== '') {
        return String(emp.department_id);
    }

    return String(emp.department_name || 'Chưa có phòng ban');
};

function resetPayrollStats() {
    document.getElementById('stat-total-salary').innerText = '0đ';
    document.getElementById('stat-total-days').innerText = '0';
    document.getElementById('stat-emp-count').innerText = '0 nhân sự';
    document.getElementById('stat-avg-kpi').innerText = '0%';
    document.getElementById('stat-warnings').innerText = '00';

    document.getElementById('side-base-salary').innerText = '0đ';
    document.getElementById('side-bonus').innerText = '0đ';
    document.getElementById('side-penalty').innerText = '0đ';
    document.getElementById('side-net-salary').innerText = '0đ';
}

function populateDepartmentFilter(data) {
    const select = document.getElementById('filter-department');

    if (!select) return;

    const currentValue = select.value;
    const departmentMap = new Map();

    data.forEach((emp) => {
        const key = getDepartmentValue(emp);
        const label = emp.department_name || 'Chưa có phòng ban';

        if (!departmentMap.has(key)) {
            departmentMap.set(key, label);
        }
    });

    const options = ['<option value="">Tất cả phòng ban</option>'];

    Array.from(departmentMap.entries())
        .sort((a, b) => a[1].localeCompare(b[1], 'vi'))
        .forEach(([value, label]) => {
            options.push(`<option value="${escapeHtml(value)}">${escapeHtml(label)}</option>`);
        });

    select.innerHTML = options.join('');

    if (currentValue && departmentMap.has(currentValue)) {
        select.value = currentValue;
    }
}

function applyPayrollFilters() {
    const departmentValue = document.getElementById('filter-department')?.value || '';
    const keyword = String(document.getElementById('filter-employee-search')?.value || '').trim().toLowerCase();

    const filteredData = allPayrollData.filter((emp) => {
        const empDepartmentValue = getDepartmentValue(emp);
        const departmentMatched = departmentValue === '' || empDepartmentValue === departmentValue;

        const haystack = [
            emp.full_name,
            emp.email,
            emp.employee_code,
            emp.department_name,
            emp.position_name,
            emp.role
        ].join(' ').toLowerCase();

        const keywordMatched = keyword === '' || haystack.includes(keyword);

        return departmentMatched && keywordMatched;
    });

    renderPayrollTable(filteredData);
}

async function loadPayrollData() {
    const token = localStorage.getItem('cah_token') || localStorage.getItem('cah_auth_token') || '';
    const baseUrl = '/creative-agency-hub/public';
    const month = document.getElementById('filter-month').value;
    const year = new Date().getFullYear();

    const tbody = document.getElementById('js-payroll-table-body');
    tbody.innerHTML = '<tr><td colspan="8" style="text-align: center; padding: 20px;">Đang tính toán...</td></tr>';

    try {
        const response = await fetch(`${baseUrl}/api/payroll/summary?month=${month}&year=${year}&_=${Date.now()}`, {
            method: 'GET',
            cache: 'no-store',
            headers: {
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'Cache-Control': 'no-cache'
            }
        });

        const res = await response.json();

        if (res.status === 'success') {
            allPayrollData = Array.isArray(res.data) ? res.data : [];

            populateDepartmentFilter(allPayrollData);
            applyPayrollFilters();
        } else {
            tbody.innerHTML = `<tr><td colspan="8" style="text-align: center; color: red;">Lỗi: ${escapeHtml(res.message)}</td></tr>`;
        }
    } catch (error) {
        tbody.innerHTML = `<tr><td colspan="8" style="text-align: center; color: red;">Lỗi kết nối máy chủ!</td></tr>`;
        console.error(error);
    }
}

function renderPayrollTable(data) {
    const tbody = document.getElementById('js-payroll-table-body');

    if (!Array.isArray(data) || data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" style="text-align: center; padding: 20px;">Không tìm thấy dữ liệu lương phù hợp.</td></tr>';
        resetPayrollStats();
        return;
    }

    let html = '';
    let totalContractBase = 0;
    let totalNet = 0;
    let totalBonus = 0;
    let totalPenalty = 0;
    let totalDays = 0;
    let totalKpi = 0;
    let warnings = 0;

    data.forEach(emp => {
        const baseSalary = Number(emp.base_salary || 0);
        const netSalary = Number(emp.financial?.net_salary || 0);
        const bonus = Number(emp.financial?.bonus || 0);
        const penalty = Number(emp.financial?.penalty || 0);
        const actualDays = Number(emp.attendance?.actual_days || 0);
        const lateDays = Number(emp.attendance?.late || 0);
        const earlyDays = Number(emp.attendance?.early || 0);
        const kpiPercent = Number(emp.kpi?.percent || 0);

        totalContractBase += baseSalary;
        totalNet += netSalary;
        totalBonus += bonus;
        totalPenalty += penalty;
        totalDays += actualDays;
        totalKpi += kpiPercent;

        if (kpiPercent < 80) warnings++;

        let statusTone = 'success';
        let statusText = 'Đã chốt';

        if (baseSalary <= 0) {
            statusTone = 'danger';
            statusText = 'Thiếu hợp đồng';
        } else if (kpiPercent < 80) {
            statusTone = 'danger';
            statusText = 'Cần kiểm tra';
        } else if (lateDays > 0 || earlyDays > 0) {
            statusTone = 'warning';
            statusText = 'Có vi phạm';
        }

        const departmentName = emp.department_name || 'Chưa có phòng ban';
        const positionName = emp.position_name || emp.role || 'N/A';

        html += `
            <tr>
                <td>
                    <div class="employee-cell">
                        <div class="employee-avatar">${escapeHtml(getInitials(emp.full_name))}</div>
                        <div class="employee-name">
                            <strong>${escapeHtml(emp.full_name || 'Không rõ tên')}</strong>
                            <small>ID: ${escapeHtml(emp.employee_id)}</small>
                        </div>
                    </div>
                </td>

                <td>
                    <div class="employee-name">
                        <strong>${escapeHtml(departmentName)}</strong>
                        <small style="text-transform: capitalize;">${escapeHtml(positionName)} • ${escapeHtml(emp.role || 'N/A')}</small>
                    </div>
                </td>

                <td><strong>${actualDays} / ${emp.attendance?.standard_days || 24}</strong></td>
                <td>${lateDays}</td>
                <td><strong>${kpiPercent}%</strong></td>
                <td>${formatCurrency(baseSalary)}</td>
                <td class="salary-amount">${formatCurrency(netSalary)}</td>
                <td>
                    <span class="badge badge-${statusTone}">
                        ${escapeHtml(statusText)}
                    </span>
                </td>
            </tr>
        `;
    });

    tbody.innerHTML = html;

    document.getElementById('stat-total-salary').innerText = formatCompactMoney(totalNet);
    document.getElementById('stat-total-days').innerText = totalDays;
    document.getElementById('stat-emp-count').innerText = data.length + ' nhân sự';
    document.getElementById('stat-avg-kpi').innerText = Math.round(totalKpi / data.length) + '%';
    document.getElementById('stat-warnings').innerText = warnings < 10 ? '0' + warnings : warnings;

    document.getElementById('side-base-salary').innerText = formatCurrency(totalContractBase);
    document.getElementById('side-bonus').innerText = formatCurrency(totalBonus);
    document.getElementById('side-penalty').innerText = totalPenalty > 0 ? '-' + formatCurrency(totalPenalty) : formatCurrency(0);
    document.getElementById('side-net-salary').innerText = formatCurrency(totalNet);
}

async function exportExcel() {
    const token = localStorage.getItem('cah_token') || localStorage.getItem('cah_auth_token') || '';
    const baseUrl = '/creative-agency-hub/public';
    const month = document.getElementById('filter-month').value;
    const year = new Date().getFullYear();

    try {
        const response = await fetch(`${baseUrl}/api/payroll/export?month=${month}&year=${year}`, {
            method: 'GET',
            cache: 'no-store',
            headers: {
                'Authorization': `Bearer ${token}`,
                'Cache-Control': 'no-cache'
            }
        });

        if (!response.ok) {
            throw new Error("Không thể xuất file");
        }

        const blob = await response.blob();
        const url = window.URL.createObjectURL(blob);

        const a = document.createElement('a');
        a.style.display = 'none';
        a.href = url;
        a.download = `Bang_Luong_Thang_${month}_${year}.csv`;

        document.body.appendChild(a);
        a.click();

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