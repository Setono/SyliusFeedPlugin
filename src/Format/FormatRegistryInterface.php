<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Format;

use Setono\SyliusFeedPlugin\Registry\RegistryInterface;

/**
 * @extends RegistryInterface<FormatInterface>
 */
interface FormatRegistryInterface extends RegistryInterface
{
    public function get(string $code): FormatInterface;
}
