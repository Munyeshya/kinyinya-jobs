<?php
require_once __DIR__ . '/../includes/data.php';
kj_require_role('admin');

// #23 — Admin reviews a pending posting and approves or rejects it before
// it becomes visible to job seekers.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !kj_csrf_valid($_POST['csrf'] ?? null)) {
    $_SESSION['flash'] = 'The request expired. Please try again.';
    header('Location: dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'], $_POST['user_action'])) {
    $active = $_POST['user_action'] === 'activate';
    $_SESSION['flash'] = kj_user_set_active((int) $_POST['user_id'], $active)
        ? ($active ? 'User account activated.' : 'User account deactivated.')
        : 'That account could not be changed.';
    header('Location: dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['job_id'], $_POST['action'])) {
    $jobId = (int) $_POST['job_id'];
    $action = $_POST['action'] === 'approve' ? 'approved' : 'rejected';
    if (kj_job_set_status($jobId, $action)) {
        $_SESSION['flash'] = $action === 'approved'
            ? 'Job posting approved — it is now visible to job seekers.'
            : 'Job posting rejected — it will not be shown to job seekers.';
    } else {
        $reviewedJob = kj_job($jobId);
        $_SESSION['flash'] = $action === 'approved' && $reviewedJob && kj_job_is_expired($reviewedJob)
            ? 'That posting cannot be approved because its deadline has passed. Ask the employer to edit and resubmit it.'
            : 'That posting could not be updated.';
    }
    header('Location: dashboard.php');
    exit;
}

$jobs = kj_jobs();
$apps = kj_applications();
$seekers = kj_seekers();
$employers = kj_employers();
$users = kj_users();

$pendingJobs = kj_jobs_pending();
usort($pendingJobs, fn($a, $b) => strcmp($b['posted'], $a['posted']));

$activeJobs = array_filter($jobs, 'kj_job_is_visible');

$statusCounts = ['submitted' => 0, 'under_review' => 0, 'shortlisted' => 0, 'hired' => 0, 'rejected' => 0];
foreach ($apps as $a) { $statusCounts[$a['status']] = ($statusCounts[$a['status']] ?? 0) + 1; }
$maxCount = max(1, max($statusCounts));

$categoryCounts = [];
foreach ($jobs as $j) { $categoryCounts[$j['category']] = ($categoryCounts[$j['category']] ?? 0) + 1; }

$recentApps = $apps;
usort($recentApps, fn($a, $b) => strcmp($b['date'], $a['date']));
$recentApps = array_slice($recentApps, 0, 6);

$pageTitle = 'Admin overview — Kinyinya Jobs';
require __DIR__ . '/../includes/header.php';
?>

<section class="pagehead">
  <p class="eyebrow">Administrator</p>
  <h1>Platform overview</h1>
  <p>Aggregate activity across all employers and job seekers on Kinyinya Jobs.</p>
</section>

<?php if (!empty($_SESSION['flash'])): ?>
  <div class="flash"><?= htmlspecialchars($_SESSION['flash']) ?></div>
  <?php unset($_SESSION['flash']); ?>
<?php endif; ?>

<h2 class="section-title">Pending approvals (<?= count($pendingJobs) ?>)</h2>
<p style="color:var(--ink-soft); font-size:0.9rem; margin-top:-8px;">New postings stay hidden from job seekers until an admin reviews them for compliance with platform terms and conditions.</p>

<?php if (empty($pendingJobs)): ?>
  <div class="empty">No postings waiting on review right now.</div>
<?php endif; ?>

<?php foreach ($pendingJobs as $job): $emp = kj_employer($job['employer_id']); ?>
  <div class="card job-card">
    <div class="info">
      <span class="code">JOB-<?= $job['id'] ?> · <?= htmlspecialchars($job['category']) ?></span>
      <h3><?= htmlspecialchars($job['title']) ?></h3>
      <div class="meta">
        <span><?= htmlspecialchars($emp['name']) ?></span>
        <span><?= htmlspecialchars($job['type']) ?></span>
        <span><?= htmlspecialchars($job['location']) ?></span>
        <span><?= htmlspecialchars(kj_salary_range($job)) ?></span>
        <span>Deadline <?= htmlspecialchars($job['deadline']) ?></span>
        <span class="badge under_review">Pending review</span>
      </div>
      <p><?= htmlspecialchars($job['description']) ?></p>
      <p style="color:var(--ink-soft); font-size:0.85rem;"><strong>Requirements:</strong> <?= htmlspecialchars($job['requirements']) ?></p>
    </div>
    <div class="actions">
      <form method="post" style="display:inline;">
        <input type="hidden" name="csrf" value="<?= htmlspecialchars(kj_csrf_token()) ?>">
        <input type="hidden" name="job_id" value="<?= $job['id'] ?>">
        <input type="hidden" name="action" value="approve">
        <button class="btn btn-primary btn-sm" type="submit">Approve</button>
      </form>
      <form method="post" style="display:inline;">
        <input type="hidden" name="csrf" value="<?= htmlspecialchars(kj_csrf_token()) ?>">
        <input type="hidden" name="job_id" value="<?= $job['id'] ?>">
        <input type="hidden" name="action" value="reject">
        <button class="btn btn-ghost btn-sm" type="submit">Reject</button>
      </form>
    </div>
  </div>
<?php endforeach; ?>

<div class="stats">
  <div class="stat"><span class="n"><?= count($seekers) ?></span><span class="l">Registered job seekers</span></div>
  <div class="stat"><span class="n"><?= count($employers) ?></span><span class="l">Registered employers</span></div>
  <div class="stat accent"><span class="n"><?= count($activeJobs) ?></span><span class="l">Active job postings</span></div>
  <div class="stat warn"><span class="n"><?= count($apps) ?></span><span class="l">Total applications</span></div>
</div>

<div class="grid-2">
  <div>
    <h2 class="section-title">Applications by status</h2>
    <div class="card">
      <?php foreach ($statusCounts as $status => $count): ?>
        <div style="margin-bottom:10px;">
          <div style="display:flex; justify-content:space-between; font-size:0.85rem; margin-bottom:3px;">
            <span class="badge <?= $status ?>"><?= kj_status_label($status) ?></span>
            <span style="font-family:var(--font-mono); color:var(--ink-soft);"><?= $count ?></span>
          </div>
          <div style="background:var(--green-100); border-radius:4px; height:8px; overflow:hidden;">
            <div style="background:var(--green-500); height:100%; width:<?= (int) round(($count / $maxCount) * 100) ?>%;"></div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <div>
    <h2 class="section-title">Postings by category</h2>
    <div class="card">
      <?php foreach ($categoryCounts as $cat => $count): ?>
        <div style="display:flex; justify-content:space-between; padding:6px 0; border-bottom:1px solid var(--line); font-size:0.9rem;">
          <span><?= htmlspecialchars($cat) ?></span>
          <span style="font-family:var(--font-mono);"><?= $count ?></span>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<h2 class="section-title">Recent applications</h2>
<table class="kj">
  <thead>
    <tr><th>Applicant</th><th>Job</th><th>Employer</th><th>Date</th><th>Status</th></tr>
  </thead>
  <tbody>
    <?php foreach ($recentApps as $a): $job = kj_job($a['job_id']); $emp = kj_employer($job['employer_id']); $seeker = kj_seeker($a['seeker_id']); ?>
    <tr>
      <td><?= htmlspecialchars($seeker['name']) ?></td>
      <td><?= htmlspecialchars($job['title']) ?></td>
      <td><?= htmlspecialchars($emp['name']) ?></td>
      <td><?= htmlspecialchars($a['date']) ?></td>
      <td><span class="badge <?= $a['status'] ?>"><?= kj_status_label($a['status']) ?></span></td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<h2 class="section-title">User accounts</h2>
<table class="kj">
  <thead><tr><th>Email</th><th>Role</th><th>Created</th><th>Status</th><th>Action</th></tr></thead>
  <tbody>
    <?php foreach ($users as $account): ?>
    <tr>
      <td><?= htmlspecialchars($account['email']) ?></td>
      <td><?= htmlspecialchars(ucfirst($account['role'])) ?></td>
      <td><?= htmlspecialchars($account['created_at']) ?></td>
      <td><span class="badge <?= $account['is_active'] ? 'active' : 'expired' ?>"><?= $account['is_active'] ? 'Active' : 'Inactive' ?></span></td>
      <td>
        <?php if ($account['role'] === 'admin'): ?>
          <span style="color:var(--ink-soft); font-size:.8rem;">Protected</span>
        <?php else: ?>
          <form method="post">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars(kj_csrf_token()) ?>">
            <input type="hidden" name="user_id" value="<?= (int) $account['id'] ?>">
            <input type="hidden" name="user_action" value="<?= $account['is_active'] ? 'deactivate' : 'activate' ?>">
            <button class="btn <?= $account['is_active'] ? 'btn-danger' : 'btn-primary' ?> btn-sm" type="submit"><?= $account['is_active'] ? 'Deactivate' : 'Activate' ?></button>
          </form>
        <?php endif; ?>
      </td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<h2 class="section-title">Employers</h2>
<table class="kj">
  <thead><tr><th>Company</th><th>Industry</th><th>Location</th><th>Active postings</th></tr></thead>
  <tbody>
    <?php foreach ($employers as $e): ?>
    <tr>
      <td><?= htmlspecialchars($e['name']) ?></td>
      <td><?= htmlspecialchars($e['industry']) ?></td>
      <td><?= htmlspecialchars($e['location']) ?></td>
      <td><?= count(array_filter(kj_jobs_for_employer($e['id']), 'kj_job_is_visible')) ?></td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<?php require __DIR__ . '/../includes/footer.php'; ?>
