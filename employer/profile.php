<?php
require_once __DIR__ . '/../includes/data.php';
kj_require_role('employer');

$employer = kj_current_user();
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!kj_csrf_valid($_POST['csrf'] ?? null)) {
        $error = 'The form expired. Refresh the page and try again.';
    } elseif (trim($_POST['name'] ?? '') === '' || trim($_POST['location'] ?? '') === '') {
        $error = 'Company name and location are required.';
    } else {
        kj_employer_update((int) $employer['id'], $_POST);
        $_SESSION['flash'] = 'Company profile updated.';
        header('Location: ' . kj_url('employer/profile.php'));
        exit;
    }
}
$employer = kj_current_user();
$pageTitle = 'Company profile - Kinyinya Jobs';
require __DIR__ . '/../includes/header.php';
?>
<section class="pagehead">
  <p class="eyebrow">Employer profile</p>
  <h1>Company profile</h1>
  <p>Provide basic company information so job seekers know who is recruiting.</p>
</section>
<?php if (!empty($_SESSION['flash'])): ?><div class="flash"><?= htmlspecialchars($_SESSION['flash']); unset($_SESSION['flash']); ?></div><?php endif; ?>
<?php if ($error): ?><div class="flash error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<form method="post" class="card auth-card">
  <input type="hidden" name="csrf" value="<?= htmlspecialchars(kj_csrf_token()) ?>">
  <div class="grid-2">
    <div class="field"><label for="name">Company name</label><input id="name" name="name" required value="<?= htmlspecialchars($employer['name']) ?>"></div>
    <div class="field"><label for="industry">Industry</label><input id="industry" name="industry" value="<?= htmlspecialchars($employer['industry']) ?>"></div>
  </div>
  <div class="field"><label for="location">Location</label><input id="location" name="location" required value="<?= htmlspecialchars($employer['location']) ?>"></div>
  <div class="field"><label for="about">About the company</label><textarea id="about" name="about" placeholder="Briefly describe the organization"><?= htmlspecialchars($employer['about'] ?? '') ?></textarea></div>
  <button class="btn btn-primary" type="submit">Save company profile</button>
</form>
<?php require __DIR__ . '/../includes/footer.php'; ?>
