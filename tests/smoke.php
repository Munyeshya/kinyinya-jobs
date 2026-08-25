<?php
/**
 * Small, dependency-free verification script for the PHP/MySQL build.
 * Run from the project root with: php tests/smoke.php
 */
if (PHP_SAPI !== 'cli') exit("Run this script from the command line.\n");

require_once __DIR__ . '/../includes/data.php';

$checks = [];
$check = function (string $name, bool $passed) use (&$checks): void {
    $checks[] = [$name, $passed];
};

$pdo = kj_db();
$tableNames = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
$check('Core tables exist', count(array_intersect(['users', 'seekers', 'employers', 'jobs', 'applications', 'messages', 'notifications'], $tableNames)) === 7);

$jobColumns = $pdo->query('SHOW COLUMNS FROM jobs')->fetchAll(PDO::FETCH_COLUMN);
$seekerColumns = $pdo->query('SHOW COLUMNS FROM seekers')->fetchAll(PDO::FETCH_COLUMN);
$userColumns = $pdo->query('SHOW COLUMNS FROM users')->fetchAll(PDO::FETCH_COLUMN);
$check('Job location is stored', in_array('location', $jobColumns, true));
$check('Job capacity is stored', in_array('positions_total', $jobColumns, true));
$check('Seeker CV path is stored', in_array('resume_url', $seekerColumns, true));
$check('User activation is stored', in_array('is_active', $userColumns, true));
$check('Users table is queryable', (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn() >= 0);
$check('Job visibility rules work',
    kj_job_is_visible(['active' => 1, 'status' => 'approved', 'deadline' => date('Y-m-d', strtotime('+1 day'))])
    && !kj_job_is_visible(['active' => 1, 'status' => 'pending', 'deadline' => date('Y-m-d', strtotime('+1 day'))])
    && !kj_job_is_visible(['active' => 1, 'status' => 'approved', 'deadline' => date('Y-m-d')])
    && !kj_job_is_visible(['active' => 1, 'status' => 'approved', 'deadline' => date('Y-m-d', strtotime('-1 day'))])
);
$check('CSRF token is generated', strlen(kj_csrf_token()) === 48);
$passwordHash = password_hash('smoke-password', PASSWORD_DEFAULT);
$check('Passwords use secure hashes', password_verify('smoke-password', $passwordHash) && !password_verify('wrong-password', $passwordHash));
$check('Unspecified salary has a clear label', kj_salary_range(['salary_min' => 0, 'salary_max' => 0]) === 'Salary not specified');
$check('Remaining positions are calculated', kj_job_positions_remaining(['positions_total' => 3, 'positions_filled' => 1]) === 2);

// Exercise connected workflows inside a transaction and roll everything back.
$workflowChecks = [
    'Admin approval makes a job visible' => false,
    'Employer edits require fresh approval' => false,
    'Expired jobs cannot be approved' => false,
    'Reached expiration date turns a job off' => false,
    'Applications store optional letters correctly' => false,
    'Duplicate applications are prevented' => false,
    'A filled job disappears from seeker search' => false,
    'Hiring cannot exceed the number needed' => false,
    'An reopened position becomes visible again' => false,
];
try {
    $pdo->beginTransaction();
    $suffix = bin2hex(random_bytes(6));

    $stmt = $pdo->prepare("INSERT INTO users (email, password_hash, role) VALUES (?, ?, 'employer')");
    $stmt->execute(["smoke-employer-{$suffix}@example.test", $passwordHash]);
    $employerUserId = (int) $pdo->lastInsertId();
    $stmt = $pdo->prepare("INSERT INTO employers (user_id, name, industry, location, about) VALUES (?, 'Smoke Employer', 'Testing', 'Kinyinya', '')");
    $stmt->execute([$employerUserId]);
    $employerId = (int) $pdo->lastInsertId();

    $jobData = [
        'title' => 'Smoke Test Vacancy', 'type' => 'Full-time', 'category' => 'Testing',
        'location' => 'Kinyinya', 'description' => 'Temporary workflow test',
        'requirements' => 'Testing', 'salary_min' => 0, 'salary_max' => 0,
        'positions_total' => 1,
        'deadline' => date('Y-m-d', strtotime('+7 days')),
    ];
    $jobId = kj_job_create($employerId, $jobData);
    $workflowChecks['Admin approval makes a job visible'] = kj_job_set_status($jobId, 'approved') && kj_job_is_visible(kj_job($jobId));

    $jobData['title'] = 'Edited Smoke Test Vacancy';
    kj_job_update_for_employer($jobId, $employerId, $jobData);
    $workflowChecks['Employer edits require fresh approval'] = kj_job($jobId)['status'] === 'pending';

    $expiredData = $jobData;
    $expiredData['deadline'] = date('Y-m-d', strtotime('-1 day'));
    kj_job_update_for_employer($jobId, $employerId, $expiredData);
    $workflowChecks['Expired jobs cannot be approved'] = !kj_job_set_status($jobId, 'approved');

    $reachedData = $jobData;
    $reachedData['deadline'] = (string) $pdo->query('SELECT CURDATE()')->fetchColumn();
    kj_job_update_for_employer($jobId, $employerId, $reachedData);
    kj_expire_jobs();
    $workflowChecks['Reached expiration date turns a job off'] = !(bool) kj_job($jobId)['active'];

    kj_job_update_for_employer($jobId, $employerId, $jobData);
    kj_job_set_status($jobId, 'approved');
    $stmt = $pdo->prepare("INSERT INTO users (email, password_hash, role) VALUES (?, ?, 'seeker')");
    $stmt->execute(["smoke-seeker-{$suffix}@example.test", $passwordHash]);
    $seekerUserId = (int) $pdo->lastInsertId();
    $stmt = $pdo->prepare("INSERT INTO seekers (user_id, name, skills, education, location) VALUES (?, 'Smoke Seeker', '', '', 'Kinyinya')");
    $stmt->execute([$seekerUserId]);
    $seekerId = (int) $pdo->lastInsertId();
    $applicationId = kj_application_create($seekerId, $jobId, '');
    $stmt = $pdo->prepare('SELECT cover_letter FROM applications WHERE id = ?');
    $stmt->execute([$applicationId]);
    $workflowChecks['Applications store optional letters correctly'] = $applicationId !== null && $stmt->fetchColumn() === null;
    $workflowChecks['Duplicate applications are prevented'] = kj_application_create($seekerId, $jobId, '') === null;

    $stmt = $pdo->prepare("INSERT INTO users (email, password_hash, role) VALUES (?, ?, 'seeker')");
    $stmt->execute(["smoke-seeker-two-{$suffix}@example.test", $passwordHash]);
    $secondSeekerUserId = (int) $pdo->lastInsertId();
    $stmt = $pdo->prepare("INSERT INTO seekers (user_id, name, skills, education, location) VALUES (?, 'Second Smoke Seeker', '', '', 'Kinyinya')");
    $stmt->execute([$secondSeekerUserId]);
    $secondSeekerId = (int) $pdo->lastInsertId();
    $secondApplicationId = kj_application_create($secondSeekerId, $jobId, '');

    kj_application_set_status($applicationId, $jobId, 'hired');
    $filledJob = kj_job($jobId);
    $workflowChecks['A filled job disappears from seeker search'] = kj_job_positions_remaining($filledJob) === 0 && !kj_job_is_visible($filledJob);
    $workflowChecks['Hiring cannot exceed the number needed'] = !kj_application_set_status($secondApplicationId, $jobId, 'hired');

    kj_application_set_status($applicationId, $jobId, 'under_review');
    $reopenedJob = kj_job($jobId);
    $workflowChecks['An reopened position becomes visible again'] = kj_job_positions_remaining($reopenedJob) === 1 && kj_job_is_visible($reopenedJob);
} catch (Throwable $exception) {
    echo '[INFO] Workflow test error: ' . $exception->getMessage() . PHP_EOL;
} finally {
    if ($pdo->inTransaction()) $pdo->rollBack();
}
foreach ($workflowChecks as $name => $passed) $check($name, $passed);

$failed = 0;
foreach ($checks as [$name, $passed]) {
    echo ($passed ? '[PASS] ' : '[FAIL] ') . $name . PHP_EOL;
    if (!$passed) $failed++;
}
exit($failed ? 1 : 0);
