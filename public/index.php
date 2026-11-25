<?php

declare(strict_types=1);

// PHP built-in server: let it serve real files (css/js/img) itself
if (PHP_SAPI === 'cli-server') {
    $file = __DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if (is_file($file)) {
        return false;
    }
}

require dirname(__DIR__) . '/app/bootstrap.php';

// Session (hardened cookie, idle timeout) ----------------------------------
session_name('AGRIMAPSID');
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'httponly' => true,
    'samesite' => 'Lax',
    'secure'   => !empty($_SERVER['HTTPS']),
]);
session_start();

$timeout = (int) config('session_timeout', 1800);
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout) {
    Auth::logout();
    flash('warning', 'Your session expired. Please log in again.');
}
$_SESSION['last_activity'] = time();

// Routes + dispatch --------------------------------------------------------
$router = new Router((string) config('base_url', ''));
require BASE_PATH . '/config/routes.php';
$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
