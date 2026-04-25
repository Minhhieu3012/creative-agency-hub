<?php
session_start();
require_once '../config/db_connect.php';

date_default_timezone_set('Asia/Ho_Chi_Minh');

// ĐÓNG VAI TRÒ LÀ MANAGER (user_id = 2 là Trần Thị B theo DB mẫu)
$_SESSION['user_id'] = 2; 
$manager_id = $_SESSION['user_id'];
$month = date('m');
$year = date('Y');
$standard_days = 24; // Ngày công chuẩn

// Lấy thông tin người quản lý
$stmtUser = $pdo->prepare("SELECT full_name, role FROM users WHERE id = ?");
$stmtUser->execute([$manager_id]);
$manager = $stmtUser->fetch();

// ==========================================
// 1. XỬ LÝ LOGIC DUYỆT / TỪ CHỐI ĐƠN
// ==========================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action_leave'])) {
    $request_id = $_POST['request_id'];
    $emp_id = $_POST['emp_id']; 
    $days_to_deduct = $_POST['days_requested'];
    $action = $_POST['action_leave']; 

    if ($action == 'Approved') {
        try {
            $pdo->beginTransaction();
            $pdo->prepare("UPDATE leave_requests SET status = 'Approved' WHERE id = ?")->execute([$request_id]);
            $pdo->prepare("UPDATE users SET leave_balance = leave_balance - ? WHERE id = ?")->execute([$days_to_deduct, $emp_id]);
            $pdo->commit();
            
            $_SESSION['flash_msg'] = "Đã DUYỆT đơn và trừ quỹ phép của nhân viên thành công!";
            $_SESSION['flash_type'] = "success";
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['flash_msg'] = "Lỗi hệ thống: " . $e->getMessage();
            $_SESSION['flash_type'] = "danger";
        }
    } elseif ($action == 'Rejected') {
        $pdo->prepare("UPDATE leave_requests SET status = 'Rejected' WHERE id = ?")->execute([$request_id]);
        $_SESSION['flash_msg'] = "Đã TỪ CHỐI đơn nghỉ phép!";
        $_SESSION['flash_type'] = "warning";
    }
    header("Location: manager_approvals.php");
    exit();
}

// ==========================================
// 2. LẤY DANH SÁCH ĐƠN NGHỈ PHÉP
// ==========================================
$sqlLeave = "
    SELECT lr.*, u.full_name, u.leave_balance 
    FROM leave_requests lr
    JOIN users u ON lr.user_id = u.id
    ORDER BY CASE WHEN lr.status = 'Pending' THEN 1 ELSE 2 END, lr.created_at DESC
";
$requests = $pdo->query($sqlLeave)->fetchAll();

// ==========================================
// 3. TỔNG HỢP BẢNG LƯƠNG TOÀN NHÂN VIÊN
// ==========================================
$stmtEmp = $pdo->query("SELECT id, full_name, role, base_salary FROM users ORDER BY role, full_name");
$employees = $stmtEmp->fetchAll();

$payroll_data = [];
foreach ($employees as $emp) {
    $emp_id = $emp['id'];
    
    // Lấy dữ liệu chấm công tháng này
    $stmtAtt = $pdo->prepare("SELECT COUNT(id) as total_days, SUM(is_late) as total_late, SUM(is_early_leave) as total_early FROM attendance WHERE user_id = ? AND MONTH(check_date) = ? AND YEAR(check_date) = ?");
    $stmtAtt->execute([$emp_id, $month, $year]);
    $att = $stmtAtt->fetch();

    $actual_days = $att['total_days'] ?? 0;
    $late = $att['total_late'] ?? 0;
    $early = $att['total_early'] ?? 0;

    // Tính lương cơ bản tạm tính (chưa cộng KPI)
    $salary_per_day = $emp['base_salary'] / $standard_days;
    $actual_salary = $salary_per_day * $actual_days;
    $penalty = ($late + $early) * 50000;
    $net_salary = max(0, $actual_salary - $penalty); 

    $emp['actual_days'] = $actual_days;
    $emp['late'] = $late;
    $emp['early'] = $early;
    $emp['net_salary'] = $net_salary;
    
    $payroll_data[] = $emp;
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>Không gian Quản lý - Creative Agency Hub</title>

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
    border-radius: 1rem;
    box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
  }

  .text-gradient {
    background: -webkit-linear-gradient(45deg, #1cc88a, #13855c);
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

          <div class="row mb-4">
            <div class="col-12 text-center">
              <h3 class="fw-bold text-gradient mb-1"><i class="bi bi-shield-lock me-2"></i>Không gian Quản lý</h3>
              <p class="text-muted small mb-3">Xin chào Manager:
                <strong><?= htmlspecialchars($manager['full_name']) ?></strong></p>

              <div class="d-flex justify-content-center flex-wrap" style="gap: 8px;">
                <a href="../index.php" class="btn btn-sm btn-dark rounded-pill shadow-sm px-3"><i
                    class="bi bi-house-door me-1"></i> Trang chủ</a>
                <a href="manager_approvals.php" class="btn btn-sm btn-success rounded-pill shadow-sm px-3"><i
                    class="bi bi-check2-square me-1"></i> Quản trị Nhân sự</a>
              </div>
            </div>
          </div>

          <?php if (isset($_SESSION['flash_msg'])): ?>
          <div class="alert alert-<?= $_SESSION['flash_type'] ?> alert-dismissible fade show rounded-3 shadow-sm"
            role="alert">
            <i class="bi bi-info-circle-fill me-2"></i> <?= $_SESSION['flash_msg'] ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span
                aria-hidden="true">&times;</span></button>
          </div>
          <?php endif; ?>

          <div class="card card-custom shadow-sm mb-4">
            <div
              class="card-header py-3 bg-white d-flex flex-row align-items-center justify-content-between border-bottom">
              <h6 class="m-0 fw-bold text-success"><i class="bi bi-inboxes me-2"></i>Đơn xin nghỉ phép cần xử lý</h6>
            </div>
            <div class="card-body p-0">
              <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                  <thead class="bg-light text-muted text-center" style="font-size: 0.9rem;">
                    <tr>
                      <th class="py-3">Nhân viên</th>
                      <th>Thời gian nghỉ</th>
                      <th class="text-left">Lý do</th>
                      <th>Số ngày</th>
                      <th>Trạng thái</th>
                      <th>Hành động</th>
                    </tr>
                  </thead>
                  <tbody class="text-center" style="font-size: 0.9rem;">
                    <?php if(empty($requests)): ?>
                    <tr>
                      <td colspan="6" class="text-muted py-5">Chưa có đơn từ nào cần xử lý.</td>
                    </tr>
                    <?php else: foreach($requests as $req): 
                                            $d = (strtotime($req['end_date']) - strtotime($req['start_date'])) / 86400 + 1;
                                        ?>
                    <tr>
                      <td class="py-3">
                        <div class="d-flex align-items-center justify-content-center">
                          <img class="rounded-circle me-2"
                            src="https://ui-avatars.com/api/?name=<?= urlencode($req['full_name']) ?>&size=35"
                            style="width:35px">
                          <div class="text-left ml-2">
                            <div class="fw-bold text-dark"><?= htmlspecialchars($req['full_name']) ?></div>
                            <div class="small text-muted">Quỹ phép: <?= $req['leave_balance'] ?> ngày</div>
                          </div>
                        </div>
                      </td>
                      <td>
                        <span class="text-primary fw-bold"><?= date('d/m', strtotime($req['start_date'])) ?></span> <i
                          class="bi bi-arrow-right text-muted mx-1"></i> <span
                          class="text-primary fw-bold"><?= date('d/m/Y', strtotime($req['end_date'])) ?></span>
                      </td>
                      <td class="text-left" style="max-width: 250px;">
                        <span class="text-truncate d-inline-block w-100"
                          title="<?= htmlspecialchars($req['reason']) ?>">
                          <?= htmlspecialchars($req['reason']) ?>
                        </span>
                      </td>
                      <td><span class="badge badge-secondary p-2"><?= $d ?> ngày</span></td>
                      <td>
                        <?php if($req['status'] == 'Pending'): ?>
                        <span class="badge badge-warning text-dark p-2"><i class="bi bi-hourglass-split"></i> Chờ
                          duyệt</span>
                        <?php elseif($req['status'] == 'Approved'): ?>
                        <span class="badge badge-success p-2"><i class="bi bi-check-circle"></i> Đã duyệt</span>
                        <?php else: ?>
                        <span class="badge badge-danger p-2"><i class="bi bi-x-circle"></i> Từ chối</span>
                        <?php endif; ?>
                      </td>
                      <td>
                        <?php if($req['status'] == 'Pending'): ?>
                        <form method="POST" class="d-inline-flex">
                          <input type="hidden" name="request_id" value="<?= $req['id'] ?>">
                          <input type="hidden" name="emp_id" value="<?= $req['user_id'] ?>">
                          <input type="hidden" name="days_requested" value="<?= $d ?>">

                          <button type="submit" name="action_leave" value="Approved"
                            class="btn btn-success btn-sm me-1 shadow-sm mr-1" title="Duyệt"
                            onclick="return confirm('Duyệt đơn và trừ <?= $d ?> ngày phép của <?= $req['full_name'] ?>?');">
                            <i class="bi bi-check-lg"></i>
                          </button>
                          <button type="submit" name="action_leave" value="Rejected"
                            class="btn btn-danger btn-sm shadow-sm" title="Từ chối"
                            onclick="return confirm('Bạn muốn từ chối đơn này?');">
                            <i class="bi bi-x-lg"></i>
                          </button>
                        </form>
                        <?php else: ?>
                        <span class="text-muted small fst-italic">Đã xử lý</span>
                        <?php endif; ?>
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
              <h6 class="m-0 fw-bold text-primary"><i class="bi bi-cash-stack me-2"></i>Thống kê Lương tạm tính - Tháng
                <?= $month ?>/<?= $year ?></h6>
              <span class="badge badge-light text-primary border px-3 py-2">Ngày công chuẩn: <?= $standard_days ?>
                ngày</span>
            </div>
            <div class="card-body p-0">
              <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                  <thead class="bg-light text-muted text-center" style="font-size: 0.9rem;">
                    <tr>
                      <th class="py-3 text-left pl-4">Nhân sự</th>
                      <th>Lương cơ bản</th>
                      <th>Ngày công thực tế</th>
                      <th>Lỗi vi phạm</th>
                      <th>Tiền phạt</th>
                      <th class="text-right pr-4">Lương tạm tính (VNĐ)</th>
                    </tr>
                  </thead>
                  <tbody class="text-center" style="font-size: 0.9rem;">
                    <?php foreach($payroll_data as $p): ?>
                    <tr>
                      <td class="py-3 text-left pl-4">
                        <div class="fw-bold text-dark"><?= htmlspecialchars($p['full_name']) ?></div>
                        <span
                          class="badge <?= $p['role'] == 'Manager' ? 'badge-primary' : 'badge-secondary' ?> p-1 mt-1"><?= htmlspecialchars($p['role']) ?></span>
                      </td>
                      <td class="text-success fw-bold"><?= number_format($p['base_salary']) ?> ₫</td>
                      <td>
                        <span class="badge badge-info p-2 px-3"><?= $p['actual_days'] ?> / <?= $standard_days ?></span>
                      </td>
                      <td>
                        <div class="small text-muted">
                          Trễ: <strong class="text-warning"><?= $p['late'] ?></strong> | Sớm: <strong
                            class="text-danger"><?= $p['early'] ?></strong>
                        </div>
                      </td>
                      <td class="text-danger">-<?= number_format(($p['late'] + $p['early']) * 50000) ?> ₫</td>
                      <td class="text-right pr-4 fw-bold text-primary" style="font-size: 1.1rem;">
                        <?= number_format($p['net_salary']) ?> ₫
                      </td>
                    </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
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