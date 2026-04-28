<?php
session_start();
require_once '../config/db_connect.php';

// Thiết lập múi giờ
date_default_timezone_set('Asia/Ho_Chi_Minh');

// Giả lập session ID để View có thể render giao diện (Trong thực tế sẽ lấy từ lúc Login)
$_SESSION['employee_id'] = 3; 
$emp_id = $_SESSION['employee_id'];

$today = date('Y-m-d');
$month = date('m');
$year = date('Y');

// ==========================================
// LẤY DỮ LIỆU HIỂN THỊ LÊN GIAO DIỆN
// ==========================================
// 1. Trạng thái nút bấm hôm nay
$stmt = $pdo->prepare("SELECT check_in_time, check_out_time FROM attendances WHERE employee_id = ? AND work_date = ?");
$stmt->execute([$emp_id, $today]);
$attendance_today = $stmt->fetch();

$has_checked_in = !empty($attendance_today['check_in_time']);
$has_checked_out = !empty($attendance_today['check_out_time']);

// 2. Lấy dữ liệu nguyên tháng
$stmtDetails = $pdo->prepare("SELECT * FROM attendances WHERE employee_id = ? AND MONTH(work_date) = ? AND YEAR(work_date) = ? ORDER BY work_date DESC");
$stmtDetails->execute([$emp_id, $month, $year]);
$raw_details = $stmtDetails->fetchAll();

// Tính toán thống kê báo cáo
$report = [
    'total_days' => count($raw_details),
    'total_late' => 0,
    'total_early' => 0,
    'total_hours' => 0
];

$details = []; 

foreach ($raw_details as $row) {
    $is_late = ($row['status'] == 'Late');
    $is_early_leave = false;
    $work_hours = 0;

    if ($is_late) {
        $report['total_late']++;
    }

    if (!empty($row['check_out_time'])) {
        $out_time_limit = date('Y-m-d 17:30:00', strtotime($row['work_date']));
        if ($row['check_out_time'] < $out_time_limit) {
            $is_early_leave = true;
            $report['total_early']++;
        }

        $in_sec = strtotime($row['check_in_time']);
        $out_sec = strtotime($row['check_out_time']);
        $work_hours = max(0, round(($out_sec - $in_sec - 5400) / 3600, 2)); // Trừ 1.5h nghỉ trưa
        $report['total_hours'] += $work_hours;
    }

    $details[] = [
        'check_date' => $row['work_date'],
        'check_in' => $row['check_in_time'] ? date('H:i:s', strtotime($row['check_in_time'])) : null,
        'check_out' => $row['check_out_time'] ? date('H:i:s', strtotime($row['check_out_time'])) : null,
        'is_late' => $is_late,
        'is_early_leave' => $is_early_leave,
        'work_hours' => $work_hours
    ];
}
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
  body {
    background-color: #f4f7fc;
  }

  .fw-bold {
    font-weight: 700 !important;
  }

  .text-gradient {
    background: linear-gradient(45deg, #4e73df, #36b9cc);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
  }

  .card-custom {
    border: none;
    border-radius: 1.25rem;
    box-shadow: 0 0.5rem 1.5rem rgba(0, 0, 0, 0.05);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
  }

  .card-custom:hover {
    transform: translateY(-3px);
    box-shadow: 0 0.75rem 2rem rgba(0, 0, 0, 0.08);
  }

  .btn-checkin {
    border-radius: 50px;
    padding: 14px 20px;
    font-weight: 700;
    font-size: 0.9rem;
    letter-spacing: 1px;
    transition: all 0.3s ease;
  }

  .main-icon {
    font-size: 4rem;
    display: inline-block;
    animation: pulse-soft 2s infinite;
  }

  @keyframes pulse-soft {
    0% {
      transform: scale(1);
      opacity: 1;
    }

    50% {
      transform: scale(1.08);
      opacity: 0.8;
    }

    100% {
      transform: scale(1);
      opacity: 1;
    }
  }

  .stat-card {
    border-radius: 1rem;
    padding: 1.25rem !important;
    border: none !important;
    cursor: pointer;
    transition: all 0.2s;
  }

  .stat-card:hover {
    transform: scale(1.02);
  }

  .stat-icon {
    font-size: 2rem;
  }

  .stat-value {
    font-size: 1.5rem;
    line-height: 1.2;
  }

  .stat-label {
    font-size: 0.85rem;
    font-weight: 600;
    text-transform: uppercase;
    margin-bottom: 5px;
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
              <h3 class="fw-bold text-gradient mb-1">Creative Agency Hub</h3>
              <p class="text-muted small mb-3">Cổng thông tin nội bộ - Quản lý nhân sự</p>
              <div class="d-flex justify-content-center flex-wrap" style="gap: 10px;">
                <a href="../index.php" class="btn btn-sm btn-dark rounded-pill px-4 shadow-sm"><i
                    class="bi bi-house-door me-1"></i> Trang chủ</a>
                <a href="attendance.php" class="btn btn-sm btn-primary rounded-pill px-4 shadow-sm"><i
                    class="bi bi-clock-history me-1"></i> Chấm công</a>
                <a href="leave_request.php"
                  class="btn btn-sm btn-outline-primary rounded-pill bg-white px-4 shadow-sm"><i
                    class="bi bi-envelope-paper me-1"></i> Xin nghỉ phép</a>
                <a href="payroll_summary.php"
                  class="btn btn-sm btn-outline-primary rounded-pill bg-white px-4 shadow-sm"><i
                    class="bi bi-receipt me-1"></i> Bảng lương</a>
              </div>
            </div>
          </div>

          <div class="row align-items-stretch">

            <div class="col-lg-5 mb-4">
              <div class="card card-custom h-100">
                <div class="card-body p-5 text-center d-flex flex-column justify-content-center">
                  <div class="mb-4">
                    <i class="bi bi-clock-history main-icon text-primary mb-3"></i>
                    <h4 class="fw-bold text-gray-800 mb-1">Ghi nhận thời gian</h4>
                    <p class="text-muted mb-0">Hôm nay: <strong class="text-dark"><?= date('d/m/Y') ?></strong></p>
                  </div>

                  <div class="d-grid gap-3 mt-2" style="display: grid;">
                    <button type="button" onclick="handleAttendance('checkin')"
                      class="btn btn-checkin btn-primary shadow-sm text-white" <?= $has_checked_in ? 'disabled' : '' ?>>
                      <i class="bi bi-box-arrow-in-right me-2" style="font-size: 1.1rem;"></i> BẮT ĐẦU CA LÀM
                    </button>
                    <button type="button" onclick="handleAttendance('checkout')"
                      class="btn btn-checkin btn-danger shadow-sm text-white"
                      <?= (!$has_checked_in || $has_checked_out) ? 'disabled' : '' ?>>
                      <i class="bi bi-box-arrow-right me-2" style="font-size: 1.1rem;"></i> KẾT THÚC CA LÀM
                    </button>
                  </div>

                  <?php if ($has_checked_in): ?>
                  <div class="alert alert-success mt-4 mb-0 rounded-pill small py-2 font-weight-bold shadow-sm"
                    role="alert">
                    <i class="bi bi-check-circle-fill me-1"></i> Đã vào ca lúc:
                    <span class="badge badge-success px-2 py-1 ml-1"
                      style="font-size: 0.9rem;"><?= date('H:i:s', strtotime($attendance_today['check_in_time'])) ?></span>
                  </div>
                  <?php endif; ?>
                </div>
              </div>
            </div>

            <div class="col-lg-7 mb-4">
              <div class="card card-custom h-100">
                <div class="card-body p-4 d-flex flex-column">
                  <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
                    <h5 class="fw-bold mb-0 text-gray-800"><i class="bi bi-bar-chart-line-fill text-success me-2"></i>
                      Thống kê Tháng <?= $month ?></h5>
                  </div>

                  <div class="row flex-grow-1 align-content-center">
                    <div class="col-sm-6 mb-4">
                      <div class="stat-card h-100 d-flex align-items-center"
                        style="background-color: rgba(78, 115, 223, 0.1);" data-toggle="modal"
                        data-target="#modalTotalDays">
                        <i class="bi bi-calendar-check stat-icon me-3 text-primary"></i>
                        <div>
                          <div class="stat-label text-primary">Tổng ngày làm</div>
                          <div class="stat-value fw-bold text-primary"><?= $report['total_days'] ?? 0 ?> <span
                              class="fw-normal" style="font-size: 0.9rem;">ngày</span></div>
                        </div>
                      </div>
                    </div>
                    <div class="col-sm-6 mb-4">
                      <div class="stat-card h-100 d-flex align-items-center"
                        style="background-color: rgba(54, 185, 204, 0.1);" data-toggle="modal"
                        data-target="#modalTotalDays">
                        <i class="bi bi-stopwatch stat-icon me-3 text-info"></i>
                        <div>
                          <div class="stat-label text-info">Tổng giờ làm</div>
                          <div class="stat-value fw-bold text-info"><?= $report['total_hours'] ?? 0 ?> <span
                              class="fw-normal" style="font-size: 0.9rem;">giờ</span></div>
                        </div>
                      </div>
                    </div>
                    <div class="col-sm-6 mb-4">
                      <div class="stat-card h-100 d-flex align-items-center"
                        style="background-color: rgba(246, 194, 62, 0.15);" data-toggle="modal"
                        data-target="#modalLate">
                        <i class="bi bi-exclamation-triangle stat-icon me-3 text-warning"></i>
                        <div>
                          <div class="stat-label text-warning">Số lần đi muộn</div>
                          <div class="stat-value fw-bold text-warning"><?= $report['total_late'] ?? 0 ?> <span
                              class="fw-normal" style="font-size: 0.9rem;">lần</span></div>
                        </div>
                      </div>
                    </div>
                    <div class="col-sm-6 mb-4">
                      <div class="stat-card h-100 d-flex align-items-center"
                        style="background-color: rgba(231, 74, 59, 0.1);" data-toggle="modal" data-target="#modalEarly">
                        <i class="bi bi-door-open stat-icon me-3 text-danger"></i>
                        <div>
                          <div class="stat-label text-danger">Số lần về sớm</div>
                          <div class="stat-value fw-bold text-danger"><?= $report['total_early'] ?? 0 ?> <span
                              class="fw-normal" style="font-size: 0.9rem;">lần</span></div>
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
      <?php if(file_exists('../components/footer.php')) include('../components/footer.php'); ?>
    </div>
  </div>

  <div class="modal fade" id="modalTotalDays" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
      <div class="modal-content rounded-3 border-0 shadow-lg">
        <div class="modal-header border-0 bg-primary text-white py-3">
          <h6 class="modal-title fw-bold mb-0"><i class="bi bi-calendar-check me-2"></i>Chi tiết ngày công</h6>
          <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close"><span
              aria-hidden="true">&times;</span></button>
        </div>
        <div class="modal-body p-0">
          <table class="table table-hover table-sm mb-0 text-center align-middle" style="font-size: 0.95rem;">
            <thead class="bg-light text-muted">
              <tr>
                <th class="py-3">Ngày</th>
                <th>Giờ vào</th>
                <th>Giờ ra</th>
                <th>Số giờ</th>
                <th>Trạng thái</th>
              </tr>
            </thead>
            <tbody>
              <?php if(empty($details)): ?>
              <tr>
                <td colspan="5" class="text-muted py-5">Chưa có dữ liệu chấm công.</td>
              </tr>
              <?php else: foreach($details as $row): ?>
              <tr>
                <td class="py-3"><strong class="text-dark"><?= date('d/m/Y', strtotime($row['check_date'])) ?></strong>
                </td>
                <td><?= $row['check_in'] ?? '<span class="text-muted">-</span>' ?></td>
                <td><?= $row['check_out'] ?? '<span class="text-muted">-</span>' ?></td>
                <td><span class="badge badge-info text-white px-2 py-1 rounded-pill"><?= $row['work_hours'] ?> h</span>
                </td>
                <td>
                  <?php if($row['is_late']): ?><span
                    class="badge badge-warning text-dark px-2 py-1 rounded-pill me-1">Muộn</span><?php endif; ?>
                  <?php if($row['is_early_leave']): ?><span
                    class="badge badge-danger px-2 py-1 rounded-pill">Sớm</span><?php endif; ?>
                  <?php if(!$row['is_late'] && !$row['is_early_leave'] && $row['check_out']): ?><span
                    class="badge badge-success px-2 py-1 rounded-pill">Đúng giờ</span><?php endif; ?>
                </td>
              </tr>
              <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <script src="../assets/vendor/jquery/jquery.min.js"></script>
  <script src="../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="../assets/js/sb-admin-2.min.js"></script>

  <script>
  function handleAttendance(action) {
    // Lấy JWT Token (Chìa khóa) từ LocalStorage
    const token = localStorage.getItem('jwt_token');

    if (!token) {
      alert("Lỗi: Bạn chưa đăng nhập (Chưa có Token)!");
      return;
    }

    // Tạo đường dẫn API tương ứng (checkin hoặc checkout)
    const apiUrl = '/creative-agency-hub/public/api/attendance/' + action;

    // Gọi Fetch API xuống AttendanceController
    fetch(apiUrl, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': 'Bearer ' + token
        }
      })
      .then(response => response.json())
      .then(data => {
        if (data.status === 'success') {
          alert("✅ " + data.message);
          // Reload trang để View tự động lấy dữ liệu mới từ Database và render lại thống kê
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