<?php
session_start();
require_once '../config/db_connect.php';

date_default_timezone_set('Asia/Ho_Chi_Minh');

// GIẢ LẬP ĐĂNG NHẬP: Lấy ID 3 (Nguyễn Văn A - Role: Employee) để test
$_SESSION['employee_id'] = 3; 
$emp_id = $_SESSION['employee_id'];

// ==========================================
// 1. LẤY THÔNG TIN QUỸ PHÉP TỪ BẢNG EMPLOYEES
// ==========================================
$stmtUser = $pdo->prepare("SELECT full_name, remaining_leave_days FROM employees WHERE id = ?");
$stmtUser->execute([$emp_id]);
$user = $stmtUser->fetch();

// DÙNG floatval() ĐỂ XÓA ĐUÔI .00 (Ví dụ: 12.00 -> 12)
$leave_balance = isset($user['remaining_leave_days']) ? floatval($user['remaining_leave_days']) : 0;

// ==========================================
// 2. XỬ LÝ NHÂN VIÊN HỦY ĐƠN (KHI CÒN PENDING)
// (Vẫn giữ bằng PHP thuần vì Controller chưa có API Delete)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['cancel_leave'])) {
    $req_id = $_POST['request_id'];
    
    // Đảm bảo chỉ được xóa đơn của chính mình và đơn đó đang chờ duyệt
    $stmtCancel = $pdo->prepare("DELETE FROM leave_requests WHERE id = ? AND employee_id = ? AND status = 'Pending'");
    $stmtCancel->execute([$req_id, $emp_id]);
    
    if ($stmtCancel->rowCount() > 0) {
        $_SESSION['flash_msg'] = "Đã hủy đơn xin nghỉ phép thành công!";
        $_SESSION['flash_type'] = "success";
    } else {
        $_SESSION['flash_msg'] = "Không thể hủy! Đơn này đã được xử lý hoặc không tồn tại.";
        $_SESSION['flash_type'] = "danger";
    }
    header("Location: leave_request.php");
    exit();
}

// ==========================================
// 3. LẤY LỊCH SỬ ĐƠN CỦA CHÍNH NHÂN VIÊN NÀY
// ==========================================
$stmtRequests = $pdo->prepare("SELECT * FROM leave_requests WHERE employee_id = ? ORDER BY created_at DESC");
$stmtRequests->execute([$emp_id]);
$requests = $stmtRequests->fetchAll();
?>

<!DOCTYPE html>
<html lang="vi">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>Xin Nghỉ Phép - Creative Agency Hub</title>

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

  .rounded-3 {
    border-radius: 0.75rem !important;
  }

  .card-custom {
    border: none;
    border-radius: 0.75rem;
    box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.05);
    transition: box-shadow 0.3s ease;
  }

  .card-custom:hover {
    box-shadow: 0 0.5rem 2rem rgba(0, 0, 0, 0.08);
  }

  .text-gradient {
    background: -webkit-linear-gradient(45deg, #0d6efd, #6610f2);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
  }
  </style>
</head>

<body id="page-top">

  <div id="wrapper">
    <?php if(file_exists('../components/sidebar.php')) include('../components/sidebar.php'); ?>

    <div id="content-wrapper" class="d-flex flex-column">
      <div id="content">
        <?php if(file_exists('../components/navbar.php')) include('../components/navbar.php'); ?>

        <div class="container-fluid py-4">

          <div class="row mb-4">
            <div class="col-12 text-center">
              <h3 class="fw-bold text-gradient mb-1">Creative Agency Hub</h3>
              <p class="text-muted small mb-3">Cổng thông tin nội bộ - Portal Nhân viên</p>

              <div class="d-flex justify-content-center flex-wrap" style="gap: 8px;">
                <a href="../index.php" class="btn btn-sm btn-dark rounded-pill shadow-sm px-4"><i
                    class="bi bi-house-door me-1"></i> Trang chủ</a>
                <a href="attendance.php" class="btn btn-sm btn-outline-primary rounded-pill shadow-sm bg-white px-4"><i
                    class="bi bi-clock-history me-1"></i> Chấm công</a>
                <a href="leave_request.php" class="btn btn-sm btn-primary rounded-pill shadow-sm px-4"><i
                    class="bi bi-envelope-paper me-1"></i> Xin nghỉ phép</a>
                <a href="payroll_summary.php"
                  class="btn btn-sm btn-outline-primary rounded-pill shadow-sm bg-white px-4"><i
                    class="bi bi-receipt me-1"></i> Bảng lương</a>
              </div>
            </div>
          </div>

          <div class="row align-items-stretch">

            <div class="col-lg-4 mb-4">
              <div class="card card-custom h-100">
                <div class="card-body p-4">
                  <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
                    <h6 class="fw-bold text-primary mb-0"><i class="bi bi-pencil-square me-2"></i>Tạo đơn mới</h6>
                    <span class="badge badge-success px-2 py-1" style="font-size: 0.85rem;">Quỹ phép:
                      <?= $leave_balance ?> ngày</span>
                  </div>

                  <?php if (isset($_SESSION['flash_msg'])): ?>
                  <div
                    class="alert alert-<?= $_SESSION['flash_type'] ?> alert-dismissible fade show text-left rounded-3 small py-2 px-3 mb-3"
                    role="alert">
                    <i class="bi bi-info-circle-fill me-1"></i> <?= $_SESSION['flash_msg'] ?>
                    <button type="button" class="close p-2" data-dismiss="alert" aria-label="Close">
                      <span aria-hidden="true">&times;</span>
                    </button>
                  </div>
                  <?php unset($_SESSION['flash_msg']); unset($_SESSION['flash_type']); endif; ?>

                  <form id="leaveForm" onsubmit="event.preventDefault(); submitLeaveRequest();">
                    <div class="form-group mb-3">
                      <label class="font-weight-bold text-muted small">Từ ngày:</label>
                      <input type="date" id="start_date" class="form-control" required min="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="form-group mb-3">
                      <label class="font-weight-bold text-muted small">Đến ngày:</label>
                      <input type="date" id="end_date" class="form-control" required min="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="form-group mb-4">
                      <label class="font-weight-bold text-muted small">Lý do nghỉ phép:</label>
                      <textarea id="reason" class="form-control" rows="3" required
                        placeholder="Nhập chi tiết lý do..."></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 rounded-pill py-2 font-weight-bold shadow-sm"
                      <?= $leave_balance <= 0 ? 'disabled' : '' ?>>
                      <i class="bi bi-send me-1"></i> GỬI YÊU CẦU
                    </button>
                  </form>

                </div>
              </div>
            </div>

            <div class="col-lg-8 mb-4">
              <div class="card card-custom h-100">
                <div class="card-header bg-white p-4 border-bottom d-flex justify-content-between align-items-center">
                  <h6 class="fw-bold mb-0 text-primary"><i class="bi bi-list-check me-2"></i>Lịch sử nghỉ phép của bạn
                  </h6>
                </div>
                <div class="card-body p-0">
                  <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" style="font-size: 0.95rem;">
                      <thead class="bg-light text-muted">
                        <tr>
                          <th class="pl-4 py-3">Thời gian nghỉ</th>
                          <th>Lý do</th>
                          <th class="text-center">Số ngày</th>
                          <th class="text-center pr-4">Trạng thái / Tác vụ</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php if(empty($requests)): ?>
                        <tr>
                          <td colspan="4" class="text-center text-muted py-5">Chưa có dữ liệu nghỉ phép.</td>
                        </tr>
                        <?php else: foreach($requests as $req): 
                            $d = (strtotime($req['end_date']) - strtotime($req['start_date'])) / 86400 + 1; 
                        ?>
                        <tr>
                          <td class="pl-4 py-3">
                            <strong class="text-dark"><?= date('d/m/Y', strtotime($req['start_date'])) ?></strong> <br>
                            <small class="text-muted">đến <?= date('d/m/Y', strtotime($req['end_date'])) ?></small>
                          </td>
                          <td style="max-width: 250px;">
                            <span class="d-inline-block text-truncate w-100 text-muted"
                              title="<?= htmlspecialchars($req['reason']) ?>">
                              <?= htmlspecialchars($req['reason']) ?>
                            </span>
                          </td>
                          <td class="text-center">
                            <span class="badge badge-light border text-dark px-2 py-1 rounded-pill"><?= $d ?>
                              ngày</span>
                          </td>
                          <td class="text-center pr-4">
                            <?php if($req['status'] == 'Pending'): ?>
                            <span class="badge badge-warning text-dark px-3 py-1 mb-1 d-block rounded-pill"><i
                                class="bi bi-hourglass-split"></i> Chờ duyệt</span>

                            <form method="POST" class="d-inline"
                              onsubmit="return confirm('Bạn có chắc chắn muốn hủy đơn này không?');">
                              <input type="hidden" name="request_id" value="<?= $req['id'] ?>">
                              <button type="submit" name="cancel_leave"
                                class="btn btn-outline-danger btn-sm rounded-pill mt-1"
                                style="font-size: 0.75rem; padding: 2px 10px;">
                                <i class="bi bi-trash"></i> Hủy đơn
                              </button>
                            </form>

                            <?php elseif($req['status'] == 'Approved'): ?>
                            <span class="badge badge-success px-3 py-2 rounded-pill"><i class="bi bi-check-circle"></i>
                              Đã duyệt</span>
                            <?php else: ?>
                            <span class="badge badge-danger px-3 py-2 rounded-pill"><i class="bi bi-x-circle"></i> Từ
                              chối</span>
                            <?php endif; ?>
                          </td>
                        </tr>
                        <?php endforeach; endif; ?>
                      </tbody>
                    </table>
                  </div>
                </div>
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
  function submitLeaveRequest() {
    // Lấy JWT Token từ LocalStorage
    const token = localStorage.getItem('jwt_token');

    if (!token) {
      alert("Lỗi: Bạn chưa đăng nhập (Chưa có Token)!");
      return;
    }

    // Lấy dữ liệu từ các thẻ input
    const start_date = document.getElementById('start_date').value;
    const end_date = document.getElementById('end_date').value;
    const reason = document.getElementById('reason').value;

    // Gọi Fetch API xuống LeaveController
    fetch('/creative-agency-hub/public/api/leaves', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': 'Bearer ' + token
        },
        body: JSON.stringify({
          start_date: start_date,
          end_date: end_date,
          reason: reason
        })
      })
      .then(response => response.json())
      .then(data => {
        if (data.status === 'success') {
          alert("✅ " + data.message);
          // Reload trang để cập nhật lại bảng danh sách và quỹ phép
          location.reload();
        } else {
          alert("❌ Lỗi: " + data.message);
        }
      })
      .catch(error => {
        console.error('Lỗi:', error);
        alert("❌ Lỗi: Không thể kết nối đến máy chủ API!");
      });
  }
  </script>
</body>

</html>
