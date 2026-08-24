<?php
require_once __DIR__ . '/includes/data.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !kj_csrf_valid($_POST['csrf'] ?? null)) {
    header('Location: ' . kj_url('index.php') . '#login');
    exit;
}

if (kj_login($_POST['email'] ?? '', $_POST['password'] ?? '')) {
    $target = match ($_SESSION['role']) {
        'seeker' => 'seeker/dashboard.php',
        'employer' => 'employer/dashboard.php',
        'admin' => 'admin/dashboard.php',
    };
    header('Location: ' . kj_url($target));
    exit;
}

$_SESSION['login_error'] = 'Incorrect email address or password.';
header('Location: ' . kj_url('index.php') . '#login');
exit;
