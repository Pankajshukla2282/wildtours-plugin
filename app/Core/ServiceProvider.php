<?php

declare(strict_types=1);

namespace PWT\Core;

defined('ABSPATH') || exit;

/**
 * Base class for all plugin service providers.
 *
 * Service providers are responsible for:
 * - Registering services with the container
 * - Registering WordPress hooks
 * - Bootstrapping individual plugin modules
 */
abstract class ServiceProvider
{
    /**
     * Dependency injection container.
     */
    protected Container $container;

    /**
     * Create a new service provider instance.
     */
    final public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Get the container instance.
     */
    protected function container(): Container
    {
        return $this->container;
    }

    /**
     * Register a transient service.
     *
     * @param callable|string $concrete
     */
    protected function bind(string $abstract, callable|string $concrete): void
    {
        $this->container->bind($abstract, $concrete);
    }

    /**
     * Register a singleton service.
     *
     * @param callable|string $concrete
     */
    protected function singleton(string $abstract, callable|string $concrete): void
    {
        $this->container->singleton($abstract, $concrete);
    }

    /**
     * Resolve a service from the container.
     */
    protected function make(string $abstract): object
    {
        return $this->container->make($abstract);
    }

    /**
     * Determine whether a service exists.
     */
    protected function has(string $abstract): bool
    {
        return $this->container->has($abstract);
    }

    /**
     * Register services.
     *
     * Override in child providers if needed.
     */
    public function register(): void
    {
    }

    /**
     * Boot the provider.
     *
     * Override in child providers if needed.
     */
    public function boot(): void
    {
    }
}