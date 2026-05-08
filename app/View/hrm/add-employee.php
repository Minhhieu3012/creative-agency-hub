<?php
$pageTitle = 'Thêm nhân sự mới | Creative Agency Hub';
$pageCss = ['hrm.css'];
$activeMenu = 'employees';
$topbarTitle = 'Thêm nhân sự';

ob_start();
?>

<?php
$pageHeading = 'Thêm nhân sự mới';
$pageSubtitle = 'Tạo hồ sơ nhân viên mới và phân bổ vào hệ thống tổ chức.';
require __DIR__ . '/../components/page-header.php';
?>

<section class="card" style="max-width: 900px; margin: 0 auto;">
    <div class="card-body">
        <form id="js-add-employee-form">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                <div>
                    <label style="display:block; margin-bottom:8px; font-weight:bold;">Họ và tên <span style="color:red">*</span></label>
                    <input type="text" name="full_name" required style="width:100%; padding:10px; border:1px solid #ddd; border-radius:4px;">
                </div>
                <div>
                    <label style="display:block; margin-bottom:8px; font-weight:bold;">Số điện thoại</label>
                    <input type="text" name="phone" style="width:100%; padding:10px; border:1px solid #ddd; border-radius:4px;">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                <div>
                    <label style="display:block; margin-bottom:8px; font-weight:bold;">Email <span style="color:red">*</span></label>
                    <input type="email" name="email" required style="width:100%; padding:10px; border:1px solid #ddd; border-radius:4px;">
                </div>
                <div>
                    <label style="display:block; margin-bottom:8px; font-weight:bold;">Mật khẩu khởi tạo <span style="color:red">*</span></label>
                    <input type="password" name="password" required style="width:100%; padding:10px; border:1px solid #ddd; border-radius:4px;">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; margin-bottom: 25px;">
                <div>
                    <label style="display:block; margin-bottom:8px; font-weight:bold;">Phòng ban</label>
                    <select id="js-dept-select" name="department_id" required style="width:100%; padding:10px; border:1px solid #ddd; border-radius:4px;"></select>
                </div>
                <div>
                    <label style="display:block; margin-bottom:8px; font-weight:bold;">Chức danh</label>
                    <select id="js-pos-select" name="position_id" required style="width:100%; padding:10px; border:1px solid #ddd; border-radius:4px;"></select>
                </div>
                <div>
                    <label style="display:block; margin-bottom:8px; font-weight:bold;">Vai trò</label>
                    <select name="role" required style="width:100%; padding:10px; border:1px solid #ddd; border-radius:4px;">
                        <option value="employee">Nhân viên</option>
                        <option value="manager">Quản lý</option>
                        <option value="admin">Quản trị viên</option>
                    </select>
                </div>
            </div>

            <div style="display:flex; gap:10px; padding-top: 20px; border-top: 1px solid #eee;">
                <button type="submit" class="btn btn-primary" id="js-submit-btn">Tạo hồ sơ nhân sự</button>
                <a href="employees.php" class="btn btn-light">Hủy bỏ</a>
            </div>
        </form>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const token = localStorage.getItem('cah_token');
    const baseUrl = '/creative-agency-hub';
    const form = document.getElementById('js-add-employee-form');
    const btn = document.getElementById('js-submit-btn');

    // 1. Tải dữ liệu ban đầu cho Dropdown
    fetch(`${baseUrl}/public/api/organization/data`, { 
        headers: { 'Authorization': 'Bearer ' + token } 
    })
    .then(res => res.json())
    .then(res => {
        if (res.status === 'success') {
            const dSel = document.getElementById('js-dept-select');
            const pSel = document.getElementById('js-pos-select');
            res.data.departments.forEach(d => dSel.add(new Option(d.name, d.id)));
            res.data.positions.forEach(p => pSel.add(new Option(p.name, p.id)));
        }
    });

    // 2. Xử lý sự kiện lưu
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        btn.disabled = true;
        btn.textContent = 'Đang xử lý...';

        // FIX: Sử dụng Object.fromEntries (đúng chính tả)
        // Hoặc dùng cách thủ công này để an toàn tuyệt đối:
        const formData = new FormData(form);
        const data = {};
        formData.forEach((value, key) => {
            data[key] = value;
        });

        fetch(`${baseUrl}/public/api/employees`, {
            method: 'POST',
            headers: { 
                'Authorization': 'Bearer ' + token, 
                'Content-Type': 'application/json' 
            },
            body: JSON.stringify(data)
        })
        .then(async res => {
            const contentType = res.headers.get('content-type');
            if (contentType && contentType.includes('application/json')) {
                const result = await res.json();
                if (res.ok && result.status === 'success') {
                    alert('Đã tạo nhân sự thành công!');
                    window.location.href = 'employees.php';
                } else {
                    throw new Error(result.message || 'Lỗi từ Server');
                }
            } else {
                throw new Error('Lỗi hệ thống (Mã ' + res.status + ')');
            }
        })
        .catch(err => {
            alert('Thông báo: ' + err.message);
            btn.disabled = false;
            btn.textContent = 'Tạo hồ sơ nhân sự';
        });
    });
});
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/app.php';
?>