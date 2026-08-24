<?php
/**
 * Kinyinya Jobs — database configuration.
 *
 * Edit the values below to match your MySQL setup, OR leave them as-is
 * and set the same names as environment variables (useful on hosts that
 * inject DB credentials that way, e.g. DB_HOST=... DB_NAME=... etc.).
 *
 * This file is intentionally the only place that holds credentials —
 * do not commit real production passwords here.
 */

$localConfigFile = __DIR__ . '/local-config.php';
$localConfig = is_file($localConfigFile) ? require $localConfigFile : [];

// Keep sessions beside the project so PHP CLI, Workbench setups, and XAMPP
// do not depend on different machine-wide session.save_path permissions.
if (session_status() === PHP_SESSION_NONE) {
    $sessionDirectory = dirname(__DIR__) . '/.runtime/sessions';
    if (!is_dir($sessionDirectory)) @mkdir($sessionDirectory, 0770, true);
    if (is_dir($sessionDirectory) && is_writable($sessionDirectory)) {
        ini_set('session.save_path', $sessionDirectory);
    }
}

function kj_config_value(string $name, string $default, array $localConfig): string {
    $environmentValue = getenv($name);
    if ($environmentValue !== false && $environmentValue !== '') return $environmentValue;
    return isset($localConfig[$name]) ? (string) $localConfig[$name] : $default;
}

define('DB_HOST', kj_config_value('DB_HOST', '127.0.0.1', $localConfig));
define('DB_PORT', kj_config_value('DB_PORT', '3306', $localConfig));
define('DB_NAME', kj_config_value('DB_NAME', 'kinyinya_jobs', $localConfig));
define('DB_USER', kj_config_value('DB_USER', 'kinyinya_app', $localConfig));
define('DB_PASS', kj_config_value('DB_PASS', 'kinyinya_dev_password', $localConfig));
define('KJ_CONFIGURED', is_file($localConfigFile) || getenv('DB_USER') !== false);

/** Return a URL that works at localhost:8000 and in any XAMPP htdocs subfolder. */
function kj_url(string $path = ''): string {
    $documentRoot = isset($_SERVER['DOCUMENT_ROOT']) ? realpath($_SERVER['DOCUMENT_ROOT']) : false;
    $projectRoot = realpath(dirname(__DIR__));
    $base = '';
    if ($documentRoot && $projectRoot) {
        $doc = str_replace('\\', '/', rtrim($documentRoot, '/\\'));
        $project = str_replace('\\', '/', rtrim($projectRoot, '/\\'));
        if (stripos($project, $doc) === 0) $base = substr($project, strlen($doc));
    }
    return rtrim($base, '/') . '/' . ltrim($path, '/');
}
