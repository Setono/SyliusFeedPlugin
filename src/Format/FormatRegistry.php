<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Format;

use Setono\SyliusFeedPlugin\Registry\Registry;

/**
 * @extends Registry<FormatInterface>
 */
final class FormatRegistry extends Registry implements FormatRegistryInterface
{
    public function get(string $code): FormatInterface
    {
        return $this->getByKey($code);
    }

    /**
     * @param FormatInterface $item
     */
    protected function getKey(object $item): string
    {
        return $item->getCode();
    }
}
