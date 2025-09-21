<?php

namespace Psa\Core\Web;

class Router
{
    public function __construct(
        protected array $routes
    )
    {
    }

    public function run()
    {
        $className = $this->getRouteClass();
        $action = new $className;

        $result = $action->run();
        if (is_array($result) || is_null($result)) {
            header('Content-Type: application/json');
            echo json_encode($result, JSON_UNESCAPED_UNICODE);
        }
    }

    private function getRouteClass()
    {
        $route = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        if (isset($this->routes[$route])) {
            return $this->routes[$route];
        }

        return $this->routes['*'];
    }
}