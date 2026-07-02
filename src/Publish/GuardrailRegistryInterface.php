<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Publish;

use Setono\SyliusFeedPlugin\Registry\RegistryInterface;

/**
 * @extends RegistryInterface<GuardrailInterface>
 */
interface GuardrailRegistryInterface extends RegistryInterface
{
    public function get(string $type): GuardrailInterface;
}
