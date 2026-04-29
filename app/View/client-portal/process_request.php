<?php
session_start();

if (!isset($_SESSION['client_id'])) {
    header("Location: login-client.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: request.php");
    exit();
}

$title = trim($_POST['title'] ?? '');
$type = trim($_POST['type'] ?? '');
$content = trim($_POST['content'] ?? '');

if ($title === '' || $type === '' || $content === '') {
    $_SESSION['error'] = 'Vui lòng nhập đầy đủ thông tin bắt buộc.';
    header("Location: request.php");
    exit();
}

// Demo flow: accept request and show a success message.
$_SESSION['success'] = 'Yêu cầu đã được gửi thành công!';

header("Location: request.php");
exit();
