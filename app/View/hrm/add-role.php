<?php
$pageTitle = 'Thêm chức danh mới | Creative Agency Hub';
$pageCss = ['hrm.css'];
$activeMenu = 'departments';
$topbarTitle = 'Thêm chức danh';

ob_start();
?>

<?php
$pageHeading = 'Tạo chức danh & Phân quyền';
$pageSubtitle = 'Thiết lập tên vị trí công việc và mô tả trách nhiệm hệ thống.';
require __DIR__ . '/../components/page-header.php';
?>

<section class="card">
    <div class="card-body">
        <form id="add-role-form" style="max-width: 600px;">
            <div style="margin-bottom: 20px;">
                <label style="display:block; margin-bottom:8px; font-weight:bold;">Tên chức danh (Vị trí)</label>
                <input type="text" id="role-name" name="name" placeholder="VD: Senior Frontend Developer" required style="width:100%; padding:10px; border:1px solid #ddd; border-radius:4px;">
            </div>
            <div style="margin-bottom: 20px;">
                <label style="display:block; margin-bottom:8px; font-weight:bold;">Mô tả quyền hạn & Trách nhiệm</label>
                <textarea id="role-desc" name="description" rows="4" placeholder="Mô tả tóm tắt về quyền hạn của vị trí này..." style="width:100%; padding:10px; border:1px solid #ddd; border-radius:4px;"></textarea>
            </div>
            <div style="display:flex; gap:10px;">
                <button type="submit" class="btn btn-primary">Lưu chức danh</button>
                <a href="departments.php" class="btn btn-light">Hủy bỏ</a>
            </div>
        </form>
    </div>
</section>

<script>
document.getElementById('add-role-form').addEventListener('submit', function(e) {
    e.preventDefault();

    const token = localStorage.getItem('cah_token');
    const name = document.getElementById('role-name').value;
    const description = document.getElementById('role-desc').value;

    fetch('/creative-agency-hub/public/api/organization/positions/store', {
        method: 'POST',
        headers: {
            'Authorization': 'Bearer ' + token,
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ name, description })
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            alert('Đã thêm chức danh mới!');
            window.location.href = 'departments.php';
        } else {
            alert('Lỗi: ' + data.message);
        }
    })
    .catch(err => {
        console.error('Lỗi:', err);
        alert('Không thể kết nối API');
    });
});
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/app.php';
?>