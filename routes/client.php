<?php
/**
 * CLIENT WEB ROUTES
 *
 * Client chỉ đi qua Client Portal.
 */

return [
    ['GET', '/client', function () {
        cah_redirect(APP_URL . '/client/projects');
    }, ['client']],

    ['GET', '/client/login', function () {
        cah_redirect(PROJECT_URL . '/app/View/client-portal/login-client.php');
    }, null],

    ['GET', '/client/projects', function () {
        cah_redirect(PROJECT_URL . '/app/View/client-portal/projects.php');
    }, ['client']],

    ['GET', '/client/tasks', function () {
        cah_redirect(PROJECT_URL . '/app/View/client-portal/tasks.php');
    }, ['client']],

    ['GET', '/client/support', function () {
        cah_redirect(PROJECT_URL . '/app/View/client-portal/support.php');
    }, ['client']],
];