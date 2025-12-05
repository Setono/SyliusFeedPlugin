<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Model;

/**
 * @deprecated since 0.6.6 This interface is deprecated and will be removed in 0.7.0
 * Use \Setono\SyliusFeedPlugin\Model\LocalizedSizeAwareInterface instead.
 */
interface SizeAwareInterface
{
    /**
     * Must return a string or an object with __toString implemented
     *
     * @return string|\Stringable|null
     */
    public function getSize();
}
