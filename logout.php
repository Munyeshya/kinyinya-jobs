<?php
require_once __DIR__ . '/includes/config.php';
session_start();
session_unset();
session_destroy();
if (ini_get('session.use_cookies')) {
    $cookie = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $cookie['path'], $cookie['domain'], $cookie['secure'], $cookie['httponly']);
}
header('Location: ' . kj_url('index.php'));
exit;
