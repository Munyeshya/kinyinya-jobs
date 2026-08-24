<?php
require_once __DIR__ . '/../includes/data.php';
kj_require_role('seeker');

$seeker = kj_current_user();
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!kj_csrf_valid($_POST['csrf'] ?? null)) {
        $error = 'The form expired. Refresh the page and try again.';
    } elseif (trim($_POST['name'] ?? '') === '' || trim($_POST['location'] ?? '') === '') {
        $error = 'Name and location are required.';
    } else {
        kj_seeker_update((int) $seeker['id'], $_POST);
        $_SESSION['flash'] = 'Your profile was updated.';
        header('Location: ' . kj_url('seeker/profile.php'));
        exit;
    }
}
$seeker = kj_current_user();
$pageTitle = 'My profile - Kinyinya Jobs';
require __DIR__ . '/../includes/header.php';
?>
<section class="pagehead">
  <p class="eyebrow">Job-seeker profile</p>
  <h1>My profile</h1>
  <p>Keep your skills, education, and location current so employers can understand your background.</p>
</section>
<?php if (!empty($_SESSION['flash'])): ?><div class="flash"><?= htmlspecialchars($_SESSION['flash']); unset($_SESSION['flash']); ?></div><?php endif; ?>
<?php if ($error): ?><div class="flash error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<form method="post" class="card auth-card">
  <input type="hidden" name="csrf" value="<?= htmlspecialchars(kj_csrf_token()) ?>">
  <div class="grid-2">
    <div class="field"><label for="name">Full name</label><input id="name" name="name" required value="<?= htmlspecialchars($seeker['name']) ?>"></div>
    <div class="field"><label for="location">Location</label><input id="location" name="location" required value="<?= htmlspecialchars($seeker['location']) ?>"></div>
  </div>
  <div class="field"><label for="education">Education</label><input id="education" name="education" value="<?= htmlspecialchars($seeker['education']) ?>" placeholder="e.g. A2 Accounting"></div>
  <div class="field"><label for="skills">Skills</label><textarea id="skills" name="skills" placeholder="List relevant skills separated by commas"><?= htmlspecialchars($seeker['skills']) ?></textarea></div>
  <button class="btn btn-primary" type="submit">Save profile</button>
</form>
<?php require __DIR__ . '/../includes/footer.php'; ?>
