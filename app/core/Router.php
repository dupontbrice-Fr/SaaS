<?php
class Router {
    private array $routes = [];
    private string $basePath = '';

    public function get(string $path, callable|array $handler): void {
        $this->addRoute('GET', $path, $handler);
    }

    public function post(string $path, callable|array $handler): void {
        $this->addRoute('POST', $path, $handler);
    }

    public function put(string $path, callable|array $handler): void {
        $this->addRoute('PUT', $path, $handler);
    }

    public function delete(string $path, callable|array $handler): void {
        $this->addRoute('DELETE', $path, $handler);
    }

    private function addRoute(string $method, string $path, callable|array $handler): void {
        $pattern = preg_replace('/\{([a-z_]+)\}/', '(?P<$1>[^/]+)', $path);
        $pattern = '#^' . $pattern . '$#';
        $this->routes[] = compact('method', 'path', 'pattern', 'handler');
    }

    public function dispatch(): void {
        $method = $_SERVER['REQUEST_METHOD'];
        // Support method override via POST field
        if ($method === 'POST' && isset($_POST['_method'])) {
            $method = strtoupper($_POST['_method']);
        }

        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $uri = rtrim($uri, '/') ?: '/';

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) continue;
            if (preg_match($route['pattern'], $uri, $matches)) {
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                $this->callHandler($route['handler'], $params);
                return;
            }
        }

        // 404
        http_response_code(404);
        echo '<h1>404 - Page introuvable</h1>';
    }

    private function callHandler(callable|array $handler, array $params): void {
        if (is_callable($handler)) {
            call_user_func($handler, $params);
        } elseif (is_array($handler)) {
            [$class, $method] = $handler;
            $controller = new $class();
            $controller->$method($params);
        }
    }
}
