<?php namespace Jonsa\PimpleResolver\Test;

use Jonsa\PimpleResolver\Events;
use Jonsa\PimpleResolver\ServiceProvider;
use Jonsa\PimpleResolver\Test\Data\Application;
use Jonsa\PimpleResolver\Test\Data\TestResolver;
use Pimple\Container;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * Class TestServiceProvider
 *
 * @package Jonsa\PimpleResolver\Test
 * @author Jonas Sandström
 */
class ServiceProviderTest extends \PHPUnit_Framework_TestCase
{

    public function testResolveMethodIsRegisteredInTheContainer()
    {
        $container = new Container();
        $container->register(new ServiceProvider(false));

        $concrete = 'Jonsa\\PimpleResolver\\Test\\Data\\FooClass';

        $object = $container['make']($concrete);

        $this->assertInstanceOf($concrete, $object);
    }

    public function testCustomMakeMethod()
    {
        $container = new Container();

        $this->assertFalse(isset($container['build']));
        $container->register(new ServiceProvider(false, 'build'));
        $this->assertTrue(isset($container['build']));

        $concrete = 'Jonsa\\PimpleResolver\\Test\\Data\\FooClass';
        $object = $container['build']($concrete);

        $this->assertInstanceOf($concrete, $object);
    }

    public function testCustomBindMethod()
    {
        $container = new Container();

        $this->assertFalse(isset($container['binding']));
        $container->register(new ServiceProvider(false, 'make', 'binding'));
        $this->assertTrue(isset($container['binding']));

        $abstract = 'Jonsa\\PimpleResolver\\Test\\Data\\FooInterface';
        $concrete = 'Jonsa\\PimpleResolver\\Test\\Data\\FooClass';
        $container['binding']($abstract, $concrete);

        $object = $container['make']($abstract);

        $this->assertInstanceOf($concrete, $object);
    }

    public function testCustomResolverClass()
    {
        $container = new Container();
        $container->register(new ServiceProvider(false));
        $resolver = new TestResolver();

        $container[ServiceProvider::CLASS_RESOLVER] = function () use ($resolver) {
            return $resolver;
        };

        $container['make']('Jonsa\\PimpleResolver\\Test\\Data\\FooClass');

        $this->assertEquals(1, $resolver->count);
    }

    public function testContainerInstanceRegisteredInTheContainer()
    {
        $container = new Container();
        $container->register(new ServiceProvider());

        $object = $container['make'](get_class($container));

        $this->assertSame($container, $object);
    }

    public function testExtendedContainerInstanceRegisteredInTheContainerWithBothNames()
    {
        $app = new Application();
        $app->register(new ServiceProvider());

        $object = $app['make'](get_class($app));
        $container = $app['make']('Pimple\\Container');

        $this->assertSame($app, $object);
        $this->assertSame($container, $object);
    }

    public function testEventListener()
    {
        $dispatcher = new EventDispatcher();
        $container = new Container();
        $container->register(new ServiceProvider(false), array(
            ServiceProvider::EVENT_DISPATCHER => $dispatcher
        ));

        $count = 1;
        $dispatcher->addListener(Events::CLASS_RESOLVED, function () use (&$count) {
            $count++;
        });

        $concrete = 'Jonsa\\PimpleResolver\\Test\\Data\\FooClass';
        $container['make']($concrete);

        $this->assertEquals(2, $count);
    }

    /**
     * @expectedException \PHPUnit_Framework_Error
     */
    public function testInvalidDispatcherProvidedThrowsAnError()
    {
        $container = new Container();
        $container->register(new ServiceProvider(false), array(
            ServiceProvider::EVENT_DISPATCHER => new \stdClass()
        ));

        $concrete = 'Jonsa\\PimpleResolver\\Test\\Data\\FooClass';
        $container['make']($concrete);
    }
}
