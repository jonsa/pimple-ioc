<?php namespace Test\Jonsa\PimpleResolver;

use Jonsa\PimpleResolver\ClassResolver;
use Jonsa\PimpleResolver\Events;
use Pimple\Container;
use Test\Jonsa\PimpleResolver\Data\FooClass;

/**
 * Class ClassResolverTest
 *
 * @package Test\Jonsa\PimpleResolver
 * @author Jonas Sandström
 */
class ClassResolverTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @var Container
	 */
	private $container;

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

		$this->container = new Container();
		$this->resolver = new ClassResolver($this->container);
	}

	/**
	 * @expectedException \Jonsa\PimpleResolver\Exception\BindingResolutionException
	 */
	public function testBindingExceptionThrownIfClassCannotBeInstantiated()
	{
		$abstract = 'Test\\Jonsa\\PimpleResolver\\Data\\FooInterface';

		$this->resolver->resolve($abstract);
	}

	public function testResolveAbstractImplementation()
	{
		$abstract = 'Test\\Jonsa\\PimpleResolver\\Data\\FooInterface';
		$concrete = 'Test\\Jonsa\\PimpleResolver\\Data\\FooClass';

		$this->container[$abstract] = $concrete;

		$object = $this->resolver->resolve($abstract);

		$this->assertInstanceOf($concrete, $object);
	}

	public function testAutomaticallyResolveConcreteClasses()
	{
		$concrete = 'Test\\Jonsa\\PimpleResolver\\Data\\FooClass';

		$object = $this->resolver->resolve($concrete);

		$this->assertInstanceOf($concrete, $object);
	}

	public function testResolvedClassesShouldBeDifferent()
	{
		$concrete = 'Test\\Jonsa\\PimpleResolver\\Data\\FooClass';

		$object1 = $this->resolver->resolve($concrete);
		$object2 = $this->resolver->resolve($concrete);

		$this->assertNotSame($object1, $object2);
	}

	public function testDependenciesAreAutomaticallyResolved()
	{
		$abstract = 'Test\\Jonsa\\PimpleResolver\\Data\\FooInterface';
		$concrete = 'Test\\Jonsa\\PimpleResolver\\Data\\FooClass';
		$expected = 'Test\\Jonsa\\PimpleResolver\\Data\\BarClass';

		$this->container[$abstract] = $concrete;

		$object = $this->resolver->resolve($expected);

		$this->assertInstanceOf($expected, $object);
	}

	public function testConstructorArgumentsProvidedAtRuntime()
	{
		$concrete = 'Test\\Jonsa\\PimpleResolver\\Data\\BazClass';

		$object = $this->resolver->resolve($concrete, array(
			'extra' => 10
		));

		$this->assertEquals(10, $object->extra);
	}

	public function testConcreteConstructorArgumentsProvidedAtRuntime()
	{
		$concrete = 'Test\\Jonsa\\PimpleResolver\\Data\\BazClass';
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
		$concrete = 'Test\\Jonsa\\PimpleResolver\\Data\\FooClass';
		$count = 0;

		$this->resolver->registerEventListener(function () use (&$count) {
			$count++;
		});

		$this->resolver->resolve($concrete);

		$this->assertEquals(1, $count);
	}

	public function testEventListenersAreOnlyFiredForRequestedEvents()
	{
		$concrete = 'Test\\Jonsa\\PimpleResolver\\Data\\FooClass';
		$events = array(Events::CLASS_RESOLVED);
		$count1 = 0;
		$count2 = 0;

		$this->resolver->registerEventListener(function () use (&$count1) {
			$count1++;
		}, $events);

		$this->resolver->registerEventListener(function () use (&$count2) {
			$count2++;
		}, array());

		$this->resolver->resolve($concrete);

		$this->assertEquals(1, $count1);
		$this->assertEquals(0, $count2);
	}

}
