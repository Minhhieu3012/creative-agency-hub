<?php
/**
 * ADMIN WEB ROUTES
 * Đợt 1 chỉ tách URL luồng admin, chưa move view vật lý.
 * Đợt sau sẽ tạo app/View/admin riêng.
 */

return [
    ['GET', '/admin', function () {
        cah_redirect(APP_URL . '/admin/dashboard');
    }, ['admin']],

    ['GET', '/admin/login', function () {
        cah_redirect(PROJECT_URL . '/app/View/auth/login.php?portal=admin');
    }, null],

    ['GET', '/admin/dashboard', function () {
        cah_redirect(PROJECT_URL . '/app/View/dashboard/index.php?portal=admin');
    }, ['admin']],

    ['GET', '/admin/accounts', function () {
        cah_redirect(PROJECT_URL . '/app/View/hrm/employees.php?portal=admin');
    }, ['admin']],

    ['GET', '/admin/organization', function () {
        cah_redirect(PROJECT_URL . '/app/View/hrm/departments.php?portal=admin');
    }, ['admin']],
];