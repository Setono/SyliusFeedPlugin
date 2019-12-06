<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Model;

interface BrandAwareInterface
{
    /**
     * Must return a string or an object with __toString implemented
     *
     * @return string|object|null
     */
    public function getBrand();
}
