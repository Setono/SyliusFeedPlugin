<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Transformation;

use Setono\SyliusFeedPlugin\Registry\Registry;

/**
 * @extends Registry<TransformationInterface>
 */
final class TransformationRegistry extends Registry implements TransformationRegistryInterface
{
    public function get(string $type): TransformationInterface
    {
        return $this->getByKey($type);
    }

    /**
     * @param TransformationInterface $item
     */
    protected function getKey(object $item): string
    {
        return $item->getType();
    }
}
