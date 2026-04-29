<?php
session_start();
require_once '../config/db_connect.php';

date_default_timezone_set('Asia/Ho_Chi_Minh');

// Để duyệt đơn bằng PHP hiển thị ra list, ta gán cứng giả lập ID 2 (Sếp B)
$_SESSION['employee_id'] = 2; 
$manager_id = $_SESSION['employee_id'];
$month = date('m');
$year = date('Y');

// Lấy thông tin Sếp
$stmtUser = $pdo->prepare("SELECT full_name FROM employees WHERE id = ?");
$stmtUser->execute([$manager_id]);
$manager = $stmtUser->fetch();

// ==========================================
// LẤY DANH SÁCH ĐƠN CỦA LÍNH CHỜ DUYỆT (Truy vấn theo bảng EMPLOYEES)
// ==========================================
$sqlLeave = "
    SELECT lr.*, e.full_name, e.remaining_leave_days 
    FROM leave_requests lr
    JOIN employees e ON lr.employee_id = e.id
    WHERE lr.status = 'Pending' AND e.manager_id = ?
    ORDER BY lr.created_at DESC
";
$stmtLeaves = $pdo->prepare($sqlLeave);
$stmtLeaves->execute([$manager_id]);
$requests = $stmtLeaves->fetchAll();
?>

<!DOCTYPE html>
<html lang="vi">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>Không gian Quản lý - Creative Agency Hub</title>
  <link href="../public/assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
  <link href="../public/assets/css/sb-admin-2.min.css" rel="stylesheet">`n  <link href="../public/assets/css/agency-theme.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
  <style>
  body {
    background-color: #f4f7fc;
  }

  .fw-bold {
    font-weight: 700 !important;
  }

  .card-custom {
    border: none;
    border-radius: 1.25rem;
    box-shadow: 0 0.5rem 1.5rem rgba(0, 0, 0, 0.05);
  }

  .text-gradient {
    background: linear-gradient(45deg, #1cc88a, #13855c);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
  }
  </style>
</head>

<body id="page-top">
  <div id="wrapper">
    <?php if(file_exists('../components/sidebar.php')) include('../components/sidebar.php'); ?>
    <div id="content-wrapper" class="d-flex flex-column w-100">
      <div id="content">
        <?php if(file_exists('../components/navbar.php')) include('../components/navbar.php'); ?>
        <div class="container-fluid py-4">

          <div class="row mb-4">
            <div class="col-12 text-center">
              <h3 class="fw-bold text-gradient mb-1"><i class="bi bi-shield-lock me-2"></i>Không gian Quản lý</h3>
              <p class="text-muted small mb-3">Xin chào Manager:
                <strong><?= htmlspecialchars($manager['full_name'] ?? 'Sếp') ?></strong></p>
              <div class="d-flex justify-content-center flex-wrap" style="gap: 10px;">
                <a href="../index.php" class="btn btn-sm btn-dark rounded-pill px-4 shadow-sm"><i
                    class="bi bi-house-door me-1"></i> Trang chủ</a>
                <a href="manager_approvals.php" class="btn btn-sm btn-success rounded-pill px-4 shadow-sm"><i
                    class="bi bi-check2-square me-1"></i> Quản trị Nhân sự</a>
              </div>
            </div>
          </div>

          <div class="card card-custom shadow-sm mb-4">
            <div
              class="card-header py-3 bg-white d-flex flex-row align-items-center justify-content-between border-bottom">
              <h6 class="m-0 fw-bold text-success"><i class="bi bi-inboxes me-2"></i>Đơn xin nghỉ phép cần xử lý (Của
                Cấp dưới)</h6>
            </div>
            <div class="card-body p-0">
              <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                  <thead class="bg-light text-muted text-center" style="font-size: 0.9rem;">
                    <tr>
                      <th class="py-3 pl-4 text-left">Nhân viên</th>
                      <th>Thời gian nghỉ</th>
                      <th class="text-left">Lý do</th>
                      <th>Số ngày</th>
                      <th>Hành động</th>
                    </tr>
                  </thead>
                  <tbody class="text-center" style="font-size: 0.95rem;">
                    <?php if(empty($requests)): ?>
                    <tr>
                      <td colspan="5" class="text-muted py-5">Không có đơn từ nào cần duyệt. Tuyệt vời! 🎉</td>
                    </tr>
                    <?php else: foreach($requests as $req): 
                        $d = (strtotime($req['end_date']) - strtotime($req['start_date'])) / 86400 + 1;
                    ?>
                    <tr>
                      <td class="py-3 pl-4 text-left">
                        <div class="d-flex align-items-center">
                          <img class="rounded-circle me-3 shadow-sm"
                            src="https://ui-avatars.com/api/?name=<?= urlencode($req['full_name']) ?>&size=40"
                            style="width:40px">
                          <div>
                            <div class="fw-bold text-dark"><?= htmlspecialchars($req['full_name']) ?></div>
                            <div class="small text-success">Quỹ phép: <?= floatval($req['remaining_leave_days']) ?> ngày
                            </div>
                          </div>
                        </div>
                      </td>
                      <td><span class="text-primary fw-bold"><?= date('d/m/Y', strtotime($req['start_date'])) ?></span>
                        <i class="bi bi-arrow-right text-muted mx-1"></i> <span
                          class="text-primary fw-bold"><?= date('d/m/Y', strtotime($req['end_date'])) ?></span></td>
                      <td class="text-left" style="max-width: 250px;"><span
                          class="text-truncate d-inline-block w-100 text-muted"
                          title="<?= htmlspecialchars($req['reason']) ?>"><?= htmlspecialchars($req['reason']) ?></span>
                      </td>
                      <td><span class="badge badge-light border text-dark px-3 py-2 rounded-pill"><?= $d ?> ngày</span>
                      </td>
                      <td>
                        <button onclick="handleApprove(<?= $req['id'] ?>, 'Approved')"
                          class="btn btn-success btn-sm rounded-circle shadow-sm mx-1"
                          style="width: 35px; height: 35px;" title="Duyệt"><i class="bi bi-check-lg"></i></button>
                        <button onclick="handleApprove(<?= $req['id'] ?>, 'Rejected')"
                          class="btn btn-danger btn-sm rounded-circle shadow-sm mx-1" style="width: 35px; height: 35px;"
                          title="Từ chối"><i class="bi bi-x-lg"></i></button>
                      </td>
                    </tr>
                    <?php endforeach; endif; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>

          <div class="card card-custom shadow-sm mb-4">
            <div
              class="card-header py-3 bg-white d-flex flex-row align-items-center justify-content-between border-bottom">
              <h6 class="m-0 fw-bold text-primary"><i class="bi bi-cash-stack me-2"></i>Bảng Lương Tổng Hợp Toàn Công Ty
                - Tháng <?= $month ?></h6>
            </div>
            <div class="card-body p-0">
              <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                  <thead class="bg-light text-muted text-center" style="font-size: 0.9rem;">
                    <tr>
                      <th class="py-3 text-left pl-4">Nhân sự</th>
                      <th>Lương cơ bản</th>
                      <th>Ngày công</th>
                      <th>Thưởng KPI</th>
                      <th>Vi phạm</th>
                      <th class="text-right pr-4">Thực lãnh (VNĐ)</th>
                    </tr>
                  </thead>
                  <tbody id="payrollTableBody" class="text-center" style="font-size: 0.95rem;">
                    <tr>
                      <td colspan="6" class="py-4 text-primary">
                        <div class="spinner-border spinner-border-sm me-2"></div>Đang tải dữ liệu từ API...
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>

        </div>
      </div>
      <?php if(file_exists('../components/footer.php')) include('../components/footer.php'); ?>
    </div>
  </div>

  <script src="../public/assets/vendor/jquery/jquery.min.js"></script>
  <script src="../public/assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="../public/assets/js/sb-admin-2.min.js"></script>

  <script>
  const token = localStorage.getItem('jwt_token');

  // 1. Hàm gọi API Duyệt Đơn
  function handleApprove(id, action) {
    if (!token) return alert("Vui lòng đăng nhập (Chưa có Token)!");
    if (!confirm(`Bạn chắc chắn muốn ${action === 'Approved' ? 'DUYỆT' : 'TỪ CHỐI'} đơn này?`)) return;

    fetch(`/creative-agency-hub/public/api/leaves/${id}/approve`, {
        method: 'PATCH',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': 'Bearer ' + token
        },
        body: JSON.stringify({
          action: action
        })
      })
      .then(res => res.json())
      .then(data => {
        if (data.status === 'success') {
          alert("✅ " + data.message);
          location.reload();
        } else {
          alert("❌ Lỗi: " + data.message);
        }
      }).catch(err => alert("Lỗi kết nối API!"));
  }

  // 2. Hàm gọi API Lấy Bảng lương
  if (token) {
    fetch('/creative-agency-hub/public/api/payroll/summary', {
        method: 'GET',
        headers: {
          'Authorization': 'Bearer ' + token
        }
      })
      .then(res => res.json())
      .then(data => {
        if (data.status === 'success') {
          const tbody = document.getElementById('payrollTableBody');
          tbody.innerHTML = ''; // Xóa chữ loading

          data.data.forEach(p => {
            const roleBadge = p.role === 'admin' ? 'badge-danger' : (p.role === 'manager' ? 'badge-primary' :
              'badge-secondary');

            const row = `
                    <tr>
                      <td class="py-3 text-left pl-4">
                        <div class="fw-bold text-dark">${p.full_name}</div>
                        <span class="badge ${roleBadge} p-1 px-2 mt-1 rounded-pill">${p.role.toUpperCase()}</span>
                      </td>
                      <td class="text-muted">${p.base_salary.toLocaleString()} ₫</td>
                      <td><span class="badge badge-info px-3 py-2 rounded-pill">${p.attendance.actual_days} / ${p.attendance.standard_days}</span></td>
                      <td class="text-success fw-bold">+ ${p.financial.bonus.toLocaleString()} ₫ <br><small class="text-muted fw-normal">(${p.kpi.percent}%)</small></td>
                      <td class="text-danger fw-bold">- ${p.financial.penalty.toLocaleString()} ₫ <br><small class="text-muted fw-normal">(${p.attendance.late + p.attendance.early} lỗi)</small></td>
                      <td class="text-right pr-4 fw-bold text-primary" style="font-size: 1.15rem;">${p.financial.net_salary.toLocaleString()} ₫</td>
                    </tr>`;
            tbody.innerHTML += row;
          });
        }
      });
  }
  </script>
</body>

</html>
