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
        $_SESSION['flash_msg'] = "Đã xóa toàn bộ dữ liệu chấm công hôm nay.";
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
            $msg .= "<strong>$late_minutes phút</strong>.";
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
        $_SESSION['flash_msg'] = "Check-out thành công! Tổng làm: <strong>$work_hours giờ</strong>.";
        $_SESSION['flash_type'] = "info";
        header("Location: attendance.php");
        exit();
    }
}

// Lấy trạng thái nút bấm hôm nay
$stmt = $pdo->prepare("SELECT check_in, check_out FROM attendance WHERE user_id = ? AND check_date = ?");
$stmt->execute([$user_id, $today]);
$attendance_today = $stmt->fetch();
$has_checked_in = !empty($attendance_today['check_in']);
$has_checked_out = !empty($attendance_today['check_out']);

// Lấy dữ liệu báo cáo tổng quát
$stmt = $pdo->prepare("SELECT COUNT(id) as total_days, SUM(is_late) as total_late, SUM(is_early_leave) as total_early, SUM(work_hours) as total_hours FROM attendance WHERE user_id = ? AND MONTH(check_date) = ?");
$stmt->execute([$user_id, $month]);
$report = $stmt->fetch();

// LẤY DỮ LIỆU CHI TIẾT CHO CÁC MODAL
$stmtDetails = $pdo->prepare("SELECT * FROM attendance WHERE user_id = ? AND MONTH(check_date) = ? ORDER BY check_date DESC");
$stmtDetails->execute([$user_id, $month]);
$details = $stmtDetails->fetchAll();
?>

<!DOCTYPE html>
<html lang="vi">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>Chấm Công - Creative Agency Hub</title>

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

  /* Bo góc vừa phải */
  .rounded-4 {
    border-radius: 1rem !important;
  }

  /* Chỉnh lại shadow cho mềm mại, giống SB Admin hơn */
  .card-custom {
    border: none;
    border-radius: 0.75rem;
    box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
  }

  /* Thu nhỏ nút bấm */
  .btn-checkin {
    border-radius: 50px;
    padding: 10px 20px;
    font-weight: 600;
    font-size: 0.85rem;
    letter-spacing: 0.5px;
  }


  .stat-icon {
    font-size: 1.75rem;
    opacity: 0.8;
  }

  .main-icon {
    font-size: 3.5rem;
  }



  .text-gradient {
    background: -webkit-linear-gradient(45deg, #4e73df, #224abe);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
  }


  .stat-card {
    transition: all 0.2s ease;
    cursor: pointer;
    border-radius: 0.5rem;
    padding: 1rem !important;
  }

  .stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 0.25rem 0.75rem rgba(0, 0, 0, 0.1);
  }

  .stat-value {
    font-size: 1.4rem;
  }


  .stat-label {
    font-size: 0.8rem;
  }




  .d-grid {
    display: grid;
  }

  .gap-2 {
    gap: 0.75rem;
  }

  .gap-3 {
    gap: 1rem;
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
              <p class="text-muted small mb-3">Cổng thông tin nội bộ - Quản lý nhân sự</p>

              <div class="d-flex justify-content-center flex-wrap" style="gap: 8px;">
                <a href="http://localhost/LTW/creative-agency-hub"
                  class="btn btn-sm btn-dark rounded-pill shadow-sm px-3"><i class="bi bi-house-door me-1"></i> Trang
                  chủ</a>
                <a href="attendance.php" class="btn btn-sm btn-primary rounded-pill shadow-sm px-3"><i
                    class="bi bi-clock-history me-1"></i> Chấm công</a>
                <a href="leave_request.php"
                  class="btn btn-sm btn-outline-primary rounded-pill shadow-sm bg-white px-3"><i
                    class="bi bi-envelope-paper me-1"></i> Xin nghỉ phép</a>
                <a href="payroll_summary.php"
                  class="btn btn-sm btn-outline-primary rounded-pill shadow-sm bg-white px-3"><i
                    class="bi bi-receipt me-1"></i> Bảng lương</a>
              </div>
            </div>
          </div>

          <div class="row align-items-stretch">

            <div class="col-md-5 mb-4">
              <div class="card card-custom h-100">
                <div class="card-body p-4 text-center d-flex flex-column justify-content-center">
                  <i class="bi bi-clock-history main-icon text-primary mb-2"></i>
                  <h5 class="fw-bold mb-1 text-gray-800">Ghi nhận thời gian</h5>
                  <p class="text-muted small mb-3">Hôm nay: <?= date('d/m/Y') ?></p>

                  <?php if (isset($_SESSION['flash_msg'])): ?>
                  <div
                    class="alert alert-<?= $_SESSION['flash_type'] ?> alert-dismissible fade show text-left rounded-3 small py-2 px-3"
                    role="alert">
                    <i class="bi bi-info-circle-fill me-1"></i> <?= $_SESSION['flash_msg'] ?>
                    <button type="button" class="close p-2" data-dismiss="alert" aria-label="Close">
                      <span aria-hidden="true">&times;</span>
                    </button>
                  </div>
                  <?php unset($_SESSION['flash_msg']); unset($_SESSION['flash_type']); ?>
                  <?php endif; ?>

                  <form method="POST" class="d-grid gap-2 mt-1">
                    <button type="submit" name="check_in" class="btn btn-primary btn-checkin"
                      <?= $has_checked_in ? 'disabled' : '' ?>>
                      <i class="bi bi-box-arrow-in-right me-1"></i> BẮT ĐẦU CA LÀM
                    </button>

                    <button type="submit" name="check_out" class="btn btn-outline-danger btn-checkin"
                      <?= (!$has_checked_in || $has_checked_out) ? 'disabled' : '' ?>>
                      <i class="bi bi-box-arrow-right me-1"></i> KẾT THÚC CA LÀM
                    </button>

                    <button type="submit" name="reset_test"
                      class="btn btn-light border btn-sm rounded-pill py-2 mt-3 text-muted"
                      onclick="return confirm('Xóa dữ liệu chấm công hôm nay?');">
                      <i class="bi bi-arrow-clockwise"></i> Reset bản nháp
                    </button>
                  </form>

                  <?php if ($has_checked_in): ?>
                  <div class="alert alert-success mt-3 mb-0 rounded-pill small py-2" role="alert">
                    <i class="bi bi-check-circle-fill me-1"></i> Giờ vào ca:
                    <strong><?= $attendance_today['check_in'] ?></strong>
                  </div>
                  <?php endif; ?>
                </div>
              </div>
            </div>

            <div class="col-md-7 mb-4">
              <div class="card card-custom h-100">
                <div class="card-body p-4 d-flex flex-column">
                  <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
                    <h6 class="fw-bold mb-0 text-gray-800">
                      <i class="bi bi-bar-chart-line text-success me-1"></i> Thống kê Tháng <?= $month ?>
                    </h6>
                  </div>

                  <div class="row flex-grow-1 align-content-center">
                    <div class="col-sm-6 mb-3">
                      <div class="bg-light border d-flex align-items-center stat-card h-100" data-toggle="modal"
                        data-target="#modalTotalDays">
                        <i class="bi bi-calendar-check text-primary stat-icon me-3"></i>
                        <div>
                          <div class="stat-label text-muted">Tổng ngày làm</div>
                          <div class="stat-value fw-bold text-dark"><?= $report['total_days'] ?? 0 ?> <span
                              class="fw-normal text-muted" style="font-size: 0.8rem;">ngày</span></div>
                        </div>
                      </div>
                    </div>

                    <div class="col-sm-6 mb-3">
                      <div class="bg-light border d-flex align-items-center stat-card h-100" data-toggle="modal"
                        data-target="#modalTotalDays">
                        <i class="bi bi-stopwatch text-info stat-icon me-3"></i>
                        <div>
                          <div class="stat-label text-muted">Tổng giờ làm</div>
                          <div class="stat-value fw-bold text-dark"><?= $report['total_hours'] ?? 0 ?> <span
                              class="fw-normal text-muted" style="font-size: 0.8rem;">giờ</span></div>
                        </div>
                      </div>
                    </div>

                    <div class="col-sm-6 mb-3">
                      <div class="bg-light border border-warning d-flex align-items-center stat-card h-100"
                        data-toggle="modal" data-target="#modalLate">
                        <i class="bi bi-exclamation-triangle text-warning stat-icon me-3"></i>
                        <div>
                          <div class="stat-label text-muted">Số lần đi muộn</div>
                          <div class="stat-value fw-bold text-warning"><?= $report['total_late'] ?? 0 ?> <span
                              class="fw-normal text-muted" style="font-size: 0.8rem;">lần</span></div>
                        </div>
                      </div>
                    </div>

                    <div class="col-sm-6 mb-3">
                      <div class="bg-light border border-danger d-flex align-items-center stat-card h-100"
                        data-toggle="modal" data-target="#modalEarly">
                        <i class="bi bi-door-open text-danger stat-icon me-3"></i>
                        <div>
                          <div class="stat-label text-muted">Số lần về sớm</div>
                          <div class="stat-value fw-bold text-danger"><?= $report['total_early'] ?? 0 ?> <span
                              class="fw-normal text-muted" style="font-size: 0.8rem;">lần</span></div>
                        </div>
                      </div>
                    </div>
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

  <div class="modal fade" id="modalTotalDays" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
      <div class="modal-content rounded-3">
        <div class="modal-header border-0 bg-light py-3">
          <h6 class="modal-title fw-bold text-primary mb-0"><i class="bi bi-calendar-check me-2"></i>Chi tiết ngày công
          </h6>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
              aria-hidden="true">&times;</span></button>
        </div>
        <div class="modal-body p-0">
          <table class="table table-hover table-sm mb-0 text-center align-middle" style="font-size: 0.9rem;">
            <thead class="bg-light text-muted">
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
                <td colspan="5" class="text-muted py-4">Chưa có dữ liệu chấm công.</td>
              </tr>
              <?php else: foreach($details as $row): ?>
              <tr>
                <td><strong><?= date('d/m/Y', strtotime($row['check_date'])) ?></strong></td>
                <td><?= $row['check_in'] ?? '<span class="text-muted">-</span>' ?></td>
                <td><?= $row['check_out'] ?? '<span class="text-muted">-</span>' ?></td>
                <td><span class="badge badge-info text-white p-1 px-2"><?= $row['work_hours'] ?> h</span></td>
                <td>
                  <?php if($row['is_late']): ?><span
                    class="badge badge-warning text-dark p-1 px-2 me-1">Muộn</span><?php endif; ?>
                  <?php if($row['is_early_leave']): ?><span
                    class="badge badge-danger p-1 px-2">Sớm</span><?php endif; ?>
                  <?php if(!$row['is_late'] && !$row['is_early_leave'] && $row['check_out']): ?><span
                    class="badge badge-success p-1 px-2">Đúng giờ</span><?php endif; ?>
                </td>
              </tr>
              <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <div class="modal fade" id="modalLate" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
      <div class="modal-content border-warning rounded-3">
        <div class="modal-header border-0 bg-warning text-dark py-3">
          <h6 class="modal-title fw-bold mb-0"><i class="bi bi-exclamation-triangle me-2"></i>Lịch sử đi muộn</h6>
          <button type="button" class="close text-dark" data-dismiss="modal" aria-label="Close"><span
              aria-hidden="true">&times;</span></button>
        </div>
        <div class="modal-body p-0" style="font-size: 0.9rem;">
          <ul class="list-group list-group-flush">
            <?php $hasLate = false; foreach($details as $row): 
                            if($row['is_late']): $hasLate = true; $late_mins = round((strtotime($row['check_in']) - strtotime('08:30:00')) / 60); ?>
            <li class="list-group-item d-flex justify-content-between align-items-center py-2">
              <div><strong class="text-dark"><?= date('d/m/Y', strtotime($row['check_date'])) ?></strong> <span
                  class="text-muted ml-2">Vào: <?= $row['check_in'] ?></span></div>
              <span class="badge badge-warning text-dark p-1 px-2 badge-pill">Trễ <?= $late_mins ?> phút</span>
            </li>
            <?php endif; endforeach; if(!$hasLate): ?>
            <li class="list-group-item text-center text-muted py-3">Bạn không đi muộn ngày nào.</li>
            <?php endif; ?>
          </ul>
        </div>
      </div>
    </div>
  </div>

  <div class="modal fade" id="modalEarly" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
      <div class="modal-content border-danger rounded-3">
        <div class="modal-header border-0 bg-danger text-white py-3">
          <h6 class="modal-title fw-bold mb-0"><i class="bi bi-door-open me-2"></i>Lịch sử về sớm</h6>
          <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close"><span
              aria-hidden="true">&times;</span></button>
        </div>
        <div class="modal-body p-0" style="font-size: 0.9rem;">
          <ul class="list-group list-group-flush">
            <?php $hasEarly = false; foreach($details as $row): 
                            if($row['is_early_leave'] && $row['check_out']): $hasEarly = true; $early_mins = round((strtotime('17:30:00') - strtotime($row['check_out'])) / 60); ?>
            <li class="list-group-item d-flex justify-content-between align-items-center py-2">
              <div><strong class="text-dark"><?= date('d/m/Y', strtotime($row['check_date'])) ?></strong> <span
                  class="text-muted ml-2">Ra: <?= $row['check_out'] ?></span></div>
              <span class="badge badge-danger p-1 px-2 badge-pill">Sớm <?= $early_mins ?> phút</span>
            </li>
            <?php endif; endforeach; if(!$hasEarly): ?>
            <li class="list-group-item text-center text-muted py-3">Bạn không về sớm ngày nào.</li>
            <?php endif; ?>
          </ul>
        </div>
      </div>
    </div>
  </div>

  <script src="../assets/vendor/jquery/jquery.min.js"></script>
  <script src="../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="../assets/js/sb-admin-2.min.js"></script>

</body>

</html>