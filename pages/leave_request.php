<?php
session_start();
require_once '../config/db_connect.php';

date_default_timezone_set('Asia/Ho_Chi_Minh');

$_SESSION['user_id'] = 1; 
$user_id = $_SESSION['user_id'];

// 1. LẤY THÔNG TIN QUỸ PHÉP
$stmtUser = $pdo->prepare("SELECT full_name, leave_balance FROM users WHERE id = ?");
$stmtUser->execute([$user_id]);
$user = $stmtUser->fetch();
$leave_balance = $user['leave_balance'] ?? 0;

// --- XỬ LÝ LOGIC ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // A. LUỒNG NHÂN VIÊN: GỬI ĐƠN NGHỈ PHÉP
    if (isset($_POST['submit_leave'])) {
        $start_date = $_POST['start_date'];
        $end_date = $_POST['end_date'];
        $reason = $_POST['reason'];

        // Tính số ngày xin nghỉ
        $days_requested = (strtotime($end_date) - strtotime($start_date)) / 86400 + 1;

        if ($days_requested <= 0) {
            $_SESSION['flash_msg'] = "Ngày kết thúc phải sau ngày bắt đầu!";
            $_SESSION['flash_type'] = "danger";
        } elseif ($days_requested > $leave_balance) {
            $_SESSION['flash_msg'] = "Bạn chỉ còn $leave_balance ngày phép. Không thể xin nghỉ $days_requested ngày!";
            $_SESSION['flash_type'] = "warning";
        } else {
            // Lưu vào DB với trạng thái mặc định là Pending
            $stmt = $pdo->prepare("INSERT INTO leave_requests (user_id, start_date, end_date, reason) VALUES (?, ?, ?, ?)");
            $stmt->execute([$user_id, $start_date, $end_date, $reason]);
            
            $_SESSION['flash_msg'] = "Gửi đơn xin nghỉ $days_requested ngày thành công. Vui lòng chờ duyệt!";
            $_SESSION['flash_type'] = "success";
        }
        header("Location: leave_request.php");
        exit();
    }

    // B. LUỒNG QUẢN LÝ (Chỉ dùng để Test): DUYỆT / TỪ CHỐI ĐƠN
    if (isset($_POST['action_leave'])) {
        $request_id = $_POST['request_id'];
        $action = $_POST['action_leave']; // 'Approved' hoặc 'Rejected'
        $days_to_deduct = $_POST['days_requested'];

        if ($action == 'Approved') {
            try {
                $pdo->beginTransaction();
                // 1. Đổi trạng thái thành Approved
                $pdo->prepare("UPDATE leave_requests SET status = 'Approved' WHERE id = ?")->execute([$request_id]);
                // 2. Trừ quỹ phép của nhân viên
                $pdo->prepare("UPDATE users SET leave_balance = leave_balance - ? WHERE id = ?")->execute([$days_to_deduct, $user_id]);
                $pdo->commit();
                $_SESSION['flash_msg'] = "Đã DUYỆT đơn và trừ quỹ phép thành công!";
                $_SESSION['flash_type'] = "success";
            } catch (Exception $e) {
                $pdo->rollBack();
                $_SESSION['flash_msg'] = "Lỗi khi duyệt đơn: " . $e->getMessage();
                $_SESSION['flash_type'] = "danger";
            }
        } elseif ($action == 'Rejected') {
            // Từ chối thì chỉ đổi trạng thái, không trừ phép
            $pdo->prepare("UPDATE leave_requests SET status = 'Rejected' WHERE id = ?")->execute([$request_id]);
            $_SESSION['flash_msg'] = "Đã TỪ CHỐI đơn nghỉ phép!";
            $_SESSION['flash_type'] = "danger";
        }
        header("Location: leave_request.php");
        exit();
    }
}

// 3. LẤY LỊCH SỬ ĐƠN NGHỈ PHÉP CỦA NHÂN VIÊN
$stmtRequests = $pdo->prepare("SELECT * FROM leave_requests WHERE user_id = ? ORDER BY created_at DESC");
$stmtRequests->execute([$user_id]);
$requests = $stmtRequests->fetchAll();
?>

<!DOCTYPE html>
<html lang="vi">

<head>
  <meta charset="UTF-8">
  <title>Xin Nghỉ Phép - Creative Agency Hub</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
  <style>
  body {
    background-color: #f4f7f6;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  }

  .card-custom {
    border: none;
    border-radius: 1rem;
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.08);
  }

  .text-gradient {
    background: -webkit-linear-gradient(45deg, #0d6efd, #6610f2);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
  }
  </style>
</head>

<body class="py-5">

  <div class="container">
    <div class="row mb-4">
      <div class="col-12 text-center">
        <h2 class="fw-bold text-gradient">Creative Agency Hub</h2>
        <p class="text-muted mb-3">Cổng thông tin nội bộ - Quản lý nhân sự</p>
        <div class="d-flex justify-content-center gap-3">
          <a href="attendance.php" class="btn btn-outline-primary rounded-pill shadow-sm bg-white"><i
              class="bi bi-clock-history me-1"></i> Chấm công</a>
          <a href="leave_request.php" class="btn btn-primary rounded-pill shadow-sm"><i
              class="bi bi-envelope-paper me-1"></i> Xin nghỉ phép</a>
          <a href="payroll_summary.php" class="btn btn-outline-primary rounded-pill shadow-sm bg-white"><i
              class="bi bi-receipt me-1"></i> Bảng lương</a>
        </div>
      </div>
    </div>

    <?php if (isset($_SESSION['flash_msg'])): ?>
    <div class="alert alert-<?= $_SESSION['flash_type'] ?> alert-dismissible fade show rounded-4 text-center"
      role="alert">
      <?= $_SESSION['flash_msg'] ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['flash_msg']); unset($_SESSION['flash_type']); endif; ?>

    <div class="row g-4">
      <div class="col-lg-4">
        <div class="card card-custom h-100">
          <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
              <h5 class="fw-bold text-primary mb-0"><i class="bi bi-pencil-square me-2"></i>Tạo đơn mới</h5>
              <span class="badge bg-success fs-6 rounded-pill">Quỹ phép: <?= $leave_balance ?> ngày</span>
            </div>

            <form method="POST">
              <div class="mb-3">
                <label class="form-label fw-bold text-muted">Từ ngày:</label>
                <input type="date" name="start_date" class="form-control" required min="<?= date('Y-m-d') ?>">
              </div>
              <div class="mb-3">
                <label class="form-label fw-bold text-muted">Đến ngày:</label>
                <input type="date" name="end_date" class="form-control" required min="<?= date('Y-m-d') ?>">
              </div>
              <div class="mb-4">
                <label class="form-label fw-bold text-muted">Lý do nghỉ phép:</label>
                <textarea name="reason" class="form-control" rows="3" required
                  placeholder="Nhập chi tiết lý do..."></textarea>
              </div>
              <button type="submit" name="submit_leave" class="btn btn-primary w-100 rounded-pill py-2 fw-bold"
                <?= $leave_balance <= 0 ? 'disabled' : '' ?>>
                <i class="bi bi-send me-1"></i> GỬI YÊU CẦU
              </button>
            </form>
          </div>
        </div>
      </div>

      <div class="col-lg-8">
        <div class="card card-custom h-100">
          <div class="card-header bg-white p-4 border-bottom">
            <h5 class="fw-bold mb-0"><i class="bi bi-list-check me-2 text-primary"></i>Lịch sử nghỉ phép của bạn</h5>
          </div>
          <div class="card-body p-4">
            <div class="table-responsive">
              <table class="table table-hover align-middle">
                <thead class="table-light">
                  <tr>
                    <th>Thời gian nghỉ</th>
                    <th>Lý do</th>
                    <th class="text-center">Số ngày</th>
                    <th class="text-center">Trạng thái</th>
                    <th class="text-end border-start border-warning bg-warning bg-opacity-10">Tác vụ Manager (Test)</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if(empty($requests)): ?>
                  <tr>
                    <td colspan="5" class="text-center text-muted py-4">Chưa có dữ liệu nghỉ phép.</td>
                  </tr>
                  <?php else: ?>
                  <?php foreach($requests as $req): 
                                        $d = (strtotime($req['end_date']) - strtotime($req['start_date'])) / 86400 + 1;
                                    ?>
                  <tr>
                    <td>
                      <strong><?= date('d/m/Y', strtotime($req['start_date'])) ?></strong> <br>
                      <small class="text-muted">đến <?= date('d/m/Y', strtotime($req['end_date'])) ?></small>
                    </td>
                    <td><?= htmlspecialchars($req['reason']) ?></td>
                    <td class="text-center"><span class="badge bg-secondary"><?= $d ?> ngày</span></td>
                    <td class="text-center">
                      <?php if($req['status'] == 'Pending'): ?>
                      <span class="badge bg-warning text-dark rounded-pill"><i class="bi bi-hourglass-split"></i> Chờ
                        duyệt</span>
                      <?php elseif($req['status'] == 'Approved'): ?>
                      <span class="badge bg-success rounded-pill"><i class="bi bi-check-circle"></i> Đã duyệt</span>
                      <?php else: ?>
                      <span class="badge bg-danger rounded-pill"><i class="bi bi-x-circle"></i> Từ chối</span>
                      <?php endif; ?>
                    </td>

                    <td class="text-end border-start border-warning bg-warning bg-opacity-10">
                      <?php if($req['status'] == 'Pending'): ?>
                      <form method="POST" class="d-inline-flex gap-1">
                        <input type="hidden" name="request_id" value="<?= $req['id'] ?>">
                        <input type="hidden" name="days_requested" value="<?= $d ?>">
                        <button type="submit" name="action_leave" value="Approved" class="btn btn-sm btn-success"
                          title="Duyệt"><i class="bi bi-check"></i></button>
                        <button type="submit" name="action_leave" value="Rejected" class="btn btn-sm btn-danger"
                          title="Từ chối"><i class="bi bi-x"></i></button>
                      </form>
                      <?php else: ?>
                      <span class="text-muted small">Đã xử lý</span>
                      <?php endif; ?>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>