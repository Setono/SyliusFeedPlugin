<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Specification;

/**
 * All specifications MUST extend this class
 */
abstract class Specification
{
    /**
     * Making the constructor final allows us to always be able to instantiate an extending class without worrying about constructor arguments
     */
    final public function __construct()
    {
    }
}
