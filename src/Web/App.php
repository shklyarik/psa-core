<?php

namespace Psa\Core\Web;

use Psa\Core\Common\Container;
use RuntimeException;
use ReflectionMethod;

class App
{
    public function __construct(
        protected array $alias,
        protected Container $di,
        protected Router $router,
    )
    {
    }

    public function run()
    {
        $fullClassName = $this->router->getRouteClass();

        $action = new $fullClassName;

        $refMethod = new ReflectionMethod($action, 'run');
        $params = $refMethod->getParameters();

        $args = [];
        foreach ($params as $param) {
            $name = $param->getName();
            if ($name == 'app') {
                $args[] = $this;
            } else if ($this->di->has($name)) {
                $args[] = $this->di->get($name);
            } elseif ($param->isDefaultValueAvailable()) {
                $args[] = $param->getDefaultValue();
            } else {
                throw new RuntimeException("Not found DI instance for the ID: \${$name}");
            }
        }

        $result = $refMethod->invokeArgs($action, $args);

        if (is_array($result) || is_null($result)) {
            header('Content-Type: application/json');
            echo json_encode($result, JSON_UNESCAPED_UNICODE);
        } else {
            echo $result;
        }
    }
}
