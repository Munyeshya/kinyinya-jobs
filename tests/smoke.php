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
$check('Seeker CV path is stored', in_array('resume_url', $seekerColumns, true));
$check('User activation is stored', in_array('is_active', $userColumns, true));
$check('Seed users are present', (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn() >= 9);
$check('Approved jobs are available', count(array_filter(kj_jobs(), 'kj_job_is_visible')) > 0);
$check('CSRF token is generated', strlen(kj_csrf_token()) === 48);

$failed = 0;
foreach ($checks as [$name, $passed]) {
    echo ($passed ? '[PASS] ' : '[FAIL] ') . $name . PHP_EOL;
    if (!$passed) $failed++;
}
exit($failed ? 1 : 0);
