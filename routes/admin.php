<?php
/**
 * ADMIN WEB ROUTES
 *
 * Admin = quản trị hệ thống web.
 * Admin không tạo project/task trong workflow vận hành thường ngày.
 */

return [
    ['GET', '/admin', function () {
        cah_redirect(APP_URL . '/admin/dashboard');
    }, ['admin']],

    ['GET', '/admin/login', function () {
        cah_redirect(PROJECT_URL . '/app/View/admin/auth/login.php');
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