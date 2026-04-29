<?php

class Router {
    private $routes = [];

    public function get($uri, $action) {
        $this->routes['GET'][$uri] = $action;
    }

    public function post($uri, $action) {
        $this->routes['POST'][$uri] = $action;
    }
    public function put($uri, $action) {
        $this->routes['PUT'][$uri] = $action;
    }

    public function delete($uri, $action) {
        $this->routes['DELETE'][$uri] = $action;
    }
    public function patch($uri, $action) {
        $this->routes['PATCH'][$uri] = $action;
    }

    public function resolve($method, $uri) {
        $uri = rtrim($uri, '/') ?: '/';

        if (!isset($this->routes[$method])) {
            return $this->notFound();
        }

        foreach ($this->routes[$method] as $route => $action) {

            // convert {id} → regex
            $pattern = preg_replace('#\{[^/]+\}#', '([^/]+)', $route);
            $pattern = "#^" . $pattern . "$#";

            if (preg_match($pattern, $uri, $matches)) {

                array_shift($matches); // remove full match

                return $this->callAction($action, $matches);
            }
        }

        return $this->notFound();
    }
    public function add($method, $uri, $action) {
        $this->routes[$method][$uri] = $action;
    }

    private function callAction($action, $params = []) {

        // nếu là closure
        if (is_callable($action)) {
            return call_user_func_array($action, $params);
        }

        // Controller@method
        if (is_string($action)) {

            [$controller, $method] = explode('@', $action);

            if (str_contains($controller, '\\')) {
                $controllerClass = $controller;
            } else {
                $controllerClass = "App\\Controllers\\$controller";
            }

            if (!class_exists($controllerClass)) {
                throw new Exception("Controller $controllerClass not found");
            }

            $controller = new $controllerClass();

            return call_user_func_array([$controller, $method], $params);
        }
    }

    private function notFound() {
        http_response_code(404);
        echo json_encode([
            "status" => "error",
            "message" => "Route not found"
        ]);
    }
}