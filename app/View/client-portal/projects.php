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
    <title>Tiến độ dự án - Client Portal</title>
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

        <li class="nav-item active">
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
                <span class="font-weight-bold text-primary">Tiến độ dự án</span>
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
                <h4 class="mb-4">Tiến độ dự án</h4>

                <!-- Ví dụ 1 dự án mẫu -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold text-primary">
                            Danh sách dự án
                        </h6>
                    </div>
                    <div class="card-body">

                        <!-- Chưa có dự án -->
                        <div class="text-center py-5">
                            <i class="fas fa-project-diagram fa-3x text-gray-300 mb-3"></i>
                            <p class="text-muted">Chưa có dự án nào được giao.</p>
                            <a href="request.php" class="btn btn-primary btn-sm">
                                <i class="fas fa-paper-plane mr-1"></i> Gửi yêu cầu
                            </a>
                        </div>

                        <!-- 
                            Khi có dữ liệu từ database thì hiện như này:
                            
                            Tên dự án: Website bán hàng
                            Trạng thái: Đang thực hiện
                            Deadline: 30/06/2024
                            Tiến độ: 60%
                        -->

                    </div>
                </div>

                <!-- Giải thích cấu trúc khi có dữ liệu -->
                <div class="card shadow mb-4 border-left-info">
                    <div class="card-body">
                        <p class="font-weight-bold text-info mb-2">
                            <i class="fas fa-info-circle mr-1"></i> 
                            Ví dụ giao diện khi có dự án:
                        </p>

                        <!-- Dự án mẫu 1 -->
                        <div class="card mb-3 border-left-primary">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h6 class="font-weight-bold mb-0">Website bán hàng</h6>
                                    <span class="badge badge-primary">Đang thực hiện</span>
                                </div>
                                <div class="row mb-2">
                                    <div class="col-6">
                                        <small class="text-muted">
                                            <i class="fas fa-calendar mr-1"></i> 
                                            Deadline: 30/06/2024
                                        </small>
                                    </div>
                                    <div class="col-6 text-right">
                                        <small class="text-muted">Tiến độ: 60%</small>
                                    </div>
                                </div>
                                <div class="progress" style="height: 10px;">
                                    <div class="progress-bar bg-primary" style="width: 60%"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Dự án mẫu 2 -->
                        <div class="card mb-3 border-left-success">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h6 class="font-weight-bold mb-0">App đặt hàng</h6>
                                    <span class="badge badge-success">Hoàn thành</span>
                                </div>
                                <div class="row mb-2">
                                    <div class="col-6">
                                        <small class="text-muted">
                                            <i class="fas fa-calendar mr-1"></i> 
                                            Deadline: 15/03/2024
                                        </small>
                                    </div>
                                    <div class="col-6 text-right">
                                        <small class="text-muted">Tiến độ: 100%</small>
                                    </div>
                                </div>
                                <div class="progress" style="height: 10px;">
                                    <div class="progress-bar bg-success" style="width: 100%"></div>
                                </div>
                            </div>
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