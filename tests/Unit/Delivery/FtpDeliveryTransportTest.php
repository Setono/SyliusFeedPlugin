<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\Delivery;

use PHPUnit\Framework\TestCase;
use Setono\SyliusFeedPlugin\Delivery\FtpDeliveryTransport;

final class FtpDeliveryTransportTest extends TestCase
{
    /**
     * @test
     */
    public function it_exposes_its_type(): void
    {
        self::assertSame('ftp', (new FtpDeliveryTransport())->getType());
    }

    /**
     * @test
     */
    public function it_only_builds_a_filesystem_when_the_adapter_is_installed(): void
    {
        $transport = new FtpDeliveryTransport();
        $config = ['host' => 'localhost', 'root' => '/', 'username' => 'u', 'password' => 'p'];

        if (class_exists('League\\Flysystem\\Ftp\\FtpAdapter')) {
            $transport->createFilesystem($config);
            self::addToAssertionCount(1);

            return;
        }

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Install league/flysystem-ftp to use the 'ftp' delivery transport");

        $transport->createFilesystem($config);
    }
}
