<?php
session_start();
require_once '../config/db_connect.php';

// Thiết lập múi giờ
date_default_timezone_set('Asia/Ho_Chi_Minh');

$_SESSION['user_id'] = 1; 
$user_id = $_SESSION['user_id'];
$today = date('Y-m-d');
$now = date('H:i:s');
$month = date('m');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['reset_test'])) {
        $pdo->prepare("DELETE FROM attendance WHERE user_id = ? AND check_date = ?")->execute([$user_id, $today]);
        $_SESSION['flash_msg'] = "Đã xóa toàn bộ dữ liệu chấm công hôm nay. Bạn có thể test lại từ đầu!";
        $_SESSION['flash_type'] = "success";
        header("Location: attendance.php");
        exit();
    }

    if (isset($_POST['check_in'])) {
        $start_time_str = '08:30:00';
        $is_late = ($now > $start_time_str) ? 1 : 0;
        $msg = "Check-in thành công lúc $now.";
        $alert_type = "success";

        if ($is_late) {
            $late_seconds = strtotime($now) - strtotime($start_time_str);
            $late_hours = floor($late_seconds / 3600);
            $late_minutes = floor(($late_seconds % 3600) / 60);
            $msg .= " Bạn đã vào ca trễ ";
            if ($late_hours > 0) $msg .= "<strong>$late_hours giờ</strong> ";
            $msg .= "<strong>$late_minutes phút</strong> so với quy định.";
            $alert_type = "warning";
        }

        $pdo->prepare("INSERT INTO attendance (user_id, check_date, check_in, is_late) VALUES (?, ?, ?, ?)")->execute([$user_id, $today, $now, $is_late]);
        $_SESSION['flash_msg'] = $msg;
        $_SESSION['flash_type'] = $alert_type;
        header("Location: attendance.php");
        exit();
    }

    if (isset($_POST['check_out'])) {
        $is_early = ($now < '17:30:00') ? 1 : 0;
        $stmt = $pdo->prepare("SELECT check_in FROM attendance WHERE user_id = ? AND check_date = ?");
        $stmt->execute([$user_id, $today]);
        $record = $stmt->fetch();
        
        $work_hours = 0;
        if ($record && $record['check_in']) {
            $in_time = strtotime($record['check_in']);
            $out_time = strtotime($now);
            $work_hours = max(0, round(($out_time - $in_time - 5400) / 3600, 2));
        }

        $pdo->prepare("UPDATE attendance SET check_out = ?, is_early_leave = ?, work_hours = ? WHERE user_id = ? AND check_date = ?")->execute([$now, $is_early, $work_hours, $user_id, $today]);
        $_SESSION['flash_msg'] = "Check-out thành công! Bạn đã làm việc tổng cộng <strong>$work_hours giờ</strong> trong hôm nay.";
        $_SESSION['flash_type'] = "info";
        header("Location: attendance.php");
        exit();
    }
}

// 1. Lấy trạng thái nút bấm hôm nay
$stmt = $pdo->prepare("SELECT check_in, check_out FROM attendance WHERE user_id = ? AND check_date = ?");
$stmt->execute([$user_id, $today]);
$attendance_today = $stmt->fetch();
$has_checked_in = !empty($attendance_today['check_in']);
$has_checked_out = !empty($attendance_today['check_out']);

// 2. Lấy dữ liệu báo cáo tổng quát
$stmt = $pdo->prepare("SELECT COUNT(id) as total_days, SUM(is_late) as total_late, SUM(is_early_leave) as total_early, SUM(work_hours) as total_hours FROM attendance WHERE user_id = ? AND MONTH(check_date) = ?");
$stmt->execute([$user_id, $month]);
$report = $stmt->fetch();

// 3. LẤY DỮ LIỆU CHI TIẾT CHO CÁC MODAL THỐNG KÊ (MỚI)
$stmtDetails = $pdo->prepare("SELECT * FROM attendance WHERE user_id = ? AND MONTH(check_date) = ? ORDER BY check_date DESC");
$stmtDetails->execute([$user_id, $month]);
$details = $stmtDetails->fetchAll();
?>

<!DOCTYPE html>
<html lang="vi">

<head>
  <meta charset="UTF-8">
  <title>Chấm Công - Creative Agency Hub</title>
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

  .btn-checkin {
    border-radius: 50px;
    padding: 12px 30px;
    font-weight: 600;
    letter-spacing: 0.5px;
  }

  .stat-icon {
    font-size: 2.5rem;
    opacity: 0.8;
  }

  .text-gradient {
    background: -webkit-linear-gradient(45deg, #0d6efd, #6610f2);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
  }

  /* Hiệu ứng hover cho thẻ thống kê */
  .stat-card {
    transition: all 0.2s ease;
    cursor: pointer;
  }

  .stat-card:hover {
    transform: translateY(-3px) scale(1.02);
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
  }
  </style>
</head>

<body class="py-5">
  <div class="container">
    <div class="row mb-4">
      <div class="col-12 text-center">
        <h2 class="fw-bold text-gradient">Creative Agency Hub</h2>
        <p class="text-muted mb-3">Cổng thông tin nội bộ - Quản lý nhân sự</p>

        <div class="d-flex justify-content-center gap-3">
          <a href="attendance.php" class="btn btn-primary rounded-pill shadow-sm">
            <i class="bi bi-clock-history me-1"></i> Chấm công
          </a>
          <a href="leave_request.php" class="btn btn-outline-primary rounded-pill shadow-sm bg-white">
            <i class="bi bi-envelope-paper me-1"></i> Xin nghỉ phép
          </a>
          <a href="payroll_summary.php" class="btn btn-outline-primary rounded-pill shadow-sm bg-white">
            <i class="bi bi-receipt me-1"></i> Bảng lương
          </a>
        </div>
      </div>
    </div>

    <div class="row g-4">
      <div class="col-lg-5">
        <div class="card card-custom h-100">
          <div class="card-body p-4 text-center d-flex flex-column justify-content-center">
            <i class="bi bi-clock-history display-1 text-primary mb-3"></i>
            <h4 class="fw-bold mb-1">Ghi nhận thời gian</h4>
            <p class="text-muted mb-3">Hôm nay: <?= date('d/m/Y') ?></p>

            <?php if (isset($_SESSION['flash_msg'])): ?>
            <div class="alert alert-<?= $_SESSION['flash_type'] ?> alert-dismissible fade show text-start rounded-4"
              role="alert">
              <i class="bi bi-info-circle-fill me-2"></i> <?= $_SESSION['flash_msg'] ?>
              <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['flash_msg']); unset($_SESSION['flash_type']); ?>
            <?php endif; ?>

            <form method="POST" class="d-grid gap-3 mt-2">
              <button type="submit" name="check_in" class="btn btn-primary btn-checkin"
                <?= $has_checked_in ? 'disabled' : '' ?>>
                <i class="bi bi-box-arrow-in-right me-2"></i> BẮT ĐẦU CA LÀM
              </button>

              <button type="submit" name="check_out" class="btn btn-outline-danger btn-checkin"
                <?= (!$has_checked_in || $has_checked_out) ? 'disabled' : '' ?>>
                <i class="bi bi-box-arrow-right me-2"></i> KẾT THÚC CA LÀM
              </button>

              <hr class="my-3 text-muted">
              <button type="submit" name="reset_test" class="btn btn-dark btn-sm rounded-pill py-2"
                onclick="return confirm('Xóa dữ liệu chấm công hôm nay?');">
                <i class="bi bi-arrow-clockwise me-1"></i> RESET DỮ LIỆU BẢN NHÁP
              </button>
            </form>

            <?php if ($has_checked_in): ?>
            <div class="alert alert-success mt-4 rounded-pill" role="alert">
              <i class="bi bi-check-circle-fill me-2"></i> Giờ vào ca:
              <strong><?= $attendance_today['check_in'] ?></strong>
            </div>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <div class="col-lg-7">
        <div class="card card-custom h-100">
          <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-2">
              <h4 class="fw-bold mb-0">
                <i class="bi bi-bar-chart-line text-success me-2"></i> Thống kê Tháng <?= $month ?>
              </h4>
            </div>

            <div class="row g-3">
              <div class="col-sm-6">
                <div class="p-3 bg-light rounded-4 border d-flex align-items-center stat-card" data-bs-toggle="modal"
                  data-bs-target="#modalTotalDays">
                  <i class="bi bi-calendar-check text-primary stat-icon me-3"></i>
                  <div>
                    <h6 class="mb-0 text-muted">Tổng ngày làm</h6>
                    <h3 class="mb-0 fw-bold"><?= $report['total_days'] ?? 0 ?> <span
                        class="fs-6 fw-normal text-muted">ngày</span></h3>
                  </div>
                </div>
              </div>

              <div class="col-sm-6">
                <div class="p-3 bg-light rounded-4 border d-flex align-items-center stat-card" data-bs-toggle="modal"
                  data-bs-target="#modalTotalDays">
                  <i class="bi bi-stopwatch text-info stat-icon me-3"></i>
                  <div>
                    <h6 class="mb-0 text-muted">Tổng giờ làm</h6>
                    <h3 class="mb-0 fw-bold"><?= $report['total_hours'] ?? 0 ?> <span
                        class="fs-6 fw-normal text-muted">giờ</span></h3>
                  </div>
                </div>
              </div>

              <div class="col-sm-6">
                <div class="p-3 bg-light rounded-4 border border-warning d-flex align-items-center stat-card"
                  data-bs-toggle="modal" data-bs-target="#modalLate">
                  <i class="bi bi-exclamation-triangle text-warning stat-icon me-3"></i>
                  <div>
                    <h6 class="mb-0 text-muted">Số lần đi muộn</h6>
                    <h3 class="mb-0 fw-bold text-warning"><?= $report['total_late'] ?? 0 ?> <span
                        class="fs-6 fw-normal text-muted">lần</span></h3>
                  </div>
                </div>
              </div>

              <div class="col-sm-6">
                <div class="p-3 bg-light rounded-4 border border-danger d-flex align-items-center stat-card"
                  data-bs-toggle="modal" data-bs-target="#modalEarly">
                  <i class="bi bi-door-open text-danger stat-icon me-3"></i>
                  <div>
                    <h6 class="mb-0 text-muted">Số lần về sớm</h6>
                    <h3 class="mb-0 fw-bold text-danger"><?= $report['total_early'] ?? 0 ?> <span
                        class="fs-6 fw-normal text-muted">lần</span></h3>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="modal fade" id="modalTotalDays" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
      <div class="modal-content rounded-4">
        <div class="modal-header border-0 bg-light">
          <h5 class="modal-title fw-bold text-primary"><i class="bi bi-calendar-check me-2"></i>Chi tiết ngày công Tháng
            <?= $month ?></h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body p-0">
          <table class="table table-hover mb-0 text-center align-middle">
            <thead class="table-light">
              <tr>
                <th>Ngày</th>
                <th>Giờ vào</th>
                <th>Giờ ra</th>
                <th>Số giờ</th>
                <th>Trạng thái</th>
              </tr>
            </thead>
            <tbody>
              <?php if(empty($details)): ?>
              <tr>
                <td colspan="5" class="text-muted py-4">Chưa có dữ liệu chấm công trong tháng này.</td>
              </tr>
              <?php else: ?>
              <?php foreach($details as $row): ?>
              <tr>
                <td><strong><?= date('d/m/Y', strtotime($row['check_date'])) ?></strong></td>
                <td><?= $row['check_in'] ?? '<span class="text-muted">-</span>' ?></td>
                <td><?= $row['check_out'] ?? '<span class="text-muted">Chưa out</span>' ?></td>
                <td><span class="badge bg-info text-dark"><?= $row['work_hours'] ?> h</span></td>
                <td>
                  <?php if($row['is_late']): ?><span class="badge bg-warning text-dark me-1">Đi
                    muộn</span><?php endif; ?>
                  <?php if($row['is_early_leave']): ?><span class="badge bg-danger">Về sớm</span><?php endif; ?>
                  <?php if(!$row['is_late'] && !$row['is_early_leave'] && $row['check_out']): ?><span
                    class="badge bg-success">Đúng giờ</span><?php endif; ?>
                </td>
              </tr>
              <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <div class="modal fade" id="modalLate" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
      <div class="modal-content rounded-4 border-warning">
        <div class="modal-header border-0 bg-warning bg-opacity-10">
          <h5 class="modal-title fw-bold text-warning"><i class="bi bi-exclamation-triangle me-2"></i>Lịch sử đi muộn
            Tháng <?= $month ?></h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body p-0">
          <ul class="list-group list-group-flush">
            <?php 
            $hasLate = false;
            foreach($details as $row): 
              if($row['is_late']): 
                $hasLate = true;
                $late_mins = round((strtotime($row['check_in']) - strtotime('08:30:00')) / 60);
            ?>
            <li class="list-group-item d-flex justify-content-between align-items-center py-3">
              <div>
                <h6 class="mb-0 fw-bold"><?= date('d/m/Y', strtotime($row['check_date'])) ?></h6>
                <small class="text-muted">Check-in lúc: <?= $row['check_in'] ?></small>
              </div>
              <span class="badge bg-warning text-dark rounded-pill">Trễ <?= $late_mins ?> phút</span>
            </li>
            <?php endif; endforeach; ?>
            <?php if(!$hasLate): ?>
            <li class="list-group-item text-center text-muted py-4">Tuyệt vời! Bạn không đi muộn ngày nào.</li>
            <?php endif; ?>
          </ul>
        </div>
      </div>
    </div>
  </div>

  <div class="modal fade" id="modalEarly" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
      <div class="modal-content rounded-4 border-danger">
        <div class="modal-header border-0 bg-danger bg-opacity-10">
          <h5 class="modal-title fw-bold text-danger"><i class="bi bi-door-open me-2"></i>Lịch sử về sớm Tháng
            <?= $month ?></h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body p-0">
          <ul class="list-group list-group-flush">
            <?php 
            $hasEarly = false;
            foreach($details as $row): 
              if($row['is_early_leave'] && $row['check_out']): 
                $hasEarly = true;
                $early_mins = round((strtotime('17:30:00') - strtotime($row['check_out'])) / 60);
            ?>
            <li class="list-group-item d-flex justify-content-between align-items-center py-3">
              <div>
                <h6 class="mb-0 fw-bold"><?= date('d/m/Y', strtotime($row['check_date'])) ?></h6>
                <small class="text-muted">Check-out lúc: <?= $row['check_out'] ?></small>
              </div>
              <span class="badge bg-danger rounded-pill">Sớm <?= $early_mins ?> phút</span>
            </li>
            <?php endif; endforeach; ?>
            <?php if(!$hasEarly): ?>
            <li class="list-group-item text-center text-muted py-4">Nhiệt huyết! Bạn không về sớm ngày nào.</li>
            <?php endif; ?>
          </ul>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>