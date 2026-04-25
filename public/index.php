<?php
// Bật hiển thị lỗi để dễ debug trong môi trường dev
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Nạp các thư viện từ Composer (bao gồm phpdotenv)
require_once __DIR__ . '/../vendor/autoload.php';

// Khởi tạo và nạp biến môi trường từ file .env ở thư mục gốc
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

// Test thử xem hệ thống đã đọc được file .env chưa
// echo "<h1>Hệ thống đã chạy!</h1>";
// echo "<h3>Chào mừng đến với dự án: " . $_ENV['APP_NAME'] . "</h3>";
// echo "<p>Môi trường hiện tại: " . $_ENV['APP_ENV'] . "</p>";