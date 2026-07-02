<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\Delivery;

use PHPUnit\Framework\TestCase;
use Setono\SyliusFeedPlugin\Delivery\S3DeliveryTransport;

final class S3DeliveryTransportTest extends TestCase
{
    /**
     * @test
     */
    public function it_exposes_its_type(): void
    {
        self::assertSame('s3', (new S3DeliveryTransport())->getType());
    }

    /**
     * @test
     */
    public function it_only_builds_a_filesystem_when_the_adapter_is_installed(): void
    {
        $transport = new S3DeliveryTransport();
        $config = [
            'bucket' => 'my-bucket',
            'prefix' => 'feeds',
            'client' => ['region' => 'eu-west-1', 'version' => 'latest'],
        ];

        if (class_exists('League\\Flysystem\\AwsS3V3\\AwsS3V3Adapter') && class_exists('Aws\\S3\\S3Client')) {
            $transport->createFilesystem($config);
            self::addToAssertionCount(1);

            return;
        }

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Install league/flysystem-aws-s3-v3 to use the 's3' delivery transport");

        $transport->createFilesystem($config);
    }
}
