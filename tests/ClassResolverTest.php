<?php namespace Jonsa\PimpleResolver\Test;

use Jonsa\PimpleResolver\ClassResolver;
use Jonsa\PimpleResolver\Events;
use Jonsa\PimpleResolver\Test\Data\FooClass;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * Class ClassResolverTest
 *
 * @package Jonsa\PimpleResolver\Test
 * @author Jonas Sandström
 */
class ClassResolverTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var ClassResolver
     */
    private $resolver;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->resolver = new ClassResolver();
    }

    /**
     * @expectedException \Jonsa\PimpleResolver\Exception\BindingResolutionException
     */
    public function testBindingExceptionThrownIfClassCannotBeInstantiated()
    {
        $abstract = 'Jonsa\\PimpleResolver\\Test\\Data\\FooInterface';

        $this->resolver->resolve($abstract);
    }

    public function testResolveAbstractImplementation()
    {
        $abstract = 'Jonsa\\PimpleResolver\\Test\\Data\\FooInterface';
        $concrete = 'Jonsa\\PimpleResolver\\Test\\Data\\FooClass';

        $this->resolver->bind($abstract, $concrete);
        $object = $this->resolver->resolve($abstract);

        $this->assertInstanceOf($concrete, $object);
    }

    public function testAutomaticallyResolveConcreteClasses()
    {
        $concrete = 'Jonsa\\PimpleResolver\\Test\\Data\\FooClass';

        $object = $this->resolver->resolve($concrete);

        $this->assertInstanceOf($concrete, $object);
    }

    public function testResolvedClassesShouldBeDifferent()
    {
        $concrete = 'Jonsa\\PimpleResolver\\Test\\Data\\FooClass';

        $object1 = $this->resolver->resolve($concrete);
        $object2 = $this->resolver->resolve($concrete);

        $this->assertNotSame($object1, $object2);
    }

    public function testDependenciesAreAutomaticallyResolved()
    {
        $abstract = 'Jonsa\\PimpleResolver\\Test\\Data\\FooInterface';
        $concrete = 'Jonsa\\PimpleResolver\\Test\\Data\\FooClass';
        $expected = 'Jonsa\\PimpleResolver\\Test\\Data\\BarClass';

        $this->resolver->bind($abstract, $concrete);
        $object = $this->resolver->resolve($expected);

        $this->assertInstanceOf($expected, $object);
    }

    public function testConstructorArgumentsProvidedAtRuntime()
    {
        $concrete = 'Jonsa\\PimpleResolver\\Test\\Data\\BazClass';

        $object = $this->resolver->resolve($concrete, array(
            'extra' => 10
        ));

        $this->assertEquals(10, $object->extra);
    }

    public function testConcreteConstructorArgumentsProvidedAtRuntime()
    {
        $concrete = 'Jonsa\\PimpleResolver\\Test\\Data\\BazClass';
        $foo = new FooClass();

        $object1 = $this->resolver->resolve($concrete);
        $object2 = $this->resolver->resolve($concrete, array(
            'foo' => $foo,
        ));

        $this->assertNotSame($foo, $object1->foo);
        $this->assertSame($foo, $object2->foo);
    }

    public function testEventCallbackIsCalledWhenClassInstantiated()
    {
        $dispatcher = new EventDispatcher();
        $resolver = new ClassResolver($dispatcher);

        $concrete = 'Jonsa\\PimpleResolver\\Test\\Data\\FooClass';
        $count = 0;

        $dispatcher->addListener(Events::CLASS_RESOLVED, function () use (&$count) {
            $count++;
        });

        $resolver->resolve($concrete);

        $this->assertEquals(1, $count);
    }

}
