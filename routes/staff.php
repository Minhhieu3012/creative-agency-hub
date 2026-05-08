<?php
/**
 * STAFF WEB ROUTES
 * Staff = Manager + Employee.
 * Manager vận hành project/task.
 * Employee làm task/chấm công/nghỉ phép.
 */

return [
    ['GET', '/', function () {
        cah_redirect(APP_URL . '/staff/dashboard');
    }, ['manager', 'employee']],

    ['GET', '/staff', function () {
        cah_redirect(APP_URL . '/staff/dashboard');
    }, ['manager', 'employee']],

    ['GET', '/staff/login', function () {
        cah_redirect(PROJECT_URL . '/app/View/auth/login.php?portal=staff');
    }, null],

    ['GET', '/staff/dashboard', function () {
        cah_redirect(PROJECT_URL . '/app/View/dashboard/index.php?portal=staff');
    }, ['manager', 'employee']],

    ['GET', '/staff/profile', function () {
        cah_redirect(PROJECT_URL . '/app/View/hrm/profile.php');
    }, ['manager', 'employee']],

    ['GET', '/staff/projects', function () {
        cah_redirect(PROJECT_URL . '/app/View/tasks/projects.php');
    }, ['manager']],

    ['GET', '/staff/kanban', function () {
        cah_redirect(PROJECT_URL . '/app/View/tasks/kanban.php');
    }, ['manager', 'employee']],

    ['GET', '/staff/gantt', function () {
        cah_redirect(PROJECT_URL . '/app/View/tasks/gantt.php');
    }, ['manager']],

    ['GET', '/staff/attendance', function () {
        cah_redirect(PROJECT_URL . '/app/View/payroll/attendance.php');
    }, ['manager', 'employee']],

    ['GET', '/staff/leaves', function () {
        cah_redirect(PROJECT_URL . '/app/View/payroll/leave_request.php');
    }, ['manager', 'employee']],

    ['GET', '/staff/payroll', function () {
        cah_redirect(PROJECT_URL . '/app/View/payroll/payroll_summary.php');
    }, ['manager']],
];