<?php
session_start();
require_once '../config/db_connect.php';

// Thiết lập múi giờ
date_default_timezone_set('Asia/Ho_Chi_Minh');

$_SESSION['user_id'] = 1; 
$user_id = $_SESSION['user_id'];
$month = date('m');
$year = date('Y');

// 1. LẤY THÔNG TIN NHÂN VIÊN (Lương cơ bản)
$stmtUser = $pdo->prepare("SELECT full_name, role, base_salary FROM users WHERE id = ?");
$stmtUser->execute([$user_id]);
$user = $stmtUser->fetch();
$base_salary = $user['base_salary'] ?? 0;

// 2. LẤY DỮ LIỆU CHẤM CÔNG TRONG THÁNG
$stmtAtt = $pdo->prepare("SELECT COUNT(id) as total_days, SUM(is_late) as total_late, SUM(is_early_leave) as total_early FROM attendance WHERE user_id = ? AND MONTH(check_date) = ? AND YEAR(check_date) = ?");
$stmtAtt->execute([$user_id, $month, $year]);
$report = $stmtAtt->fetch();

$actual_days = $report['total_days'] ?? 0;
$total_late = $report['total_late'] ?? 0;
$total_early = $report['total_early'] ?? 0;

// 3. LOGIC TÍNH TOÁN LƯƠNG & KPI
$standard_days = 24; // Ngày công chuẩn
$salary_per_day = $base_salary / $standard_days;
$actual_salary = $salary_per_day * $actual_days;

// Nhận giá trị KPI từ Form (nếu có người nhập), mặc định là 100%
$kpi_percent = isset($_POST['kpi_percent']) ? (int)$_POST['kpi_percent'] : 100;

// Tính thưởng KPI
$bonus = 0;
if ($kpi_percent > 100) {
    $bonus_rate = ($kpi_percent - 100) / 100;
    $bonus = $base_salary * $bonus_rate; 
}

// Tính phạt chuyên cần (50k/lần)
$penalty = ($total_late + $total_early) * 50000;

// Tổng lãnh
$net_salary = $actual_salary + $bonus - $penalty;
// Không để lương âm
if ($net_salary < 0) $net_salary = 0; 
?>

<!DOCTYPE html>
<html lang="vi">

<head>
  <meta charset="UTF-8">
  <title>Bảng Lương - Creative Agency Hub</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
  <style>
  body {
    background-color: #f4f7f6;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  }

  .card-custom {
    border: none;
    border-radius: 1rem;
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.08);
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

<body class="py-5">

  <div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h2 class="fw-bold text-gradient mb-0">Creative Agency Hub</h2>
        <p class="text-muted">Báo cáo Tổng hợp & Lương Tháng <?= $month ?>/<?= $year ?></p>
      </div>
      <a href="attendance.php" class="btn btn-outline-secondary rounded-pill shadow-sm">
        <i class="bi bi-arrow-left me-1"></i> Quay lại Chấm công
      </a>
    </div>

    <div class="row g-4">
      <div class="col-lg-4">
        <div class="card card-custom mb-4 overflow-hidden">
          <div class="bg-gradient-primary p-4 text-center">
            <img src="https://ui-avatars.com/api/?name=<?= urlencode($user['full_name']) ?>&background=random&size=100"
              class="rounded-circle border border-3 border-white shadow-sm mb-3" alt="Avatar">
            <h5 class="fw-bold mb-0"><?= htmlspecialchars($user['full_name']) ?></h5>
            <span class="badge bg-light text-primary mt-2"><?= htmlspecialchars($user['role']) ?></span>
          </div>
          <div class="card-body p-4">
            <div class="d-flex justify-content-between border-bottom pb-2 mb-2">
              <span class="text-muted">Lương cơ bản:</span>
              <strong class="text-success"><?= number_format($base_salary) ?> ₫</strong>
            </div>
            <div class="d-flex justify-content-between border-bottom pb-2 mb-2">
              <span class="text-muted">Ngày công chuẩn:</span>
              <strong><?= $standard_days ?> ngày</strong>
            </div>
          </div>
        </div>

        <div class="card card-custom border-primary border-2">
          <div class="card-body p-4">
            <h6 class="fw-bold text-primary mb-3"><i class="bi bi-sliders me-2"></i>Chấm điểm KPI tháng này</h6>
            <form method="POST">
              <div class="input-group mb-3">
                <input type="number" name="kpi_percent" class="form-control" value="<?= $kpi_percent ?>" min="0"
                  max="200" step="1">
                <span class="input-group-text bg-light">%</span>
              </div>
              <button type="submit" class="btn btn-primary w-100 rounded-pill">
                <i class="bi bi-calculator me-1"></i> Cập nhật bảng lương
              </button>
            </form>
            <small class="text-muted d-block mt-2 text-center">*(>100% sẽ được tính thưởng)*</small>
          </div>
        </div>
      </div>

      <div class="col-lg-8">
        <div class="card card-custom h-100">
          <div class="card-header bg-white p-4 border-bottom">
            <h4 class="fw-bold mb-0"><i class="bi bi-receipt me-2 text-primary"></i>Chi tiết phiếu lương</h4>
          </div>
          <div class="card-body p-4">
            <div class="table-responsive">
              <table class="table table-borderless table-hover align-middle">
                <thead class="table-light">
                  <tr>
                    <th>Hạng mục tính toán</th>
                    <th class="text-center">Thông số</th>
                    <th class="text-end">Thành tiền (VNĐ)</th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td>
                      <i class="bi bi-calendar-check text-primary me-2"></i><strong>Lương ngày công thực tế</strong>
                    </td>
                    <td class="text-center">
                      <span class="badge bg-primary rounded-pill"><?= $actual_days ?> / <?= $standard_days ?>
                        ngày</span>
                    </td>
                    <td class="text-end fw-bold">
                      <?= number_format($actual_salary) ?> ₫
                    </td>
                  </tr>

                  <tr>
                    <td>
                      <i class="bi bi-graph-up-arrow text-success me-2"></i><strong>Thưởng hiệu suất (KPI)</strong>
                    </td>
                    <td class="text-center">
                      <span class="badge <?= $kpi_percent > 100 ? 'bg-success' : 'bg-secondary' ?> rounded-pill">
                        Đạt <?= $kpi_percent ?>%
                      </span>
                    </td>
                    <td class="text-end fw-bold text-success">
                      + <?= number_format($bonus) ?> ₫
                    </td>
                  </tr>

                  <tr class="border-bottom border-danger border-opacity-25">
                    <td class="pb-3">
                      <i class="bi bi-exclamation-triangle text-danger me-2"></i><strong>Phạt vi phạm chuyên
                        cần</strong>
                      <div class="text-muted small ms-4">Trễ: <?= $total_late ?> | Sớm: <?= $total_early ?> (50k/lần)
                      </div>
                    </td>
                    <td class="text-center pb-3">
                      <span class="badge bg-danger rounded-pill"><?= $total_late + $total_early ?> vi phạm</span>
                    </td>
                    <td class="text-end fw-bold text-danger pb-3">
                      - <?= number_format($penalty) ?> ₫
                    </td>
                  </tr>

                  <tr>
                    <td colspan="2" class="pt-4 text-end">
                      <h4 class="fw-bold mb-0">TỔNG THỰC LÃNH:</h4>
                    </td>
                    <td class="pt-4 text-end">
                      <h3 class="fw-bold text-primary mb-0"><?= number_format($net_salary) ?> ₫</h3>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <div class="alert alert-info mt-4 rounded-4" role="alert">
              <i class="bi bi-info-circle-fill me-2"></i>
              Phiếu lương được tính toán tự động dựa trên dữ liệu từ hệ thống chấm công. Mọi thắc mắc vui lòng liên hệ
              phòng Hành chính - Nhân sự.
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>