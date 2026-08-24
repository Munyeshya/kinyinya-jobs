<?php
require_once __DIR__ . '/../includes/data.php';
kj_require_role('seeker');

$seeker = kj_current_user();
$myApps = kj_applications_for_seeker($seeker['id']);
$notifications = kj_notifications_for_user((int) ($_SESSION['account_id'] ?? 0));
kj_mark_notifications_read((int) ($_SESSION['account_id'] ?? 0));
usort($myApps, fn($a, $b) => strcmp($b['date'], $a['date']));

$counts = ['submitted' => 0, 'under_review' => 0, 'shortlisted' => 0, 'hired' => 0, 'rejected' => 0];
foreach ($myApps as $a) { $counts[$a['status']] = ($counts[$a['status']] ?? 0) + 1; }

$pageTitle = 'My applications — Kinyinya Jobs';
require __DIR__ . '/../includes/header.php';
?>

<section class="pagehead">
  <p class="eyebrow">Job seeker dashboard</p>
  <h1>Welcome back, <?= htmlspecialchars($seeker['name']) ?></h1>
  <p><?= htmlspecialchars($seeker['skills']) ?> · <?= htmlspecialchars($seeker['education']) ?> · <?= htmlspecialchars($seeker['location']) ?></p>
</section>

<?php if (!empty($_SESSION['flash'])): ?>
  <div class="flash"><?= htmlspecialchars($_SESSION['flash']) ?></div>
  <?php unset($_SESSION['flash']); ?>
<?php endif; ?>

<div class="stats">
  <div class="stat"><span class="n"><?= count($myApps) ?></span><span class="l">Applications sent</span></div>
  <div class="stat accent"><span class="n"><?= $counts['shortlisted'] ?></span><span class="l">Shortlisted</span></div>
  <div class="stat"><span class="n"><?= $counts['hired'] ?></span><span class="l">Hired</span></div>
  <div class="stat warn"><span class="n"><?= $counts['under_review'] + $counts['submitted'] ?></span><span class="l">Awaiting response</span></div>
</div>

<div style="margin-bottom:20px;">
  <a class="btn btn-primary" href="jobs.php">Browse open jobs</a>
  <a class="btn btn-ghost" href="profile.php">Update profile</a>
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

<h2 class="section-title">Application status</h2>

<?php if (empty($myApps)): ?>
  <div class="empty">You haven't applied to anything yet. <a href="jobs.php">Browse open jobs</a> to get started.</div>
<?php else: ?>
  <table class="kj">
    <thead>
      <tr><th>Job</th><th>Employer</th><th>Applied</th><th>Status</th></tr>
    </thead>
    <tbody>
      <?php foreach ($myApps as $a): $job = kj_job($a['job_id']); $emp = kj_employer($job['employer_id']); ?>
      <tr>
        <td><?= htmlspecialchars($job['title']) ?></td>
        <td><?= htmlspecialchars($emp['name']) ?></td>
        <td><?= htmlspecialchars($a['date']) ?></td>
        <td>
          <span class="badge <?= $a['status'] ?>"><?= kj_status_label($a['status']) ?></span>
          <?php if (in_array($a['status'], ['shortlisted', 'hired'], true)): ?>
            <a class="btn btn-ghost btn-sm" href="<?= kj_url('messages.php?application_id=' . $a['id']) ?>">Message</a>
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
