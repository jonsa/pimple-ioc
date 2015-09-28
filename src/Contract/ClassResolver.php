<?php namespace Jonsa\PimpleResolver\Contract;

/**
 * Interface ClassResolver
 *
 * @package Jonsa\PimpleResolver\Contract
 * @author Jonas Sandström
 */
interface ClassResolver {

	/**
	 * Register an event listener to class resolver.
	 *
	 * @param \Closure $listener
	 * @param array $toEvents
	 */
	public function registerEventListener(\Closure $listener, array $toEvents = null);

	/**
	 * Instantiate a concrete instance of the given type.
	 *
	 * @param string $abstract
	 * @param array $parameters
	 *
	 * @return mixed
	 */
	public function resolve($abstract, $parameters = array());

	/**
	 * Bind an abstract definition to a concrete implementation.
	 *
	 * @param string $abstract
	 * @param string|\Closure $concrete
	 * @param bool $protect
	 *
	 * @return void
	 */
	public function bind($abstract, $concrete, $protect = false);

}
