<?php
/**
 * App configuration. Values come from .env (never commit the real .env).
 */
$envFile = dirname(__DIR__) . '/.env';
$env = is_file($envFile) ? parse_ini_file($envFile, false, INI_SCANNER_RAW) : [];
$get = static fn (string $key, string $default = '') => $env[$key] ?? (getenv($key) ?: $default);

return [
    'app_name'        => 'Municipal Agriculture Office',
    'debug'           => $get('APP_DEBUG', 'true') === 'true',
    // '' if the web server points at /public; '/mao-system/public' if under htdocs
    'base_url'        => rtrim($get('BASE_URL', ''), '/'),
    'timezone'        => 'Asia/Manila',
    // Seconds of inactivity before auto-logout. 0 = no idle logout (you stay
    // logged in until you log out, or close the browser / all system tabs).
    // Set SESSION_TIMEOUT in .env to turn the idle logout back on.
    'session_timeout' => (int) $get('SESSION_TIMEOUT', '0'),
    'per_page'        => 15,

    'db' => [
        'host' => $get('DB_HOST', '127.0.0.1'),
        'port' => $get('DB_PORT', '3306'),
        'name' => $get('DB_NAME', 'db_mao_system'),
        'user' => $get('DB_USER', 'root'),
        'pass' => $get('DB_PASS', ''),
    ],
];
