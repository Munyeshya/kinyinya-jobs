<?php
require_once __DIR__ . '/../includes/data.php';
kj_require_role('employer');
$employer = kj_current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!kj_csrf_valid($_POST['csrf'] ?? null)) {
        $_SESSION['flash'] = 'The form expired. Please try again.';
    } else {
        kj_job_create($employer['id'], $_POST);
        $_SESSION['flash'] = 'Job posting submitted. It will go live once an admin reviews and approves it.';
    }
    header('Location: dashboard.php');
    exit;
}

$pageTitle = 'Post a job — Kinyinya Jobs';
require __DIR__ . '/../includes/header.php';
?>

<section class="pagehead">
  <p class="eyebrow">Employer · New posting</p>
  <h1>Post a job vacancy</h1>
  <p>Fields marked with the section headers below map to the three-step posting form described in the platform design.</p>
</section>

<form method="post" class="card">
  <input type="hidden" name="csrf" value="<?= htmlspecialchars(kj_csrf_token()) ?>">
  <h3>1. Basic information</h3>
  <div class="grid-2">
    <div class="field">
      <label for="title">Job title</label>
      <input id="title" name="title" type="text" required placeholder="e.g. Cashier">
    </div>
    <div class="field">
      <label for="type">Employment type</label>
      <select id="type" name="type">
        <option>Full-time</option>
        <option>Part-time</option>
        <option>Contract</option>
        <option>Temporary</option>
      </select>
    </div>
  </div>
  <div class="field">
    <label for="category">Category</label>
    <input id="category" name="category" type="text" placeholder="e.g. Retail, IT, Construction">
  </div>

  <h3>2. Detailed description</h3>
  <div class="field">
    <label for="description">Responsibilities</label>
    <textarea id="description" name="description" placeholder="Describe day-to-day duties"></textarea>
  </div>
  <div class="field">
    <label for="requirements">Requirements / qualifications</label>
    <textarea id="requirements" name="requirements" placeholder="Education, experience, skills"></textarea>
  </div>

  <h3>3. Application details</h3>
  <div class="grid-2">
    <div class="field">
      <label for="salary_min">Salary range — minimum (RWF)</label>
      <input id="salary_min" name="salary_min" type="number" min="0" step="1000">
    </div>
    <div class="field">
      <label for="salary_max">Salary range — maximum (RWF)</label>
      <input id="salary_max" name="salary_max" type="number" min="0" step="1000">
    </div>
  </div>
  <div class="field">
    <label for="deadline">Application deadline</label>
    <input id="deadline" name="deadline" type="date" value="<?= date('Y-m-d', strtotime('+30 days')) ?>">
  </div>

  <button class="btn btn-primary" type="submit">Publish posting</button>
  <a class="btn btn-ghost" href="dashboard.php">Cancel</a>
</form>

<?php require __DIR__ . '/../includes/footer.php'; ?>
