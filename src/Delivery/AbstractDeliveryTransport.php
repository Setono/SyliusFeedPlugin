<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Delivery;

use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\FilesystemOperator;

/**
 * Shared behaviour for the built-in transports: wraps a built Flysystem adapter in a
 * {@see Filesystem} and, for transports whose adapter package is optional, provides the actionable
 * "install X" error and a typed config reader (§12).
 */
abstract class AbstractDeliveryTransport implements DeliveryTransportInterface
{
    final public function createFilesystem(array $transportConfig): FilesystemOperator
    {
        return new Filesystem($this->createAdapter($transportConfig));
    }

    /**
     * @param array<string, mixed> $transportConfig
     */
    abstract protected function createAdapter(array $transportConfig): FilesystemAdapter;

    protected function notInstalled(string $package): \RuntimeException
    {
        return new \RuntimeException(sprintf(
            "Install %s to use the '%s' delivery transport",
            $package,
            $this->getType(),
        ));
    }

    /**
     * @param array<string, mixed> $config
     */
    protected function stringConfig(array $config, string $key, string $default = ''): string
    {
        $value = $config[$key] ?? $default;

        return is_string($value) ? $value : $default;
    }
}
