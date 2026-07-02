<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Delivery;

use League\Flysystem\FilesystemAdapter;
use Webmozart\Assert\Assert;

/**
 * Delivers to an S3 bucket. The Flysystem AWS S3 v3 adapter (and the AWS SDK) is an optional
 * integrator install — the transport is always registered but only builds a filesystem when
 * league/flysystem-aws-s3-v3 is present, otherwise it fails with an actionable message (§12).
 */
final class S3DeliveryTransport extends AbstractDeliveryTransport
{
    public function getType(): string
    {
        return 's3';
    }

    protected function createAdapter(array $transportConfig): FilesystemAdapter
    {
        $adapterClass = 'League\\Flysystem\\AwsS3V3\\AwsS3V3Adapter';
        $clientClass = 'Aws\\S3\\S3Client';

        if (!class_exists($adapterClass) || !class_exists($clientClass)) {
            throw $this->notInstalled('league/flysystem-aws-s3-v3');
        }

        $clientConfig = $transportConfig['client'] ?? $transportConfig;
        Assert::isArray($clientConfig);

        $client = new $clientClass($clientConfig);
        $adapter = new $adapterClass(
            $client,
            $this->stringConfig($transportConfig, 'bucket'),
            $this->stringConfig($transportConfig, 'prefix'),
        );
        Assert::isInstanceOf($adapter, FilesystemAdapter::class);

        return $adapter;
    }
}
