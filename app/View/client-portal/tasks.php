<?php
session_start();
if (!isset($_SESSION['client_id'])) {
    header("Location: login-client.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Task được chia sẻ - Client Portal</title>
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

        <li class="nav-item active">
            <a class="nav-link" href="tasks.php">
                <i class="fas fa-fw fa-tasks"></i>
                <span>Task được chia sẻ</span>
            </a>
        </li>

        <hr class="sidebar-divider">
        <div class="sidebar-heading">Hỗ trợ</div>

        <li class="nav-item">
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
                <span class="font-weight-bold text-primary">Task được chia sẻ</span>
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
                <h4 class="mb-4">Task được chia sẻ</h4>

                <!-- Bảng task -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Danh sách Task</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead class="thead-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Tên Task</th>
                                        <th>Dự án</th>
                                        <th>Deadline</th>
                                        <th>Trạng thái</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td colspan="5" class="text-center py-4">
                                            <i class="fas fa-tasks fa-2x text-gray-300 mb-2"></i>
                                            <p class="text-muted mb-0">Chưa có task nào được chia sẻ</p>
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
