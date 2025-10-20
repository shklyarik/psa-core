<?php

namespace Psa\Core\Cli;

use Psa\Core\Common\Container;
use ReflectionMethod;
use RuntimeException;
use Psa\Core\Common\AppTrait;

class App
{
    use AppTrait;

    public function __construct(
        protected array $alias,
        public readonly Container $di,
        public readonly Dispatcher $dispatcher,
    )
    {
    }

    public function run()
    {
        $fullClassName = $this->dispatcher->getCommandClass();
        $commandInstance = new $fullClassName;

        $refMethod = new ReflectionMethod($commandInstance, 'run');
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

        $result = $refMethod->invokeArgs($commandInstance, $args);
        if (is_string($result)) {
            echo $result . PHP_EOL;
        }
    }
}