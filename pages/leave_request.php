<?php
session_start();
require_once '../config/db_connect.php';

date_default_timezone_set('Asia/Ho_Chi_Minh');

$_SESSION['user_id'] = 1; // LUÔN ĐÓNG VAI TRÒ LÀ NHÂN VIÊN 1
$user_id = $_SESSION['user_id'];

// 1. LẤY THÔNG TIN QUỸ PHÉP
$stmtUser = $pdo->prepare("SELECT full_name, leave_balance FROM users WHERE id = ?");
$stmtUser->execute([$user_id]);
$user = $stmtUser->fetch();
$leave_balance = $user['leave_balance'] ?? 0;

// 2. XỬ LÝ NHÂN VIÊN GỬI ĐƠN
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_leave'])) {
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $reason = $_POST['reason'];

    $days_requested = (strtotime($end_date) - strtotime($start_date)) / 86400 + 1;

    if ($days_requested <= 0) {
        $_SESSION['flash_msg'] = "Ngày kết thúc phải sau ngày bắt đầu!";
        $_SESSION['flash_type'] = "danger";
    } elseif ($days_requested > $leave_balance) {
        $_SESSION['flash_msg'] = "Bạn chỉ còn $leave_balance ngày phép. Không thể xin nghỉ $days_requested ngày!";
        $_SESSION['flash_type'] = "warning";
    } else {
        $stmt = $pdo->prepare("INSERT INTO leave_requests (user_id, start_date, end_date, reason) VALUES (?, ?, ?, ?)");
        $stmt->execute([$user_id, $start_date, $end_date, $reason]);
        
        $_SESSION['flash_msg'] = "Gửi đơn thành công. Vui lòng chờ Quản lý duyệt!";
        $_SESSION['flash_type'] = "success";
    }
    header("Location: leave_request.php");
    exit();
}

// 3. LẤY LỊCH SỬ ĐƠN CỦA CHÍNH NHÂN VIÊN NÀY
$stmtRequests = $pdo->prepare("SELECT * FROM leave_requests WHERE user_id = ? ORDER BY created_at DESC");
$stmtRequests->execute([$user_id]);
$requests = $stmtRequests->fetchAll();
?>

<!DOCTYPE html>
<html lang="vi">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>Xin Nghỉ Phép - Creative Agency Hub</title>

  <link href="../assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
  <link href="../assets/css/sb-admin-2.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

  <style>
  .fw-bold {
    font-weight: 700 !important;
  }

  .rounded-3 {
    border-radius: 0.75rem !important;
  }

  .card-custom {
    border: none;
    border-radius: 0.75rem;
    box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
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
    <?php include('../components/sidebar.php'); ?>

    <div id="content-wrapper" class="d-flex flex-column bg-light">
      <div id="content">
        <?php include('../components/navbar.php'); ?>

        <div class="container-fluid py-4">

          <div class="row mb-3">
            <div class="col-12 text-center">
              <h3 class="fw-bold text-gradient mb-1">Creative Agency Hub</h3>
              <p class="text-muted small mb-3">Cổng thông tin nội bộ - Portal Nhân viên</p>

              <div class="d-flex justify-content-center flex-wrap" style="gap: 8px;">
                <a href="http://localhost/LTW/creative-agency-hub"
                  class="btn btn-sm btn-dark rounded-pill shadow-sm px-3"><i class="bi bi-house-door me-1"></i> Trang
                  chủ</a>
                <a href="attendance.php" class="btn btn-sm btn-outline-primary rounded-pill shadow-sm bg-white px-3"><i
                    class="bi bi-clock-history me-1"></i> Chấm công</a>
                <a href="leave_request.php" class="btn btn-sm btn-primary rounded-pill shadow-sm px-3"><i
                    class="bi bi-envelope-paper me-1"></i> Xin nghỉ phép</a>
                <a href="payroll_summary.php"
                  class="btn btn-sm btn-outline-primary rounded-pill shadow-sm bg-white px-3"><i
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

                  <form method="POST">
                    <div class="form-group mb-3">
                      <label class="font-weight-bold text-muted small">Từ ngày:</label>
                      <input type="date" name="start_date" class="form-control" required min="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="form-group mb-3">
                      <label class="font-weight-bold text-muted small">Đến ngày:</label>
                      <input type="date" name="end_date" class="form-control" required min="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="form-group mb-4">
                      <label class="font-weight-bold text-muted small">Lý do nghỉ phép:</label>
                      <textarea name="reason" class="form-control" rows="3" required
                        placeholder="Nhập chi tiết lý do..."></textarea>
                    </div>
                    <button type="submit" name="submit_leave"
                      class="btn btn-primary w-100 rounded-pill py-2 font-weight-bold"
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
                    <table class="table table-hover align-middle mb-0" style="font-size: 0.9rem;">
                      <thead class="bg-light text-muted">
                        <tr>
                          <th class="pl-4 py-3">Thời gian nghỉ</th>
                          <th>Lý do</th>
                          <th class="text-center">Số ngày</th>
                          <th class="text-center pr-4">Trạng thái</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php if(empty($requests)): ?>
                        <tr>
                          <td colspan="4" class="text-center text-muted py-5">Chưa có dữ liệu nghỉ phép.</td>
                        </tr>
                        <?php else: foreach($requests as $req): $d = (strtotime($req['end_date']) - strtotime($req['start_date'])) / 86400 + 1; ?>
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
                            <span class="badge badge-secondary px-2 py-1"><?= $d ?> ngày</span>
                          </td>
                          <td class="text-center pr-4">
                            <?php if($req['status'] == 'Pending'): ?>
                            <span class="badge badge-warning text-dark px-2 py-1"><i class="bi bi-hourglass-split"></i>
                              Chờ duyệt</span>
                            <?php elseif($req['status'] == 'Approved'): ?>
                            <span class="badge badge-success px-2 py-1"><i class="bi bi-check-circle"></i> Đã
                              duyệt</span>
                            <?php else: ?>
                            <span class="badge badge-danger px-2 py-1"><i class="bi bi-x-circle"></i> Từ chối</span>
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

      <?php include('../components/footer.php'); ?>

    </div>
  </div>

  <script src="../assets/vendor/jquery/jquery.min.js"></script>
  <script src="../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="../assets/js/sb-admin-2.min.js"></script>

</body>

</html>