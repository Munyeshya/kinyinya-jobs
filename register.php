<?php
require_once __DIR__ . '/includes/data.php';

if (isset($_SESSION['role'])) {
    header('Location: ' . kj_url('index.php'));
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!kj_csrf_valid($_POST['csrf'] ?? null)) {
        $error = 'The form expired. Refresh the page and try again.';
    } else {
        [$created, $message] = kj_register($_POST);
        if ($created) {
            $_SESSION['flash'] = $message;
            header('Location: ' . kj_url('index.php') . '#login');
            exit;
        }
        $error = $message;
    }
}

$pageTitle = 'Create account - Kinyinya Jobs';
require __DIR__ . '/includes/header.php';
?>
<section class="pagehead">
  <p class="eyebrow">Join the local employment platform</p>
  <h1>Create an account</h1>
  <p>Create your account first. You can complete your profile details after signing in.</p>
</section>

<?php if ($error): ?><div class="flash error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<form method="post" class="card auth-card">
  <input type="hidden" name="csrf" value="<?= htmlspecialchars(kj_csrf_token()) ?>">
  <div class="field">
    <label for="role">Account type</label>
    <select id="role" name="role" required>
      <option value="seeker" <?= ($_POST['role'] ?? '') === 'seeker' ? 'selected' : '' ?>>Job seeker</option>
      <option value="employer" <?= ($_POST['role'] ?? '') === 'employer' ? 'selected' : '' ?>>Employer</option>
    </select>
  </div>
  <div class="field"><label for="name">Full name or company name</label><input id="name" name="name" required value="<?= htmlspecialchars($_POST['name'] ?? '') ?>"></div>
  <div class="grid-2">
    <div class="field"><label for="email">Email address</label><input id="email" name="email" type="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"></div>
    <div class="field"><label for="password">Password</label><input id="password" name="password" type="password" minlength="6" required><small>At least 6 characters.</small></div>
  </div>
  <button class="btn btn-primary" type="submit">Create account</button>
  <a class="btn btn-ghost" href="<?= htmlspecialchars(kj_url('index.php')) ?>#login">Back to login</a>
</form>
<?php require __DIR__ . '/includes/footer.php'; ?>
