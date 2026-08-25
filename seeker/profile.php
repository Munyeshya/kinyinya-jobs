<?php
require_once __DIR__ . '/../includes/data.php';
kj_require_role('seeker');

$seeker = kj_current_user();
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $uploadedResumeUrl = null;
    $uploadedResumePath = null;
    $removeResume = isset($_POST['remove_resume']);
    if (!kj_csrf_valid($_POST['csrf'] ?? null)) {
        $error = 'The form expired. Refresh the page and try again.';
    } elseif (trim($_POST['name'] ?? '') === '' || trim($_POST['location'] ?? '') === '') {
        $error = 'Name and location are required.';
    } elseif (!empty($_FILES['resume']['name'])) {
        $file = $_FILES['resume'];
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $error = 'The CV upload could not be completed.';
        } elseif ($file['size'] > 5 * 1024 * 1024) {
            $error = 'The CV must be 5 MB or smaller.';
        } elseif ($extension !== 'pdf') {
            $error = 'Upload a PDF file only.';
        } else {
            if (!kj_is_pdf_file($file['tmp_name'])) {
                $error = 'The selected file is not a valid PDF.';
            } else {
                $directory = __DIR__ . '/../uploads/resumes';
                if (!is_dir($directory)) @mkdir($directory, 0775, true);
                $filename = 'seeker-' . (int) $seeker['id'] . '-' . bin2hex(random_bytes(10)) . '.pdf';
                $uploadedResumePath = $directory . '/' . $filename;
                if (move_uploaded_file($file['tmp_name'], $uploadedResumePath)) {
                    $uploadedResumeUrl = 'uploads/resumes/' . $filename;
                } else {
                    $error = 'The CV could not be saved. Check the uploads folder permissions.';
                }
            }
        }
    }

    if ($error === '') {
        $oldResumeUrl = $seeker['resume_url'] ?? null;
        kj_seeker_update((int) $seeker['id'], $_POST);
        if ($uploadedResumeUrl !== null || $removeResume) {
            kj_seeker_resume_update((int) $seeker['id'], $uploadedResumeUrl);
            if (!empty($oldResumeUrl) && $oldResumeUrl !== $uploadedResumeUrl) {
                $resumeDirectory = realpath(__DIR__ . '/../uploads/resumes');
                $oldResumePath = realpath(__DIR__ . '/../' . ltrim($oldResumeUrl, '/\\'));
                if ($resumeDirectory && $oldResumePath && str_starts_with($oldResumePath, $resumeDirectory . DIRECTORY_SEPARATOR)) {
                    @unlink($oldResumePath);
                }
            }
        }
        $_SESSION['flash'] = 'Your profile was updated.';
        header('Location: ' . kj_url('seeker/profile.php'));
        exit;
    } elseif ($uploadedResumePath && is_file($uploadedResumePath)) {
        @unlink($uploadedResumePath);
    }
}
$seeker = kj_current_user();
$hasPdfResume = !empty($seeker['resume_url']) && strtolower(pathinfo($seeker['resume_url'], PATHINFO_EXTENSION)) === 'pdf';
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
<form method="post" enctype="multipart/form-data" class="card auth-card">
  <input type="hidden" name="csrf" value="<?= htmlspecialchars(kj_csrf_token()) ?>">
  <div class="grid-2">
    <div class="field"><label for="name">Full name</label><input id="name" name="name" required value="<?= htmlspecialchars($seeker['name']) ?>"></div>
    <div class="field"><label for="location">Location</label><input id="location" name="location" required value="<?= htmlspecialchars($seeker['location']) ?>"></div>
  </div>
  <div class="field"><label for="education">Education</label><input id="education" name="education" value="<?= htmlspecialchars($seeker['education']) ?>" placeholder="e.g. A2 Accounting"></div>
  <div class="field"><label for="skills">Skills</label><textarea id="skills" name="skills" placeholder="List relevant skills separated by commas"><?= htmlspecialchars($seeker['skills']) ?></textarea></div>
  <div class="field">
    <label for="resume">CV / Resume</label>
    <input id="resume" name="resume" type="file" accept=".pdf,application/pdf">
    <small>Optional. PDF only, up to 5 MB. A new file replaces the current one.</small>
    <?php if (!empty($seeker['resume_url'])): ?>
      <?php if ($hasPdfResume): ?>
        <p><a href="<?= htmlspecialchars(kj_url('resume.php?seeker_id=' . $seeker['id'])) ?>">View CV inside the system</a></p>
      <?php else: ?>
        <p class="field-note warning">The current CV is not a PDF. Replace it to use the in-system viewer.</p>
      <?php endif; ?>
      <label class="check-option"><input type="checkbox" name="remove_resume" value="1"> Remove current CV</label>
    <?php endif; ?>
  </div>
  <button class="btn btn-primary" type="submit">Save profile</button>
</form>
<?php require __DIR__ . '/../includes/footer.php'; ?>
