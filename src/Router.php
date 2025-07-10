<?php

require_once __DIR__ . "/utils/httpHelper.php";

class Router
{
    private $routes = [];
    private $currentGroupPrefix = '';
    private $currentMiddlewares = [];

    public function group($prefix, $callback, $middlewares = [])
    {
        $previousPrefix = $this->currentGroupPrefix;
        $previousMiddlewares = $this->currentMiddlewares;

        $this->currentGroupPrefix .= $prefix;
        $this->currentMiddlewares = array_merge(
            $this->currentMiddlewares,
            is_array($middlewares) ? $middlewares : [$middlewares]
        );

        $callback($this);

        $this->currentGroupPrefix = $previousPrefix;
        $this->currentMiddlewares = $previousMiddlewares;
    }

    public function add($method, $path, $handler, $middlewares = [])
    {
        $fullPath = $this->currentGroupPrefix . $path;
        $allMiddlewares = array_merge($this->currentMiddlewares, $middlewares);

        $this->routes[] = [
            'method' => strtoupper($method),
            'path' => $fullPath,
            'handler' => $handler,
            'middlewares' => $allMiddlewares,
        ];
    }

    public function dispatch($method, $uri, $body)
    {
        foreach ($this->routes as $route) {
            if (
                $route['method'] === $method &&
                $route['path'] === $uri
            ) {
                // Run middlewares
                foreach ($route['middlewares'] as $middleware) {
                    $result = $middleware($body);
                    if ($result === false) {
                        // Middleware can stop execution
                        return;
                    }
                }
                // Call handler
                call_user_func($route['handler'], $body);

                // Exists
                return;
            }
        }
        respond(["error" => "Not found"], 404);
    }
}