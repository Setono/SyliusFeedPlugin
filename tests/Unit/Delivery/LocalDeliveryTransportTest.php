<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\Delivery;

use PHPUnit\Framework\TestCase;
use Setono\SyliusFeedPlugin\Delivery\LocalDeliveryTransport;
use Symfony\Component\Filesystem\Filesystem;

final class LocalDeliveryTransportTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir() . '/ssfp-local-transport-' . bin2hex(random_bytes(6));
    }

    protected function tearDown(): void
    {
        (new Filesystem())->remove($this->directory);
    }

    /**
     * @test
     */
    public function it_exposes_its_type(): void
    {
        self::assertSame('local', (new LocalDeliveryTransport())->getType());
    }

    /**
     * @test
     */
    public function it_builds_a_working_filesystem(): void
    {
        $filesystem = (new LocalDeliveryTransport())->createFilesystem(['path' => $this->directory]);

        $filesystem->write('feeds/out.xml', 'payload');

        self::assertTrue($filesystem->fileExists('feeds/out.xml'));
        self::assertSame('payload', $filesystem->read('feeds/out.xml'));
        self::assertFileExists($this->directory . '/feeds/out.xml');
    }

    /**
     * @test
     */
    public function it_requires_a_path(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        (new LocalDeliveryTransport())->createFilesystem([]);
    }
}
