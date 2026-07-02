<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Delivery;

use Setono\SyliusFeedPlugin\Registry\Registry;

/**
 * @extends Registry<DeliveryTransportInterface>
 */
final class DeliveryTransportRegistry extends Registry implements DeliveryTransportRegistryInterface
{
    public function get(string $type): DeliveryTransportInterface
    {
        return $this->getByKey($type);
    }

    /**
     * @param DeliveryTransportInterface $item
     */
    protected function getKey(object $item): string
    {
        return $item->getType();
    }
}
