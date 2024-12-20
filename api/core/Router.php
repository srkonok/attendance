<?php
namespace core;

class Router {
    private $routes = [];

    public function post($uri, $action) {
        $this->routes['POST'][$uri] = $action;
    }

    public function get($uri, $action) {
        $this->routes['GET'][$uri] = $action;
    }

    public function dispatch() {
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $method = $_SERVER['REQUEST_METHOD'];

        if (isset($this->routes[$method][$uri])) {
            $action = $this->routes[$method][$uri];
            $this->callAction($action);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Invalid API endpoint or method']);
        }
    }

    private function callAction($action) {
        list($controllerName, $methodName) = explode('@', $action);
        $controllerClass = 'controllers\\' . $controllerName;

        $controller = new $controllerClass();
        $controller->$methodName();
    }
}
?>
