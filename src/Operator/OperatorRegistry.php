<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Operator;

use Setono\SyliusFeedPlugin\Registry\Registry;

/**
 * @extends Registry<OperatorInterface>
 */
final class OperatorRegistry extends Registry implements OperatorRegistryInterface
{
    public function get(string $name): OperatorInterface
    {
        return $this->getByKey($name);
    }

    /**
     * @param OperatorInterface $item
     */
    protected function getKey(object $item): string
    {
        return $item->getName();
    }
}
