<?php $baseUrl = '/creative-agency-hub'; ?>
<nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">
    <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3">
        <i class="fa fa-bars"></i>
    </button>

    <span class="font-weight-bold text-primary">Creative Agency Hub</span>

    <ul class="navbar-nav ml-auto">
        <li class="nav-item dropdown no-arrow mx-1">
            <a class="nav-link dropdown-toggle" href="#" id="alertsDropdown" role="button" data-toggle="dropdown">
                <i class="fas fa-bell fa-fw"></i>
                <span class="badge badge-danger badge-counter">3</span>
            </a>
        </li>

        <div class="topbar-divider d-none d-sm-block"></div>

        <li class="nav-item dropdown no-arrow">
            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-toggle="dropdown">
                <span class="mr-2 d-none d-lg-inline text-gray-600">Xin chao, Admin</span>
                <img class="img-profile rounded-circle" width="32" height="32"
                    src="<?= $baseUrl ?>/public/assets/images/undraw_profile.svg" alt="Profile">
            </a>
            <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in">
                <a class="dropdown-item" href="#">
                    <i class="fas fa-user fa-sm fa-fw mr-2 text-gray-400"></i>
                    Ho so ca nhan
                </a>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item" href="<?= $baseUrl ?>/app/View/client-portal/login.php">
                    <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i>
                    Dang xuat
                </a>
            </div>
        </li>
    </ul>
</nav>
