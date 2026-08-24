<?php
require_once __DIR__ . '/../includes/data.php';
kj_require_role('employer');
$employer = kj_current_user();

$jobId = (int) ($_GET['job_id'] ?? 0);
$job = kj_job($jobId);

if (!$job || $job['employer_id'] !== $employer['id']) {
    header('Location: dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['application_id'], $_POST['status'])) {
    $appId = (int) $_POST['application_id'];
    if (!kj_csrf_valid($_POST['csrf'] ?? null)) {
        $_SESSION['flash'] = 'The request expired. Please try again.';
    } elseif (kj_application_set_status($appId, $jobId, $_POST['status'])) {
        $_SESSION['flash'] = 'Applicant status updated.';
    } else {
        $_SESSION['flash'] = 'No application status change was made.';
    }
    header("Location: applicants.php?job_id=$jobId");
    exit;
}

$apps = kj_applications_for_job($jobId);
usort($apps, fn($a, $b) => strcmp($b['date'], $a['date']));

$keyword = trim($_GET['q'] ?? '');
$education = $_GET['education'] ?? '';
$educationLevels = [];
foreach ($apps as $app) {
    $profile = kj_seeker($app['seeker_id']);
    if ($profile && $profile['education'] !== '') $educationLevels[] = $profile['education'];
}
$educationLevels = array_values(array_unique($educationLevels));
sort($educationLevels);

$apps = array_filter($apps, function ($app) use ($keyword, $education) {
    $profile = kj_seeker($app['seeker_id']);
    if (!$profile) return false;
    if ($education !== '' && $profile['education'] !== $education) return false;
    if ($keyword !== '' && stripos($profile['name'] . ' ' . $profile['skills'] . ' ' . $profile['education'], $keyword) === false) return false;
    return true;
});

$statuses = ['submitted', 'under_review', 'shortlisted', 'hired', 'rejected'];

$pageTitle = 'Applicants — Kinyinya Jobs';
require __DIR__ . '/../includes/header.php';
?>

<section class="pagehead">
  <p class="eyebrow">Employer · <?= htmlspecialchars($job['title']) ?></p>
  <h1>Applicants (<?= count($apps) ?>)</h1>
  <p><a href="dashboard.php">&larr; Back to dashboard</a></p>
</section>

<form method="get" class="card compact-filter">
  <div class="grid-2">
    <div class="field"><label for="q">Search applicant</label><input id="q" name="q" value="<?= htmlspecialchars($keyword) ?>" placeholder="Name or skill"></div>
    <div class="field"><label for="education">Education level</label><select id="education" name="education"><option value="">All education levels</option><?php foreach ($educationLevels as $level): ?><option value="<?= htmlspecialchars($level) ?>" <?= $education === $level ? 'selected' : '' ?>><?= htmlspecialchars($level) ?></option><?php endforeach; ?></select></div>
  </div>
  <button class="btn btn-primary btn-sm" type="submit">Filter applicants</button>
  <a class="btn btn-ghost btn-sm" href="applicants.php?job_id=<?= $jobId ?>">Reset</a>
</form>

<?php if (!empty($_SESSION['flash'])): ?>
  <div class="flash"><?= htmlspecialchars($_SESSION['flash']) ?></div>
  <?php unset($_SESSION['flash']); ?>
<?php endif; ?>

<?php if (empty($apps)): ?>
  <div class="empty">No applicants match the selected filters.</div>
<?php endif; ?>

<?php foreach ($apps as $a): $seeker = kj_seeker($a['seeker_id']); ?>
  <div class="card">
    <div style="display:flex; justify-content:space-between; flex-wrap:wrap; gap:12px;">
      <div>
        <h3><?= htmlspecialchars($seeker['name']) ?></h3>
        <div class="meta">
          <span><?= htmlspecialchars($seeker['skills']) ?></span>
          <span><?= htmlspecialchars($seeker['education']) ?></span>
          <span>Applied <?= htmlspecialchars($a['date']) ?></span>
        </div>
        <p>“<?= htmlspecialchars($a['cover_letter']) ?>”</p>
      </div>
      <div style="min-width:220px;">
        <span class="badge <?= $a['status'] ?>"><?= kj_status_label($a['status']) ?></span>
        <form method="post" style="margin-top:10px;">
          <input type="hidden" name="csrf" value="<?= htmlspecialchars(kj_csrf_token()) ?>">
          <input type="hidden" name="application_id" value="<?= $a['id'] ?>">
          <div class="field">
            <select name="status">
              <?php foreach ($statuses as $s): ?>
                <option value="<?= $s ?>" <?= $s === $a['status'] ? 'selected' : '' ?>><?= kj_status_label($s) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <button class="btn btn-primary btn-sm" type="submit">Update status</button>
        </form>
        <?php if (in_array($a['status'], ['shortlisted', 'hired'], true)): ?>
          <a class="btn btn-ghost btn-sm" href="<?= kj_url('messages.php?application_id=' . $a['id']) ?>">Message applicant</a>
        <?php endif; ?>
      </div>
    </div>
  </div>
<?php endforeach; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
