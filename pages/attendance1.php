<?php
session_start();
// require_once '../config/db_connect.php'; // Gọi kết nối DB
// Giả lập dữ liệu PHP để render giao diện (Trong thực tế bạn sẽ query từ DB)
$hasCheckedIn = true; // True nếu đã check-in hôm nay
$hasCheckedOut = false; // True nếu đã check-out hôm nay
$leaveBalance = 10;
$totalWorkDays = 15;
$lateCount = 2;
?>

<!DOCTYPE html>
<html lang="vi">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Chấm công & Nghỉ phép - Creative Agency Hub</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
  .clock-display {
    font-size: 3rem;
    font-weight: bold;
    font-family: monospace;
    color: #333;
  }

  .card-stat {
    transition: transform 0.2s;
  }

  .card-stat:hover {
    transform: translateY(-5px);
  }
  </style>
</head>

<body class="bg-light">

  <div class="container-fluid py-4">
    <h2 class="mb-4 text-primary"><i class="fas fa-calendar-check me-2"></i>Chấm công & Nghỉ phép</h2>

    <div class="row mb-4">
      <div class="col-12">
        <div class="card shadow-sm border-0 text-center py-4">
          <div class="card-body">
            <h5 class="text-muted mb-3">Thời gian hiện tại</h5>
            <div id="realtime-clock" class="clock-display mb-4">00:00:00</div>

            <form action="../actions/process_attendance.php" method="POST" class="d-flex justify-content-center gap-4">
              <button type="submit" name="action" value="check_in"
                class="btn btn-success btn-lg px-5 py-3 rounded-pill fw-bold shadow"
                <?= $hasCheckedIn ? 'disabled' : '' ?>>
                <i class="fas fa-sign-in-alt me-2"></i>Vào ca (Check-in)
              </button>

              <button type="submit" name="action" value="check_out"
                class="btn btn-danger btn-lg px-5 py-3 rounded-pill fw-bold shadow"
                <?= (!$hasCheckedIn || $hasCheckedOut) ? 'disabled' : '' ?>>
                <i class="fas fa-sign-out-alt me-2"></i>Tan ca (Check-out)
              </button>
            </form>
            <?php if($hasCheckedIn && !$hasCheckedOut): ?>
            <p class="text-success mt-3 small"><i class="fas fa-check-circle"></i> Bạn đã check-in thành công hôm nay.
              Hãy nhớ check-out khi ra về!</p>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>

    <div class="row mb-4">
      <div class="col-md-4 mb-3">
        <div class="card card-stat shadow-sm border-0 bg-primary text-white h-100">
          <div class="card-body d-flex align-items-center">
            <div class="fs-1 me-3"><i class="fas fa-briefcase"></i></div>
            <div>
              <h6 class="text-white-50 mb-1">Công chuẩn tháng này</h6>
              <h3 class="mb-0"><?= $totalWorkDays ?> ngày</h3>
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-4 mb-3">
        <div class="card card-stat shadow-sm border-0 bg-warning text-dark h-100">
          <div class="card-body d-flex align-items-center">
            <div class="fs-1 me-3"><i class="fas fa-clock"></i></div>
            <div>
              <h6 class="text-dark-50 mb-1">Đi muộn / Về sớm</h6>
              <h3 class="mb-0"><?= $lateCount ?> lần</h3>
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-4 mb-3">
        <div class="card card-stat shadow-sm border-0 bg-info text-white h-100">
          <div class="card-body d-flex align-items-center">
            <div class="fs-1 me-3"><i class="fas fa-umbrella-beach"></i></div>
            <div>
              <h6 class="text-white-50 mb-1">Phép năm còn lại</h6>
              <h3 class="mb-0"><?= $leaveBalance ?> ngày</h3>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="card shadow-sm border-0 mb-4">
      <div class="card-header bg-white pt-3 pb-0 border-bottom-0">
        <ul class="nav nav-tabs" id="leaveTabs" role="tablist">
          <li class="nav-item" role="presentation">
            <button class="nav-link active fw-bold" id="new-request-tab" data-bs-toggle="tab"
              data-bs-target="#new-request" type="button" role="tab">
              <i class="fas fa-plus-circle me-1"></i> Gửi đơn mới
            </button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link fw-bold" id="history-tab" data-bs-toggle="tab" data-bs-target="#history"
              type="button" role="tab">
              <i class="fas fa-history me-1"></i> Lịch sử đơn
            </button>
          </li>
        </ul>
      </div>
      <div class="card-body">
        <div class="tab-content" id="leaveTabsContent">
          <div class="tab-pane fade show active" id="new-request" role="tabpanel">
            <form action="../actions/process_leave.php" method="POST" class="row g-3">
              <div class="col-md-6">
                <label class="form-label">Từ ngày</label>
                <input type="date" name="start_date" class="form-control" required>
              </div>
              <div class="col-md-6">
                <label class="form-label">Đến ngày</label>
                <input type="date" name="end_date" class="form-control" required>
              </div>
              <div class="col-12">
                <label class="form-label">Lý do nghỉ</label>
                <textarea name="reason" class="form-control" rows="3" placeholder="Nhập lý do chi tiết..."
                  required></textarea>
              </div>
              <div class="col-12">
                <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane me-2"></i>Gửi yêu
                  cầu</button>
              </div>
            </form>
          </div>
          <div class="tab-pane fade" id="history" role="tabpanel">
            <div class="table-responsive">
              <table class="table table-hover align-middle">
                <thead class="table-light">
                  <tr>
                    <th>Thời gian nghỉ</th>
                    <th>Số ngày</th>
                    <th>Lý do</th>
                    <th>Trạng thái</th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td>10/10/2023 - 11/10/2023</td>
                    <td>2</td>
                    <td>Nghỉ ốm</td>
                    <td><span class="badge bg-success">Approved</span></td>
                  </tr>
                  <tr>
                    <td>25/10/2023 - 25/10/2023</td>
                    <td>1</td>
                    <td>Việc gia đình</td>
                    <td><span class="badge bg-warning text-dark">Pending</span></td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="card shadow-sm border-0">
      <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
        <h5 class="mb-0 fw-bold text-secondary"><i class="fas fa-list-alt me-2"></i>Chi tiết chấm công</h5>
        <div class="d-flex gap-2">
          <input type="month" class="form-control form-control-sm" value="<?= date('Y-m') ?>">
          <a href="../actions/export_attendance.php?type=excel" class="btn btn-sm btn-outline-success"><i
              class="fas fa-file-excel me-1"></i>Excel</a>
          <a href="../actions/export_attendance.php?type=pdf" class="btn btn-sm btn-outline-danger"><i
              class="fas fa-file-pdf me-1"></i>PDF</a>
        </div>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-striped table-hover mb-0 text-center">
            <thead class="table-dark">
              <tr>
                <th>Ngày</th>
                <th>Giờ vào</th>
                <th>Giờ ra</th>
                <th>Tổng giờ</th>
                <th>Ghi chú</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td>01/10/2023</td>
                <td class="text-success">08:25</td>
                <td>17:35</td>
                <td>8.0</td>
                <td>-</td>
              </tr>
              <tr>
                <td>02/10/2023</td>
                <td class="text-danger fw-bold">08:45</td>
                <td>17:30</td>
                <td>7.7</td>
                <td><span class="badge bg-danger">Đi muộn</span></td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
  function updateClock() {
    const now = new Date();
    const hours = String(now.getHours()).padStart(2, '0');
    const minutes = String(now.getMinutes()).padStart(2, '0');
    const seconds = String(now.getSeconds()).padStart(2, '0');
    document.getElementById('realtime-clock').textContent = `${hours}:${minutes}:${seconds}`;
  }
  setInterval(updateClock, 1000);
  updateClock(); // Khởi chạy ngay khi load
  </script>
</body>

</html>