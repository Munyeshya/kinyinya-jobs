<?php
/**
 * Kinyinya Jobs — data layer.
 *
 * All records (employers, seekers, jobs, applications, messages, notifications) live in
 * the MySQL `kinyinya_jobs` database — see database/schema.sql and
 * database/seed.sql. $_SESSION is used only for lightweight per-visitor
 * state: who is logged in (role/user_id) and one-off flash messages.
 * Every page calls the shared kj_*() helpers so database access and validation
 * remain in one place.
 */

require_once __DIR__ . '/db.php';

if (session_status() === PHP_SESSION_NONE) {
    $isHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

kj_expire_jobs();

// ---------------------------------------------------------------------
// Jobs
// ---------------------------------------------------------------------

function kj_job_select_sql(): string {
    return "SELECT j.*, (SELECT COUNT(*) FROM applications a WHERE a.job_id = j.id AND a.status = 'hired') AS positions_filled FROM jobs j";
}

function kj_jobs(): array {
    $rows = kj_db()->query(kj_job_select_sql() . ' ORDER BY j.posted DESC, j.id DESC')->fetchAll();
    return kj_index_by_id($rows);
}

function kj_job($id) {
    $stmt = kj_db()->prepare(kj_job_select_sql() . ' WHERE j.id = ?');
    $stmt->execute([(int) $id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function kj_jobs_for_employer($employer_id): array {
    $stmt = kj_db()->prepare(kj_job_select_sql() . ' WHERE j.employer_id = ? ORDER BY j.posted DESC, j.id DESC');
    $stmt->execute([(int) $employer_id]);
    return kj_index_by_id($stmt->fetchAll());
}

/**
 * #23 — Employer submits a new posting. It always starts out 'pending'
 * so it stays invisible to job seekers until an admin approves it.
 */
function kj_job_create(int $employer_id, array $data): int {
    $stmt = kj_db()->prepare(
        'INSERT INTO jobs (employer_id, title, type, category, location, description, requirements,
                            salary_min, salary_max, positions_total, deadline, posted, active, status, views)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, \'pending\', 0)'
    );
    $stmt->execute([
        $employer_id,
        trim($data['title'] ?? 'Untitled position') ?: 'Untitled position',
        $data['type'] ?? 'Full-time',
        trim($data['category'] ?? 'General') ?: 'General',
        trim($data['location'] ?? 'Kinyinya') ?: 'Kinyinya',
        trim($data['description'] ?? ''),
        trim($data['requirements'] ?? ''),
        max(0, (int) ($data['salary_min'] ?? 0)),
        max(0, (int) ($data['salary_max'] ?? 0)),
        min(999, max(1, (int) ($data['positions_total'] ?? 1))),
        $data['deadline'] ?? date('Y-m-d', strtotime('+30 days')),
        date('Y-m-d'),
    ]);
    return (int) kj_db()->lastInsertId();
}

function kj_jobs_pending(): array {
    return kj_index_by_id(kj_db()->query(kj_job_select_sql() . " WHERE j.status = 'pending' ORDER BY j.posted DESC, j.id DESC")->fetchAll());
}

function kj_job_set_status(int $job_id, string $status): bool {
    if (!in_array($status, ['pending', 'approved', 'rejected'], true)) return false;
    $job = kj_job($job_id);
    if (!$job || ($status === 'approved' && (kj_job_is_expired($job) || kj_job_is_filled($job)))) return false;
    $stmt = kj_db()->prepare('UPDATE jobs SET status = ? WHERE id = ?');
    $stmt->execute([$status, $job_id]);
    if ($stmt->rowCount() === 0) return false;

    $employer = kj_employer((int) $job['employer_id']);
    if ($employer && !empty($employer['user_id'])) {
        $message = $status === 'approved'
            ? 'Your job posting ' . $job['title'] . ' was approved and is now visible to job seekers.'
            : 'Your job posting ' . $job['title'] . ' was not approved. Review and edit it before resubmitting.';
        kj_notification_create((int) $employer['user_id'], 'job_review', $message);
    }
    return true;
}

function kj_job_update_for_employer(int $job_id, int $employer_id, array $data): bool {
    $stmt = kj_db()->prepare(
        'UPDATE jobs SET title = ?, type = ?, category = ?, location = ?, description = ?, requirements = ?,
                         salary_min = ?, salary_max = ?, positions_total = ?, deadline = ?, active = 1, status = \'pending\'
         WHERE id = ? AND employer_id = ?'
    );
    $stmt->execute([
        trim($data['title'] ?? ''),
        $data['type'] ?? 'Full-time',
        trim($data['category'] ?? 'General') ?: 'General',
        trim($data['location'] ?? 'Kinyinya') ?: 'Kinyinya',
        trim($data['description'] ?? ''),
        trim($data['requirements'] ?? ''),
        max(0, (int) ($data['salary_min'] ?? 0)),
        max(0, (int) ($data['salary_max'] ?? 0)),
        min(999, max(1, (int) ($data['positions_total'] ?? 1))),
        $data['deadline'] ?? date('Y-m-d'),
        $job_id,
        $employer_id,
    ]);
    return $stmt->rowCount() > 0;
}

function kj_job_record_view(int $job_id): void {
    if ($job_id <= 0 || !empty($_SESSION['viewed_jobs'][$job_id])) return;
    $stmt = kj_db()->prepare('UPDATE jobs SET views = views + 1 WHERE id = ?');
    $stmt->execute([$job_id]);
    if ($stmt->rowCount() > 0) $_SESSION['viewed_jobs'][$job_id] = true;
}

function kj_job_set_active_for_employer(int $job_id, int $employer_id, bool $active): bool {
    $stmt = kj_db()->prepare(
        "UPDATE jobs SET active = ? WHERE id = ? AND employer_id = ? AND deadline > CURDATE()
         AND positions_total > (SELECT COUNT(*) FROM applications WHERE job_id = jobs.id AND status = 'hired')"
    );
    $stmt->execute([$active ? 1 : 0, $job_id, $employer_id]);
    return $stmt->rowCount() > 0;
}

/**
 * #24 — Automatic job expiration.
 * Runs once per request, before any page reads job data, so any posting
 * whose expiration date has been reached is closed automatically — no manual step
 * needed from the employer or admin. In production this can additionally
 * run as a daily cron hitting the same UPDATE, so postings close even
 * when nobody is browsing the site.
 */
function kj_expire_jobs(): void {
    kj_db()->exec("UPDATE jobs SET active = 0 WHERE active = 1 AND deadline <= CURDATE()");
}

function kj_job_is_expired($job): bool {
    return strtotime($job['deadline']) <= strtotime('today');
}

function kj_job_positions_remaining(array $job): int {
    $total = max(1, (int) ($job['positions_total'] ?? 1));
    if (array_key_exists('positions_filled', $job)) {
        $filled = max(0, (int) $job['positions_filled']);
    } elseif (!empty($job['id'])) {
        $stmt = kj_db()->prepare("SELECT COUNT(*) FROM applications WHERE job_id = ? AND status = 'hired'");
        $stmt->execute([(int) ($job['id'] ?? 0)]);
        $filled = (int) $stmt->fetchColumn();
    } else {
        $filled = 0;
    }
    return max(0, $total - $filled);
}

function kj_job_is_filled(array $job): bool {
    return kj_job_positions_remaining($job) === 0;
}

function kj_job_is_visible($job): bool {
    return (bool) $job['active'] && $job['status'] === 'approved' && !kj_job_is_expired($job) && !kj_job_is_filled($job);
}

function kj_job_status_label($job): string {
    if ($job['status'] === 'pending')  return 'Pending admin review';
    if ($job['status'] === 'rejected') return 'Rejected by admin';
    if (kj_job_is_expired($job)) return 'Expired';
    if (kj_job_is_filled($job)) return 'Filled';
    return $job['active'] ? 'Active' : 'Closed';
}

function kj_job_status_class($job): string {
    if ($job['status'] === 'pending')  return 'under_review';
    if ($job['status'] === 'rejected') return 'rejected';
    if (kj_job_is_filled($job)) return 'hired';
    if (kj_job_is_expired($job) || !$job['active']) return 'expired';
    return 'active';
}

// ---------------------------------------------------------------------
// Employers / seekers
// ---------------------------------------------------------------------

function kj_employers(): array {
    return kj_index_by_id(kj_db()->query('SELECT * FROM employers ORDER BY name')->fetchAll());
}

function kj_users(): array {
    return kj_db()->query('SELECT id, email, role, is_active, created_at FROM users ORDER BY created_at DESC, id DESC')->fetchAll();
}

function kj_user_set_active(int $userId, bool $active): bool {
    if ($userId <= 0) return false;
    $stmt = kj_db()->prepare('UPDATE users SET is_active = ? WHERE id = ? AND role <> \'admin\'');
    $stmt->execute([$active ? 1 : 0, $userId]);
    return $stmt->rowCount() > 0;
}

function kj_employer($id) {
    $stmt = kj_db()->prepare('SELECT * FROM employers WHERE id = ?');
    $stmt->execute([(int) $id]);
    return $stmt->fetch() ?: null;
}

function kj_seekers(): array {
    return kj_index_by_id(kj_db()->query('SELECT * FROM seekers ORDER BY name')->fetchAll());
}

function kj_seeker($id) {
    $stmt = kj_db()->prepare('SELECT * FROM seekers WHERE id = ?');
    $stmt->execute([(int) $id]);
    return $stmt->fetch() ?: null;
}

function kj_seeker_update(int $id, array $data): bool {
    $stmt = kj_db()->prepare('UPDATE seekers SET name = ?, skills = ?, education = ?, location = ? WHERE id = ?');
    return $stmt->execute([
        trim($data['name'] ?? ''),
        trim($data['skills'] ?? ''),
        trim($data['education'] ?? ''),
        trim($data['location'] ?? ''),
        $id,
    ]);
}

function kj_seeker_resume_update(int $id, ?string $resumeUrl): bool {
    $stmt = kj_db()->prepare('UPDATE seekers SET resume_url = ? WHERE id = ?');
    return $stmt->execute([$resumeUrl, $id]);
}

function kj_employer_update(int $id, array $data): bool {
    $stmt = kj_db()->prepare('UPDATE employers SET name = ?, industry = ?, location = ?, about = ? WHERE id = ?');
    return $stmt->execute([
        trim($data['name'] ?? ''),
        trim($data['industry'] ?? ''),
        trim($data['location'] ?? ''),
        trim($data['about'] ?? ''),
        $id,
    ]);
}

// ---------------------------------------------------------------------
// Applications
// ---------------------------------------------------------------------

function kj_applications(): array {
    return kj_index_by_id(kj_db()->query('SELECT * FROM applications ORDER BY date DESC, id DESC')->fetchAll());
}

function kj_applications_for_job($job_id): array {
    $stmt = kj_db()->prepare('SELECT * FROM applications WHERE job_id = ? ORDER BY date DESC, id DESC');
    $stmt->execute([(int) $job_id]);
    return kj_index_by_id($stmt->fetchAll());
}

function kj_applications_for_seeker($seeker_id): array {
    $stmt = kj_db()->prepare('SELECT * FROM applications WHERE seeker_id = ? ORDER BY date DESC, id DESC');
    $stmt->execute([(int) $seeker_id]);
    return kj_index_by_id($stmt->fetchAll());
}

function kj_has_applied($seeker_id, $job_id): bool {
    $stmt = kj_db()->prepare('SELECT 1 FROM applications WHERE seeker_id = ? AND job_id = ? LIMIT 1');
    $stmt->execute([(int) $seeker_id, (int) $job_id]);
    return (bool) $stmt->fetchColumn();
}

function kj_application_create(int $seeker_id, int $job_id, string $cover_letter): ?int {
    $job = kj_job($job_id);
    if (!$job || !kj_job_is_visible($job)) return null;
    if (kj_has_applied($seeker_id, $job_id)) return null;
    $stmt = kj_db()->prepare(
        "INSERT INTO applications (job_id, seeker_id, date, status, cover_letter) VALUES (?, ?, ?, 'submitted', ?)"
    );
    try {
        $stmt->execute([$job_id, $seeker_id, date('Y-m-d'), $cover_letter !== '' ? $cover_letter : null]);
    } catch (PDOException $exception) {
        if ((string) $exception->getCode() === '23000') return null;
        throw $exception;
    }
    $applicationId = (int) kj_db()->lastInsertId();

    $employer = kj_employer($job['employer_id']);
    $seeker = kj_seeker($seeker_id);
    if ($employer && !empty($employer['user_id']) && $seeker) {
        kj_notification_create(
            (int) $employer['user_id'],
            'new_application',
            $seeker['name'] . ' submitted an application for ' . $job['title'] . '.'
        );
    }
    return $applicationId;
}

/** Updates an application's status, scoped to a job so an employer can only edit their own postings' applicants. */
function kj_application_set_status(int $application_id, int $job_id, string $status): bool {
    if (!in_array($status, ['submitted', 'under_review', 'shortlisted', 'hired', 'rejected'], true)) return false;
    $check = kj_db()->prepare('SELECT a.seeker_id, a.status AS current_status, j.title, j.positions_total FROM applications a JOIN jobs j ON j.id = a.job_id WHERE a.id = ? AND a.job_id = ?');
    $check->execute([$application_id, $job_id]);
    $application = $check->fetch();
    if (!$application) return false;

    if ($status === 'hired' && $application['current_status'] !== 'hired') {
        $filled = kj_db()->prepare("SELECT COUNT(*) FROM applications WHERE job_id = ? AND status = 'hired'");
        $filled->execute([$job_id]);
        if ((int) $filled->fetchColumn() >= max(1, (int) $application['positions_total'])) return false;
    }

    $stmt = kj_db()->prepare('UPDATE applications SET status = ? WHERE id = ? AND job_id = ?');
    $stmt->execute([$status, $application_id, $job_id]);
    if ($stmt->rowCount() === 0) return false;

    $seeker = kj_seeker((int) $application['seeker_id']);
    if ($seeker && !empty($seeker['user_id'])) {
        kj_notification_create(
            (int) $seeker['user_id'],
            'application_status',
            'Your application for ' . $application['title'] . ' is now ' . kj_status_label($status) . '.'
        );
    }
    return true;
}

function kj_status_label($status) {
    $labels = [
        'submitted' => 'Submitted', 'under_review' => 'Under review',
        'shortlisted' => 'Shortlisted', 'rejected' => 'Not selected', 'hired' => 'Hired',
    ];
    return $labels[$status] ?? ucfirst($status);
}

// ---------------------------------------------------------------------
// Messages
// ---------------------------------------------------------------------

function kj_messages_for_application(int $application_id): array {
    $stmt = kj_db()->prepare('SELECT * FROM messages WHERE application_id = ? ORDER BY sent_at ASC, id ASC');
    $stmt->execute([$application_id]);
    return $stmt->fetchAll();
}

// ---------------------------------------------------------------------
// Notifications
// ---------------------------------------------------------------------

function kj_notification_create(int $user_id, string $type, string $message): void {
    $stmt = kj_db()->prepare('INSERT INTO notifications (user_id, type, message) VALUES (?, ?, ?)');
    $stmt->execute([$user_id, $type, mb_substr($message, 0, 255)]);
}

function kj_notifications_for_user(int $user_id, int $limit = 6): array {
    $stmt = kj_db()->prepare('SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC, id DESC LIMIT ?');
    $stmt->bindValue(1, $user_id, PDO::PARAM_INT);
    $stmt->bindValue(2, $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function kj_mark_notifications_read(int $user_id): void {
    $stmt = kj_db()->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0');
    $stmt->execute([$user_id]);
}

function kj_message_create(int $application_id, string $sender, string $body): int {
    $stmt = kj_db()->prepare(
        "INSERT INTO messages (application_id, sender, body, sent_at, is_read) VALUES (?, ?, ?, NOW(), 0)"
    );
    $stmt->execute([$application_id, $sender, $body]);
    return (int) kj_db()->lastInsertId();
}

function kj_mark_messages_read(int $application_id, string $viewerRole): void {
    $stmt = kj_db()->prepare('UPDATE messages SET is_read = 1 WHERE application_id = ? AND sender <> ? AND is_read = 0');
    $stmt->execute([$application_id, $viewerRole]);
}

// ---------------------------------------------------------------------
// Auth / session helpers
// ---------------------------------------------------------------------

function kj_csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
    return $_SESSION['csrf_token'];
}

function kj_csrf_valid(?string $token): bool {
    return is_string($token) && hash_equals(kj_csrf_token(), $token);
}

function kj_account_by_email(string $email) {
    $stmt = kj_db()->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([strtolower(trim($email))]);
    return $stmt->fetch() ?: null;
}

function kj_login(string $email, string $password): bool {
    $account = kj_account_by_email($email);
    if (!$account || !(int) $account['is_active'] || !password_verify($password, $account['password_hash'])) return false;

    $profileId = 0;
    if ($account['role'] === 'seeker') {
        $stmt = kj_db()->prepare('SELECT id FROM seekers WHERE user_id = ?');
        $stmt->execute([$account['id']]);
        $profileId = (int) $stmt->fetchColumn();
    } elseif ($account['role'] === 'employer') {
        $stmt = kj_db()->prepare('SELECT id FROM employers WHERE user_id = ?');
        $stmt->execute([$account['id']]);
        $profileId = (int) $stmt->fetchColumn();
    }
    if ($account['role'] !== 'admin' && !$profileId) return false;

    session_regenerate_id(true);
    $_SESSION['account_id'] = (int) $account['id'];
    $_SESSION['user_id'] = $profileId;
    $_SESSION['role'] = $account['role'];
    return true;
}

function kj_register(array $data): array {
    $name = trim($data['name'] ?? '');
    $email = strtolower(trim($data['email'] ?? ''));
    $password = (string) ($data['password'] ?? '');
    $role = $data['role'] ?? '';

    if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) return [false, 'Enter a valid name and email address.'];
    if (!in_array($role, ['seeker', 'employer'], true)) return [false, 'Choose job seeker or employer.'];
    if (strlen($password) < 6) return [false, 'Password must contain at least 6 characters.'];
    if (kj_account_by_email($email)) return [false, 'An account already exists with that email address.'];

    $pdo = kj_db();
    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare('INSERT INTO users (email, password_hash, role) VALUES (?, ?, ?)');
        $stmt->execute([$email, password_hash($password, PASSWORD_DEFAULT), $role]);
        $accountId = (int) $pdo->lastInsertId();

        if ($role === 'seeker') {
            $stmt = $pdo->prepare('INSERT INTO seekers (user_id, name, skills, education, location) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([$accountId, $name, '', '', 'Kinyinya']);
        } else {
            $stmt = $pdo->prepare('INSERT INTO employers (user_id, name, industry, location, about) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([$accountId, $name, 'General', 'Kinyinya', '']);
        }
        $pdo->commit();
        return [true, 'Account created successfully. You can now log in.'];
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        return [false, 'The account could not be created. Please try again.'];
    }
}

function kj_current_user() {
    if (!isset($_SESSION['role'])) return null;
    if ($_SESSION['role'] === 'seeker')   return kj_seeker($_SESSION['user_id']);
    if ($_SESSION['role'] === 'employer') return kj_employer($_SESSION['user_id']);
    if ($_SESSION['role'] === 'admin')    return ['id' => 0, 'name' => 'Platform Admin'];
    return null;
}

function kj_require_role($role) {
    $accountId = (int) ($_SESSION['account_id'] ?? 0);
    $sessionRole = $_SESSION['role'] ?? '';
    $accountActive = false;
    if ($accountId > 0 && $sessionRole === $role) {
        $stmt = kj_db()->prepare('SELECT 1 FROM users WHERE id = ? AND role = ? AND is_active = 1');
        $stmt->execute([$accountId, $sessionRole]);
        $accountActive = (bool) $stmt->fetchColumn();
    }
    if (!$accountActive) {
        unset($_SESSION['account_id'], $_SESSION['user_id'], $_SESSION['role']);
        $_SESSION['flash'] = 'Please sign in with an active ' . str_replace('_', ' ', $role) . ' account.';
        header('Location: ' . kj_url('index.php'));
        exit;
    }
}

function kj_money($amount) {
    return number_format((float) $amount, 0) . ' RWF';
}

function kj_salary_range(array $job): string {
    $minimum = max(0, (int) ($job['salary_min'] ?? 0));
    $maximum = max(0, (int) ($job['salary_max'] ?? 0));
    if ($minimum === 0 && $maximum === 0) return 'Salary not specified';
    if ($minimum === 0) return 'Up to ' . kj_money($maximum);
    if ($maximum === 0) return 'From ' . kj_money($minimum);
    return kj_money($minimum) . ' - ' . kj_money($maximum);
}
