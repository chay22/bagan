<?php

declare(strict_types=1);

namespace Bagan\Container;

use Closure;
use ReflectionClass;
use ReflectionException;
use ReflectionParameter;

class Container implements ContainerInterface
{
    /**
     * The container's shared instances.
     *
     * @var object[]
     */
    protected $instances = [];

    /**
     * The container's bindings.
     *
     * @var array[]
     */
    protected $bindings = [];

    /**
     * The registered type aliases.
     *
     * @var string[]
     */
    protected $aliases = [];

    /**
     * Register a singleton binding in the container.
     *
     * @param  string  $abstract
     * @param  \Closure|string  $concrete
     * @return void
     */
    public function singleton(string $abstract, $concrete)
    {
        $this->bindings[$abstract] = $concrete;
    }

    /**
     * Register a binding in the container. The difference is
     * we register this binding array.
     *
     * @param  string  $abstract
     * @param  \Closure|string  $concrete
     * @return void
     */
    public function register(string $abstract, $concrete)
    {
        $this->bindings[$abstract] = [$concrete];
    }

    /**
     * Unbind bindings from container. Either from singleton
     * or register method.
     *
     * @param  $string  $abstract
     * @return void
     */
    public function unbind(string $abstract)
    {
        if (isset($this->bindings[$abstract])) {
            unset($this->bindings[$abstract]);
        }

        if (isset($this->instances[$abstract])) {
            unset($this->instances[$abstract]);
        }

        foreach ($this->aliases as $alias => $abstraction) {
            if ($abstract === $abstraction) {
                unset($this->aliases[$abstract]);
            }
        }
    }

    /**
     * Resolve the given type from the container.
     *
     * @param  string  $abstract
     * @return mixed
     *
     * @throws \Bagan\Container\NotFoundException
     * @throws \Bagan\Container\NotInstantiableException
     */
    public function make(string $abstract)
    {
        if (isset($this->bindings[$abstract])) {
            if (! is_array($this->bindings[$abstract]) && isset($this->instances[$abstract])) {
                return $this->instances[$abstract];
            }

            return $this->instances[$abstract] = $this->resolveBinding($abstract);
        }

        if (isset($this->aliases[$abstract])) {
            return $this->make($this->aliases[$abstract]);
        }

        return $this->build($abstract);
    }

    /**
     * Resolve the given type from the container.
     *
     * @param  string  $abstract
     * @return mixed
     *
     * @throws \Bagan\Container\NotInstantiableException
     */
    protected function resolveBinding($abstract)
    {
        $concrete = $this->bindings[$abstract];

        if (is_array($concrete)) {
            $concrete = $concrete[0];
        }

        if ($concrete instanceof Closure) {
            return $concrete($this);
        }

        return $this->inject($concrete);
    }

    public function inject(string $concrete)
    {
        try {
            $reflector = new ReflectionClass($concrete);

            if (! $reflector->isInstantiable()) {
                throw new ReflectionException("Class {$concrete} is not instantiable");
            }
        } catch (ReflectionException $e) {
            throw new NotInstantiableException($e->getMessage());
        }

        $constructor = $reflector->getConstructor();

        if (is_null($constructor)) {
            return new $concrete;
        }

        $dependencies = $constructor->getParameters();

        $instances = $this->resolveDependencies($dependencies);

        return $reflector->newInstanceArgs($instances);
    }

    protected function resolveDependencies($dependencies)
    {
        $results = [];

        foreach ($dependencies as $dependency) {
            $results[] = is_null($dependency->getClass())
                            ? $this->resolvePrimitive($dependency)
                            : $this->resolveClass($dependency);
        }

        return $results;
    }

    /**
     * Resolve a non-class hinted primitive dependency.
     *
     * @param  \ReflectionParameter  $parameter
     * @return mixed
     *
     * @throws \Bagan\Container\UnresolvableDependencyException
     */
    protected function resolvePrimitive(ReflectionParameter $parameter)
    {
        if ($parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        }

        throw new UnresolvableDependencyException("Unresolvable dependency resolving [$parameter] in class {$parameter->getDeclaringClass()->getName()}");
    }

    /**
     * Resolve a class based dependency from the container.
     *
     * @param  \ReflectionParameter  $parameter
     * @return mixed
     *
     * @throws \Bagan\Container\NotInstantiableException
     * @throws \Bagan\Container\UnresolvableDependencyException
     */
    protected function resolveClass(ReflectionParameter $parameter)
    {
        try {
            return $this->make($parameter->getClass()->name);
        } catch (NotInstantiableException | UnresolvableDependencyException $e) {
            if ($parameter->isOptional()) {
                return $parameter->getDefaultValue();
            }

            throw $e;
        }
    }

    /**
     * Instantiate a concrete instance of the given type.
     *
     * @param  string  $abstract
     * @return mixed
     *
     * @throws \Bagan\Container\NotFoundException
     * @throws \Bagan\Container\NotInstantiableException
     */
    public function build(string $abstract)
    {
        if (isset($this->bindings[$abstract])) {
            $instance = $this->resolveBinding($abstract);

            if (! isset($this->instances[$abstract])) {
                $this->instances[$abstract] = $instance;
            }

            return $instance;
        }

        if (isset($this->aliases[$abstract])) {
            return $this->build($this->aliases[$abstract]);
        }

        return $this->inject($abstract);
    }

    /**
     * Alias binding with another abstract name.
     *
     * @param  string  $abstract
     * @param  string  $alias
     * @return void
     */
    public function alias(string $abstract, string $alias)
    {
        $this->aliases[$alias] = $abstract;
    }

    /**
     * Determine if the given concrete is buildable.
     *
     * @param  mixed   $concrete
     * @return bool
     */
    // protected function isBuildable($concrete): bool
    // {
    //     return is_string($concrete) || $concrete instanceof Closure;
    // }

    /**
     * Finds an entry of the container by its identifier and returns it.
     *
     * @param string $id Identifier of the entry to look for.
     * @return mixed
     *
     * @throws \Bagan\Container\NotFoundException
     * @throws \Bagan\Container\NotInstantiableException
     */
    public function get($id)
    {
        return $this->make($id);
    }

    /**
     * Returns true if the container can return an entry for the given identifier.
     * Returns false otherwise.
     *
     * `has($id)` returning true does not mean that `get($id)` will not throw an exception.
     * It does however mean that `get($id)` will not throw a `NotFoundExceptionInterface`.
     *
     * @param string $id Identifier of the entry to look for.
     *
     * @return bool
     */
    public function has($id): bool
    {
        return isset($this->bindings[$id]) ||
               isset($this->aliases[$id]);
    }
}
