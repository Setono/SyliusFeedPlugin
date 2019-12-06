<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Model;

interface ConditionAwareInterface
{
    /**
     * Must return a string or an object with __toString implemented
     *
     * @return string|object|null
     */
    public function getCondition();
}
