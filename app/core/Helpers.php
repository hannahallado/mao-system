<?php
/**
 * Global helper functions (loaded before the autoloader).
 */

/** Read a config value, e.g. config('db.host') or config('debug'). */
function config(?string $key = null, mixed $default = null): mixed
{
    $value = $GLOBALS['__config'] ?? [];
    if ($key === null) {
        return $value;
    }
    foreach (explode('.', $key) as $segment) {
        if (!is_array($value) || !array_key_exists($segment, $value)) {
            return $default;
        }
        $value = $value[$segment];
    }
    return $value;
}

/** Escape output (always use in views). */
function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Build a URL relative to the app base. */
function url(string $path = ''): string
{
    return rtrim((string) config('base_url', ''), '/') . '/' . ltrim($path, '/');
}

function asset(string $path): string
{
    $path = ltrim($path, '/');
    // Append file modified time so browsers reload CSS/JS after changes
    $file = BASE_PATH . '/public/assets/' . $path;
    $version = is_file($file) ? '?v=' . filemtime($file) : '';
    return url('assets/' . $path) . $version;
}

function redirect(string $path): never
{
    header('Location: ' . url($path));
    exit;
}

/** Flash messages: flash('success', 'Saved.') then read once in the layout. */
function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function pull_flashes(): array
{
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $messages;
}

/** Remember submitted input so forms can refill after a validation error. */
function flash_old(array $input): void
{
    unset($input['password'], $input['_csrf']);
    $_SESSION['old'] = $input;
}

function old(string $key, mixed $default = ''): mixed
{
    return $_SESSION['old_now'][$key] ?? $default;
}

/** Called once per request by Controller::render so old() works inside views. */
function load_old(): void
{
    if (isset($_SESSION['old'])) {
        $_SESSION['old_now'] = $_SESSION['old'];
        unset($_SESSION['old']);
    } else {
        $_SESSION['old_now'] = [];   // old input lives for ONE page load only
    }
}

function flash_errors(array $errors): void
{
    $_SESSION['errors'] = $errors;
}

/** Field errors from the last failed validation: errors('last_name') */
function errors(?string $field = null): mixed
{
    $all = $_SESSION['errors_now'] ?? [];
    return $field === null ? $all : ($all[$field][0] ?? null);
}

function load_errors(): void
{
    if (isset($_SESSION['errors'])) {
        $_SESSION['errors_now'] = $_SESSION['errors'];
        unset($_SESSION['errors']);
    } else {
        $_SESSION['errors_now'] = [];
    }
}

function format_date(?string $date, string $format = 'M d, Y'): string
{
    if (!$date) {
        return '';
    }
    $ts = strtotime($date);
    return $ts ? date($format, $ts) : '';
}

/** Current request path without the base URL, e.g. "/farmers/5". */
function current_path(): string
{
    $current = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $base    = rtrim((string) config('base_url', ''), '/');
    if ($base !== '' && str_starts_with($current, $base)) {
        $current = substr($current, strlen($base));
    }
    return '/' . trim($current, '/');
}

/** Sidebar definition (config/menu.php). */
/** English plural of a noun phrase: "main commodity" -> "main commodities". */
function plural(string $phrase): string
{
    return match (true) {
        (bool) preg_match('/[^aeiou]y$/i', $phrase)   => substr($phrase, 0, -1) . 'ies',
        (bool) preg_match('/(s|x|z|ch|sh)$/i', $phrase) => $phrase . (preg_match('/(status|s)$/i', $phrase) ? '' : 'es'),
        default                                         => $phrase . 's',
    };
}

/** "5 minutes ago", "Yesterday", "in 3 days", "Tomorrow" (falls back to a date after a week). */
function time_ago(string $datetime): string
{
    $ts = strtotime($datetime);
    if (!$ts) {
        return '';
    }
    $diff = time() - $ts;
    $dayDiff = (int) round((strtotime(date('Y-m-d', time())) - strtotime(date('Y-m-d', $ts))) / 86400);
    if ($diff < 0) {   // future
        return match (true) {
            $dayDiff === 0  => 'Today',
            $dayDiff === -1 => 'Tomorrow',
            $dayDiff >= -14 => 'In ' . -$dayDiff . ' days',
            default         => date('M j, Y', $ts),
        };
    }
    return match (true) {
        $diff < 60      => 'Just now',
        $diff < 3600    => count_label(intdiv($diff, 60), 'minute') . ' ago',
        $dayDiff === 0  => count_label(intdiv($diff, 3600), 'hour') . ' ago',
        $dayDiff === 1  => 'Yesterday',
        $dayDiff < 7    => $dayDiff . ' days ago',
        default         => date('M j, Y', $ts),
    };
}

/** "1 boat", "3 boats". */
function count_label(int $n, string $noun): string
{
    return number_format($n) . ' ' . ($n === 1 ? $noun : plural($noun));
}

function menu(): array
{
    static $menu = null;
    return $menu ??= require BASE_PATH . '/config/menu.php';
}

/**
 * The sidebar item that matches the current page: the LONGEST item path that
 * equals the current path or is a parent of it (so /map/farms beats /map).
 */
function active_menu_path(array $menu, ?string $current = null): ?string
{
    $current = $current ?? current_path();
    $best    = null;
    foreach ($menu as $entry) {
        $paths = $entry['type'] === 'group' ? array_column($entry['items'], 1) : [$entry['path']];
        foreach ($paths as $path) {
            if (($current === $path || str_starts_with($current, $path . '/'))
                && ($best === null || strlen($path) > strlen($best))) {
                $best = $path;
            }
        }
        // Extra prefixes that belong to a link (e.g. /assistance under Programs)
        foreach ($entry['match'] ?? [] as $prefix) {
            if (($current === $prefix || str_starts_with($current, $prefix . '/')) && $best === null) {
                $best = $entry['path'];
            }
        }
    }
    return $best;
}

/**
 * Render a partial and return its HTML.
 *   partial('duplicate-modal')        -> views/partials/duplicate-modal.php
 *   partial('farmers/_form', [...])   -> views/farmers/_form.php  (names with a slash are relative to views/)
 */
function partial(string $name, array $data = []): string
{
    extract($data, EXTR_SKIP);
    ob_start();
    require APP_PATH . '/views/' . (str_contains($name, '/') ? $name : 'partials/' . $name) . '.php';
    return (string) ob_get_clean();
}

/** Current page's query string as array, minus some keys (for pagination / filter links). */
function query_without(array $drop = []): array
{
    $q = $_GET;
    foreach ($drop as $k) {
        unset($q[$k]);
    }
    return $q;
}

/** Stop the request with an error page (403, 404, 419, ...). */
function abort(int $code, string $message = ''): never
{
    $defaults = [
        403 => 'You do not have permission to open this page.',
        404 => 'The page you are looking for was not found.',
        419 => 'Your form expired. Please go back and try again.',
    ];
    http_response_code($code);
    $view   = is_file(APP_PATH . "/views/errors/$code.php") ? "errors/$code" : 'errors/error';
    $layout = class_exists('Auth') && Auth::check() ? 'main' : 'auth';
    echo Controller::render($view, [
        'code'    => $code,
        'title'   => 'Error ' . $code,
        'message' => $message ?: ($defaults[$code] ?? 'An error occurred.'),
    ], $layout);
    exit;
}
