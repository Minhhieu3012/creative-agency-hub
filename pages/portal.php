<?php
session_start();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>Internal Portal - Creative Agency Hub</title>
  <link href="../public/assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
  <link href="../public/assets/css/sb-admin-2.min.css" rel="stylesheet">`n  <link href="../public/assets/css/agency-theme.css" rel="stylesheet">
  <style>
    .card-portal {
      border: none;
      border-radius: 1rem;
      box-shadow: 0 0.5rem 1.5rem rgba(0, 0, 0, 0.06);
    }
    .card-portal .icon {
      font-size: 2rem;
    }
  </style>
</head>
<body id="page-top" class="bg-light">
  <div id="wrapper">
    <?php if (file_exists('../components/sidebar.php')) include('../components/sidebar.php'); ?>

    <div id="content-wrapper" class="d-flex flex-column w-100">
      <div id="content">
        <?php if (file_exists('../components/navbar.php')) include('../components/navbar.php'); ?>

        <div class="container-fluid py-4">
          <div class="d-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800 font-weight-bold">Agency Internal Portal</h1>
            <span class="text-muted">Chon vai tro de bat dau</span>
          </div>

          <div class="row">
            <div class="col-lg-3 col-md-6 mb-4">
              <div class="card card-portal h-100">
                <div class="card-body">
                  <div class="d-flex align-items-center mb-3">
                    <div class="icon text-primary mr-3"><i class="fas fa-user-shield"></i></div>
                    <div>
                      <h6 class="font-weight-bold mb-1">Admin</h6>
                      <small class="text-muted">Quan ly to chuc</small>
                    </div>
                  </div>
                  <a href="admin_dashboard.php" class="btn btn-primary btn-sm">Vao Dashboard</a>
                </div>
              </div>
            </div>

            <div class="col-lg-3 col-md-6 mb-4">
              <div class="card card-portal h-100">
                <div class="card-body">
                  <div class="d-flex align-items-center mb-3">
                    <div class="icon text-success mr-3"><i class="fas fa-user-tie"></i></div>
                    <div>
                      <h6 class="font-weight-bold mb-1">Manager</h6>
                      <small class="text-muted">Duyet va giao viec</small>
                    </div>
                  </div>
                  <a href="manager_dashboard.php" class="btn btn-success btn-sm">Vao Dashboard</a>
                </div>
              </div>
            </div>

            <div class="col-lg-3 col-md-6 mb-4">
              <div class="card card-portal h-100">
                <div class="card-body">
                  <div class="d-flex align-items-center mb-3">
                    <div class="icon text-info mr-3"><i class="fas fa-id-badge"></i></div>
                    <div>
                      <h6 class="font-weight-bold mb-1">Employee</h6>
                      <small class="text-muted">Cham cong va don tu</small>
                    </div>
                  </div>
                  <a href="employee_dashboard.php" class="btn btn-info btn-sm">Vao Dashboard</a>
                </div>
              </div>
            </div>

            <div class="col-lg-3 col-md-6 mb-4">
              <div class="card card-portal h-100">
                <div class="card-body">
                  <div class="d-flex align-items-center mb-3">
                    <div class="icon text-warning mr-3"><i class="fas fa-user"></i></div>
                    <div>
                      <h6 class="font-weight-bold mb-1">Client</h6>
                      <small class="text-muted">Theo doi du an</small>
                    </div>
                  </div>
                  <a href="../app/View/client-portal/login-client.php" class="btn btn-warning btn-sm">Vao Client Portal</a>
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
