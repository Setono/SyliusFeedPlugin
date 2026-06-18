<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Transformation;

use Setono\SyliusFeedPlugin\Registry\RegistryInterface;

/**
 * @extends RegistryInterface<TransformationInterface>
 */
interface TransformationRegistryInterface extends RegistryInterface
{
    public function get(string $type): TransformationInterface;
}
