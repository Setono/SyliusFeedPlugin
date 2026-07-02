<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Publish;

use Setono\SyliusFeedPlugin\Registry\Registry;

/**
 * @extends Registry<GuardrailInterface>
 */
final class GuardrailRegistry extends Registry implements GuardrailRegistryInterface
{
    public function get(string $type): GuardrailInterface
    {
        return $this->getByKey($type);
    }

    /**
     * @param GuardrailInterface $item
     */
    protected function getKey(object $item): string
    {
        return $item->getType();
    }
}
