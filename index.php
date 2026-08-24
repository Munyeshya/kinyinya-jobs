<?php
require_once __DIR__ . '/includes/data.php';

if (isset($_SESSION['role'])) {
    $target = match ($_SESSION['role']) {
        'seeker' => 'seeker/dashboard.php',
        'employer' => 'employer/dashboard.php',
        'admin' => 'admin/dashboard.php',
        default => null,
    };
    if ($target) { header('Location: ' . kj_url($target)); exit; }
}

$pageTitle = 'Kinyinya Jobs - find work, hire locally';
require __DIR__ . '/includes/header.php';
?>

<section class="pagehead">
  <p class="eyebrow">Kinyinya Sector - Gasabo District</p>
  <h1>One place to post a vacancy and one place to find one</h1>
  <p>Kinyinya Jobs replaces fragmented word-of-mouth recruitment with a shared, searchable and transparent local employment platform.</p>
</section>

<div class="purpose-grid">
  <article class="card"><h3>Find opportunities</h3><p>Job seekers discover approved local vacancies instead of depending only on personal contacts and physical notices.</p></article>
  <article class="card"><h3>Recruit efficiently</h3><p>Employers publish vacancies and review applications in one organized place.</p></article>
  <article class="card"><h3>Track progress</h3><p>Applicants see whether an application is submitted, under review, shortlisted, hired, or rejected.</p></article>
</div>

<h2 id="login" class="section-title">Sign in</h2>

<?php if (!empty($_SESSION['flash'])): ?><div class="flash"><?= htmlspecialchars($_SESSION['flash']); unset($_SESSION['flash']); ?></div><?php endif; ?>
<?php if (!empty($_SESSION['login_error'])): ?><div class="flash error"><?= htmlspecialchars($_SESSION['login_error']); unset($_SESSION['login_error']); ?></div><?php endif; ?>

<div class="auth-layout">
  <form method="post" action="<?= htmlspecialchars(kj_url('login.php')) ?>" class="card auth-card">
    <input type="hidden" name="csrf" value="<?= htmlspecialchars(kj_csrf_token()) ?>">
    <div class="field"><label for="email">Email address</label><input id="email" name="email" type="email" required autocomplete="email"></div>
    <div class="field"><label for="password">Password</label><input id="password" name="password" type="password" required autocomplete="current-password"></div>
    <button class="btn btn-primary" type="submit">Sign in</button>
    <a class="btn btn-accent" href="<?= htmlspecialchars(kj_url('register.php')) ?>">Create account</a>
  </form>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
