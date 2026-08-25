<?php
require_once __DIR__ . '/../includes/data.php';
kj_require_role('seeker');

$seeker = kj_current_user();
$jobId = (int) ($_GET['id'] ?? $_POST['job_id'] ?? 0);
$job = kj_job($jobId);
if (!$job || !kj_job_is_visible($job)) {
    $_SESSION['flash'] = 'That vacancy is no longer available.';
    header('Location: ' . kj_url('seeker/jobs.php'));
    exit;
}
kj_job_record_view($jobId);

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!kj_csrf_valid($_POST['csrf'] ?? null)) {
        $error = 'The form expired. Refresh the page and try again.';
    } elseif (kj_has_applied((int) $seeker['id'], $jobId)) {
        $error = 'You have already applied for this vacancy.';
    } elseif (kj_application_create((int) $seeker['id'], $jobId, trim($_POST['cover_letter'] ?? '')) !== null) {
        $_SESSION['flash'] = 'Your application was submitted successfully.';
        header('Location: ' . kj_url('seeker/dashboard.php'));
        exit;
    } else {
        $error = 'The application could not be submitted. Please try again.';
    }
}

$employer = kj_employer($job['employer_id']);
$alreadyApplied = kj_has_applied((int) $seeker['id'], $jobId);
$pageTitle = $job['title'] . ' - Kinyinya Jobs';
require __DIR__ . '/../includes/header.php';
?>
<section class="pagehead">
  <p class="eyebrow"><?= htmlspecialchars($job['category']) ?> - <?= htmlspecialchars($job['type']) ?></p>
  <h1><?= htmlspecialchars($job['title']) ?></h1>
  <p><?= htmlspecialchars($employer['name']) ?> - <?= htmlspecialchars($job['location']) ?> - Deadline <?= htmlspecialchars($job['deadline']) ?></p>
</section>

<div class="grid-2 job-detail-grid">
  <article class="card">
    <h2 class="section-title">About this job</h2>
    <p><?= nl2br(htmlspecialchars($job['description'])) ?></p>
    <h2 class="section-title">Requirements</h2>
    <p><?= nl2br(htmlspecialchars($job['requirements'])) ?></p>
    <h2 class="section-title">Salary range</h2>
    <p><?= htmlspecialchars(kj_salary_range($job)) ?></p>
  </article>
  <aside>
    <div class="card">
      <h3><?= $alreadyApplied ? 'Application submitted' : 'Apply for this position' ?></h3>
      <?php if ($error): ?><div class="flash error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
      <?php if ($alreadyApplied): ?>
        <p>You already applied for this vacancy. You can track its progress from your dashboard.</p>
        <a class="btn btn-primary" href="dashboard.php">View my applications</a>
      <?php else: ?>
        <p>Review your profile before applying so the employer can see your current skills and education.</p>
        <form method="post">
          <input type="hidden" name="csrf" value="<?= htmlspecialchars(kj_csrf_token()) ?>">
          <input type="hidden" name="job_id" value="<?= $job['id'] ?>">
          <div class="field"><label for="cover_letter">Short cover letter (optional)</label><textarea id="cover_letter" name="cover_letter" placeholder="Briefly explain why you are interested in this role."><?= htmlspecialchars($_POST['cover_letter'] ?? '') ?></textarea></div>
          <button class="btn btn-primary" type="submit">Submit application</button>
          <a class="btn btn-ghost" href="jobs.php">Back to jobs</a>
        </form>
      <?php endif; ?>
    </div>
  </aside>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
