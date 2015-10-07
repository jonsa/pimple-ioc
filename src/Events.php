<?php namespace Jonsa\PimpleResolver;

/**
 * Class Events
 *
 * @package Jonsa\PimpleResolver
 * @author Jonas Sandström
 */
final class Events
{

    /**
     * The CLASS_RESOLVED event occurs when a class has been resolved
     * and instantiated out of the container.
     *
     * @var string
     */
    const CLASS_RESOLVED = 'jonsa.pimple_resolver.class_resolved';

}
