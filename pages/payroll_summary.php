<?php
session_start();
require_once '../config/db_connect.php';
$month = date('m');
$year = date('Y');
?>
<!DOCTYPE html>
<html lang="vi">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>Bảng Lương - Creative Agency Hub</title>
  <link href="../public/assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
  <link href="../public/assets/css/sb-admin-2.min.css" rel="stylesheet">`n  <link href="../public/assets/css/agency-theme.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
  <style>
  body {
    background-color: #f4f7fc;
  }

  .fw-bold {
    font-weight: 700 !important;
  }

  .card-custom {
    border: none;
    border-radius: 1.25rem;
    box-shadow: 0 0.5rem 1.5rem rgba(0, 0, 0, 0.05);
  }

  .bg-gradient-primary {
    background: linear-gradient(45deg, #4e73df, #224abe);
    color: white;
  }

  .text-gradient {
    background: linear-gradient(45deg, #4e73df, #36b9cc);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
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
              <p class="text-muted small mb-3">Cổng thông tin nội bộ - Portal Nhân viên</p>
              <div class="d-flex justify-content-center flex-wrap" style="gap: 10px;">
                <a href="../index.php" class="btn btn-sm btn-dark rounded-pill shadow-sm px-4"><i
                    class="bi bi-house-door me-1"></i> Trang chủ</a>
                <a href="attendance.php" class="btn btn-sm btn-outline-primary rounded-pill bg-white px-4 shadow-sm"><i
                    class="bi bi-clock-history me-1"></i> Chấm công</a>
                <a href="leave_request.php"
                  class="btn btn-sm btn-outline-primary rounded-pill bg-white px-4 shadow-sm"><i
                    class="bi bi-envelope-paper me-1"></i> Xin nghỉ phép</a>
                <a href="payroll_summary.php" class="btn btn-sm btn-primary rounded-pill px-4 shadow-sm"><i
                    class="bi bi-receipt me-1"></i> Bảng lương</a>
              </div>
            </div>
          </div>

          <div
            class="d-flex justify-content-between align-items-center mb-4 bg-white p-3 rounded-pill shadow-sm border px-4">
            <h6 class="fw-bold mb-0 text-gray-800"><i class="bi bi-cash-coin text-success me-2"></i> Phiếu lương cá nhân
              - Tháng <?= $month ?>/<?= $year ?></h6>
            <button onclick="downloadExcel()" class="btn btn-sm btn-success rounded-pill"><i
                class="bi bi-file-earmark-excel me-1"></i> Tải Excel</button>
          </div>

          <div class="row align-items-stretch" id="payrollContent" style="display: none;">
            <div class="col-lg-4 mb-4">
              <div class="card card-custom mb-4 overflow-hidden">
                <div class="bg-gradient-primary p-4 text-center">
                  <img id="empAvatar" src="" class="rounded-circle border border-3 border-white shadow-sm mb-3"
                    alt="Avatar" width="100">
                  <h5 class="fw-bold mb-0" id="empName">Đang tải...</h5>
                  <span class="badge badge-light text-primary mt-2 px-3 py-1 rounded-pill" id="empRole">...</span>
                </div>
                <div class="card-body p-4">
                  <div class="d-flex justify-content-between border-bottom pb-2 mb-2">
                    <span class="text-muted">Lương cơ bản:</span>
                    <strong class="text-success" id="baseSalary">0 ₫</strong>
                  </div>
                  <div class="d-flex justify-content-between border-bottom pb-2 mb-2">
                    <span class="text-muted">Ngày công chuẩn:</span>
                    <strong id="standardDays">24 ngày</strong>
                  </div>
                </div>
              </div>

              <div class="card card-custom border-left-success">
                <div class="card-body p-4 text-center">
                  <h6 class="fw-bold text-success mb-3"><i class="bi bi-graph-up-arrow me-2"></i>Tiến độ KPI Tháng</h6>
                  <h2 class="fw-bold text-dark mb-0" id="kpiPercent">0%</h2>
                  <p class="small text-muted mt-2">Hoàn thành: <strong id="kpiTasks">0/5</strong> công việc đúng hạn</p>
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
                          <th class="py-3 pl-3">Hạng mục tính toán</th>
                          <th class="text-center py-3">Thông số</th>
                          <th class="text-right py-3 pr-3">Thành tiền (VNĐ)</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr>
                          <td class="pl-3 py-3"><i class="bi bi-calendar-check text-primary me-2"></i><strong
                              class="text-dark">Lương ngày công</strong></td>
                          <td class="text-center py-3"><span class="badge badge-primary px-3 py-2 rounded-pill"
                              id="actualDaysBadge">0 / 24 ngày</span></td>
                          <td class="text-right fw-bold py-3 pr-3 text-dark" id="actualSalary">0 ₫</td>
                        </tr>
                        <tr>
                          <td class="pl-3 py-3"><i class="bi bi-award text-success me-2"></i><strong
                              class="text-dark">Thưởng vượt KPI</strong></td>
                          <td class="text-center py-3"><span class="badge badge-success px-3 py-2 rounded-pill"
                              id="bonusBadge">Đạt 0%</span></td>
                          <td class="text-right fw-bold text-success py-3 pr-3" id="bonusValue">+ 0 ₫</td>
                        </tr>
                        <tr class="border-bottom">
                          <td class="pb-4 pl-3 pt-3">
                            <i class="bi bi-exclamation-triangle text-danger me-2"></i><strong class="text-dark">Phạt
                              chuyên cần</strong>
                            <div class="text-muted small mt-1 ml-4" id="penaltyDetail">Đi trễ: 0 | Về sớm: 0</div>
                          </td>
                          <td class="text-center pb-4 pt-3"><span class="badge badge-danger px-3 py-2 rounded-pill"
                              id="penaltyBadge">0 vi phạm</span></td>
                          <td class="text-right fw-bold text-danger pb-4 pt-3 pr-3" id="penaltyValue">- 0 ₫</td>
                        </tr>
                        <tr>
                          <td colspan="2" class="pt-4 text-right">
                            <h5 class="fw-bold mb-0 text-dark">TỔNG THỰC LÃNH:</h5>
                          </td>
                          <td class="pt-4 text-right pr-3">
                            <h3 class="fw-bold text-primary mb-0" id="netSalary">0 ₫</h3>
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div id="loading" class="text-center py-5">
            <div class="spinner-border text-primary" role="status"></div>
            <p class="mt-2 text-muted">Đang tính toán dữ liệu lương...</p>
          </div>

        </div>
      </div>
      <?php if(file_exists('../components/footer.php')) include('../components/footer.php'); ?>
    </div>
  </div>

  <script src="../public/assets/vendor/jquery/jquery.min.js"></script>
  <script src="../public/assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="../public/assets/js/sb-admin-2.min.js"></script>

  <script>
  const token = localStorage.getItem('jwt_token');

  if (!token) {
    alert("Bạn chưa đăng nhập!");
  } else {
    fetch('/creative-agency-hub/public/api/payroll/summary', {
        method: 'GET',
        headers: {
          'Authorization': 'Bearer ' + token
        }
      })
      .then(res => res.json())
      .then(data => {
        if (data.status === 'success' && data.data.length > 0) {
          document.getElementById('loading').style.display = 'none';
          document.getElementById('payrollContent').style.display = 'flex';

          const emp = data.data[0]; // Lấy data của chính nhân viên này

          // Map data to UI
          document.getElementById('empName').textContent = emp.full_name;
          document.getElementById('empAvatar').src = "https://ui-avatars.com/api/?name=" + encodeURIComponent(emp
            .full_name) + "&background=random&size=100";
          document.getElementById('empRole').textContent = emp.role.toUpperCase();
          document.getElementById('baseSalary').textContent = emp.base_salary.toLocaleString() + ' ₫';
          document.getElementById('standardDays').textContent = emp.attendance.standard_days + ' ngày';

          document.getElementById('kpiPercent').textContent = emp.kpi.percent + '%';
          document.getElementById('kpiPercent').className = emp.kpi.percent >= 100 ? 'fw-bold text-success mb-0' :
            'fw-bold text-warning mb-0';
          document.getElementById('kpiTasks').textContent = emp.kpi.completed_tasks + '/' + emp.kpi.target_tasks;

          document.getElementById('actualDaysBadge').textContent = emp.attendance.actual_days + ' / ' + emp.attendance
            .standard_days + ' ngày';
          document.getElementById('actualSalary').textContent = emp.financial.actual_salary.toLocaleString() + ' ₫';

          document.getElementById('bonusBadge').textContent = 'Đạt ' + emp.kpi.percent + '%';
          document.getElementById('bonusValue').textContent = '+ ' + emp.financial.bonus.toLocaleString() + ' ₫';

          document.getElementById('penaltyDetail').textContent = 'Đi trễ: ' + emp.attendance.late + ' | Về sớm: ' +
            emp.attendance.early;
          document.getElementById('penaltyBadge').textContent = (emp.attendance.late + emp.attendance.early) +
            ' vi phạm';
          document.getElementById('penaltyValue').textContent = '- ' + emp.financial.penalty.toLocaleString() + ' ₫';

          document.getElementById('netSalary').textContent = emp.financial.net_salary.toLocaleString() + ' ₫';
        }
      })
      .catch(err => console.error(err));
  }

  function downloadExcel() {
    if (!token) return;
    window.open('/creative-agency-hub/public/api/payroll/export?token=' + token, '_blank');
    // Lưu ý: Trong thực tế Export nên parse Token qua Header hoặc Cookie thay vì URL params. 
    // Nếu dùng Postman bạn chọn Send and Download ở API export là được.
  }
  </script>
</body>

</html>
