<?php

namespace Psa\Core\Web;

class Router
{
    public function __construct(
        protected array $routes
    )
    {
    }

    public function getRouteClass()
    {
        $route = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        if (isset($this->routes[$route])) {
            return $this->routes[$route];
        }

        return $this->routes['*'];
    }
}