<?php
$pageTitle = 'Thêm phòng ban mới | Creative Agency Hub';
$pageCss = ['hrm.css'];
$activeMenu = 'departments';
$topbarTitle = 'Thêm phòng ban';

ob_start();
?>

<?php
$pageHeading = 'Tạo phòng ban mới';
$pageSubtitle = 'Thiết lập thông tin cơ bản cho đơn vị tổ chức mới trong hệ thống.';
require __DIR__ . '/../components/page-header.php';
?>

<section class="card">
    <div class="card-body">
        <form id="add-dept-form" style="max-width: 600px;">
            <div style="margin-bottom: 20px;">
                <label style="display:block; margin-bottom:8px; font-weight:bold;">Tên phòng ban</label>
                <input type="text" id="dept-name" name="name" required style="width:100%; padding:10px; border:1px solid #ddd; border-radius:4px;">
            </div>
            <div style="margin-bottom: 20px;">
                <label style="display:block; margin-bottom:8px; font-weight:bold;">Mô tả</label>
                <textarea id="dept-desc" name="description" rows="4" style="width:100%; padding:10px; border:1px solid #ddd; border-radius:4px;"></textarea>
            </div>
            <div style="display:flex; gap:10px;">
                <button type="submit" class="btn btn-primary">Lưu phòng ban</button>
                <a href="departments.php" class="btn btn-light">Hủy bỏ</a>
            </div>
        </form>
    </div>
</section>

<script>
document.getElementById('add-dept-form').addEventListener('submit', function(e) {
    e.preventDefault(); // Chặn việc load lại trang

    const token = localStorage.getItem('cah_token');
    const name = document.getElementById('dept-name').value;
    const description = document.getElementById('dept-desc').value;

    fetch('/creative-agency-hub/public/api/organization/store', {
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
            alert('Lưu thành công!');
            window.location.href = 'departments.php'; // Quay lại trang sơ đồ
        } else {
            alert('Lỗi: ' + data.message);
        }
    })
    .catch(err => {
        console.error('Lỗi kết nối:', err);
        alert('Không thể kết nối đến máy chủ.');
    });
});
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/app.php';
?>