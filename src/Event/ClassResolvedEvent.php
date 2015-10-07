<?php namespace Jonsa\PimpleResolver\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Class ClassResolvedEvent
 *
 * @package Jonsa\PimpleResolver\Event
 * @author Jonas Sandström
 */
class ClassResolvedEvent extends Event
{

    /**
     * @var mixed
     */
    private $class;

    /**
     * @param mixed $class
     */
    public function __construct($class)
    {
        $this->class = $class;
    }

    /**
     * @return mixed
     */
    public function getResolvedObject()
    {
        return $this->class;
    }

}
