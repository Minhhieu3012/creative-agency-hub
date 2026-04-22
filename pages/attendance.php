<?php
// Bắt đầu session và kết nối DB (Giả định)
// session_start();
// include '../includes/db_connect.php'; 

// Giả lập dữ liệu user đăng nhập
$user_id = 1; 
$today = date('Y-m-d');
$now = date('H:i:s');
$message = "";

// 1. Xử lý Logic Check-in / Check-out & Gửi đơn phép (Mô phỏng POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] == 'checkin') {
            $status = ($now > "08:15:00") ? 'Đi muộn' : 'Đúng giờ';
            // SQL: INSERT INTO attendance...
            $message = "<div class='alert alert-success'>Check-in thành công lúc $now ($status)</div>";
        } elseif ($_POST['action'] == 'checkout') {
            $status_update = ($now < "17:00:00") ? 'Về sớm' : 'Đúng giờ';
            // SQL: UPDATE attendance SET check_out...
            $message = "<div class='alert alert-warning'>Check-out thành công lúc $now ($status_update)</div>";
        }
    } elseif (isset($_POST['submit_leave'])) {
        $start = $_POST['start_date'];
        $end = $_POST['end_date'];
        $reason = $_POST['reason'];
        // SQL: INSERT INTO leave_requests...
        $message = "<div class='alert alert-info'>Đã gửi đơn xin nghỉ từ $start đến $end. Trạng thái: Pending.</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
  <meta charset="UTF-8">
  <title>Chấm công & Tiền lương - Creative Agency Hub</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
</head>

<body class="bg-light">

  <div class="container-fluid py-4">
    <h2 class="mb-4 fw-bold text-primary">Hệ thống Chấm công & Tiền lương</h2>
    <?= $message ?>

    <div class="row g-4">
      <div class="col-md-4">
        <div class="card shadow-sm mb-4 border-0">
          <div class="card-header bg-dark text-white fw-bold">Điểm danh ngày: <?= date('d/m/Y') ?></div>
          <div class="card-body text-center py-4">
            <h3 id="realtime-clock" class="display-5 fw-bold text-secondary mb-4">00:00:00</h3>
            <form method="POST">
              <div class="d-grid gap-2">
                <button type="submit" name="action" value="checkin" class="btn btn-success btn-lg">VÀO CA
                  (Check-in)</button>
                <button type="submit" name="action" value="checkout" class="btn btn-danger btn-lg">TAN CA
                  (Check-out)</button>
              </div>
            </form>
          </div>
        </div>

        <div class="card shadow-sm border-0">
          <div class="card-header bg-warning text-dark fw-bold">Tạo đơn nghỉ phép</div>
          <div class="card-body">
            <form method="POST">
              <div class="mb-2">
                <label class="form-label text-muted small">Từ ngày</label>
                <input type="date" name="start_date" class="form-control" required>
              </div>
              <div class="mb-2">
                <label class="form-label text-muted small">Đến ngày</label>
                <input type="date" name="end_date" class="form-control" required>
              </div>
              <div class="mb-3">
                <label class="form-label text-muted small">Lý do</label>
                <textarea name="reason" class="form-control" rows="2" required></textarea>
              </div>
              <button type="submit" name="submit_leave" class="btn btn-primary w-100">Gửi chờ duyệt</button>
            </form>
          </div>
        </div>
      </div>

      <div class="col-md-8">
        <div class="card shadow-sm mb-4 border-0">
          <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <span class="fw-bold text-primary">Lịch sử chấm công tháng <?= date('m/Y') ?></span>
            <button onclick="exportToExcel()" class="btn btn-sm btn-outline-success">Xuất Excel</button>
          </div>
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-hover table-striped mb-0" id="attendanceTable">
                <thead class="table-light">
                  <tr>
                    <th>Ngày</th>
                    <th>Giờ vào</th>
                    <th>Giờ ra</th>
                    <th>Trạng thái</th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td>20/04/2026</td>
                    <td>08:05:00</td>
                    <td>17:05:00</td>
                    <td><span class="badge bg-success">Đúng giờ</span></td>
                  </tr>
                  <tr>
                    <td>21/04/2026</td>
                    <td>08:20:00</td>
                    <td>17:00:00</td>
                    <td><span class="badge bg-warning text-dark">Đi muộn</span></td>
                  </tr>
                  <tr>
                    <td>22/04/2026</td>
                    <td>08:00:00</td>
                    <td>16:30:00</td>
                    <td><span class="badge bg-danger">Về sớm</span></td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <div class="card shadow-sm border-0 border-start border-primary border-4">
          <div class="card-header bg-white fw-bold">Mô phỏng tính lương & KPI (Interactive)</div>
          <div class="card-body">
            <div class="row mb-3">
              <div class="col-md-6 mb-2">
                <label class="form-label small">Lương cơ bản (VNĐ)</label>
                <input type="number" id="calc_basic" class="form-control" value="15000000" oninput="calculateSalary()">
              </div>
              <div class="col-md-6 mb-2">
                <label class="form-label small">Số lần đi muộn (50k/lần)</label>
                <input type="number" id="calc_late" class="form-control" value="2" oninput="calculateSalary()">
              </div>
              <div class="col-md-6 mb-2">
                <label class="form-label small">Ngày công thực tế / 26</label>
                <input type="number" id="calc_days" class="form-control" value="24" step="0.5"
                  oninput="calculateSalary()">
              </div>
              <div class="col-md-6 mb-2">
                <label class="form-label small">Điểm KPI (0 - 100)</label>
                <input type="range" id="calc_kpi" class="form-range" min="0" max="100" value="85"
                  oninput="updateKpiLabel(); calculateSalary()">
                <div class="text-center fw-bold text-primary" id="kpi_label">85 điểm</div>
              </div>
            </div>

            <div class="bg-light p-3 rounded">
              <div class="d-flex justify-content-between mb-2">
                <span>Lương theo ngày công:</span>
                <strong id="res_work_salary">0 VNĐ</strong>
              </div>
              <div class="d-flex justify-content-between mb-2">
                <span>Thưởng/Phạt KPI:</span>
                <strong id="res_kpi_adj" class="text-secondary">0 VNĐ</strong>
              </div>
              <div class="d-flex justify-content-between mb-2">
                <span>Phạt đi muộn:</span>
                <strong id="res_late_penalty" class="text-danger">0 VNĐ</strong>
              </div>
              <hr>
              <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Thực nhận:</h5>
                <h4 class="mb-0 text-success fw-bold" id="res_total">0 VNĐ</h4>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script>
  // 1. Đồng hồ thời gian thực
  setInterval(() => {
    const now = new Date();
    document.getElementById('realtime-clock').innerText = now.toLocaleTimeString('vi-VN', {
      hour12: false
    });
  }, 1000);

  // 2. Hàm xuất bảng ra Excel sử dụng SheetJS
  function exportToExcel() {
    let table = document.getElementById("attendanceTable");
    let workbook = XLSX.utils.table_to_book(table, {
      sheet: "ChamCong"
    });
    XLSX.writeFile(workbook, "BangChamCong_Thang" + (new Date().getMonth() + 1) + ".xlsx");
  }

  // 3. Logic Công cụ tính lương (Bộ mô phỏng)
  function updateKpiLabel() {
    document.getElementById('kpi_label').innerText = document.getElementById('calc_kpi').value + " điểm";
  }

  function calculateSalary() {
    const basicSalary = parseFloat(document.getElementById('calc_basic').value) || 0;
    const workDays = parseFloat(document.getElementById('calc_days').value) || 0;
    const kpi = parseInt(document.getElementById('calc_kpi').value) || 0;
    const lateCount = parseInt(document.getElementById('calc_late').value) || 0;

    // Tính lương theo ngày
    const workSalary = (basicSalary / 26) * workDays;

    // Tính KPI
    let kpiAdjustment = 0;
    let kpiTextClass = "text-secondary";
    let kpiSign = "";

    if (kpi >= 90) {
      kpiAdjustment = basicSalary * 0.10; // Thưởng 10%
      kpiTextClass = "text-success";
      kpiSign = "+";
    } else if (kpi < 70) {
      kpiAdjustment = -(basicSalary * 0.05); // Phạt 5%
      kpiTextClass = "text-danger";
    }

    // Phạt đi muộn
    const latePenalty = lateCount * 50000;

    // Tổng lương
    const totalSalary = workSalary + kpiAdjustment - latePenalty;

    // Format tiền tệ
    const formatVND = (num) => Math.round(num).toLocaleString('vi-VN') + " VNĐ";

    // Cập nhật DOM
    document.getElementById('res_work_salary').innerText = formatVND(workSalary);

    const kpiEl = document.getElementById('res_kpi_adj');
    kpiEl.innerText = kpiSign + formatVND(kpiAdjustment);
    kpiEl.className = kpiTextClass;

    document.getElementById('res_late_penalty').innerText = "-" + formatVND(latePenalty);
    document.getElementById('res_total').innerText = formatVND(totalSalary > 0 ? totalSalary : 0);
  }

  // Chạy tính toán lần đầu khi load trang
  calculateSalary();
  </script>
</body>

</html>