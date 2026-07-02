<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Delivery;

use League\Flysystem\FilesystemAdapter;
use League\Flysystem\Local\LocalFilesystemAdapter;
use Webmozart\Assert\Assert;

/**
 * Delivers to a local directory. Always available (league/flysystem-local is a hard dependency), so
 * it is the transport the test suite exercises.
 */
final class LocalDeliveryTransport extends AbstractDeliveryTransport
{
    public function getType(): string
    {
        return 'local';
    }

    protected function createAdapter(array $transportConfig): FilesystemAdapter
    {
        $path = $transportConfig['path'] ?? null;
        Assert::stringNotEmpty($path, 'The "local" delivery transport requires a non-empty "path" config value');

        return new LocalFilesystemAdapter($path);
    }
}
