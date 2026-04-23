<?php
session_start();
require_once '../config/db_connect.php';

$_SESSION['user_id'] = 1; 
$user_id = $_SESSION['user_id'];
$today = date('Y-m-d');
$now = date('H:i:s');
$month = date('m');

// --- CẬP NHẬT LOGIC XỬ LÝ CHECK-IN / CHECK-OUT & RESET ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // 0. Logic Reset Dữ liệu (Dành cho Test)
    if (isset($_POST['reset_test'])) {
        $pdo->prepare("DELETE FROM attendance WHERE user_id = ? AND check_date = ?")->execute([$user_id, $today]);
        
        $_SESSION['flash_msg'] = "Đã xóa toàn bộ dữ liệu chấm công hôm nay. Bạn có thể test lại từ đầu!";
        $_SESSION['flash_type'] = "success";
        
        header("Location: attendance.php");
        exit();
    }

    // 1. Logic Check-in: Tính thời gian đi trễ
    if (isset($_POST['check_in'])) {
        $start_time_str = '08:30:00';
        $is_late = ($now > $start_time_str) ? 1 : 0;
        
        $msg = "Check-in thành công lúc $now.";
        $alert_type = "success";

        if ($is_late) {
            // Tính số giây trễ
            $late_seconds = strtotime($now) - strtotime($start_time_str);
            $late_hours = floor($late_seconds / 3600);
            $late_minutes = floor(($late_seconds % 3600) / 60);
            
            $msg .= " Bạn đã vào ca trễ ";
            if ($late_hours > 0) $msg .= "<strong>$late_hours giờ</strong> ";
            $msg .= "<strong>$late_minutes phút</strong> so với quy định.";
            $alert_type = "warning"; // Đổi màu thông báo thành vàng cảnh báo
        }

        $pdo->prepare("INSERT INTO attendance (user_id, check_date, check_in, is_late) VALUES (?, ?, ?, ?)")->execute([$user_id, $today, $now, $is_late]);
        
        // Lưu thông báo vào session để hiển thị
        $_SESSION['flash_msg'] = $msg;
        $_SESSION['flash_type'] = $alert_type;
        
        header("Location: attendance.php");
        exit();
    }

    // 2. Logic Check-out: Thông báo tổng số giờ làm
    if (isset($_POST['check_out'])) {
        $is_early = ($now < '17:30:00') ? 1 : 0;
        $stmt = $pdo->prepare("SELECT check_in FROM attendance WHERE user_id = ? AND check_date = ?");
        $stmt->execute([$user_id, $today]);
        $record = $stmt->fetch();
        
        $work_hours = 0;
        if ($record && $record['check_in']) {
            $in_time = strtotime($record['check_in']);
            $out_time = strtotime($now);
            // Trừ 1.5 tiếng nghỉ trưa (5400 giây)
            $work_hours = max(0, round(($out_time - $in_time - 5400) / 3600, 2));
        }

        $pdo->prepare("UPDATE attendance SET check_out = ?, is_early_leave = ?, work_hours = ? WHERE user_id = ? AND check_date = ?")->execute([$now, $is_early, $work_hours, $user_id, $today]);
        
        // Lưu thông báo tổng giờ làm
        $_SESSION['flash_msg'] = "Check-out thành công! Bạn đã làm việc tổng cộng <strong>$work_hours giờ</strong> trong hôm nay.";
        $_SESSION['flash_type'] = "info";

        header("Location: attendance.php");
        exit();
    }
}

// Lấy trạng thái hôm nay
$stmt = $pdo->prepare("SELECT check_in, check_out FROM attendance WHERE user_id = ? AND check_date = ?");
$stmt->execute([$user_id, $today]);
$attendance_today = $stmt->fetch();
$has_checked_in = !empty($attendance_today['check_in']);
$has_checked_out = !empty($attendance_today['check_out']);

// Lấy dữ liệu báo cáo
$stmt = $pdo->prepare("SELECT COUNT(id) as total_days, SUM(is_late) as total_late, SUM(is_early_leave) as total_early, SUM(work_hours) as total_hours FROM attendance WHERE user_id = ? AND MONTH(check_date) = ?");
$stmt->execute([$user_id, $month]);
$report = $stmt->fetch();
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
    transition: transform 0.3s ease;
  }

  .card-custom:hover {
    transform: translateY(-5px);
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
    background: -webkit-linear-gradient(45deg, #007bff, #6610f2);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
  }
  </style>
</head>

<body class="py-5">

  <div class="container">
    <div class="row mb-4">
      <div class="col-12 text-center">
        <h2 class="fw-bold text-gradient">Creative Agency Hub</h2>
        <p class="text-muted">Cổng thông tin nội bộ - Quản lý nhân sự</p>
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
            <?php 
                // Xóa session sau khi hiển thị để không báo lại khi F5
                unset($_SESSION['flash_msg']); 
                unset($_SESSION['flash_type']); 
            ?>
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
                onclick="return confirm('Bạn có chắc chắn muốn xóa dữ liệu chấm công hôm nay để test lại?');">
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
            <h4 class="fw-bold mb-4 border-bottom pb-2">
              <i class="bi bi-bar-chart-line text-success me-2"></i> Thống kê Tháng <?= $month ?>
            </h4>

            <div class="row g-3">
              <div class="col-sm-6">
                <div class="p-3 bg-light rounded-4 border d-flex align-items-center">
                  <i class="bi bi-calendar-check text-primary stat-icon me-3"></i>
                  <div>
                    <h6 class="mb-0 text-muted">Tổng ngày làm</h6>
                    <h3 class="mb-0 fw-bold"><?= $report['total_days'] ?? 0 ?> <span
                        class="fs-6 fw-normal text-muted">ngày</span></h3>
                  </div>
                </div>
              </div>
              <div class="col-sm-6">
                <div class="p-3 bg-light rounded-4 border d-flex align-items-center">
                  <i class="bi bi-stopwatch text-info stat-icon me-3"></i>
                  <div>
                    <h6 class="mb-0 text-muted">Tổng giờ làm</h6>
                    <h3 class="mb-0 fw-bold"><?= $report['total_hours'] ?? 0 ?> <span
                        class="fs-6 fw-normal text-muted">giờ</span></h3>
                  </div>
                </div>
              </div>
              <div class="col-sm-6">
                <div class="p-3 bg-light rounded-4 border border-warning d-flex align-items-center">
                  <i class="bi bi-exclamation-triangle text-warning stat-icon me-3"></i>
                  <div>
                    <h6 class="mb-0 text-muted">Số lần đi muộn</h6>
                    <h3 class="mb-0 fw-bold text-warning"><?= $report['total_late'] ?? 0 ?> <span
                        class="fs-6 fw-normal text-muted">lần</span></h3>
                  </div>
                </div>
              </div>
              <div class="col-sm-6">
                <div class="p-3 bg-light rounded-4 border border-danger d-flex align-items-center">
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

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>