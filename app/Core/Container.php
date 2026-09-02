<?php

declare(strict_types=1);

namespace PWT\Core;

defined('ABSPATH') || exit;

use InvalidArgumentException;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionParameter;

/**
 * Lightweight dependency injection container.
 */
final class Container
{
    /**
     * Registered service providers.
     *
     * @var ServiceProvider[]
     */
    private array $providers = [];

    /**
     * Registered bindings.
     *
     * @var array<string, callable|string>
     */
    private array $bindings = [];

    /**
     * Singleton instances.
     *
     * @var array<string, object>
     */
    private array $instances = [];

    /**
     * Register a service provider.
     */
    public function register(string $provider): void
    {
        if (!is_subclass_of($provider, ServiceProvider::class)) {
            throw new InvalidArgumentException(
                sprintf(
                    '%s must extend %s.',
                    $provider,
                    ServiceProvider::class
                )
            );
        }

        $this->providers[] = new $provider($this);
    }

    /**
     * Register transient binding.
     */
    public function bind(string $abstract, callable|string $concrete): void
    {
        $this->bindings[$abstract] = $concrete;
    }

    /**
     * Register singleton binding.
     */
    public function singleton(string $abstract, callable|string $concrete): void
    {
        $this->bindings[$abstract] = $concrete;
    }

    /**
     * Resolve service.
     */
    public function make(string $abstract): object
    {
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        $object = $this->resolve($abstract);

        $this->instances[$abstract] = $object;

        return $object;
    }

    /**
     * Determine whether a service exists.
     */
    public function has(string $abstract): bool
    {
        return isset($this->bindings[$abstract])
            || isset($this->instances[$abstract])
            || class_exists($abstract);
    }

    /**
     * Boot providers.
     */
    public function boot(): void
    {
        foreach ($this->providers as $provider) {
            $provider->register();
        }

        foreach ($this->providers as $provider) {
            $provider->boot();
        }
    }

    /**
     * Resolve an object.
     */
    private function resolve(string $abstract): object
    {
        $concrete = $this->bindings[$abstract] ?? $abstract;

        if (is_callable($concrete)) {
            return $concrete($this);
        }

        $reflection = new ReflectionClass($concrete);

        if (!$reflection->isInstantiable()) {
            throw new InvalidArgumentException(
                sprintf('Class [%s] is not instantiable.', $concrete)
            );
        }

        $constructor = $reflection->getConstructor();

        if ($constructor === null) {
            return new $concrete();
        }

        $dependencies = [];

        foreach ($constructor->getParameters() as $parameter) {
            $dependencies[] = $this->resolveParameter($parameter);
        }

        return $reflection->newInstanceArgs($dependencies);
    }

    /**
     * Resolve constructor parameter.
     */
    private function resolveParameter(ReflectionParameter $parameter): mixed
    {
        $type = $parameter->getType();

        if (!$type instanceof ReflectionNamedType || $type->isBuiltin()) {
            if ($parameter->isDefaultValueAvailable()) {
                return $parameter->getDefaultValue();
            }

            throw new InvalidArgumentException(
                sprintf(
                    'Unable to resolve parameter $%s.',
                    $parameter->getName()
                )
            );
        }

        return $this->make($type->getName());
    }
}