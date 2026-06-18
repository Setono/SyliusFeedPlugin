<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Writer;

use Setono\SyliusFeedPlugin\Registry\RegistryInterface;

/**
 * @extends RegistryInterface<FeedWriterInterface>
 */
interface FeedWriterRegistryInterface extends RegistryInterface
{
    public function get(string $format): FeedWriterInterface;
}
