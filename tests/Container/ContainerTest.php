<?php

declare(strict_types=1);

namespace Bagan\Test\Container;

use Bagan\Container\{
    Container,
    NotFoundException,
    NotInstantiableException,
    UnresolvableDependencyException
};
use Bagan\Test\Container\Mock\{A, B, C, D, E};
use Exception;
use PHPUnit\Framework\{ExpectationFailedException, TestCase};
use ReflectionException;

/**
 * @coversDefaultClass \Bagan\Container\Container
 */
class ContainerTest extends TestCase
{
    /**
     * Assert that container does not need service registration.
     * @covers ::inject
     * @covers ::resolveDependencies
     * @covers ::resolvePrimitive
     */
    public function testContainerInjects()
    {
        $container = new Container();

        $a = $container->inject(A::class);
        $this->assertInstanceOf(A::class, $a);
    }

    /**
     * Assert that container can check for registered services.
     * @covers ::has
     * @covers ::alias
     * @covers ::singleton
     */
    public function testContainerChecks()
    {
        $container = new Container();

        $this->assertFalse($container->has(A::class));

        $c = $container->singleton(B::class, B::class);
        $this->assertTrue($container->has(B::class));

        $container->alias(B::class, 'myalias');
        $this->assertTrue($container->has('myalias'));
    }

    /**
     * Assert that container binding can be removed.
     * @covers ::alias
     * @covers ::unbind
     * @covers ::has
     * @covers ::inject
     * @covers ::make
     * @covers ::resolveBinding
     * @covers ::resolveDependencies
     * @covers ::resolvePrimitive
     * @covers ::singleton
     */
    public function testContainerRemovals()
    {
        $container = new Container();
        $container->unbind(A::class);

        $container->singleton(A::class, A::class);
        $this->assertTrue($container->has(A::class));
        $container->unbind(A::class);
        $this->assertFalse($container->has(A::class));

        $container->singleton(A::class, A::class);
        $this->assertTrue($container->has(A::class));
        $container->alias(A::class, 'myalias');
        $this->assertTrue($container->has('myalias'));
        $container->make(A::class);
        $container->unbind(A::class);
        $this->assertFalse($container->has(A::class));
    }

    /**
     * Asserts that the container can get a service.
     * @covers ::singleton
     * @covers ::has
     * @covers ::get
     * @covers ::make
     * @covers ::build
     * @covers ::inject
     * @covers ::resolveBinding
     * @covers ::resolveDependencies
     * @covers ::resolvePrimitive
     */
    public function testContainerRetrievals()
    {
        $container = new Container();
        $container->singleton(A::class, A::class);
        $this->assertTrue($container->has(A::class));
        $a = $container->get(A::class);
        $b = $container->make(A::class);
        $c = $container->build(A::class);
        $d = $container->inject(A::class);
        $this->assertInstanceOf(A::class, $a);
        $this->assertInstanceOf(A::class, $b);
        $this->assertInstanceOf(A::class, $c);
        $this->assertInstanceOf(A::class, $d);
    }

    /**
     * Asserts that the container can register a singleton.
     * @covers ::singleton
     * @covers ::make
     * @covers ::inject
     * @covers ::resolveBinding
     * @covers ::resolveDependencies
     * @covers ::resolvePrimitive
     */
    public function testContainerSingleton()
    {
        $container = new Container();
        $container->singleton(A::class, A::class);

        $a = $container->make(A::class);
        $this->assertInstanceOf(A::class, $a);

        $a->prop = 'sample';
        $b = $container->make(A::class);
        $this->assertEquals($a, $b);
        $this->assertSame($a, $b);
        $this->assertEquals($a->prop, $b->prop);
    }

    /**
     * Assert that the container can not build singleton.
     * @covers ::singleton
     * @covers ::inject
     * @covers ::make
     * @covers ::build
     * @covers ::resolveBinding
     * @covers ::resolveDependencies
     * @covers ::resolvePrimitive
     */
    public function testContainerBuildSingletonFail()
    {
        $container = new Container();
        $container->singleton(A::class, A::class);

        $a = $container->make(A::class);
        $this->assertInstanceOf(A::class, $a);

        $a->prop = 'sample';
        $b = $container->build(A::class);
        $this->assertInstanceOf(A::class, $b);

        $this->expectException(ExpectationFailedException::class);
        $this->assertEquals($a->prop, $b->prop);
        $this->assertEquals($a, $b);
    }

    /**
     * Assert that the container can not inject singleton.
     * @covers ::singleton
     * @covers ::make
     * @covers ::inject
     * @covers ::resolveBinding
     * @covers ::resolveDependencies
     * @covers ::resolvePrimitive
     */
    public function testContainerInjectSingletonFails()
    {
        $container = new Container();
        $container->singleton(A::class, A::class);

        $a = $container->make(A::class);
        $this->assertInstanceOf(A::class, $a);

        $a->prop = 'sample';
        $b = $container->inject(A::class);
        $this->assertInstanceOf(A::class, $b);

        $this->expectException(ExpectationFailedException::class);
        $this->assertEquals($a->prop, $b->prop);
        $this->assertSame($a, $b);
    }

    /**
     * Assert that the container always instantiate new service.
     * @covers ::register
     * @covers ::make
     * @covers ::build
     * @covers ::inject
     * @covers ::resolveBinding
     * @covers ::resolveDependencies
     * @covers ::resolvePrimitive
     */
    public function testContainerFreshObjects()
    {
        $container = new Container();
        $container->register(A::class, A::class);

        $a = $container->make(A::class);
        $this->assertInstanceOf(A::class, $a);
        $a->prop = 'sample';

        $b = $container->build(A::class);
        $this->assertInstanceOf(A::class, $b);
        $b->prop = 'sample2';

        $this->expectException(ExpectationFailedException::class);
        $this->assertEquals($a->prop, 'sample2');
        $this->assertEquals($a->prop, $b->prop);
        $this->assertSame($a, $b);
    }

    /**
     * Assert that container can instantiate object without registering.
     * @covers ::build
     * @covers ::inject
     * @covers ::resolveDependencies
     * @covers ::resolvePrimitive
     */
    public function testContainerFreshObjectsWithoutRegisters()
    {
        $container = new Container();
        $a = $container->build(A::class);

        $this->assertInstanceOf(A::class, $a);
    }

    /**
     * Assert that the container failed to instantiate primitive without default value.
     * @covers ::register
     * @covers ::make
     * @covers ::inject
     * @covers ::resolveBinding
     * @covers ::resolveDependencies
     * @covers ::resolvePrimitive
     */
    public function testContainerDependencyInjectionPrimitiveFailures()
    {
        $container = new Container();
        $container->register(B::class, B::class);

        $this->expectException(UnresolvableDependencyException::class);
        $container->make(B::class);
        $container->get(B::class);
    }

    /**
     * Assert that the container throw exception on not found service
     * @covers ::get
     * @covers ::inject
     * @covers ::make
     * @covers ::build
     * @covers ::has
     * @covers ::resolveDependencies
     */
    public function testContainerNotFounds()
    {
        $container = new Container();

        $this->expectException(ReflectionException::class);
        $container->get(E::class);
    }

    /**
     * Assert that the container failed to instantiate primitive without default value.
     * @covers ::register
     * @covers ::make
     * @covers ::build
     * @covers ::inject
     * @covers ::resolveBinding
     * @covers ::resolveDependencies
     * @covers ::resolveClass
     * @covers ::resolvePrimitive
     */
    public function testContainerDependencyInjectionOnInjectionPrimitiveFailures()
    {
        $container = new Container();
        $container->register(D::class, D::class);

        $this->expectException(UnresolvableDependencyException::class);
        $container->make(D::class);
    }

    /**
     * Assert that the container failed to instantiate primitive with default value.
     * @covers ::register
     * @covers ::make
     * @covers ::build
     * @covers ::inject
     * @covers ::resolveBinding
     * @covers ::resolveDependencies
     * @covers ::resolvePrimitive
     * @covers ::resolveClass
     */
    public function testContainerDependencyInjectionPrimitiveSuccess()
    {
        $container = new Container();
        $container->register(C::class, C::class);

        $c = $container->make(C::class);
        $this->assertInstanceOf(C::class, $c);
        $this->assertEquals($c->prop, 2);
    }

    /**
     * Assert that container can not retrieve service that is not instantiable.
     * @covers ::inject
     */
    public function testContainerNotInstantiableFailures()
    {
        $container = new Container();
        $this->expectException(NotInstantiableException::class);
        $container->inject('NotExistedClass');
    }

    /**
     * Assert that container can be retrieved with alias.
     * @covers ::register
     * @covers ::singleton
     * @covers ::alias
     * @covers ::make
     * @covers ::build
     * @covers ::inject
     * @covers ::resolveBinding
     * @covers ::resolveClass
     * @covers ::resolveDependencies
     * @covers ::resolvePrimitive
     */
    public function testContainerAlias()
    {
        $container = new Container();
        $container->singleton(A::class, A::class);
        $container->alias(A::class, 'myalias');
        $container->register(C::class, C::class);
        $container->alias(C::class, 'myalias2');

        $a = $container->make(A::class);
        $b = $container->make('myalias');
        $c = $container->build(C::class);
        $d = $container->build('myalias2');

        $this->assertInstanceOf(A::class, $a);
        $this->assertInstanceOf(A::class, $b);
        $this->assertInstanceOf(C::class, $c);
        $this->assertInstanceOf(C::class, $d);
        $this->assertEquals($a, $b);
    }

    /**
     * Assert that container accepts closure as the concrete.
     * @covers ::register
     * @covers ::singleton
     * @covers ::make
     * @covers ::inject
     * @covers ::resolveBinding
     * @covers ::resolveDependencies
     * @covers ::resolvePrimitive
     */
    public function testContainerConcreteClosures()
    {
        $container = new Container();
        $container->singleton(A::class, A::class);
        $container->register(B::class, function ($container) {
            return new B(2, $container->make(A::class));
        });

        $a = $container->make(A::class);
        $b = $container->make(B::class);

        $this->assertInstanceOf(A::class, $a);
        $this->assertInstanceOf(B::class, $b);
        $this->assertSame($a, $b->prop);
    }
}
