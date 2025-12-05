<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Model;

interface GtinAwareInterface
{
    /**
     * Must return a string or an object with __toString implemented
     *
     * @return string|\Stringable|null
     */
    public function getGtin();
}
