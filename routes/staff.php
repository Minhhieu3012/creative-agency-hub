<?php
/**
 * STAFF WEB ROUTES
 *
 * Staff = Manager + Employee.
 */

return [
    ['GET', '/', function () {
        cah_redirect(APP_URL . '/staff/dashboard');
    }, ['manager', 'employee']],

    ['GET', '/staff', function () {
        cah_redirect(APP_URL . '/staff/dashboard');
    }, ['manager', 'employee']],

    ['GET', '/staff/login', function () {
        cah_redirect(PROJECT_URL . '/app/View/staff/auth/login.php');
    }, null],

    ['GET', '/staff/dashboard', function () {
        cah_redirect(PROJECT_URL . '/app/View/dashboard/index.php?portal=staff');
    }, ['manager', 'employee']],

    ['GET', '/staff/profile', function () {
        cah_redirect(PROJECT_URL . '/app/View/hrm/profile.php');
    }, ['manager', 'employee']],

    ['GET', '/staff/employees', function () {
        cah_redirect(PROJECT_URL . '/app/View/hrm/employees.php');
    }, ['manager']],

    ['GET', '/staff/projects', function () {
        cah_redirect(PROJECT_URL . '/app/View/tasks/projects.php');
    }, ['manager']],

    ['GET', '/staff/kanban', function () {
        cah_redirect(PROJECT_URL . '/app/View/tasks/kanban.php');
    }, ['manager', 'employee']],

    ['GET', '/staff/gantt', function () {
        cah_redirect(PROJECT_URL . '/app/View/tasks/gantt.php');
    }, ['manager']],

    ['GET', '/staff/activity', function () {
        cah_redirect(PROJECT_URL . '/app/View/tasks/activity.php');
    }, ['manager', 'employee']],

    ['GET', '/staff/attendance', function () {
        cah_redirect(PROJECT_URL . '/app/View/payroll/attendance.php');
    }, ['manager', 'employee']],

    ['GET', '/staff/leaves', function () {
        cah_redirect(PROJECT_URL . '/app/View/payroll/leave_request.php');
    }, ['manager', 'employee']],

    ['GET', '/staff/approvals', function () {
        cah_redirect(PROJECT_URL . '/app/View/payroll/manager_approvals.php');
    }, ['manager']],
];