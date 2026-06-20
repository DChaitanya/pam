<?php
/**
 * Central configuration loader.
 *
 * Secrets (DB credentials) are read from the environment or a local .env file
 * that is NEVER committed to version control (see .gitignore). This removes the
 * hardcoded credentials that previously lived in db_connect.php (finding S2).
 *
 * Returns an associative array of settings.
 */

$root = __DIR__;
$envFile = $root . '/.env';

// Minimal .env loader (no external dependency). Lines: KEY=VALUE, # comments allowed.
if (is_readable($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) {
            continue;
        }
        list($key, $value) = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        // Strip optional surrounding quotes.
        if (strlen($value) >= 2 &&
            ($value[0] === '"' || $value[0] === "'") &&
            substr($value, -1) === $value[0]) {
            $value = substr($value, 1, -1);
        }
        if (getenv($key) === false) {
            putenv("$key=$value");
            $_ENV[$key] = $value;
        }
    }
}

$env = function ($key, $default = null) {
    $value = getenv($key);
    return $value === false ? $default : $value;
};

return [
    'host'     => $env('DB_HOST', '127.0.0.1'),
    'port'     => (int) $env('DB_PORT', 3306),
    'database' => $env('DB_NAME', 'pam'),
    'username' => $env('DB_USER', 'root'),
    'password' => $env('DB_PASS', ''),
    'debug'    => filter_var($env('APP_DEBUG', '0'), FILTER_VALIDATE_BOOLEAN),
];
