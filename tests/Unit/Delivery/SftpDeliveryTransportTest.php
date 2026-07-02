<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\Delivery;

use PHPUnit\Framework\TestCase;
use Setono\SyliusFeedPlugin\Delivery\SftpDeliveryTransport;

final class SftpDeliveryTransportTest extends TestCase
{
    /**
     * @test
     */
    public function it_exposes_its_type(): void
    {
        self::assertSame('sftp', (new SftpDeliveryTransport())->getType());
    }

    /**
     * @test
     */
    public function it_only_builds_a_filesystem_when_the_adapter_is_installed(): void
    {
        $transport = new SftpDeliveryTransport();
        $config = ['host' => 'localhost', 'username' => 'u', 'password' => 'p', 'root' => '/upload'];

        if (class_exists('League\\Flysystem\\PhpseclibV3\\SftpAdapter')) {
            $transport->createFilesystem($config);
            self::addToAssertionCount(1);

            return;
        }

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Install league/flysystem-sftp-v3 to use the 'sftp' delivery transport");

        $transport->createFilesystem($config);
    }
}
