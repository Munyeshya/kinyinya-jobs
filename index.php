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

<section class="home-hero">
  <div class="home-hero-copy">
    <p class="eyebrow">Kinyinya Sector · Gasabo District</p>
    <h1>Local opportunities.<br><span>Clear applications.</span><br>Better hiring.</h1>
    <p class="hero-lede">A simple recruitment platform where local employers publish trusted vacancies and job seekers apply, follow progress, and stay informed.</p>
    <div class="hero-actions">
      <a class="btn btn-accent btn-lg" href="<?= htmlspecialchars(kj_url('register.php')) ?>">Create your account</a>
      <a class="btn btn-light btn-lg" href="#login">Sign in</a>
    </div>
  </div>

  <div class="hero-device" aria-hidden="true">
    <div class="device-frame">
      <div class="device-camera"></div>
      <div class="device-screen">
        <div class="device-nav"><strong>KJ</strong><span>Jobs</span><span>Applications</span><span>Profiles</span></div>
        <div class="device-content">
          <div class="device-message">
            <small>Local recruitment network</small>
            <strong>Find the right match in Kinyinya.</strong>
            <span>Search · Apply · Track</span>
          </div>
          <div class="candidate-stack">
            <div class="candidate-card candidate-one"><b>AN</b><span><strong>Aline N.</strong><small>Customer service</small></span></div>
            <div class="candidate-card candidate-two"><b>IM</b><span><strong>Ivan M.</strong><small>IT support</small></span></div>
            <div class="candidate-card candidate-three"><b>CK</b><span><strong>Chantal K.</strong><small>Accounting</small></span></div>
          </div>
        </div>
      </div>
    </div>
    <div class="device-base"></div>
  </div>

  <div class="hero-trust-grid" aria-label="Platform benefits">
    <div><span class="trust-symbol">✓</span><p><strong>Reviewed vacancies</strong><small>Jobs become public after administrator approval.</small></p></div>
    <div><span class="trust-symbol">↗</span><p><strong>Broader local reach</strong><small>Employers connect beyond personal networks.</small></p></div>
    <div><span class="trust-symbol">◎</span><p><strong>Transparent progress</strong><small>Applicants can follow every status change.</small></p></div>
  </div>
</section>

<section class="home-benefits" aria-labelledby="why-kinyinya-jobs">
  <div class="section-intro">
    <p class="eyebrow">Built for the local community</p>
    <h2 id="why-kinyinya-jobs">Recruitment without the guesswork</h2>
    <p>The platform replaces scattered notices and word-of-mouth updates with one organized workflow.</p>
  </div>
  <div class="benefit-grid">
    <article class="benefit-card">
      <span class="benefit-icon" aria-hidden="true">01</span>
      <h3>Find relevant work</h3>
      <p>Search approved vacancies by keyword, category, job type, and location.</p>
    </article>
    <article class="benefit-card">
      <span class="benefit-icon" aria-hidden="true">02</span>
      <h3>Hire with structure</h3>
      <p>Publish openings, review profiles and CVs, and manage every applicant from one dashboard.</p>
    </article>
    <article class="benefit-card">
      <span class="benefit-icon" aria-hidden="true">03</span>
      <h3>Know what happens next</h3>
      <p>Follow applications from submission through review, shortlisting, hiring, or rejection.</p>
    </article>
  </div>
</section>

<section class="role-paths" aria-label="Choose how you will use the platform">
  <article class="role-path seeker-path">
    <p class="eyebrow">For job seekers</p>
    <h2>Put your next opportunity within reach.</h2>
    <p>Create a short account, complete your profile when ready, and apply to approved local jobs.</p>
    <ul>
      <li>Upload an optional CV</li>
      <li>Filter vacancies by location</li>
      <li>Track every application status</li>
    </ul>
    <a class="text-link" href="<?= htmlspecialchars(kj_url('register.php')) ?>">Create a seeker account <span>→</span></a>
  </article>
  <article class="role-path employer-path">
    <p class="eyebrow">For employers</p>
    <h2>Reach candidates beyond your network.</h2>
    <p>Share clear vacancies and review applicants through a consistent, organized process.</p>
    <ul>
      <li>Manage open and closed postings</li>
      <li>Filter applicants by skill and education</li>
      <li>Send status updates and messages</li>
    </ul>
    <a class="text-link" href="<?= htmlspecialchars(kj_url('register.php')) ?>">Create an employer account <span>→</span></a>
  </article>
</section>

<section id="login" class="home-login">
  <div class="login-copy">
    <p class="eyebrow">Welcome back</p>
    <h2>Continue where you left off.</h2>
    <p>Sign in to browse jobs, manage applications, publish vacancies, or review platform activity according to your account role.</p>
    <div class="login-note">
      <strong>New to Kinyinya Jobs?</strong>
      <span>Account creation is short. Profile details can be completed after signing in.</span>
    </div>
  </div>

  <div class="login-form-wrap">
    <?php if (!empty($_SESSION['flash'])): ?><div class="flash"><?= htmlspecialchars($_SESSION['flash']); unset($_SESSION['flash']); ?></div><?php endif; ?>
    <?php if (!empty($_SESSION['login_error'])): ?><div class="flash error"><?= htmlspecialchars($_SESSION['login_error']); unset($_SESSION['login_error']); ?></div><?php endif; ?>

    <form method="post" action="<?= htmlspecialchars(kj_url('login.php')) ?>" class="login-card">
      <input type="hidden" name="csrf" value="<?= htmlspecialchars(kj_csrf_token()) ?>">
      <div class="login-card-head">
        <span class="login-mark" aria-hidden="true">KJ</span>
        <div><h3>Sign in</h3><p>Use your registered account.</p></div>
      </div>
      <div class="field"><label for="email">Email address</label><input id="email" name="email" type="email" required autocomplete="email" placeholder="name@example.com"></div>
      <div class="field"><label for="password">Password</label><input id="password" name="password" type="password" required autocomplete="current-password" placeholder="Enter your password"></div>
      <button class="btn btn-primary btn-block" type="submit">Sign in to your account</button>
      <p class="form-switch">No account yet? <a href="<?= htmlspecialchars(kj_url('register.php')) ?>">Create one here</a></p>
    </form>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
