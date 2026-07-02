<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Delivery;

use League\Flysystem\FilesystemAdapter;
use Webmozart\Assert\Assert;

/**
 * Delivers over FTP. The Flysystem FTP adapter is an optional integrator install — the transport is
 * always registered but only builds a filesystem when league/flysystem-ftp is present, otherwise it
 * fails with an actionable message (§12).
 */
final class FtpDeliveryTransport extends AbstractDeliveryTransport
{
    public function getType(): string
    {
        return 'ftp';
    }

    protected function createAdapter(array $transportConfig): FilesystemAdapter
    {
        $adapterClass = 'League\\Flysystem\\Ftp\\FtpAdapter';
        $optionsClass = 'League\\Flysystem\\Ftp\\FtpConnectionOptions';

        if (!class_exists($adapterClass) || !class_exists($optionsClass)) {
            throw $this->notInstalled('league/flysystem-ftp');
        }

        $options = $optionsClass::fromArray($transportConfig);
        $adapter = new $adapterClass($options);
        Assert::isInstanceOf($adapter, FilesystemAdapter::class);

        return $adapter;
    }
}
