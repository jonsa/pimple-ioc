<?php namespace Test\Jonsa\PimpleResolver\Data;

class BazClass {

	public $foo;
	public $extra;

	public function __construct(FooClass $foo, $extra = 0)
	{
		$this->foo = $foo;
		$this->extra = $extra;
	}

}
