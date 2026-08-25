<?php
/** Add job capacity to an existing Kinyinya Jobs database. */
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Run this migration from the command line.\n");
}

require_once __DIR__ . '/../includes/db.php';

$pdo = kj_db();
$stmt = $pdo->prepare(
    'SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?'
);
$stmt->execute(['jobs', 'positions_total']);

if ((int) $stmt->fetchColumn() === 0) {
    $pdo->exec('ALTER TABLE jobs ADD COLUMN positions_total INT UNSIGNED NOT NULL DEFAULT 1 AFTER salary_max');
    echo "Added positions_total column.\n";
} else {
    echo "positions_total column already exists.\n";
}
