<?php
require_once __DIR__ . '/../includes/data.php';
kj_require_role('employer');

$employer = kj_current_user();
$jobId = (int) ($_GET['id'] ?? $_POST['job_id'] ?? 0);
$job = kj_job($jobId);
if (!$job || (int) $job['employer_id'] !== (int) $employer['id']) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $salaryMin = max(0, (int) ($_POST['salary_min'] ?? 0));
    $salaryMax = max(0, (int) ($_POST['salary_max'] ?? 0));
    if (!kj_csrf_valid($_POST['csrf'] ?? null)) {
        $error = 'The form expired. Refresh the page and try again.';
    } elseif (trim($_POST['title'] ?? '') === '' || trim($_POST['location'] ?? '') === '' || empty($_POST['deadline'])) {
        $error = 'Job title, location, and deadline are required.';
    } elseif (strtotime($_POST['deadline']) < strtotime('today')) {
        $error = 'The deadline must be today or a future date.';
    } elseif ($salaryMax && $salaryMax < $salaryMin) {
        $error = 'Maximum salary cannot be lower than minimum salary.';
    } else {
        kj_job_update_for_employer($jobId, (int) $employer['id'], $_POST);
        $_SESSION['flash'] = 'Job posting updated and sent to an administrator for approval.';
        header('Location: dashboard.php');
        exit;
    }
    $job = array_merge($job, $_POST);
}

$pageTitle = 'Edit job - Kinyinya Jobs';
require __DIR__ . '/../includes/header.php';
?>
<section class="pagehead">
  <p class="eyebrow">Employer - Vacancy management</p>
  <h1>Edit job posting</h1>
  <p>Update the vacancy information. Every edit is sent to an administrator for approval before it is shown to job seekers again.</p>
</section>
<?php if ($error): ?><div class="flash error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<form method="post" class="card">
  <input type="hidden" name="csrf" value="<?= htmlspecialchars(kj_csrf_token()) ?>">
  <input type="hidden" name="job_id" value="<?= $jobId ?>">
  <div class="grid-2">
    <div class="field"><label for="title">Job title</label><input id="title" name="title" required value="<?= htmlspecialchars($job['title']) ?>"></div>
    <div class="field"><label for="type">Employment type</label><select id="type" name="type"><?php foreach (['Full-time', 'Part-time', 'Contract', 'Temporary'] as $type): ?><option <?= $job['type'] === $type ? 'selected' : '' ?>><?= $type ?></option><?php endforeach; ?></select></div>
  </div>
  <div class="field"><label for="category">Category</label><input id="category" name="category" value="<?= htmlspecialchars($job['category']) ?>"></div>
  <div class="field"><label for="location">Job location</label><input id="location" name="location" required value="<?= htmlspecialchars($job['location'] ?? 'Kinyinya') ?>"></div>
  <div class="field"><label for="description">Responsibilities</label><textarea id="description" name="description"><?= htmlspecialchars($job['description']) ?></textarea></div>
  <div class="field"><label for="requirements">Requirements</label><textarea id="requirements" name="requirements"><?= htmlspecialchars($job['requirements']) ?></textarea></div>
  <div class="grid-2">
    <div class="field"><label for="salary_min">Minimum salary (RWF)</label><input id="salary_min" name="salary_min" type="number" min="0" value="<?= (int) $job['salary_min'] ?>"></div>
    <div class="field"><label for="salary_max">Maximum salary (RWF)</label><input id="salary_max" name="salary_max" type="number" min="0" value="<?= (int) $job['salary_max'] ?>"></div>
  </div>
  <div class="field"><label for="deadline">Application deadline</label><input id="deadline" name="deadline" type="date" required value="<?= htmlspecialchars($job['deadline']) ?>"></div>
  <button class="btn btn-primary" type="submit">Save and resubmit</button>
  <a class="btn btn-ghost" href="dashboard.php">Cancel</a>
</form>
<?php require __DIR__ . '/../includes/footer.php'; ?>
