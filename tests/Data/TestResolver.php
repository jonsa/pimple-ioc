<?php namespace Jonsa\PimpleResolver\Test\Data;

class TestResolver implements \Jonsa\PimpleResolver\Contract\ClassResolver
{

    public $count = 0;

    public function resolve($abstract, $parameters = array())
    {
        $this->count++;
    }

    public function bind($abstract, $concrete, $protect = false)
    {
    }

}
