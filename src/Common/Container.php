<?php

namespace Psa\Core\Common;

use InvalidArgumentException;
use ReflectionClass;
use Exception;

/**
 * Simple Dependency Injection (DI) Container.
 *
 * This container can:
 * - Store and retrieve predefined services from configuration.
 * - Automatically resolve and instantiate classes using reflection.
 * - Handle constructor dependencies recursively.
 */
class Container
{
    /**
     * @var array Configuration array with service definitions.
     * Example:
     * [
     *     'db' => [
     *         'class' => PDO::class,
     *         'dsn' => 'mysql:host=localhost;dbname=test',
     *         'username' => 'root',
     *         'password' => '',
     *     ],
     * ]
     */
    private array $config;

    /**
     * @var array Stores already created service instances (singleton style).
     */
    private array $instances = [];

    /**
     * Container constructor.
     *
     * @param array $config Service configuration.
     */
    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    /**
     * Retrieve a service by ID or class name.
     *
     * @param string $id Service identifier or class name.
     * @return mixed The resolved service instance.
     *
     * @throws InvalidArgumentException If the service is not found.
     */
    public function get(string $id)
    {
        // Return already instantiated service
        if (isset($this->instances[$id])) {
            return $this->instances[$id];
        }

        // If service is described in configuration
        if (isset($this->config[$id])) {
            $definition = $this->config[$id];
            $class = $definition['class'];
            unset($definition['class']);

            $object = $this->build($class, $definition);

            return $this->instances[$id] = $object;
        }

        // If it is a class without config — try to build it automatically
        if (class_exists($id)) {
            return $this->instances[$id] = $this->build($id);
        }

        throw new InvalidArgumentException("Service '$id' not found");
    }

    /**
     * Build a class instance with resolved dependencies.
     *
     * @param string $class Fully qualified class name.
     * @param array $params Additional parameters for constructor.
     * @return object The created instance.
     *
     * @throws Exception If a required dependency cannot be resolved.
     */
    private function build(string $class, array $params = [])
    {
        $reflector = new ReflectionClass($class);

        // If there is no constructor — create instance directly
        $constructor = $reflector->getConstructor();
        if (!$constructor) {
            return new $class;
        }

        $dependencies = [];
        foreach ($constructor->getParameters() as $param) {
            $name  = $param->getName();
            $type  = $param->getType();

            if ($type && !$type->isBuiltin()) {
                // If parameter is a class — try to resolve it from container
                $dependencies[] = $this->get($type->getName());
            } elseif (isset($params[$name])) {
                // If parameter is provided in config
                $dependencies[] = $params[$name];
            } elseif ($param->isDefaultValueAvailable()) {
                // If parameter has a default value
                $dependencies[] = $param->getDefaultValue();
            } else {
                throw new Exception("Cannot create $class: no value for \$$name");
            }
        }

        return $reflector->newInstanceArgs($dependencies);
    }

    /**
     * Check if container can provide a service.
     *
     * @param string $id Service identifier or class name.
     * @return bool
     */
    public function has(string $id): bool
    {
        if (isset($this->instances[$id])) {
            return true;
        }

        if (isset($this->config[$id])) {
            return true;
        }

        return false;
    }
}
