<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Delivery;

use League\Flysystem\FilesystemAdapter;
use Webmozart\Assert\Assert;

/**
 * Delivers over SFTP. The Flysystem SFTP (phpseclib v3) adapter is an optional integrator install —
 * the transport is always registered but only builds a filesystem when league/flysystem-sftp-v3 is
 * present, otherwise it fails with an actionable message (§12).
 */
final class SftpDeliveryTransport extends AbstractDeliveryTransport
{
    public function getType(): string
    {
        return 'sftp';
    }

    protected function createAdapter(array $transportConfig): FilesystemAdapter
    {
        $adapterClass = 'League\\Flysystem\\PhpseclibV3\\SftpAdapter';
        $providerClass = 'League\\Flysystem\\PhpseclibV3\\SftpConnectionProvider';

        if (!class_exists($adapterClass) || !class_exists($providerClass)) {
            throw $this->notInstalled('league/flysystem-sftp-v3');
        }

        $provider = $providerClass::fromArray($transportConfig);
        $adapter = new $adapterClass($provider, $this->stringConfig($transportConfig, 'root', '/'));
        Assert::isInstanceOf($adapter, FilesystemAdapter::class);

        return $adapter;
    }
}
