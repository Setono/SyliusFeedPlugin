<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Model;

use Sylius\Component\Locale\Model\LocaleInterface;

interface LocalizedSizeAwareInterface
{
    /**
     * Must return a string or an object with __toString implemented
     *
     * @return string|object|null
     */
    public function getSize(LocaleInterface $locale);
}
