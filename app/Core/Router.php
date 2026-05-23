<?php

declare(strict_types=1);

namespace App\Core;

class Router
{
    private array $routes = [];

    public function add(string $method, string $pattern, array $handler, array $options = []): void
    {
        $this->routes[] = [
            'method' => strtoupper($method),
            'pattern' => $pattern,
            'handler' => $handler,
            'options' => $options,
        ];
    }

    public function dispatch(string $method, string $uri): void
    {
        $normalizedMethod = strtoupper($method);
        $normalizedUri = '/' . trim($uri, '/');
        $normalizedUri = $normalizedUri === '//' ? '/' : $normalizedUri;

        foreach ($this->routes as $route) {
            if ($route['method'] !== $normalizedMethod) {
                continue;
            }

            $regex = preg_replace('#\{([^/]+)\}#', '(?P<$1>[^/]+)', $route['pattern']);
            $regex = '#^' . $regex . '$#';

            if (!preg_match($regex, $normalizedUri, $matches)) {
                continue;
            }

            if (($route['options']['auth'] ?? false) && !Auth::check()) {
                Session::flash('error', '先にログインしてください。');
                redirect('/admin/login');
            }

            [$controllerClass, $methodName] = $route['handler'];
            $controller = new $controllerClass();

            $params = array_values(array_filter(
                $matches,
                static fn ($key): bool => !is_int($key),
                ARRAY_FILTER_USE_KEY
            ));

            $controller->{$methodName}(...$params);
            return;
        }

        http_response_code(404);
        echo '404 Not Found';
    }
}

