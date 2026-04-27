<?php
session_start();
require_once '../config/db_connect.php';

// Thiết lập múi giờ
date_default_timezone_set('Asia/Ho_Chi_Minh');

// GIẢ LẬP ĐĂNG NHẬP (Luôn đóng vai trò là nhân viên Nguyễn Văn A - ID 2)
if (!isset($_SESSION['employee_id'])) {
    $_SESSION['employee_id'] = 1; 
    $_SESSION['role'] = 'employee';
}

$emp_id = $_SESSION['employee_id'];
$month = date('m');
$year = date('Y');
$standard_days = 24; // Ngày công chuẩn

// 1. LẤY THÔNG TIN NHÂN VIÊN VÀ LƯƠNG CƠ BẢN (Từ bảng employees và employee_contracts)
$sqlUser = "SELECT e.full_name, e.role, c.salary as base_salary 
            FROM employees e 
            LEFT JOIN employee_contracts c ON e.id = c.employee_id AND c.status = 'active'
            WHERE e.id = ?";
$stmtUser = $pdo->prepare($sqlUser);
$stmtUser->execute([$emp_id]);
$user = $stmtUser->fetch();
$base_salary = $user['base_salary'] ?? 0;

// 2. LẤY DỮ LIỆU CHẤM CÔNG TRONG THÁNG (Từ bảng attendances)
$stmtAtt = $pdo->prepare("SELECT * FROM attendances WHERE employee_id = ? AND MONTH(work_date) = ? AND YEAR(work_date) = ?");
$stmtAtt->execute([$emp_id, $month, $year]);
$atts = $stmtAtt->fetchAll();

// Tính toán ngày đi làm, số lần trễ, sớm bằng PHP
$actual_days = count($atts);
$total_late = 0; 
$total_early = 0;

foreach ($atts as $a) {
    if ($a['status'] == 'Late') {
        $total_late++;
    }
    // Kiểm tra về sớm (Trễ hơn 17:30:00)
    if (!empty($a['check_out_time'])) {
        $out_time = date('H:i:s', strtotime($a['check_out_time']));
        if ($out_time < '17:30:00') {
            $total_early++;
        }
    }
}

// 3. LOGIC TÍNH TOÁN LƯƠNG & KPI (Giữ nguyên logic cũ)
$salary_per_day = $base_salary / $standard_days;
$actual_salary = $salary_per_day * $actual_days;

// Nhận giá trị KPI từ Form (nếu có người nhập), mặc định là 100%
$kpi_percent = isset($_POST['kpi_percent']) ? (int)$_POST['kpi_percent'] : 100;

// Tính thưởng KPI (Chỉ thưởng khi vượt 100%)
$bonus = 0;
if ($kpi_percent > 100) {
    $bonus_rate = ($kpi_percent - 100) / 100;
    $bonus = $base_salary * $bonus_rate; 
}

// Tính phạt chuyên cần (50k/lần)
$penalty = ($total_late + $total_early) * 50000;

// Tổng lãnh (Không để lương âm)
$net_salary = max(0, $actual_salary + $bonus - $penalty); 
?>

<!DOCTYPE html>
<html lang="vi">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>Bảng Lương - Creative Agency Hub</title>

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

  .rounded-4 {
    border-radius: 1rem !important;
  }

  .card-custom {
    border: none;
    border-radius: 0.75rem;
    box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
  }

  .bg-gradient-primary {
    background: linear-gradient(45deg, #0d6efd, #6610f2);
    color: white;
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

          <div class="row mb-4">
            <div class="col-12 text-center">
              <h3 class="fw-bold text-gradient mb-1">Creative Agency Hub</h3>
              <p class="text-muted small mb-3">Cổng thông tin nội bộ - Portal Nhân viên</p>

              <div class="d-flex justify-content-center flex-wrap" style="gap: 8px;">
                <a href="http://localhost/LTW/creative-agency-hub"
                  class="btn btn-sm btn-dark rounded-pill shadow-sm px-3"><i class="bi bi-house-door me-1"></i> Trang
                  chủ</a>
                <a href="attendance.php" class="btn btn-sm btn-outline-primary rounded-pill shadow-sm bg-white px-3"><i
                    class="bi bi-clock-history me-1"></i> Chấm công</a>
                <a href="leave_request.php"
                  class="btn btn-sm btn-outline-primary rounded-pill shadow-sm bg-white px-3"><i
                    class="bi bi-envelope-paper me-1"></i> Xin nghỉ phép</a>
                <a href="payroll_summary.php" class="btn btn-sm btn-primary rounded-pill shadow-sm px-3"><i
                    class="bi bi-receipt me-1"></i> Bảng lương</a>
              </div>
            </div>
          </div>

          <div class="d-flex justify-content-between align-items-center mb-4 bg-white p-3 rounded-3 shadow-sm border">
            <h5 class="fw-bold mb-0 text-gray-800"><i class="bi bi-cash-coin text-success me-2"></i> Phiếu lương cá nhân
              - Tháng <?= $month ?>/<?= $year ?></h5>
          </div>

          <div class="row align-items-stretch">

            <div class="col-lg-4 mb-4">
              <div class="card card-custom mb-4 overflow-hidden">
                <div class="bg-gradient-primary p-4 text-center">
                  <img
                    src="https://ui-avatars.com/api/?name=<?= urlencode($user['full_name']) ?>&background=random&size=100"
                    class="rounded-circle border border-3 border-white shadow-sm mb-3" alt="Avatar">
                  <h5 class="fw-bold mb-0"><?= htmlspecialchars($user['full_name']) ?></h5>
                  <span
                    class="badge badge-light text-primary mt-2 px-2 py-1"><?= htmlspecialchars(strtoupper($user['role'])) ?></span>
                </div>
                <div class="card-body p-4">
                  <div class="d-flex justify-content-between border-bottom pb-2 mb-2">
                    <span class="text-muted">Lương cơ bản (Hợp đồng):</span>
                    <strong class="text-success"><?= number_format($base_salary) ?> ₫</strong>
                  </div>
                  <div class="d-flex justify-content-between border-bottom pb-2 mb-2">
                    <span class="text-muted">Ngày công chuẩn:</span>
                    <strong><?= $standard_days ?> ngày</strong>
                  </div>
                </div>
              </div>

              <div class="card card-custom border-left-primary">
                <div class="card-body p-4">
                  <h6 class="fw-bold text-primary mb-3"><i class="bi bi-sliders me-2"></i>Giả lập điểm KPI tháng này
                  </h6>
                  <form method="POST">
                    <div class="input-group mb-3">
                      <input type="number" name="kpi_percent" class="form-control" value="<?= $kpi_percent ?>" min="0"
                        max="200" step="1">
                      <div class="input-group-append">
                        <span class="input-group-text bg-light text-dark">%</span>
                      </div>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 rounded-pill py-2 font-weight-bold">
                      <i class="bi bi-calculator me-1"></i> Tính thử lương
                    </button>
                  </form>
                  <small class="text-muted d-block mt-3 text-center">*(>100% sẽ được tính thưởng vượt mức)*</small>
                </div>
              </div>
            </div>

            <div class="col-lg-8 mb-4">
              <div class="card card-custom h-100">
                <div class="card-header bg-white p-4 border-bottom">
                  <h6 class="fw-bold mb-0 text-primary"><i class="bi bi-receipt me-2"></i>Chi tiết phiếu lương</h6>
                </div>
                <div class="card-body p-4">
                  <div class="table-responsive">
                    <table class="table table-borderless table-hover align-middle">
                      <thead class="bg-light text-muted">
                        <tr>
                          <th class="py-2 pl-3">Hạng mục tính toán</th>
                          <th class="text-center py-2">Thông số</th>
                          <th class="text-right py-2 pr-3">Thành tiền (VNĐ)</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr>
                          <td class="pl-3 py-3">
                            <i class="bi bi-calendar-check text-primary me-2"></i><strong class="text-dark">Lương ngày
                              công thực tế</strong>
                          </td>
                          <td class="text-center py-3">
                            <span class="badge badge-primary px-3 py-2 rounded-pill"><?= $actual_days ?> /
                              <?= $standard_days ?> ngày</span>
                          </td>
                          <td class="text-right fw-bold py-3 pr-3 text-dark">
                            <?= number_format($actual_salary) ?> ₫
                          </td>
                        </tr>

                        <tr>
                          <td class="pl-3 py-3">
                            <i class="bi bi-graph-up-arrow text-success me-2"></i><strong class="text-dark">Thưởng hiệu
                              suất (KPI)</strong>
                          </td>
                          <td class="text-center py-3">
                            <span
                              class="badge <?= $kpi_percent > 100 ? 'badge-success' : 'badge-secondary' ?> px-3 py-2 rounded-pill">
                              Đạt <?= $kpi_percent ?>%
                            </span>
                          </td>
                          <td class="text-right fw-bold text-success py-3 pr-3">
                            + <?= number_format($bonus) ?> ₫
                          </td>
                        </tr>

                        <tr class="border-bottom" style="border-bottom-color: rgba(231, 74, 59, 0.2) !important;">
                          <td class="pb-4 pl-3 pt-3">
                            <i class="bi bi-exclamation-triangle text-danger me-2"></i><strong class="text-dark">Phạt vi
                              phạm chuyên cần</strong>
                            <div class="text-muted small mt-1 ml-4">Đi trễ: <?= $total_late ?> lần | Về sớm:
                              <?= $total_early ?> lần</div>
                          </td>
                          <td class="text-center pb-4 pt-3">
                            <span class="badge badge-danger px-3 py-2 rounded-pill"><?= $total_late + $total_early ?> vi
                              phạm</span>
                          </td>
                          <td class="text-right fw-bold text-danger pb-4 pt-3 pr-3">
                            - <?= number_format($penalty) ?> ₫
                          </td>
                        </tr>

                        <tr>
                          <td colspan="2" class="pt-4 text-right">
                            <h5 class="fw-bold mb-0 text-dark">TỔNG THỰC LÃNH:</h5>
                          </td>
                          <td class="pt-4 text-right pr-3">
                            <h4 class="fw-bold text-primary mb-0"><?= number_format($net_salary) ?> ₫</h4>
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </div>

                  <div class="alert alert-info mt-4 rounded-3 small py-3" role="alert">
                    <i class="bi bi-info-circle-fill me-2"></i>
                    Phiếu lương được tính toán tự động dựa trên dữ liệu từ hệ thống chấm công. Mọi thắc mắc vui lòng
                    liên hệ phòng Hành chính - Nhân sự.
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