<?php
$pageTitle = 'Chỉnh sửa nhân sự | Creative Agency Hub';
$pageCss = ['hrm.css'];
$activeMenu = 'employees';
$topbarTitle = 'Chỉnh sửa nhân sự';

ob_start();
?>

<?php
$pageHeading = 'Cập nhật thông tin nhân sự';
$pageSubtitle = 'Chỉnh sửa phòng ban, chức danh hoặc trạng thái làm việc của thành viên.';
require __DIR__ . '/../components/page-header.php';
?>

<section class="card" style="max-width: 800px;">
    <div class="card-body">
        <div id="loading-state" style="text-align: center; padding: 40px;">
            <p>Đang tải dữ liệu hệ thống...</p>
        </div>

        <div id="error-state" style="display:none; padding: 16px; margin-bottom: 20px; color: #b91c1c; background: #fee2e2; border: 1px solid #fecaca; border-radius: 12px;"></div>

        <form id="edit-employee-form" style="display: none;">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                <div>
                    <label style="display:block; margin-bottom:8px; font-weight:bold;">Họ và tên</label>
                    <input type="text" id="emp-name" disabled style="width:100%; padding:10px; background:#f5f5f5; border:1px solid #ddd; border-radius:4px;">
                </div>

                <div>
                    <label style="display:block; margin-bottom:8px; font-weight:bold;">Email</label>
                    <input type="email" id="emp-email" disabled style="width:100%; padding:10px; background:#f5f5f5; border:1px solid #ddd; border-radius:4px;">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                <div>
                    <label style="display:block; margin-bottom:8px; font-weight:bold;">Phòng ban</label>
                    <select id="emp-dept" name="department_id" required style="width:100%; padding:10px; border:1px solid #ddd; border-radius:4px;">
                        <option value="">-- Chọn phòng ban --</option>
                    </select>
                </div>

                <div>
                    <label style="display:block; margin-bottom:8px; font-weight:bold;">Chức danh</label>
                    <select id="emp-pos" name="position_id" required style="width:100%; padding:10px; border:1px solid #ddd; border-radius:4px;">
                        <option value="">-- Chọn chức danh --</option>
                    </select>
                </div>
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display:block; margin-bottom:8px; font-weight:bold;">Trạng thái làm việc</label>
                <select id="emp-status" name="status" required style="width:100%; padding:10px; border:1px solid #ddd; border-radius:4px;">
                    <option value="active">Đang làm việc</option>
                    <option value="inactive">Tạm ngưng</option>
                    <option value="resigned">Đã nghỉ việc</option>
                    <option value="suspended">Bị khóa</option>
                </select>
            </div>

            <div style="display:flex; gap:10px; margin-top: 30px;">
                <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
                <a href="employees.php" class="btn btn-light">Hủy bỏ</a>
            </div>
        </form>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const token = localStorage.getItem('cah_token') || localStorage.getItem('cah_auth_token') || '';
    const baseUrl = '/creative-agency-hub';
    const urlParams = new URLSearchParams(window.location.search);
    const employeeId = urlParams.get('id');

    const form = document.getElementById('edit-employee-form');
    const loading = document.getElementById('loading-state');
    const errorState = document.getElementById('error-state');
    const deptSelect = document.getElementById('emp-dept');
    const posSelect = document.getElementById('emp-pos');
    const statusSelect = document.getElementById('emp-status');
    const submitBtn = form.querySelector('button[type="submit"]');

    function showError(message) {
        loading.style.display = 'none';
        errorState.style.display = 'block';
        errorState.innerHTML = `<strong>Lỗi:</strong> ${escapeHtml(message)}`;
    }

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

            console.error('Raw response:', text);
            throw new Error(`API trả về dữ liệu không hợp lệ. HTTP ${response.status}`);
        }
    }

    async function apiRequest(path, options = {}) {
        const response = await fetch(`${baseUrl}/public${path}`, {
            ...options,
            headers: {
                'Accept': 'application/json',
                'Authorization': 'Bearer ' + token,
                ...(options.headers || {})
            }
        });

        return readJsonResponse(response);
    }

    if (!employeeId) {
        showError('Không tìm thấy ID nhân sự trên URL.');
        return;
    }

    if (!token) {
        showError('Bạn chưa đăng nhập hoặc token đã mất. Vui lòng đăng nhập lại.');
        return;
    }

    async function loadPageData() {
        try {
            const [orgRes, empRes] = await Promise.all([
                apiRequest('/api/organization/data'),
                apiRequest(`/api/employees/${employeeId}`)
            ]);

            const departments = orgRes.data?.departments || [];
            const positions = orgRes.data?.positions || [];
            const employee = empRes.data?.employee;

            if (!employee) {
                throw new Error('API không trả về dữ liệu nhân sự.');
            }

            deptSelect.innerHTML = '<option value="">-- Chọn phòng ban --</option>';
            posSelect.innerHTML = '<option value="">-- Chọn chức danh --</option>';

            departments.forEach((department) => {
                const option = new Option(department.name, department.id);
                deptSelect.add(option);
            });

            positions.forEach((position) => {
                const option = new Option(position.name, position.id);
                posSelect.add(option);
            });

            document.getElementById('emp-name').value = employee.full_name || '';
            document.getElementById('emp-email').value = employee.email || '';

            deptSelect.value = employee.department_id || '';
            posSelect.value = employee.position_id || '';
            statusSelect.value = employee.status || 'active';

            loading.style.display = 'none';
            errorState.style.display = 'none';
            form.style.display = 'block';
        } catch (error) {
            console.error('API Error:', error);
            showError(error.message || 'Không thể kết nối đến hệ thống.');
        }
    }

    form.addEventListener('submit', async function(event) {
        event.preventDefault();

        const updateData = {
            department_id: deptSelect.value,
            position_id: posSelect.value,
            status: statusSelect.value
        };

        if (!updateData.department_id || !updateData.position_id || !updateData.status) {
            showError('Vui lòng chọn đầy đủ phòng ban, chức danh và trạng thái.');
            return;
        }

        submitBtn.disabled = true;
        submitBtn.dataset.originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = 'Đang lưu...';

        try {
            const result = await apiRequest(`/api/employees/${employeeId}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(updateData)
            });

            alert(result.message || 'Cập nhật thông tin thành công!');
            window.location.href = 'employees.php';
        } catch (error) {
            console.error('Update Error:', error);
            alert(error.message || 'Không thể cập nhật nhân sự.');
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = submitBtn.dataset.originalText || 'Lưu thay đổi';
        }
    });

    loadPageData();
});
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/app.php';
?>