<?php $baseUrl = '/creative-agency-hub'; ?>
<ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">

  <a class="sidebar-brand d-flex align-items-center justify-content-center" href="<?= $baseUrl ?>/pages/portal.php">
    <div class="sidebar-brand-icon">
      <i class="fas fa-building"></i>
    </div>
    <div class="sidebar-brand-text mx-3">Creative Agency</div>
  </a>

  <hr class="sidebar-divider my-0">

  <li class="nav-item active">
    <a class="nav-link" href="<?= $baseUrl ?>/pages/portal.php">
      <i class="fas fa-fw fa-tachometer-alt"></i>
      <span>Tong quan</span>
    </a>
  </li>

  <hr class="sidebar-divider">

  <div class="sidebar-heading">Admin</div>
  <li class="nav-item">
    <a class="nav-link" href="<?= $baseUrl ?>/pages/admin_dashboard.php">
      <i class="fas fa-fw fa-user-shield"></i>
      <span>Dashboard Admin</span>
    </a>
  </li>
  <li class="nav-item">
    <a class="nav-link" href="<?= $baseUrl ?>/pages/employees.php">
      <i class="fas fa-fw fa-users"></i>
      <span>Quan ly nhan vien</span>
    </a>
  </li>

  <hr class="sidebar-divider">

  <div class="sidebar-heading">Manager</div>
  <li class="nav-item">
    <a class="nav-link" href="<?= $baseUrl ?>/pages/manager_dashboard.php">
      <i class="fas fa-fw fa-user-tie"></i>
      <span>Dashboard Manager</span>
    </a>
  </li>
  <li class="nav-item">
    <a class="nav-link" href="<?= $baseUrl ?>/pages/manager_approvals.php">
      <i class="fas fa-fw fa-check-circle"></i>
      <span>Duyet don</span>
    </a>
  </li>

  <hr class="sidebar-divider">

  <div class="sidebar-heading">Employee</div>
  <li class="nav-item">
    <a class="nav-link" href="<?= $baseUrl ?>/pages/employee_dashboard.php">
      <i class="fas fa-fw fa-id-badge"></i>
      <span>Dashboard Nhan vien</span>
    </a>
  </li>
  <li class="nav-item">
    <a class="nav-link" href="<?= $baseUrl ?>/pages/attendance.php">
      <i class="fas fa-fw fa-calendar-check"></i>
      <span>Cham cong</span>
    </a>
  </li>
  <li class="nav-item">
    <a class="nav-link" href="<?= $baseUrl ?>/pages/leave_request.php">
      <i class="fas fa-fw fa-envelope"></i>
      <span>Xin nghi phep</span>
    </a>
  </li>
  <li class="nav-item">
    <a class="nav-link" href="<?= $baseUrl ?>/pages/payroll_summary.php">
      <i class="fas fa-fw fa-coins"></i>
      <span>Bang luong</span>
    </a>
  </li>

  <hr class="sidebar-divider">

  <div class="sidebar-heading">Cong viec</div>
  <li class="nav-item">
    <a class="nav-link" href="<?= $baseUrl ?>/pages/tasks.php">
      <i class="fas fa-fw fa-tasks"></i>
      <span>Task Hub</span>
    </a>
  </li>
  <li class="nav-item">
    <a class="nav-link" href="<?= $baseUrl ?>/app/View/tasks/kanban.php">
      <i class="fas fa-fw fa-columns"></i>
      <span>Kanban Board</span>
    </a>
  </li>
  <li class="nav-item">
    <a class="nav-link" href="<?= $baseUrl ?>/app/View/tasks/gantt.php">
      <i class="fas fa-fw fa-project-diagram"></i>
      <span>Gantt Chart</span>
    </a>
  </li>

  <hr class="sidebar-divider">

  <div class="sidebar-heading">Client</div>
  <li class="nav-item">
    <a class="nav-link" href="<?= $baseUrl ?>/app/View/client-portal/login-client.php">
      <i class="fas fa-fw fa-user"></i>
      <span>Client Portal</span>
    </a>
  </li>

  <hr class="sidebar-divider d-none d-md-block">

  <div class="text-center d-none d-md-inline">
    <button class="rounded-circle border-0" id="sidebarToggle"></button>
  </div>

</ul>