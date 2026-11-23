<?php
/**
 * Minimal router: maps METHOD + path to "Controller@method".
 * Applies login, role and CSRF checks before calling the controller.
 */
class Router
{
    private array $routes = [];
    private string $basePath;

    public function __construct(string $basePath = '')
    {
        $this->basePath = rtrim($basePath, '/');
    }

    public function get(string $path, string $handler, array $options = []): void
    {
        $this->add('GET', $path, $handler, $options);
    }

    public function post(string $path, string $handler, array $options = []): void
    {
        $this->add('POST', $path, $handler, $options);
    }

    private function add(string $method, string $path, string $handler, array $options): void
    {
        // /farmers/{id} -> #^/farmers/(?P<id>[^/]+)$#
        $regex = '#^' . preg_replace('#\{(\w+)\}#', '(?P<$1>[^/]+)', $path) . '$#';
        $this->routes[] = [
            'method'  => $method,
            'regex'   => $regex,
            'handler' => $handler,
            'options' => $options,
        ];
    }

    public function dispatch(string $method, string $uri): void
    {
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        if ($this->basePath !== '' && str_starts_with($path, $this->basePath)) {
            $path = substr($path, strlen($this->basePath));
        }
        // Treat /index.php and /index.php/foo like / and /foo
        if (str_starts_with($path, '/index.php')) {
            $path = substr($path, strlen('/index.php'));
        }
        $path = '/' . trim($path, '/');

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method || !preg_match($route['regex'], $path, $m)) {
                continue;
            }
            $params = array_filter($m, 'is_string', ARRAY_FILTER_USE_KEY);
            $this->run($route, $params);
            return;
        }
        abort(404);
    }

    private function run(array $route, array $params): void
    {
        $opts = $route['options'];

        // 1. Login required unless route is public
        if (empty($opts['public']) && !Auth::check()) {
            flash('warning', 'Please log in to continue.');
            redirect('/login');
        }

        // 2. Role restriction
        if (!empty($opts['roles']) && !Auth::hasRole($opts['roles'])) {
            abort(403);
        }

        // 3. CSRF on every POST
        if ($route['method'] === 'POST' && !Csrf::verify()) {
            abort(419);
        }

        [$class, $action] = explode('@', $route['handler']);
        if (!class_exists($class) || !method_exists($class, $action)) {
            throw new RuntimeException("Route handler not found: {$route['handler']}");
        }
        (new $class())->$action(...array_values($params));
    }
}
