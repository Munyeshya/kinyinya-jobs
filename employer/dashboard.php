<?php
require_once __DIR__ . '/../includes/data.php';
kj_require_role('employer');

$employer = kj_current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['job_id'], $_POST['job_action'])) {
    if (!kj_csrf_valid($_POST['csrf'] ?? null)) {
        $_SESSION['flash'] = 'The request expired. Please try again.';
    } else {
        $active = $_POST['job_action'] === 'reopen';
        if (kj_job_set_active_for_employer((int) $_POST['job_id'], (int) $employer['id'], $active)) {
            $_SESSION['flash'] = $active ? 'Job posting reopened.' : 'Job posting closed.';
        } else {
            $_SESSION['flash'] = 'That job could not be updated. Expired postings cannot be reopened.';
        }
    }
    header('Location: dashboard.php');
    exit;
}

$myJobs = kj_jobs_for_employer($employer['id']);
$notifications = kj_notifications_for_user((int) ($_SESSION['account_id'] ?? 0));
kj_mark_notifications_read((int) ($_SESSION['account_id'] ?? 0));
usort($myJobs, fn($a, $b) => strcmp($b['posted'], $a['posted']));

$totalApps = 0; $newApps = 0; $liveJobs = 0;
foreach ($myJobs as $j) {
    $apps = kj_applications_for_job($j['id']);
    $totalApps += count($apps);
    foreach ($apps as $a) { if ($a['status'] === 'submitted') $newApps++; }
    if (kj_job_is_visible($j)) $liveJobs++;
}

$pageTitle = 'Employer dashboard — Kinyinya Jobs';
require __DIR__ . '/../includes/header.php';
?>

<section class="pagehead">
  <p class="eyebrow">Employer dashboard</p>
  <h1><?= htmlspecialchars($employer['name']) ?></h1>
  <p><?= htmlspecialchars($employer['industry']) ?> · <?= htmlspecialchars($employer['location']) ?></p>
</section>

<?php if (!empty($_SESSION['flash'])): ?>
  <div class="flash"><?= htmlspecialchars($_SESSION['flash']) ?></div>
  <?php unset($_SESSION['flash']); ?>
<?php endif; ?>

<div class="stats">
  <div class="stat"><span class="n"><?= count($myJobs) ?></span><span class="l">Total postings</span></div>
  <div class="stat accent"><span class="n"><?= $liveJobs ?></span><span class="l">Live &amp; visible to seekers</span></div>
  <div class="stat"><span class="n"><?= $totalApps ?></span><span class="l">Total applications</span></div>
  <div class="stat warn"><span class="n"><?= $newApps ?></span><span class="l">New, unreviewed</span></div>
</div>

<div style="margin-bottom:20px;">
  <a class="btn btn-primary" href="post-job.php">Post a new job</a>
  <a class="btn btn-ghost" href="profile.php">Update company profile</a>
</div>

<h2 class="section-title">Notifications</h2>
<?php if (empty($notifications)): ?>
  <div class="empty">You have no notifications yet.</div>
<?php else: ?>
  <div class="card notification-list">
    <?php foreach ($notifications as $notification): ?>
      <div class="notification-item <?= $notification['is_read'] ? '' : 'unread' ?>">
        <span><?= htmlspecialchars($notification['message']) ?></span>
        <small><?= htmlspecialchars($notification['created_at']) ?></small>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<h2 class="section-title">Your job postings</h2>

<?php if (empty($myJobs)): ?>
  <div class="empty">You haven't posted any jobs yet. <a href="post-job.php">Post your first vacancy</a>.</div>
<?php endif; ?>

<?php foreach ($myJobs as $job): $apps = kj_applications_for_job($job['id']); ?>
  <div class="card job-card">
    <div class="info">
      <span class="code">JOB-<?= $job['id'] ?></span>
      <h3><?= htmlspecialchars($job['title']) ?></h3>
      <div class="meta">
        <span><?= htmlspecialchars($job['type']) ?></span>
        <span><?= htmlspecialchars($job['location']) ?></span>
        <span>Expires <?= htmlspecialchars($job['deadline']) ?></span>
        <span><?= kj_job_positions_remaining($job) ?> of <?= max(1, (int) $job['positions_total']) ?> position<?= (int) $job['positions_total'] === 1 ? '' : 's' ?> left</span>
        <span><?= $job['views'] ?> views</span>
        <span class="badge <?= kj_job_status_class($job) ?>"><?= kj_job_status_label($job) ?></span>
      </div>
      <?php if ($job['status'] === 'pending'): ?>
        <p style="color:var(--ink-soft); font-size:0.85rem; margin-top:6px;">Not visible to job seekers yet — waiting on admin approval.</p>
      <?php elseif ($job['status'] === 'rejected'): ?>
        <p style="color:var(--brick-600); font-size:0.85rem; margin-top:6px;">This posting did not pass admin review and is not visible to job seekers.</p>
      <?php elseif (kj_job_is_expired($job)): ?>
        <p style="color:var(--ink-soft); font-size:0.85rem; margin-top:6px;">Its expiration date was reached — closed automatically and no longer visible to job seekers.</p>
      <?php elseif (kj_job_is_filled($job)): ?>
        <p style="color:var(--green-700); font-size:0.85rem; margin-top:6px;">All positions are filled — this posting is no longer visible to job seekers.</p>
      <?php endif; ?>
    </div>
    <div class="actions">
      <span style="font-family:var(--font-mono); font-size:0.85rem; color:var(--ink-soft);"><?= count($apps) ?> applicant<?= count($apps) === 1 ? '' : 's' ?></span>
      <a class="btn btn-ghost btn-sm" href="applicants.php?job_id=<?= $job['id'] ?>">Review applicants</a>
      <a class="btn btn-ghost btn-sm" href="edit-job.php?id=<?= $job['id'] ?>">Edit</a>
      <?php if (kj_job_is_expired($job)): ?>
        <span class="badge expired">Expired</span>
      <?php elseif (kj_job_is_filled($job)): ?>
        <span class="badge hired">Filled</span>
      <?php elseif ($job['active']): ?>
        <form method="post">
          <input type="hidden" name="csrf" value="<?= htmlspecialchars(kj_csrf_token()) ?>">
          <input type="hidden" name="job_id" value="<?= $job['id'] ?>">
          <input type="hidden" name="job_action" value="close">
          <button class="btn btn-danger btn-sm" type="submit">Close</button>
        </form>
      <?php else: ?>
        <form method="post">
          <input type="hidden" name="csrf" value="<?= htmlspecialchars(kj_csrf_token()) ?>">
          <input type="hidden" name="job_id" value="<?= $job['id'] ?>">
          <input type="hidden" name="job_action" value="reopen">
          <button class="btn btn-primary btn-sm" type="submit">Reopen</button>
        </form>
      <?php endif; ?>
    </div>
  </div>
<?php endforeach; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
