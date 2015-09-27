<?php namespace Test\Jonsa\PimpleResolver;

use Jonsa\PimpleResolver\Events;
use Jonsa\PimpleResolver\ServiceProvider;
use Pimple\Container;
use Test\Jonsa\PimpleResolver\Data\Application;
use Test\Jonsa\PimpleResolver\Data\TestResolver;

/**
 * Class TestServiceProvider
 *
 * @package Test\Jonsa\PimpleResolver
 * @author Jonas Sandström
 */
class ServiceProviderTest extends \PHPUnit_Framework_TestCase {

	public function testResolveMethodIsRegisteredInTheContainer()
	{
		$container = new Container();
		$container->register(new ServiceProvider(false));

		$concrete = 'Test\\Jonsa\\PimpleResolver\\Data\\FooClass';

		$object = $container['make']($concrete);

		$this->assertInstanceOf($concrete, $object);
	}

	public function testCustomMakeMethod()
	{
		$container = new Container();
		$container->register(new ServiceProvider(false, 'build'));

		$concrete = 'Test\\Jonsa\\PimpleResolver\\Data\\FooClass';

		$object = $container['build']($concrete);

		$this->assertEquals('build', $container[ServiceProvider::CLASS_RESOLVER_KEY]);
		$this->assertInstanceOf($concrete, $object);
	}

	public function testCustomResolverClass()
	{
		$container = new Container();
		$container->register(new ServiceProvider(false));
		$resolver = new TestResolver($container);

		$container[ServiceProvider::CLASS_RESOLVER] = function () use ($resolver) {{
			return $resolver;
		}};

		$container['make']('Test\\Jonsa\\PimpleResolver\\Data\\FooClass');

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

	public function testEventListenerMethodIsRegisteredOnTheContainer()
	{
		$container = new Container();
		$container->register(new ServiceProvider(false));
		$count = 1;

		$container[ServiceProvider::CLASS_RESOLVER_LISTENER](
			function () use (&$count) {
				$count++;
			},
			array(Events::CLASS_RESOLVED)
		);

		$concrete = 'Test\\Jonsa\\PimpleResolver\\Data\\FooClass';
		$container['make']($concrete);

		$this->assertEquals(2, $count);
	}

}
