<nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">

    <!-- Sidebar Toggle -->
    <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3">
        <i class="fa fa-bars"></i>
    </button>

    <!-- Tên trang -->
    <span class="font-weight-bold text-primary">Creative Agency Hub</span>

    <!-- Navbar bên phải -->
    <ul class="navbar-nav ml-auto">

        <!-- Thông báo -->
        <li class="nav-item dropdown no-arrow mx-1">
            <a class="nav-link dropdown-toggle" href="#" id="alertsDropdown" 
               role="button" data-toggle="dropdown">
                <i class="fas fa-bell fa-fw"></i>
                <span class="badge badge-danger badge-counter">3</span>
            </a>
        </li>

        <div class="topbar-divider d-none d-sm-block"></div>

        <!-- Thông tin user -->
        <li class="nav-item dropdown no-arrow">
            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" 
               role="button" data-toggle="dropdown">
                <span class="mr-2 d-none d-lg-inline text-gray-600">Xin chào, Admin</span>
                <img class="img-profile rounded-circle" width="32" height="32"
                    src="assets/img/undraw_profile.svg">
            </a>
            <!-- Dropdown user -->
            <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in">
                <a class="dropdown-item" href="#">
                    <i class="fas fa-user fa-sm fa-fw mr-2 text-gray-400"></i>
                    Hồ sơ cá nhân
                </a>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item" href="login.php">
                    <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i>
                    Đăng xuất
                </a>
            </div>
        </li>

    </ul>
</nav>