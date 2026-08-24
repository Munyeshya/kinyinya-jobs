<?php
/**
 * Kinyinya Jobs — MySQL connection (PDO).
 * Everything the app reads or writes — employers, seekers, jobs,
 * applications, messages — lives in the `kinyinya_jobs` database.
 * See database/schema.sql and database/seed.sql to set it up.
 */

require_once __DIR__ . '/config.php';

function kj_db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            die(
                '<h1>Database connection failed</h1>' .
                '<p>Could not connect to MySQL database <code>' . htmlspecialchars(DB_NAME) . '</code> ' .
                'on <code>' . htmlspecialchars(DB_HOST) . ':' . htmlspecialchars(DB_PORT) . '</code>.</p>' .
                '<p>Confirm MySQL is running, then use the one-time setup page to configure and import the database.</p>' .
                '<p><a href="' . htmlspecialchars(kj_url('setup.php')) . '">Open database setup</a></p>'
            );
        }
    }
    return $pdo;
}

/** Turn a list of rows (each with an 'id' key) into an array keyed by id, like the old in-memory arrays. */
function kj_index_by_id(array $rows): array {
    $out = [];
    foreach ($rows as $row) { $out[$row['id']] = $row; }
    return $out;
}
