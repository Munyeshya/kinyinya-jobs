<?php
require_once __DIR__ . '/includes/data.php';

$role = $_SESSION['role'] ?? '';
if (!in_array($role, ['seeker', 'employer'], true)) {
    header('Location: ' . kj_url('index.php'));
    exit;
}
kj_require_role($role);

$applicationId = (int) ($_GET['application_id'] ?? $_POST['application_id'] ?? 0);
$stmt = kj_db()->prepare(
    'SELECT a.*, j.title, j.employer_id, s.name AS seeker_name, s.user_id AS seeker_user_id,
            e.name AS employer_name, e.user_id AS employer_user_id
     FROM applications a
     JOIN jobs j ON j.id = a.job_id
     JOIN seekers s ON s.id = a.seeker_id
     JOIN employers e ON e.id = j.employer_id
     WHERE a.id = ?'
);
$stmt->execute([$applicationId]);
$application = $stmt->fetch();

$profileId = (int) ($_SESSION['user_id'] ?? 0);
$allowed = $application && (
    ($role === 'seeker' && (int) $application['seeker_id'] === $profileId) ||
    ($role === 'employer' && (int) $application['employer_id'] === $profileId)
);
if (!$allowed) {
    $_SESSION['flash'] = 'You cannot access that conversation.';
    header('Location: ' . kj_url('index.php'));
    exit;
}

if (!in_array($application['status'], ['shortlisted', 'hired'], true)) {
    $_SESSION['flash'] = 'Messaging becomes available when an applicant is shortlisted.';
    $target = $role === 'seeker' ? 'seeker/dashboard.php' : 'employer/applicants.php?job_id=' . $application['job_id'];
    header('Location: ' . kj_url($target));
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $body = trim($_POST['body'] ?? '');
    if (!kj_csrf_valid($_POST['csrf'] ?? null)) {
        $error = 'The form expired. Refresh the page and try again.';
    } elseif ($body === '') {
        $error = 'Write a message before sending.';
    } elseif (mb_strlen($body) > 2000) {
        $error = 'Messages must be 2,000 characters or fewer.';
    } else {
        kj_message_create($applicationId, $role, $body);
        $recipientId = $role === 'seeker' ? (int) $application['employer_user_id'] : (int) $application['seeker_user_id'];
        $senderName = $role === 'seeker' ? $application['seeker_name'] : $application['employer_name'];
        if ($recipientId > 0) {
            kj_notification_create($recipientId, 'new_message', $senderName . ' sent you a message about ' . $application['title'] . '.');
        }
        header('Location: ' . kj_url('messages.php?application_id=' . $applicationId));
        exit;
    }
}

$messages = kj_messages_for_application($applicationId);
kj_mark_messages_read($applicationId, $role);
$pageTitle = 'Messages - Kinyinya Jobs';
require __DIR__ . '/includes/header.php';
?>
<section class="pagehead">
  <p class="eyebrow">Application conversation</p>
  <h1><?= htmlspecialchars($application['title']) ?></h1>
  <p><?= htmlspecialchars($application['employer_name']) ?> and <?= htmlspecialchars($application['seeker_name']) ?></p>
</section>

<div class="card conversation">
  <?php if (empty($messages)): ?>
    <p class="empty">No messages yet. Start the conversation below.</p>
  <?php else: ?>
    <?php foreach ($messages as $message): ?>
      <div class="message <?= $message['sender'] === $role ? 'mine' : '' ?>">
        <strong><?= $message['sender'] === 'employer' ? htmlspecialchars($application['employer_name']) : htmlspecialchars($application['seeker_name']) ?></strong>
        <p><?= nl2br(htmlspecialchars($message['body'])) ?></p>
        <small><?= htmlspecialchars($message['sent_at']) ?></small>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

<?php if ($error): ?><div class="flash error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<form method="post" class="card">
  <input type="hidden" name="csrf" value="<?= htmlspecialchars(kj_csrf_token()) ?>">
  <input type="hidden" name="application_id" value="<?= $applicationId ?>">
  <div class="field"><label for="body">New message</label><textarea id="body" name="body" maxlength="2000" placeholder="Write a message about this application."><?= htmlspecialchars($_POST['body'] ?? '') ?></textarea></div>
  <button class="btn btn-primary" type="submit">Send message</button>
  <a class="btn btn-ghost" href="<?= $role === 'seeker' ? 'seeker/dashboard.php' : 'employer/applicants.php?job_id=' . $application['job_id'] ?>">Back</a>
</form>
<?php require __DIR__ . '/includes/footer.php'; ?>
