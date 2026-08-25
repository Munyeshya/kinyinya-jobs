<?php
require_once __DIR__ . '/includes/data.php';

$role = $_SESSION['role'] ?? '';
if (!in_array($role, ['seeker', 'employer'], true)) {
    http_response_code(403);
    exit('You are not allowed to access this CV.');
}
kj_require_role($role);
$currentProfileId = (int) ($_SESSION['user_id'] ?? 0);
$seekerId = (int) ($_GET['seeker_id'] ?? 0);
$applicationId = (int) ($_GET['application_id'] ?? 0);

if ($applicationId > 0) {
    $stmt = kj_db()->prepare(
        'SELECT a.seeker_id, a.job_id, j.employer_id FROM applications a JOIN jobs j ON j.id = a.job_id WHERE a.id = ?'
    );
    $stmt->execute([$applicationId]);
    $application = $stmt->fetch();
    if (!$application || $role !== 'employer' || (int) $application['employer_id'] !== $currentProfileId) {
        http_response_code(403);
        exit('You are not allowed to access this CV.');
    }
    $seekerId = (int) $application['seeker_id'];
} elseif ($role !== 'seeker' || $seekerId !== $currentProfileId) {
    http_response_code(403);
    exit('You are not allowed to access this CV.');
}

$seeker = kj_seeker($seekerId);
$relativePath = $seeker['resume_url'] ?? '';
$baseDirectory = realpath(__DIR__ . '/uploads/resumes');
$filePath = $baseDirectory && $relativePath ? realpath(__DIR__ . '/' . $relativePath) : false;
if (!$filePath || !$baseDirectory || strpos($filePath, $baseDirectory . DIRECTORY_SEPARATOR) !== 0 || !is_file($filePath)) {
    http_response_code(404);
    exit('CV not found.');
}

$extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
if ($extension !== 'pdf') {
    http_response_code(415);
    exit('Only PDF CV files can be viewed. Replace the current CV with a PDF.');
}

$raw = ($_GET['raw'] ?? '') === '1';
if (!$raw) {
    $viewerParameters = $applicationId > 0
        ? ['application_id' => $applicationId, 'raw' => 1]
        : ['seeker_id' => $seekerId, 'raw' => 1];
    $viewerUrl = kj_url('resume.php?' . http_build_query($viewerParameters));
    $backUrl = $role === 'employer'
        ? kj_url('employer/applicants.php?job_id=' . (int) $application['job_id'])
        : kj_url('seeker/profile.php');
    $pageTitle = 'View CV - Kinyinya Jobs';
    require __DIR__ . '/includes/header.php';
    ?>
    <section class="pagehead">
      <p class="eyebrow">Secure PDF viewer</p>
      <h1><?= htmlspecialchars($seeker['name']) ?> — CV</h1>
      <p>The PDF is displayed inside Kinyinya Jobs and remains limited to authorized users.</p>
    </section>
    <p><a class="btn btn-ghost" href="<?= htmlspecialchars($backUrl) ?>">&larr; Back</a></p>
    <div class="card pdf-viewer-card">
      <iframe class="pdf-viewer" src="<?= htmlspecialchars($viewerUrl) ?>" title="<?= htmlspecialchars($seeker['name']) ?> CV"></iframe>
    </div>
    <?php
    require __DIR__ . '/includes/footer.php';
    exit;
}

header('Content-Type: application/pdf');
header('Content-Length: ' . filesize($filePath));
header('Content-Disposition: inline; filename="cv.pdf"');
header('Cache-Control: private, no-store');
header('X-Content-Type-Options: nosniff');
header("Content-Security-Policy: frame-ancestors 'self'");
readfile($filePath);
