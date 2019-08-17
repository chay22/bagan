<?php

declare(strict_types=1);

namespace Bagan\Test\Container;

use Bagan\Container\{
    Container,
    NotInstantiableException,
    UnresolvableDependencyException
};
use Bagan\Test\Container\Mock\{A, B, C};
use PHPUnit\Framework\{ExpectationFailedException, TestCase};

/**
 * @coversDefaultClass \Bagan\Container\Container
 */
class ContainerTest extends TestCase
{
    /**
     * Assert that container does not need service registration.
     * @covers ::inject
     */
    public function testContainerInjects()
    {
        $container = new Container();

        $a = $container->inject(A::class);
        $this->assertInstanceOf(A::class, $a);
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
    public function testContainerDependencyInjectionOnInjectionPrimitiveFailures()
    {
        $container = new Container();
        $container->register(D::class, D::class);

        $this->expectException(NotInstantiableException::class);
        $container->make(D::class);
    }

    /**
     * Assert that the container failed to instantiate primitive with default value.
     * @covers ::register
     * @covers ::make
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
     * @covers ::alias
     * @covers ::make
     * @covers ::inject
     * @covers ::resolveBinding
     */
    public function testContainerAlias()
    {
        $container = new Container();
        $container->register(A::class, A::class);
        $container->alias(A::class, 'myalias');

        $a = $container->make(A::class);
        $b = $container->make('myalias');

        $this->assertInstanceOf(A::class, $a);
        $this->assertInstanceOf(A::class, $b);
        $this->assertEquals($a, $b);
    }
}
