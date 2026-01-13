<?php

namespace SellNow\Core;

use Exception;
use ReflectionClass;
use ReflectionException;

/**
 * Simple Dependency Injection Container
 * 
 * Provides service registration, resolution, and singleton support.
 * Implements basic auto-wiring for constructor dependencies.
 * 
 * @package SellNow\Core
 */
class Container
{
    /**
     * Singleton instance of the container
     */
    private static ?Container $instance = null;

    /**
     * Registered services (bindings)
     * @var array<string, callable>
     */
    private array $bindings = [];

    /**
     * Singleton instances
     * @var array<string, object>
     */
    private array $instances = [];

    /**
     * Private constructor for singleton pattern
     */
    private function __construct()
    {
    }

    /**
     * Get the singleton instance of the container
     * 
     * @return Container
     */
    public static function getInstance(): Container
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Register a service binding
     * 
     * @param string $abstract The service identifier (usually interface or class name)
     * @param callable|string|null $concrete The concrete implementation or factory
     * @param bool $singleton Whether to treat as singleton
     * @return void
     */
    public function bind(string $abstract, $concrete = null, bool $singleton = false): void
    {
        // If no concrete provided, use abstract as concrete
        if ($concrete === null) {
            $concrete = $abstract;
        }

        // If concrete is a string (class name), wrap in factory
        if (is_string($concrete)) {
            $className = $concrete;
            $concrete = function ($container) use ($className) {
                return $container->build($className);
            };
        }

        $this->bindings[$abstract] = [
            'concrete' => $concrete,
            'singleton' => $singleton
        ];
    }

    /**
     * Register a singleton service
     * 
     * @param string $abstract The service identifier
     * @param callable|string|null $concrete The concrete implementation or factory
     * @return void
     */
    public function singleton(string $abstract, $concrete = null): void
    {
        $this->bind($abstract, $concrete, true);
    }

    /**
     * Register an existing instance as a singleton
     * 
     * @param string $abstract The service identifier
     * @param object $instance The instance to register
     * @return void
     */
    public function instance(string $abstract, object $instance): void
    {
        $this->instances[$abstract] = $instance;
    }

    /**
     * Resolve a service from the container
     * 
     * @param string $abstract The service identifier
     * @return mixed The resolved service
     * @throws Exception If service cannot be resolved
     */
    public function make(string $abstract)
    {
        // Check if we have a singleton instance
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        // Check if we have a binding
        if (isset($this->bindings[$abstract])) {
            $binding = $this->bindings[$abstract];
            $concrete = $binding['concrete'];

            // Resolve the concrete implementation
            $object = $concrete($this);

            // Store as singleton if needed
            if ($binding['singleton']) {
                $this->instances[$abstract] = $object;
            }

            return $object;
        }

        // Try to auto-wire if it's a class
        if (class_exists($abstract)) {
            return $this->build($abstract);
        }

        throw new Exception("Service [$abstract] not found in container and cannot be auto-wired.");
    }

    /**
     * Build a class instance with dependency injection
     * 
     * @param string $className The class name to build
     * @return object The built instance
     * @throws Exception If class cannot be built
     */
    public function build(string $className): object
    {
        try {
            $reflector = new ReflectionClass($className);
        } catch (ReflectionException $e) {
            throw new Exception("Class [$className] does not exist.");
        }

        // Check if class is instantiable
        if (!$reflector->isInstantiable()) {
            throw new Exception("Class [$className] is not instantiable.");
        }

        $constructor = $reflector->getConstructor();

        // If no constructor, just instantiate
        if ($constructor === null) {
            return new $className();
        }

        $parameters = $constructor->getParameters();
        $dependencies = [];

        // Resolve constructor dependencies
        foreach ($parameters as $parameter) {
            $type = $parameter->getType();

            // If parameter has no type hint, we can't auto-wire
            if ($type === null) {
                if ($parameter->isDefaultValueAvailable()) {
                    $dependencies[] = $parameter->getDefaultValue();
                } else {
                    throw new Exception(
                        "Cannot auto-wire parameter [{$parameter->getName()}] in class [$className]. No type hint provided."
                    );
                }
                continue;
            }

            // Get the type name
            $typeName = $type->getName();

            // Resolve the dependency
            try {
                $dependencies[] = $this->make($typeName);
            } catch (Exception $e) {
                if ($parameter->isDefaultValueAvailable()) {
                    $dependencies[] = $parameter->getDefaultValue();
                } else {
                    throw $e;
                }
            }
        }

        // Create instance with dependencies
        return $reflector->newInstanceArgs($dependencies);
    }

    /**
     * Check if a service is registered
     * 
     * @param string $abstract The service identifier
     * @return bool
     */
    public function has(string $abstract): bool
    {
        return isset($this->bindings[$abstract]) || isset($this->instances[$abstract]);
    }

    /**
     * Alias for make() - allows using container as function
     * 
     * @param string $abstract The service identifier
     * @return mixed
     */
    public function get(string $abstract)
    {
        return $this->make($abstract);
    }
}
