<?php

namespace Bagan\Container;

use Psr\Container\ContainerInterface as BaseContainerInterface;

interface ContainerInterface extends BaseContainerInterface
{
    /**
     * Register a single binding in the container.
     *
     * @param  string  $abstract
     * @param  \Closure|string  $concrete
     * @return void
     */
    public function singleton(string $abstract, $concrete);

    /**
     * Register a binding in the container.
     *
     * @param  string  $abstract
     * @param  \Closure|string  $concrete
     * @return void
     */
    public function register(string $abstract, $concrete);

    /**
     * Resolve the given type from the container.
     *
     * @param  string  $abstract
     * @return mixed
     *
     * @throws \Bagan\Container\NotFoundException
     * @throws \Bagan\Container\NotInstantiableException
     */
    public function make(string $abstract);

    /**
     * Instantiate a concrete instance of the given type.
     *
     * @param  string  $abstract
     * @return mixed
     *
     * @throws \Bagan\Container\NotFoundException
     * @throws \Bagan\Container\NotInstantiableException
     */
    public function build(string $abstract);

    /**
     * Alias binding with another abstract name.
     *
     * @param  string  $abstract
     * @param  string  $alias
     * @return void
     */
    public function alias(string $abstract, string $alias);
}
