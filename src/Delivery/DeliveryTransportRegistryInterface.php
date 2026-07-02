<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Delivery;

use Setono\SyliusFeedPlugin\Registry\RegistryInterface;

/**
 * @extends RegistryInterface<DeliveryTransportInterface>
 */
interface DeliveryTransportRegistryInterface extends RegistryInterface
{
    public function get(string $type): DeliveryTransportInterface;
}
