<?php
if (!isset($_SESSION)) { require_once __DIR__ . '/data.php'; }
$role = $_SESSION['role'] ?? null;
$user = kj_current_user();

function kj_base($path) {
    return kj_url($path);
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= $pageTitle ?? 'Kinyinya Jobs' ?></title>
<link rel="stylesheet" href="<?= kj_base('assets/style.css') ?>">
</head>
<body>
<header class="topbar">
  <div class="wrap">
    <a class="brand" href="<?= kj_base('index.php') ?>">Kinyinya Jobs <small>Gasabo · Kigali</small></a>
    <nav class="topnav">
      <?php if ($role === 'seeker'): ?>
        <a href="<?= kj_base('seeker/dashboard.php') ?>">My applications</a>
        <a href="<?= kj_base('seeker/jobs.php') ?>">Browse jobs</a>
        <a href="<?= kj_base('seeker/profile.php') ?>">My profile</a>
        <span class="who">Signed in as <?= htmlspecialchars($user['name']) ?></span>
        <a class="pill" href="<?= kj_base('logout.php') ?>">Log out</a>
      <?php elseif ($role === 'employer'): ?>
        <a href="<?= kj_base('employer/dashboard.php') ?>">Dashboard</a>
        <a href="<?= kj_base('employer/post-job.php') ?>">Post a job</a>
        <a href="<?= kj_base('employer/profile.php') ?>">Company profile</a>
        <span class="who">Signed in as <?= htmlspecialchars($user['name']) ?></span>
        <a class="pill" href="<?= kj_base('logout.php') ?>">Log out</a>
      <?php elseif ($role === 'admin'): ?>
        <a href="<?= kj_base('admin/dashboard.php') ?>">Overview</a>
        <span class="who">Platform Admin</span>
        <a class="pill" href="<?= kj_base('logout.php') ?>">Log out</a>
      <?php else: ?>
        <a href="<?= kj_base('index.php') ?>#login">Log in</a>
        <a class="pill" href="<?= kj_base('register.php') ?>">Create account</a>
      <?php endif; ?>
    </nav>
  </div>
</header>
<main class="wrap">
