<?php
require_once __DIR__ . '/../includes/data.php';
kj_require_role('seeker');
$seeker = kj_current_user();

$q = trim($_GET['q'] ?? '');
$cat = $_GET['category'] ?? '';
$type = $_GET['type'] ?? '';
$location = trim($_GET['location'] ?? '');

$jobs = array_filter(kj_jobs(), function ($j) use ($q, $cat, $type, $location) {
    if (!kj_job_is_visible($j)) return false; // must be admin-approved, active, and not past its deadline
    if ($q && stripos($j['title'] . ' ' . $j['description'] . ' ' . $j['requirements'] . ' ' . $j['location'], $q) === false) return false;
    if ($cat && $j['category'] !== $cat) return false;
    if ($type && $j['type'] !== $type) return false;
    if ($location && stripos($j['location'], $location) === false) return false;
    return true;
});
usort($jobs, fn($a, $b) => strcmp($b['posted'], $a['posted']));

$visibleJobs = array_filter(kj_jobs(), 'kj_job_is_visible');
$categories = array_unique(array_map(fn($j) => $j['category'], $visibleJobs));
$types = array_unique(array_map(fn($j) => $j['type'], $visibleJobs));
$locations = array_unique(array_map(fn($j) => $j['location'], $visibleJobs));
sort($locations);

$pageTitle = 'Browse jobs — Kinyinya Jobs';
require __DIR__ . '/../includes/header.php';
?>

<section class="pagehead">
  <p class="eyebrow">Job seeker · Search</p>
  <h1>Open positions in Kinyinya</h1>
  <p>Filter by category or job type, or search by keyword.</p>
</section>

<?php if (!empty($_SESSION['flash'])): ?>
  <div class="flash"><?= htmlspecialchars($_SESSION['flash']) ?></div>
  <?php unset($_SESSION['flash']); ?>
<?php endif; ?>

<form method="get" class="card">
  <div class="grid-2">
    <div class="field">
      <label>Keyword</label>
      <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="e.g. developer, sales, mason">
    </div>
    <div class="field">
      <label>Category</label>
      <select name="category">
        <option value="">All categories</option>
        <?php foreach ($categories as $c): ?>
          <option value="<?= htmlspecialchars($c) ?>" <?= $c === $cat ? 'selected' : '' ?>><?= htmlspecialchars($c) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="field">
      <label>Location</label>
      <select name="location"><option value="">All locations</option><?php foreach ($locations as $l): ?><option value="<?= htmlspecialchars($l) ?>" <?= $location === $l ? 'selected' : '' ?>><?= htmlspecialchars($l) ?></option><?php endforeach; ?></select>
    </div>
  </div>
  <div class="field">
    <label>Job type</label>
    <select name="type">
      <option value="">All types</option>
      <?php foreach ($types as $t): ?>
        <option value="<?= htmlspecialchars($t) ?>" <?= $t === $type ? 'selected' : '' ?>><?= htmlspecialchars($t) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <button class="btn btn-primary" type="submit">Search</button>
  <a class="btn btn-ghost" href="jobs.php">Reset</a>
</form>

<h2 class="section-title"><?= count($jobs) ?> open position<?= count($jobs) === 1 ? '' : 's' ?></h2>

<?php if (empty($jobs)): ?>
  <div class="empty">No jobs match those filters right now.</div>
<?php endif; ?>

<?php foreach ($jobs as $job): $emp = kj_employer($job['employer_id']); $applied = kj_has_applied($seeker['id'], $job['id']); ?>
  <div class="card job-card">
    <div class="info">
      <span class="code">JOB-<?= $job['id'] ?> · <?= htmlspecialchars($job['category']) ?></span>
      <h3><?= htmlspecialchars($job['title']) ?></h3>
      <div class="meta">
        <span><?= htmlspecialchars($emp['name']) ?></span>
        <span><?= htmlspecialchars($job['type']) ?></span>
        <span><?= htmlspecialchars($job['location']) ?></span>
        <span><?= kj_money($job['salary_min']) ?>–<?= kj_money($job['salary_max']) ?></span>
        <span>Deadline <?= htmlspecialchars($job['deadline']) ?></span>
      </div>
      <p><?= htmlspecialchars($job['description']) ?></p>
      <p style="color:var(--ink-soft); font-size:0.85rem;"><strong>Requirements:</strong> <?= htmlspecialchars($job['requirements']) ?></p>
    </div>
    <div class="actions">
      <?php if ($applied): ?>
        <span class="badge shortlisted">Applied</span>
      <?php else: ?>
        <a class="btn btn-primary" href="job.php?id=<?= $job['id'] ?>">View and apply</a>
      <?php endif; ?>
    </div>
  </div>
<?php endforeach; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
