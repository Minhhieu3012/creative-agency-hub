<?php
session_start();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>Admin Dashboard - Creative Agency Hub</title>
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
            <h1 class="h3 mb-0 text-gray-800 font-weight-bold">Admin Dashboard</h1>
            <a href="portal.php" class="btn btn-sm btn-outline-primary">Quay ve Portal</a>
          </div>

          <div class="row">
            <div class="col-lg-3 col-md-6 mb-4">
              <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                  <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Nhan su</div>
                  <div class="h5 mb-0 font-weight-bold text-gray-800">32</div>
                </div>
              </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-4">
              <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                  <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Du an</div>
                  <div class="h5 mb-0 font-weight-bold text-gray-800">8</div>
                </div>
              </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-4">
              <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                  <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Task</div>
                  <div class="h5 mb-0 font-weight-bold text-gray-800">124</div>
                </div>
              </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-4">
              <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                  <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Don can duyet</div>
                  <div class="h5 mb-0 font-weight-bold text-gray-800">5</div>
                </div>
              </div>
            </div>
          </div>

          <div class="row">
            <div class="col-lg-6 mb-4">
              <div class="card shadow">
                <div class="card-header py-3">
                  <h6 class="m-0 font-weight-bold text-primary">Quan ly nhan su</h6>
                </div>
                <div class="card-body">
                  <p class="text-muted mb-3">Xem danh sach nhan vien va quan ly ho so.</p>
                  <a href="employees.php" class="btn btn-primary btn-sm">Mo danh sach nhan vien</a>
                </div>
              </div>
            </div>

            <div class="col-lg-6 mb-4">
              <div class="card shadow">
                <div class="card-header py-3">
                  <h6 class="m-0 font-weight-bold text-primary">Cong viec & du an</h6>
                </div>
                <div class="card-body">
                  <p class="text-muted mb-3">Quan ly task qua Kanban va Gantt.</p>
                  <a href="tasks.php" class="btn btn-info btn-sm">Mo Task Hub</a>
                </div>
              </div>
            </div>

            <div class="col-lg-6 mb-4">
              <div class="card shadow">
                <div class="card-header py-3">
                  <h6 class="m-0 font-weight-bold text-primary">Duyet don</h6>
                </div>
                <div class="card-body">
                  <p class="text-muted mb-3">Kiem soat don nghi va yeu cau.</p>
                  <a href="manager_approvals.php" class="btn btn-success btn-sm">Mo trang duyet</a>
                </div>
              </div>
            </div>

            <div class="col-lg-6 mb-4">
              <div class="card shadow">
                <div class="card-header py-3">
                  <h6 class="m-0 font-weight-bold text-primary">Client Portal</h6>
                </div>
                <div class="card-body">
                  <p class="text-muted mb-3">Theo doi giao dien danh cho khach hang.</p>
                  <a href="../app/View/client-portal/login-client.php" class="btn btn-warning btn-sm">Mo Client Portal</a>
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
