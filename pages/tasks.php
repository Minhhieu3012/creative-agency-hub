<?php
session_start();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>Task Hub - Creative Agency Hub</title>
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
            <h1 class="h3 mb-0 text-gray-800 font-weight-bold">Task Hub</h1>
            <a href="portal.php" class="btn btn-sm btn-outline-primary">Quay ve Portal</a>
          </div>

          <div class="row">
            <div class="col-lg-6 mb-4">
              <div class="card shadow">
                <div class="card-header py-3">
                  <h6 class="m-0 font-weight-bold text-primary">Kanban Board</h6>
                </div>
                <div class="card-body">
                  <p class="text-muted mb-3">Quan ly task theo trang thai (To do, Doing, Review, Done).</p>
                  <a href="../app/View/tasks/kanban.php" class="btn btn-primary btn-sm">Mo Kanban</a>
                </div>
              </div>
            </div>

            <div class="col-lg-6 mb-4">
              <div class="card shadow">
                <div class="card-header py-3">
                  <h6 class="m-0 font-weight-bold text-success">Gantt Chart</h6>
                </div>
                <div class="card-body">
                  <p class="text-muted mb-3">Theo doi tien do theo bieu do thoi gian.</p>
                  <a href="../app/View/tasks/gantt.php" class="btn btn-success btn-sm">Mo Gantt</a>
                </div>
              </div>
            </div>
          </div>

          <div class="card shadow mb-4">
            <div class="card-header py-3">
              <h6 class="m-0 font-weight-bold text-primary">Task gan day</h6>
            </div>
            <div class="card-body">
              <div class="table-responsive">
                <table class="table table-bordered">
                  <thead class="thead-light">
                    <tr>
                      <th>#</th>
                      <th>Task</th>
                      <th>Du an</th>
                      <th>Deadline</th>
                      <th>Trang thai</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr>
                      <td>1</td>
                      <td>Thiet ke landing page</td>
                      <td>Agency Website</td>
                      <td>15/05/2026</td>
                      <td><span class="badge badge-info">Doing</span></td>
                    </tr>
                    <tr>
                      <td>2</td>
                      <td>Chuan hoa brand guideline</td>
                      <td>Creative Kit</td>
                      <td>20/05/2026</td>
                      <td><span class="badge badge-warning text-dark">Review</span></td>
                    </tr>
                    <tr>
                      <td>3</td>
                      <td>Ban giao UI kit</td>
                      <td>Client A</td>
                      <td>25/05/2026</td>
                      <td><span class="badge badge-success">Done</span></td>
                    </tr>
                  </tbody>
                </table>
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
