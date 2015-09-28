<?php namespace Jonsa\PimpleResolver;

use Pimple\Container;
use Pimple\ServiceProviderInterface;

/**
 * Class ServiceProvider
 *
 * @package Jonsa\PimpleResolver
 * @author Jonas Sandström
 */
class ServiceProvider implements ServiceProviderInterface {

	/**
	 * This is the key used to bind the resolver implementation.
	 *
	 * @var string
	 */
	const CLASS_RESOLVER = 'Jonsa\\PimpleResolver::class_resolver';

	/**
	 * This key will be populated with the resolver method key.
	 *
	 * @var string
	 */
	const CLASS_RESOLVER_KEY = 'Jonsa\\PimpleResolver::class_resolver_key';

	/**
	 * Use this key to register event listener.
	 *
	 * @var string
	 */
	const CLASS_RESOLVER_LISTENER = 'Jonsa\\PimpleResolver::class_resolver_listener';

	/**
	 * @var bool
	 */
	private $bindContainerInstance;

	/**
	 * @var string
	 */
	private $makeMethod;

	/**
	 * @param bool $bindContainerInstance
	 * @param string $makeMethod
	 */
	public function __construct($bindContainerInstance = true, $makeMethod = 'make')
	{
		$this->bindContainerInstance = (bool) $bindContainerInstance;
		$this->makeMethod = $makeMethod;
	}

	/**
	 * Registers services on the given container.
	 *
	 * This method should only be used to configure services and parameters.
	 * It should not get services.
	 *
	 * @param Container $container A container instance
	 */
	public function register(Container $container)
	{
		if ($this->bindContainerInstance) {
			$class = get_class($container);

			$container[$class] = $container->protect(function () use ($container) {
				return $container;
			});

			if ('Pimple\\Container' !== $class) {
				$container['Pimple\\Container'] = $container->protect(function () use ($container) {
					return $container;
				});
			}
		}

		$container[self::CLASS_RESOLVER] = function (Container $container) {
			return new ClassResolver($container);
		};

		$container[self::CLASS_RESOLVER_KEY] = $this->makeMethod;

		$container[$this->makeMethod] = $container->protect(
			function ($abstract, $parameters = array()) use ($container) {
				return $container[ServiceProvider::CLASS_RESOLVER]
					->resolve($abstract, $parameters);
			}
		);

		$container[self::CLASS_RESOLVER_LISTENER] = $container->protect(
			function (\Closure $listener, array $toEvents = null) use ($container) {
				$container[ServiceProvider::CLASS_RESOLVER]
					->registerEventListener($listener, $toEvents);
			}
		);
	}

}
