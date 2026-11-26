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

// Session (hardened cookie, optional idle timeout) -------------------------
$timeout = (int) config('session_timeout', 0);

// No idle timeout by default: you stay logged in until you log out or close
// the browser. PHP's garbage collector (XAMPP default: 24 min) must not delete
// the session file while the browser is still open, so give it a long life.
// Closing tabs is handled in the browser by assets/js/session-guard.js.
$keepAlive = $timeout > 0 ? $timeout : 60 * 60 * 24 * 30;
ini_set('session.gc_maxlifetime', (string) $keepAlive);

session_name('AGRIMAPSID');
session_set_cookie_params([
    'lifetime' => 0, // session cookie: deleted when the browser closes
    'path'     => '/',
    'httponly' => true,
    'samesite' => 'Lax',
    'secure'   => !empty($_SERVER['HTTPS']),
]);
session_start();

if ($timeout > 0 && isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout) {
    Auth::logout();
    flash('warning', 'Your session expired. Please log in again.');
}
$_SESSION['last_activity'] = time();

// Routes + dispatch --------------------------------------------------------
$router = new Router((string) config('base_url', ''));
require BASE_PATH . '/config/routes.php';
$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
