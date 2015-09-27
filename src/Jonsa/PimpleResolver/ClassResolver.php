<?php namespace Jonsa\PimpleResolver;

use Jonsa\PimpleResolver\Contract\ClassResolver as ClassResolverContract;
use Jonsa\PimpleResolver\Event\ClassResolvedEvent;
use Jonsa\PimpleResolver\Exception\BindingResolutionException;
use Pimple\Container;

/**
 * Class ClassResolver
 *
 * @package Jonsa\PimpleResolver
 * @author Jonas Sandström
 */
class ClassResolver implements ClassResolverContract {

	/**
	 * @var Container
	 */
	private $container;

	/**
	 * @var \Closure[]
	 */
	private $listeners = array();

	/**
	 * The stack of concretions being current built.
	 *
	 * @var array
	 */
	private $buildStack = array();

	/**
	 * @param Container $container
	 */
	public function __construct(Container $container)
	{
		$this->container = $container;
	}

	/**
	 * Register an event listener to class resolver.
	 *
	 * @param \Closure $listener
	 * @param array $toEvents
	 */
	public function registerEventListener(\Closure $listener, array $toEvents = null)
	{
		$this->listeners[] = array($listener, $toEvents);
	}

	/**
	 * Instantiate a concrete instance of the given type.
	 *
	 * @param string $abstract
	 * @param array $parameters
	 *
	 * @return mixed
	 */
	public function resolve($abstract, $parameters = array())
	{
		$concrete = $this->getConcrete($abstract);

		// We're ready to instantiate an instance of the concrete type registered for
		// the binding. This will instantiate the types, as well as resolve any of
		// its "nested" dependencies recursively until all have gotten resolved.
		if ($this->isBuildable($concrete, $abstract)) {
			$object = $this->build($concrete, $parameters);
		} else {
			$object = $this->resolve($concrete, $parameters);
		}

		$this->dispatch(Events::CLASS_RESOLVED, new ClassResolvedEvent($object));

		return $object;
	}

	/**
	 * Get the concrete type for a given abstract.
	 *
	 * @param string $abstract
	 *
	 * @return mixed   $concrete
	 */
	protected function getConcrete($abstract)
	{
		// If we don't have a registered resolver or concrete for the type, we'll just
		// assume each type is a concrete name and will attempt to resolve it as is
		// since the container should be able to resolve concretes automatically.
		if (!is_scalar($abstract)) {
			return $abstract;
		}

		if (!isset($this->container[$abstract])) {
			if ($this->missingLeadingSlash($abstract) && isset($this->container['\\' . $abstract])) {
				$abstract = '\\' . $abstract;
			}

			return $abstract;
		}

		return $this->container[$abstract];
	}

	/**
	 * Determine if the given abstract has a leading slash.
	 *
	 * @param string $abstract
	 *
	 * @return bool
	 */
	protected function missingLeadingSlash($abstract)
	{
		return is_string($abstract) && strpos($abstract, '\\') !== 0;
	}

	/**
	 * Determine if the given concrete is buildable.
	 *
	 * @param mixed $concrete
	 * @param string $abstract
	 *
	 * @return bool
	 */
	protected function isBuildable($concrete, $abstract)
	{
		return $concrete === $abstract || $concrete instanceof \Closure;
	}

	/**
	 * Instantiate a concrete instance of the given type.
	 *
	 * @param string $concrete
	 * @param array $parameters
	 *
	 * @return mixed
	 *
	 * @throws BindingResolutionException
	 */
	protected function build($concrete, $parameters = array())
	{
		// If the concrete type is actually a Closure, we will just execute it and
		// hand back the results of the functions, which allows functions to be
		// used as resolvers for more fine-tuned resolution of these objects.
		if ($concrete instanceof \Closure) {
			return $concrete($this, $parameters);
		}

		$reflector = new \ReflectionClass($concrete);

		// If the type is not instantiable, the developer is attempting to resolve
		// an abstract type such as an Interface of Abstract Class and there is
		// no binding registered for the abstractions so we need to bail out.
		if (!$reflector->isInstantiable()) {
			$message = "Target [$concrete] is not instantiable.";

			throw new BindingResolutionException($message);
		}

		$this->buildStack[] = $concrete;

		$constructor = $reflector->getConstructor();

		// If there are no constructors, that means there are no dependencies then
		// we can just resolve the instances of the objects right away, without
		// resolving any other types or dependencies out of these containers.
		if (is_null($constructor)) {
			array_pop($this->buildStack);

			return new $concrete;
		}

		$dependencies = $constructor->getParameters();

		// Once we have all the constructor's parameters we can create each of the
		// dependency instances and then use the reflection instances to make a
		// new instance of this class, injecting the created dependencies in.
		$parameters = $this->keyParametersByArgument(
			$dependencies, $parameters
		);

		$instances = $this->getDependencies(
			$dependencies, $parameters
		);

		array_pop($this->buildStack);

		return $reflector->newInstanceArgs($instances);
	}

	/**
	 * If extra parameters are passed by numeric ID, rekey them by argument name.
	 *
	 * @param array $dependencies
	 * @param array $parameters
	 *
	 * @return array
	 */
	protected function keyParametersByArgument(array $dependencies, array $parameters)
	{
		foreach ($parameters as $key => $value) {
			if (is_numeric($key)) {
				unset($parameters[$key]);

				$parameters[$dependencies[$key]->name] = $value;
			}
		}

		return $parameters;
	}

	/**
	 * Resolve all of the dependencies from the ReflectionParameters.
	 *
	 * @param \ReflectionParameter[] $parameters
	 * @param array $primitives
	 *
	 * @return array
	 */
	protected function getDependencies($parameters, array $primitives = array())
	{
		$dependencies = array();

		foreach ($parameters as $parameter) {
			$dependency = $parameter->getClass();

			// If the class is null, it means the dependency is a string or some other
			// primitive type which we can not resolve since it is not a class and
			// we will just bomb out with an error since we have no-where to go.
			if (array_key_exists($parameter->name, $primitives)) {
				$dependencies[] = $primitives[$parameter->name];
			} elseif (is_null($dependency)) {
				$dependencies[] = $this->resolveNonClass($parameter);
			} else {
				$dependencies[] = $this->resolveClass($parameter);
			}
		}

		return (array) $dependencies;
	}

	/**
	 * Resolve a non-class hinted dependency.
	 *
	 * @param \ReflectionParameter $parameter
	 *
	 * @return mixed
	 *
	 * @throws BindingResolutionException
	 */
	protected function resolveNonClass(\ReflectionParameter $parameter)
	{
		if ($parameter->isDefaultValueAvailable()) {
			return $parameter->getDefaultValue();
		}

		$message = "Unresolvable dependency resolving [$parameter] in class {$parameter->getDeclaringClass()->getName()}";

		throw new BindingResolutionException($message);
	}

	/**
	 * Resolve a class based dependency from the container.
	 *
	 * @param \ReflectionParameter $parameter
	 *
	 * @return mixed
	 *
	 * @throws BindingResolutionException
	 */
	protected function resolveClass(\ReflectionParameter $parameter)
	{
		try {
			return $this->resolve($parameter->getClass()->name);
		} catch (BindingResolutionException $e) {
			// If we can not resolve the class instance, we will check to see if the value
			// is optional, and if it is we will return the optional parameter value as
			// the value of the dependency, similarly to how we do this with scalars.

			if ($parameter->isOptional()) {
				return $parameter->getDefaultValue();
			}

			throw $e;
		}
	}

	/**
	 * Relay events to all listeners.
	 *
	 * @param string $type
	 * @param mixed $event
	 */
	protected function dispatch($type, $event)
	{
		foreach ($this->listeners as $listener) {
			list($callback, $types) = $listener;

			if (null === $types || in_array($type, $types, true)) {
				$callback($event, $type);
			}
		}
	}

}
