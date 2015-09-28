<?php namespace Jonsa\PimpleResolver\Test\Data;

class TestResolver implements \Jonsa\PimpleResolver\Contract\ClassResolver {

	public $count = 0;

	public function registerEventListener(\Closure $listener, array $toEvents = null)
	{
	}

	public function resolve($abstract, $parameters = array())
	{
		$this->count++;
	}

}