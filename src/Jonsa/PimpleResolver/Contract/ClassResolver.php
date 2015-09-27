<?php namespace Jonsa\PimpleResolver\Contract;

/**
 * Interface ClassResolver
 *
 * @package Jonsa\PimpleResolver\Contract
 * @author Jonas Sandstr�m
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

}
