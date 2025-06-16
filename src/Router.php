<?php
class Router {
    private $routes = [];

    public function add($method, $path, $handler) {
        $this->routes[] = compact('method', 'path', 'handler');
    }

    public function dispatch($method, $uri, $body = null) {
        foreach ($this->routes as $route) {
            if ($method === $route['method'] && preg_match($this->convertToRegex($route['path']), $uri, $params)) {
                array_shift($params); // Remove full match
                // Pass $body as the first argument to the handler
                array_unshift($params, $body);
                return call_user_func_array($route['handler'], $params);
            }
        }
        http_response_code(404);
        echo json_encode(['error' => 'Not found']);
    }

    private function convertToRegex($path) {
        return '#^' . preg_replace('#\{[\w]+\}#', '([\w-]+)', $path) . '$#';
    }
}