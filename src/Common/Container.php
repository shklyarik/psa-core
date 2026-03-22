<?php

namespace Psa\Core\Common;

use Exception;
use InvalidArgumentException;
use ReflectionClass;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;

/**
 * Simple Dependency Injection (DI) container.
 *
 * Features:
 * - Stores and returns configured services.
 * - Keeps resolved services as shared instances.
 * - Supports autowiring by constructor type hints.
 * - Resolves callable arguments with support for:
 *   - parameter name lookup
 *   - type-compatible service lookup
 *   - ambiguity detection
 *
 * Resolution rules for object dependencies:
 * 1. If a service ID matches the parameter name, that service is used.
 * 2. The resolved service must be compatible with the parameter type.
 * 3. If no service matches the parameter name, the container searches for
 *    configured services matching the parameter type.
 * 4. If exactly one matching service is found, it is used.
 * 5. If multiple matching services are found, an exception is thrown.
 * 6. If no configured service matches, the container tries to autowire
 *    the class directly by its type.
 */
class Container
{
    /**
     * Service configuration.
     *
     * Example:
     * [
     *     'db' => [
     *         'class' => App\Db::class,
     *         'host' => 'localhost',
     *         'user' => 'root',
     *         'password' => '',
     *     ],
     * ]
     *
     * @var array<string, array<string, mixed>>
     */
    private array $config;

    /**
     * Already created shared instances.
     *
     * @var array<string, mixed>
     */
    private array $instances = [];

    /**
     * Cached reflection objects.
     *
     * @var array<string, ReflectionClass<object>>
     */
    private array $reflections = [];

    /**
     * Create a new container instance.
     *
     * @param array<string, array<string, mixed>> $config Service configuration.
     */
    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    /**
     * Get a service by ID or class name.
     *
     * If the service is configured, it is built from configuration and then
     * stored as a shared instance.
     *
     * If the given ID is a class name and no configured service exists for it,
     * the class is autowired directly and also stored as a shared instance.
     *
     * @param string $id Service ID or fully qualified class name.
     *
     * @return mixed
     *
     * @throws InvalidArgumentException If the service cannot be found.
     * @throws Exception If the service cannot be created.
     */
    public function get(string $id)
    {
        if (array_key_exists($id, $this->instances)) {
            return $this->instances[$id];
        }

        if (array_key_exists($id, $this->config)) {
            $definition = $this->config[$id];

            if (!isset($definition['class']) || !is_string($definition['class'])) {
                throw new InvalidArgumentException(
                    "Service '{$id}' must contain a valid 'class' definition."
                );
            }

            $class = $definition['class'];
            unset($definition['class']);

            $instance = $this->build($class, $definition);
            $this->instances[$id] = $instance;

            return $instance;
        }

        if (class_exists($id)) {
            $instance = $this->build($id);
            $this->instances[$id] = $instance;

            return $instance;
        }

        throw new InvalidArgumentException("Service '{$id}' not found.");
    }

    /**
     * Check whether the container can provide a service.
     *
     * Returns true when:
     * - an instance already exists
     * - a service configuration exists
     * - the given ID is a valid class name that can potentially be autowired
     *
     * @param string $id Service ID or class name.
     *
     * @return bool
     */
    public function has(string $id): bool
    {
        return array_key_exists($id, $this->instances)
            || array_key_exists($id, $this->config)
            || class_exists($id);
    }

    /**
     * Call a function or method with automatically resolved arguments.
     *
     * Explicitly provided arguments have the highest priority.
     * Remaining arguments are resolved from the container.
     *
     * Supported callables:
     * - Closure
     * - function name
     * - [$object, 'method']
     * - [ClassName::class, 'method'] for static methods
     *
     * @param callable $callable Function or method to call.
     * @param array<string, mixed> $arguments Explicit argument values by parameter name.
     *
     * @return mixed
     *
     * @throws Exception If one of the arguments cannot be resolved.
     */
    public function call(callable $callable, array $arguments = [])
    {
        if (is_array($callable)) {
            $reflection = new ReflectionMethod($callable[0], $callable[1]);
        } else {
            $reflection = new ReflectionFunction($callable);
        }

        $usedServiceIds = [];
        $resolvedArguments = $this->resolveParameters(
            $reflection->getParameters(),
            $arguments,
            $usedServiceIds
        );

        return $callable(...$resolvedArguments);
    }

    /**
     * Build an object instance and resolve its constructor dependencies.
     *
     * Explicit constructor parameters provided in $params have priority over
     * container resolution.
     *
     * @param string $class Fully qualified class name.
     * @param array<string, mixed> $params Explicit constructor parameters.
     *
     * @return object
     *
     * @throws Exception If the class cannot be instantiated or a dependency
     *                   cannot be resolved.
     */
    private function build(string $class, array $params = []): object
    {
        $reflector = $this->getReflection($class);

        if (!$reflector->isInstantiable()) {
            throw new Exception("Class '{$class}' is not instantiable.");
        }

        $constructor = $reflector->getConstructor();
        if ($constructor === null) {
            return new $class();
        }

        $usedServiceIds = [];
        $dependencies = $this->resolveParameters(
            $constructor->getParameters(),
            $params,
            $usedServiceIds
        );

        /** @var object */
        return $reflector->newInstanceArgs($dependencies);
    }

    /**
     * Resolve multiple parameters.
     *
     * Explicit values from $explicitParams are used first.
     * Remaining values are resolved from the container.
     *
     * @param ReflectionParameter[] $parameters Parameters to resolve.
     * @param array<string, mixed> $explicitParams Explicit values by parameter name.
     * @param array<string, bool> $usedServiceIds Service IDs already used during
     *                                            the current resolution pass.
     *
     * @return array<int, mixed>
     *
     * @throws Exception If one of the parameters cannot be resolved.
     */
    private function resolveParameters(
        array $parameters,
        array $explicitParams,
        array &$usedServiceIds
    ): array {
        $resolved = [];

        foreach ($parameters as $parameter) {
            $name = $parameter->getName();

            if (array_key_exists($name, $explicitParams)) {
                $resolved[] = $explicitParams[$name];
                continue;
            }

            $resolved[] = $this->resolveParameterFromContainer($parameter, $usedServiceIds);
        }

        return $resolved;
    }

    /**
     * Resolve a single parameter from the container.
     *
     * Resolution order:
     * 1. Service with the same ID as the parameter name.
     * 2. Exactly one configured service matching the parameter type.
     * 3. Direct autowiring of the parameter class.
     * 4. Default parameter value, if available.
     *
     * If multiple configured services match the parameter type, an exception
     * is thrown to prevent ambiguous dependency resolution.
     *
     * @param ReflectionParameter $parameter Parameter metadata.
     * @param array<string, bool> $usedServiceIds Service IDs already used during
     *                                            the current resolution pass.
     *
     * @return mixed
     *
     * @throws Exception If the parameter cannot be resolved.
     */
    private function resolveParameterFromContainer(
        ReflectionParameter $parameter,
        array &$usedServiceIds
    ) {
        $name = $parameter->getName();
        $type = $parameter->getType();

        if (!$type instanceof ReflectionNamedType || $type->isBuiltin()) {
            if ($parameter->isDefaultValueAvailable()) {
                return $parameter->getDefaultValue();
            }

            throw new Exception("Cannot resolve parameter \${$name}.");
        }

        $expectedClass = $type->getName();

        if (array_key_exists($name, $this->config)) {
            $service = $this->get($name);

            if (!$service instanceof $expectedClass) {
                $actualClass = get_debug_type($service);

                throw new Exception(
                    "Service '{$name}' resolved for parameter \${$name}, "
                    . "but expected '{$expectedClass}', got '{$actualClass}'."
                );
            }

            $usedServiceIds[$name] = true;

            return $service;
        }

        $matches = [];

        foreach ($this->config as $serviceId => $definition) {
            if (isset($usedServiceIds[$serviceId])) {
                continue;
            }

            if (!isset($definition['class']) || !is_string($definition['class'])) {
                continue;
            }

            $serviceClass = $definition['class'];

            if ($serviceClass === $expectedClass || is_subclass_of($serviceClass, $expectedClass)) {
                $matches[] = $serviceId;
            }
        }

        if (count($matches) === 1) {
            $serviceId = $matches[0];
            $service = $this->get($serviceId);
            $usedServiceIds[$serviceId] = true;

            return $service;
        }

        if (count($matches) > 1) {
            throw new Exception(
                "Ambiguous dependency for parameter \${$name} of type '{$expectedClass}'. "
                . "Matching services: " . implode(', ', $matches) . '.'
            );
        }

        if (class_exists($expectedClass)) {
            return $this->get($expectedClass);
        }

        if ($parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        }

        throw new Exception(
            "Cannot resolve parameter \${$name} of type '{$expectedClass}'."
        );
    }

    /**
     * Get a cached reflection instance for a class.
     *
     * @param string $class Fully qualified class name.
     *
     * @return ReflectionClass<object>
     *
     * @throws \ReflectionException If the class does not exist.
     */
    private function getReflection(string $class): ReflectionClass
    {
        if (!isset($this->reflections[$class])) {
            /** @var ReflectionClass<object> $reflection */
            $reflection = new ReflectionClass($class);
            $this->reflections[$class] = $reflection;
        }

        return $this->reflections[$class];
    }
}