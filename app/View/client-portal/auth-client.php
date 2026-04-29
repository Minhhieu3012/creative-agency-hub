<?php
session_start();

// Dang nhap demo de test nhanh, se thay bang luồng thật sau.
$email = trim($_POST['email'] ?? '');
$password = trim($_POST['password'] ?? '');

if ($email === '' || $password === '') {
    header("Location: login-client.php");
    exit();
}

$_SESSION['client_id'] = 1;

$namePart = explode('@', $email)[0] ?? '';
$namePart = str_replace(['.', '_'], ' ', $namePart);
$namePart = trim($namePart);
$_SESSION['client_name'] = $namePart !== '' ? ucwords($namePart) : 'Client Demo';
$_SESSION['client_email'] = $email;

header("Location: index.php");
exit();
