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
        'SELECT a.seeker_id, j.employer_id FROM applications a JOIN jobs j ON j.id = a.job_id WHERE a.id = ?'
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
$mimeTypes = ['pdf' => 'application/pdf', 'doc' => 'application/msword', 'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
header('Content-Type: ' . ($mimeTypes[$extension] ?? 'application/octet-stream'));
header('Content-Length: ' . filesize($filePath));
header('Content-Disposition: inline; filename="resume.' . $extension . '"');
header('Cache-Control: private, no-store');
header('X-Content-Type-Options: nosniff');
readfile($filePath);
