<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Operator;

use Setono\SyliusFeedPlugin\Registry\RegistryInterface;

/**
 * @extends RegistryInterface<OperatorInterface>
 */
interface OperatorRegistryInterface extends RegistryInterface
{
    public function get(string $name): OperatorInterface;
}
