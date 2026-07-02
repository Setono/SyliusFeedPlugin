<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Delivery;

use League\Flysystem\FilesystemOperator;

/**
 * A pluggable delivery transport (§12): given a delivery target's connection config it builds the
 * Flysystem filesystem the generated files are pushed to. Collected into the
 * {@see DeliveryTransportRegistry} via the `setono_sylius_feed.delivery_transport` tag.
 *
 * Transports whose Flysystem adapter package is optional (ftp/sftp/s3) must remain constructible
 * even when the adapter is not installed and only fail — with a clear message — when actually used.
 */
interface DeliveryTransportInterface
{
    /**
     * The transport type, e.g. "local", "ftp", "sftp" or "s3".
     */
    public function getType(): string;

    /**
     * @param array<string, mixed> $transportConfig
     *
     * @throws \RuntimeException when the transport cannot be built (e.g. its adapter package is not installed)
     */
    public function createFilesystem(array $transportConfig): FilesystemOperator;
}
