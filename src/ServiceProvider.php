<?php namespace Jonsa\PimpleResolver;

use Jonsa\PimpleResolver\Contract\ClassResolver as ClassResolverContract;
use Pimple\Container;
use Pimple\ServiceProviderInterface;

/**
 * Class ServiceProvider
 *
 * @package Jonsa\PimpleResolver
 * @author Jonas Sandström
 */
class ServiceProvider implements ServiceProviderInterface
{

    /**
     * This is the key used to bind the resolver implementation.
     *
     * @var string
     */
    const CLASS_RESOLVER = 'jonsa.pimple_resolver.class_resolver';

    /**
     * @var bool
     */
    private $bindContainerInstance;

    /**
     * @var string
     */
    private $makeMethod;

    /**
     * @var string
     */
    private $bindMethod;

    /**
     * @param bool $bindContainerInstance
     * @param string $makeMethod
     * @param string $bindMethod
     */
    public function __construct($bindContainerInstance = true, $makeMethod = 'make', $bindMethod = 'bind')
    {
        $this->bindContainerInstance = (bool)$bindContainerInstance;
        $this->makeMethod = $makeMethod;
        $this->bindMethod = $bindMethod;
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
        $that = $this;

        $container[self::CLASS_RESOLVER] = function () use ($that, $container) {
            $resolver = new ClassResolver();
            $that->bindContainer($resolver, $container);

            return $resolver;
        };

        $container[$this->makeMethod] = $container->protect(
            function ($abstract, $parameters = array()) use ($container) {
                return $container[ServiceProvider::CLASS_RESOLVER]
                    ->resolve($abstract, $parameters);
            }
        );

        $container[$this->bindMethod] = $container->protect(
            function ($abstract, $concrete, $protect = false) use ($container) {
                return $container[ServiceProvider::CLASS_RESOLVER]
                    ->bind($abstract, $concrete, $protect);
            }
        );
    }

    /**
     * @param ClassResolverContract $resolver
     * @param Container $container
     */
    private function bindContainer(ClassResolverContract $resolver, Container $container)
    {
        if ($this->bindContainerInstance) {
            $base = 'Pimple\\Container';
            $class = get_class($container);

            $resolver->bind($base, function () use ($container) {
                return $container;
            }, true);

            if ($class !== $base) {
                $resolver->bind($class, function () use ($container) {
                    return $container;
                }, true);
            }
        }
    }

}
