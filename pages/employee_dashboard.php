<?php
session_start();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>Employee Dashboard - Creative Agency Hub</title>
  <link href="../public/assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
  <link href="../public/assets/css/sb-admin-2.min.css" rel="stylesheet">`n  <link href="../public/assets/css/agency-theme.css" rel="stylesheet">
</head>
<body id="page-top" class="bg-light">
  <div id="wrapper">
    <?php if (file_exists('../components/sidebar.php')) include('../components/sidebar.php'); ?>

    <div id="content-wrapper" class="d-flex flex-column w-100">
      <div id="content">
        <?php if (file_exists('../components/navbar.php')) include('../components/navbar.php'); ?>

        <div class="container-fluid py-4">
          <div class="d-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800 font-weight-bold">Employee Dashboard</h1>
            <a href="portal.php" class="btn btn-sm btn-outline-primary">Quay ve Portal</a>
          </div>

          <div class="row">
            <div class="col-lg-4 mb-4">
              <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                  <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Cham cong hom nay</div>
                  <div class="h5 mb-0 font-weight-bold text-gray-800">Da check-in</div>
                </div>
              </div>
            </div>
            <div class="col-lg-4 mb-4">
              <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                  <div class="text-xs font-weight-bold text-success text-uppercase mb-1">So ngay phep con</div>
                  <div class="h5 mb-0 font-weight-bold text-gray-800">8</div>
                </div>
              </div>
            </div>
            <div class="col-lg-4 mb-4">
              <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                  <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Luong thang nay</div>
                  <div class="h5 mb-0 font-weight-bold text-gray-800">12,500,000</div>
                </div>
              </div>
            </div>
          </div>

          <div class="row">
            <div class="col-lg-4 mb-4">
              <div class="card shadow">
                <div class="card-header py-3">
                  <h6 class="m-0 font-weight-bold text-primary">Cham cong</h6>
                </div>
                <div class="card-body">
                  <p class="text-muted mb-3">Check-in, check-out va thong ke.</p>
                  <a href="attendance.php" class="btn btn-primary btn-sm">Mo Cham cong</a>
                </div>
              </div>
            </div>
            <div class="col-lg-4 mb-4">
              <div class="card shadow">
                <div class="card-header py-3">
                  <h6 class="m-0 font-weight-bold text-info">Xin nghi phep</h6>
                </div>
                <div class="card-body">
                  <p class="text-muted mb-3">Gui don va theo doi trang thai.</p>
                  <a href="leave_request.php" class="btn btn-info btn-sm">Mo Don nghi</a>
                </div>
              </div>
            </div>
            <div class="col-lg-4 mb-4">
              <div class="card shadow">
                <div class="card-header py-3">
                  <h6 class="m-0 font-weight-bold text-success">Bang luong</h6>
                </div>
                <div class="card-body">
                  <p class="text-muted mb-3">Xem phieu luong ca nhan.</p>
                  <a href="payroll_summary.php" class="btn btn-success btn-sm">Mo Bang luong</a>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <?php if (file_exists('../components/footer.php')) include('../components/footer.php'); ?>
    </div>
  </div>

  <script src="../public/assets/vendor/jquery/jquery.min.js"></script>
  <script src="../public/assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="../public/assets/js/sb-admin-2.min.js"></script>
</body>
</html>
