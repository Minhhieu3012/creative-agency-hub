<?php
session_start();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>Quan ly nhan vien - Creative Agency Hub</title>
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
            <h1 class="h3 mb-0 text-gray-800 font-weight-bold">Quan ly nhan vien</h1>
            <div>
              <a href="admin_dashboard.php" class="btn btn-sm btn-outline-primary">Quay ve Admin</a>
              <button class="btn btn-sm btn-primary" data-toggle="modal" data-target="#addEmployeeModal">
                <i class="fas fa-plus mr-1"></i> Them nhan vien
              </button>
            </div>
          </div>

          <div class="card shadow mb-4">
            <div class="card-header py-3">
              <h6 class="m-0 font-weight-bold text-primary">Danh sach nhan vien</h6>
            </div>
            <div class="card-body">
              <div class="table-responsive">
                <table class="table table-bordered">
                  <thead class="thead-light">
                    <tr>
                      <th>#</th>
                      <th>Ho ten</th>
                      <th>Email</th>
                      <th>Phong ban</th>
                      <th>Chuc vu</th>
                      <th>Trang thai</th>
                      <th>Hanh dong</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr>
                      <td>1</td>
                      <td>Nguyen Van A</td>
                      <td>a@agency.com</td>
                      <td>Creative</td>
                      <td>Designer</td>
                      <td><span class="badge badge-success">Active</span></td>
                      <td>
                        <button class="btn btn-sm btn-info">Sua</button>
                        <button class="btn btn-sm btn-danger">Xoa</button>
                      </td>
                    </tr>
                    <tr>
                      <td>2</td>
                      <td>Tran Thi B</td>
                      <td>b@agency.com</td>
                      <td>Account</td>
                      <td>Manager</td>
                      <td><span class="badge badge-secondary">Inactive</span></td>
                      <td>
                        <button class="btn btn-sm btn-info">Sua</button>
                        <button class="btn btn-sm btn-danger">Xoa</button>
                      </td>
                    </tr>
                    <tr>
                      <td>3</td>
                      <td>Le Van C</td>
                      <td>c@agency.com</td>
                      <td>Dev</td>
                      <td>Engineer</td>
                      <td><span class="badge badge-success">Active</span></td>
                      <td>
                        <button class="btn btn-sm btn-info">Sua</button>
                        <button class="btn btn-sm btn-danger">Xoa</button>
                      </td>
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

  <div class="modal fade" id="addEmployeeModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Them nhan vien</h5>
          <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label>Ho ten</label>
            <input type="text" class="form-control" placeholder="Nhap ho ten">
          </div>
          <div class="form-group">
            <label>Email</label>
            <input type="email" class="form-control" placeholder="name@agency.com">
          </div>
          <div class="form-group">
            <label>Phong ban</label>
            <input type="text" class="form-control" placeholder="Creative">
          </div>
          <div class="form-group">
            <label>Chuc vu</label>
            <input type="text" class="form-control" placeholder="Designer">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Dong</button>
          <button type="button" class="btn btn-primary">Luu</button>
        </div>
      </div>
    </div>
  </div>

  <script src="../public/assets/vendor/jquery/jquery.min.js"></script>
  <script src="../public/assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="../public/assets/js/sb-admin-2.min.js"></script>
</body>
</html>
