<?php
$pageTitle = 'Chỉnh sửa nhân sự | Creative Agency Hub';
$pageCss = ['hrm.css'];
$activeMenu = 'departments'; 
$topbarTitle = 'Chỉnh sửa nhân sự';

ob_start();
?>

<?php
$pageHeading = 'Cập nhật thông tin nhân sự';
$pageSubtitle = 'Chỉnh sửa vai trò, phòng ban hoặc trạng thái làm việc của thành viên.';
require __DIR__ . '/../components/page-header.php';
?>

<section class="card" style="max-width: 800px;">
    <div class="card-body">
        <div id="loading-state" style="text-align: center; padding: 40px;">
            <p>Đang tải dữ liệu hệ thống...</p>
        </div>

        <form id="edit-employee-form" style="display: none;">
            <!-- Thông tin cố định -->
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
                <!-- Chọn Phòng ban (Dữ liệu động) -->
                <div>
                    <label style="display:block; margin-bottom:8px; font-weight:bold;">Phòng ban</label>
                    <select id="emp-dept" name="department_id" style="width:100%; padding:10px; border:1px solid #ddd; border-radius:4px;">
                        <option value="">-- Chọn phòng ban --</option>
                    </select>
                </div>
                <!-- Chọn Chức danh (Dữ liệu động) -->
                <div>
                    <label style="display:block; margin-bottom:8px; font-weight:bold;">Chức danh</label>
                    <select id="emp-pos" name="position_id" style="width:100%; padding:10px; border:1px solid #ddd; border-radius:4px;">
                        <option value="">-- Chọn chức danh --</option>
                    </select>
                </div>
            </div>

            <!-- Trạng thái (Cập nhật theo yêu cầu mới) -->
            <div style="margin-bottom: 20px;">
                <label style="display:block; margin-bottom:8px; font-weight:bold;">Trạng thái làm việc</label>
                <select id="emp-status" name="status" style="width:100%; padding:10px; border:1px solid #ddd; border-radius:4px;">
                    <option value="active">Đang làm việc (Active)</option>
                    <option value="probation">Thử việc (Probation)</option>
                    <option value="on_leave">Tạm nghỉ (On Leave)</option>
                </select>
            </div>

            <div style="display:flex; gap:10px; margin-top: 30px;">
                <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
                <a href="departments.php" class="btn btn-light">Hủy bỏ</a>
            </div>
        </form>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const token = localStorage.getItem('cah_token');
    const baseUrl = '/creative-agency-hub';
    const urlParams = new URLSearchParams(window.location.search);
    const employeeId = urlParams.get('id');

    if (!employeeId) {
        alert('Không tìm thấy ID nhân sự!');
        window.location.href = 'departments.php';
        return;
    }

    const form = document.getElementById('edit-employee-form');
    const loading = document.getElementById('loading-state');
    const deptSelect = document.getElementById('emp-dept');
    const posSelect = document.getElementById('emp-pos');

    /**
     * TRACE DATA FLOW:
     * 1. Promise.all gọi đồng thời 2 API để lấy danh sách tổ chức và thông tin nhân sự.
     * 2. Render danh sách phòng ban/chức danh vào dropdown trước.
     * 3. Set giá trị mặc định cho nhân sự đang sửa.
     */
    Promise.all([
        // Lấy danh sách phòng ban và chức danh
        fetch(`${baseUrl}/public/api/organization/data`, {
            headers: { 'Authorization': 'Bearer ' + token }
        }).then(res => res.json()),
        
        // Lấy thông tin chi tiết nhân sự
        fetch(`${baseUrl}/public/api/employees/${employeeId}`, {
            headers: { 'Authorization': 'Bearer ' + token }
        }).then(res => res.json())
    ])
    .then(([orgRes, empRes]) => {
        if (orgRes.status === 'success' && empRes.status === 'success') {
            // 1. Đổ dữ liệu vào dropdown Phòng ban
            orgRes.data.departments.forEach(d => {
                const opt = new Option(d.name, d.id);
                deptSelect.add(opt);
            });

            // 2. Đổ dữ liệu vào dropdown Chức danh
            orgRes.data.positions.forEach(p => {
                const opt = new Option(p.name, p.id);
                posSelect.add(opt);
            });

            // 3. Điền thông tin nhân sự
            const emp = empRes.data.employee;
            document.getElementById('emp-name').value = emp.full_name;
            document.getElementById('emp-email').value = emp.email;
            document.getElementById('emp-status').value = emp.status;
            deptSelect.value = emp.department_id || "";
            posSelect.value = emp.position_id || "";

            loading.style.display = 'none';
            form.style.display = 'block';
        } else {
            alert('Lỗi tải dữ liệu: ' + (orgRes.message || empRes.message));
        }
    })
    .catch(err => {
        console.error('API Error:', err);
        alert('Không thể kết nối đến hệ thống.');
    });

    // Xử lý cập nhật đa trường
    form.addEventListener('submit', function(e) {
        e.preventDefault();

        const updateData = {
            status: document.getElementById('emp-status').value,
            department_id: deptSelect.value,
            position_id: posSelect.value
        };

        fetch(`${baseUrl}/public/api/employees/${employeeId}`, {
            method: 'PUT',
            headers: {
                'Authorization': 'Bearer ' + token,
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(updateData)
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                alert('Cập nhật thông tin thành công!');
                window.location.href = 'departments.php';
            } else {
                alert('Lỗi: ' + data.message);
            }
        })
        .catch(err => alert('Lỗi kết nối server!'));
    });
});
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/app.php';
?>