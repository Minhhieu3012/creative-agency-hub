<?php
session_start();
if (!isset($_SESSION['client_id'])) {
    header("Location: login-client.php");
    exit();
}

$success = $_SESSION['success'] ?? '';
$error   = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Gửi yêu cầu - Client Portal</title>
    <link href="../../../public/assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="../../../public/assets/css/sb-admin-2.css" rel="stylesheet">
</head>
<body id="page-top">
<div id="wrapper">

    <!-- Sidebar -->
    <ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">
        <a class="sidebar-brand d-flex align-items-center justify-content-center" href="index.php">
            <div class="sidebar-brand-icon">
                <i class="fas fa-building"></i>
            </div>
            <div class="sidebar-brand-text mx-3">Client Portal</div>
        </a>
        <hr class="sidebar-divider my-0">

        <li class="nav-item">
            <a class="nav-link" href="index.php">
                <i class="fas fa-fw fa-tachometer-alt"></i>
                <span>Dashboard</span>
            </a>
        </li>

        <hr class="sidebar-divider">
        <div class="sidebar-heading">Dự án</div>

        <li class="nav-item">
            <a class="nav-link" href="projects.php">
                <i class="fas fa-fw fa-project-diagram"></i>
                <span>Tiến độ dự án</span>
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link" href="tasks.php">
                <i class="fas fa-fw fa-tasks"></i>
                <span>Task được chia sẻ</span>
            </a>
        </li>

        <hr class="sidebar-divider">
        <div class="sidebar-heading">Hỗ trợ</div>

        <li class="nav-item active">
            <a class="nav-link" href="request.php">
                <i class="fas fa-fw fa-paper-plane"></i>
                <span>Gửi yêu cầu</span>
            </a>
        </li>

        <hr class="sidebar-divider d-none d-md-block">
        <div class="text-center d-none d-md-inline">
            <button class="rounded-circle border-0" id="sidebarToggle"></button>
        </div>
    </ul>

    <!-- Content Wrapper -->
    <div id="content-wrapper" class="d-flex flex-column">
        <div id="content">

            <!-- Navbar -->
            <nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">
                <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3">
                    <i class="fa fa-bars"></i>
                </button>
                <span class="font-weight-bold text-primary">Gửi yêu cầu</span>
                <ul class="navbar-nav ml-auto">
                    <li class="nav-item dropdown no-arrow">
                        <a class="nav-link dropdown-toggle" href="#" data-toggle="dropdown">
                            <span class="mr-2 d-none d-lg-inline text-gray-600">
                                <?= htmlspecialchars($_SESSION['client_name']) ?>
                            </span>
                            <i class="fas fa-user-circle fa-fw"></i>
                        </a>
                        <div class="dropdown-menu dropdown-menu-right shadow">
                            <a class="dropdown-item" href="logout.php">
                                <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i>
                                Đăng xuất
                            </a>
                        </div>
                    </li>
                </ul>
            </nav>

            <!-- Page Content -->
            <div class="container-fluid">
                <h4 class="mb-4">Gửi yêu cầu</h4>

                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> <?= $success ?>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i> <?= $error ?>
                    </div>
                <?php endif; ?>

                <!-- Form gửi yêu cầu -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-paper-plane mr-1"></i> Nội dung yêu cầu
                        </h6>
                    </div>
                    <div class="card-body">
                        <form action="process_request.php" method="POST">

                            <div class="form-group">
                                <label>Tiêu đề yêu cầu <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" 
                                       name="title" placeholder="Nhập tiêu đề..." required>
                            </div>

                            <div class="form-group">
                                <label>Loại yêu cầu <span class="text-danger">*</span></label>
                                <select class="form-control" name="type" required>
                                    <option value="">-- Chọn loại --</option>
                                    <option value="update">Cập nhật dự án</option>
                                    <option value="bug">Báo lỗi</option>
                                    <option value="feature">Yêu cầu tính năng mới</option>
                                    <option value="other">Khác</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>Nội dung chi tiết <span class="text-danger">*</span></label>
                                <textarea class="form-control" name="content" 
                                          rows="5" placeholder="Mô tả chi tiết yêu cầu..." 
                                          required></textarea>
                            </div>

                            <div class="text-right">
                                <button type="reset" class="btn btn-secondary mr-2">
                                    <i class="fas fa-times"></i> Xóa trắng
                                </button>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-paper-plane"></i> Gửi yêu cầu
                                </button>
                            </div>

                        </form>
                    </div>
                </div>

                <!-- Lịch sử yêu cầu -->
                <div class="card shadow">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-history mr-1"></i> Yêu cầu đã gửi
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead class="thead-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Tiêu đề</th>
                                        <th>Loại</th>
                                        <th>Ngày gửi</th>
                                        <th>Trạng thái</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td colspan="5" class="text-center py-4">
                                            <i class="fas fa-inbox fa-2x text-gray-300 mb-2"></i>
                                            <p class="text-muted mb-0">Chưa có yêu cầu nào</p>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <footer class="sticky-footer bg-white">
            <div class="container my-auto">
                <div class="copyright text-center my-auto">
                    <span>Creative Agency Hub &copy; 2024 - Nhóm 08</span>
                </div>
            </div>
        </footer>
    </div>

</div>

<script src="../../../public/assets/vendor/jquery/jquery.min.js"></script>
<script src="../../../public/assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../../../public/assets/js/sb-admin-2.min.js"></script>
</body>
</html>
